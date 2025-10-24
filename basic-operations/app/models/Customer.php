<?php

class Customer extends Database{

  private $db;

  public function __construct()
  {
    $this->db = new Database();
  }

  public function getCustomerByEmailOrAccountNumber($identifier) {
    $this->db->query("
            SELECT
                c.customer_id,
                c.first_name,
                c.last_name,
                c.password_hash,
                a.account_number
            FROM
                customers c
            LEFT JOIN
                emails e ON c.customer_id = e.customer_id
            LEFT JOIN
                accounts a ON c.customer_id = a.customer_id
            WHERE
                e.email = :emailIdentifier OR a.account_number = :accountIdentifier
            LIMIT 1;
        ");

    if(filter_var($identifier, FILTER_VALIDATE_EMAIL)){
        $email = $identifier;
        $account_number = null;
    } else {
        $email = null;
        $account_number = $identifier;
    }

    $this->db->bind(':emailIdentifier', $email);
    $this->db->bind(':accountIdentifier', $account_number);
    return $this->db->single();

    }

    public function loginCustomer($identifier, $password) {
        $customer = $this->getCustomerByEmailOrAccountNumber($identifier);
        
        // Debug: Check if customer is found
        if (!$customer) {
            error_log("Customer not found for identifier: $identifier"); // Or echo for testing
            return false;
        }
        
        // Debug: Check password verification
        if (password_verify($password, $customer->password_hash)) {
            return $customer;
        } else {
            error_log("Password mismatch for: $identifier"); // Or echo
            return false;
        }
    }

    public function getAccountsByCustomerId($customer_id) {
        $this->db->query("
                SELECT
                a.account_id,
                a.account_number,
                act.type_name AS account_type,
                c.first_name,
                c.last_name,
                COALESCE(SUM(CASE WHEN tt.type_name = 'Deposit' THEN t.amount ELSE 0 END), 0) AS total_deposit,
                COALESCE(SUM(CASE WHEN tt.type_name = 'Withdrawal' THEN t.amount ELSE 0 END), 0) AS total_withdrawal,
                COALESCE(SUM(CASE WHEN tt.type_name = 'Service Charge' THEN t.amount ELSE 0 END), 0) AS total_service_charge
            FROM customer_linked_accounts cla
            INNER JOIN accounts a ON cla.account_id = a.account_id
            INNER JOIN customers c ON cla.customer_id = c.customer_id
            LEFT JOIN account_types act ON a.account_type_id = act.account_type_id
            LEFT JOIN transactions t ON a.account_id = t.account_id
            LEFT JOIN transaction_types tt ON t.transaction_type_id = tt.transaction_type_id
            WHERE cla.customer_id = :customer_id AND cla.is_active = 1 AND a.is_locked = 0
            GROUP BY a.account_id, c.customer_id, a.account_number, act.type_name, c.first_name, c.last_name
            ORDER BY a.created_at DESC
        ");

        $this->db->bind(':customer_id', $customer_id);
        $rawAccounts = $this->db->resultSet();

        $accounts = [];
        foreach ($rawAccounts as $rawAccount) {
            $beginning_balance = 0.00;

            $total_deposit = (float) $rawAccount->total_deposit;
            $total_withdrawal = (float) $rawAccount->total_withdrawal;
            $total_service_charge = (float) $rawAccount->total_service_charge;

            $ending_balance = ($beginning_balance + $total_deposit) - ($total_withdrawal + $total_service_charge);

            $account = (array) $rawAccount;
            $account['account_name'] = $rawAccount->first_name . ' ' . $rawAccount->last_name;
            $account['branch'] = 'SM Fairview';
            $account['beginning_balance'] = $beginning_balance;
            $account['ending_balance'] = $ending_balance;

            if (str_contains(strtolower($account['account_type']), 'credit card')) {
                $account['available_credit'] = 5245.00;
                $account['credit_limit'] = 50000.00;
            } else {
                $account['available_credit'] = null;
                $account['credit_limit'] = null;
            }

            $this->db->query("
                SELECT
                    t.transaction_id,
                    t.amount,
                    t.description,
                    tt.type_name AS transaction_type_name,
                    t.created_at
                FROM transactions t
                JOIN transaction_types tt ON t.transaction_type_id = tt.transaction_type_id
                WHERE t.account_id = :account_id
                ORDER BY t.created_at DESC
                LIMIT 3
            ");
            $this->db->bind(':account_id', $rawAccount->account_id);
            $account['transactions'] = $this->db->resultSet();

            $accounts[] = (object) $account;
        }

        return $accounts;
    }

    public function getAccountById($id) {
        $this->db->query('SELECT * FROM accounts WHERE account_id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function deleteAccountById($id) {
        $this->db->query('UPDATE `customer_linked_accounts` SET `is_active`= 0 WHERE account_id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function addAccount($data) {
        // Step 1: Get account_id and account_type by account_number
        $this->db->query("
            SELECT account_id, account_type_id 
            FROM accounts 
            WHERE account_number = :account_number
        ");
        $this->db->bind(':account_number', $data['account_number']);
        $account = $this->db->single();

        if (!$account) {
            // No account found with that number
            return ['success' => false, 'error' => 'Account number not found.'];
        }

        // Step 2: Verify account type matches user input
        $this->db->query("SELECT account_type_id, type_name FROM account_types WHERE type_name = :account_type");
        $this->db->bind(':account_type', $data['account_type']);
        $type = $this->db->single();

        if (!$type) {
            return ['success' => false, 'error' => 'Invalid account type provided.'];
        }

        if ($account->account_type_id !== $type->account_type_id) {
            return ['success' => false, 'error' => 'Account type does not match the account number.'];
        }

        $account_id = $account->account_id;

        // Step 3: Check if link already exists
        $this->db->query("
            SELECT * FROM customer_linked_accounts 
            WHERE customer_id = :customer_id AND account_id = :account_id
        ");
        $this->db->bind(':customer_id', $data['customer_id']);
        $this->db->bind(':account_id', $account_id);
        $existing = $this->db->single();

        if ($existing) {
            if ($existing->is_active == 0) {
                // Step 4: Reactivate if inactive
                $this->db->query("
                    UPDATE customer_linked_accounts 
                    SET is_active = 1 
                    WHERE customer_id = :customer_id AND account_id = :account_id
                ");
                $this->db->bind(':customer_id', $data['customer_id']);
                $this->db->bind(':account_id', $account_id);
                $this->db->execute();
                return ['success' => true, 'message' => 'Account reactivated successfully.'];
            } else {
                return ['success' => false, 'error' => 'This account is already linked and active.'];
            }
        }

        // Step 5: Insert new link
        $this->db->query("
            INSERT INTO customer_linked_accounts (customer_id, account_id, is_active)
            VALUES (:customer_id, :account_id, 1)
        ");
        $this->db->bind(':customer_id', $data['customer_id']);
        $this->db->bind(':account_id', $account_id);

        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Account linked successfully.'];
        }
        return ['success' => false, 'error' => 'Failed to add account.'];
    }

}