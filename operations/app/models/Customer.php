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
                e.email,
                c.password_hash,
                a.account_number
            FROM
                Customers c
            LEFT JOIN
                Emails e ON c.customer_id = e.customer_id AND e.is_primary = 1 AND e.is_active = 1
            LEFT JOIN
                Accounts a ON c.customer_id = a.customer_id AND a.is_active = 1
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
        // Get accounts directly owned by the customer
        $this->db->query("
            SELECT
                a.account_id,
                a.account_number,
                a.account_type_id,
                a.is_hidden,
                a.hidden_at,
                act.type_name AS type_name,
                act.type_name AS account_type,
                c.first_name,
                c.last_name,
                COALESCE(SUM(
                    CASE tt.type_name
                        WHEN 'Deposit' THEN t.amount
                        WHEN 'Transfer In' THEN t.amount
                        WHEN 'Interest Payment' THEN t.amount
                        WHEN 'Loan Disbursement' THEN t.amount
                        WHEN 'Withdrawal' THEN -t.amount
                        WHEN 'Transfer Out' THEN -t.amount
                        WHEN 'Service Charge' THEN -t.amount
                        WHEN 'Loan Payment' THEN -t.amount
                        ELSE 0
                    END
                ), 0) AS current_balance
            FROM Accounts a
            LEFT JOIN Customers c ON c.customer_id = a.customer_id
            LEFT JOIN Account_Types act ON a.account_type_id = act.account_type_id
            LEFT JOIN Transaction t ON a.account_id = t.account_id
            LEFT JOIN Transaction_Type tt ON t.transaction_type_id = tt.transaction_type_id
            WHERE a.customer_id = :customer_id
            AND a.is_active = 1
            AND (a.is_locked = 0 OR a.is_locked IS NULL)
            GROUP BY a.account_id, a.account_number, a.account_type_id, a.is_hidden, a.hidden_at, act.type_name, c.first_name, c.last_name
            ORDER BY a.created_at DESC;
        ");

        $this->db->bind(':customer_id', $customer_id);
        $customer_accounts = $this->db->resultSet();

        foreach ($customer_accounts as $account) {
            $account->account_name = $account->first_name . ' ' . $account->last_name;
            
            // Use the calculated balance from the SQL query
            $account->beginning_balance = 0.00; // This is arbitrary, 'current_balance' is the useful value
            $account->ending_balance = (float) $account->current_balance;

            // Credit Card Logic
            if (str_contains(strtolower($account->account_type), 'credit card')) {
                $account->available_credit = 5245.00;
                $account->credit_limit = 50000.00;
            } else {
                $account->available_credit = null;
                $account->credit_limit = null;
            }

            $this->db->query("
                SELECT
                    t.transaction_id,
                    t.transaction_ref,
                    t.amount,
                    t.balance_after,
                    t.description,
                    tt.type_name AS transaction_type_name,
                    t.created_at
                FROM Transaction t
                JOIN Transaction_Type tt ON t.transaction_type_id = tt.transaction_type_id
                WHERE t.account_id = :account_id
                ORDER BY t.created_at DESC
                LIMIT 3
            ");
            $this->db->bind(':account_id', $account->account_id);
            $account->transactions = $this->db->resultSet();
        }

        return $customer_accounts;
    }

    public function getAccountById($id) {
        $this->db->query('SELECT * FROM Accounts WHERE account_id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function deleteAccountById($id) {
        $this->db->query('UPDATE Accounts SET is_active = 0, archived_at = NOW() WHERE account_id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function toggleAccountVisibility($account_id, $is_hidden) {
        $this->db->query('UPDATE Accounts SET is_hidden = :is_hidden, hidden_at = :hidden_at WHERE account_id = :account_id');
        $this->db->bind(':account_id', $account_id);
        $this->db->bind(':is_hidden', $is_hidden);
        $this->db->bind(':hidden_at', $is_hidden ? date('Y-m-d H:i:s') : null);
        return $this->db->execute();
    }

    public function addAccount($data) {
        // Input validation
        if (empty($data['account_number'])) {
            return ['success' => false, 'error' => 'Account number is required.'];
        }
        if (empty($data['account_type'])) {
            return ['success' => false, 'error' => 'Account type is required.'];
        }
        if (empty($data['customer_id'])) {
            return ['success' => false, 'error' => 'Invalid customer session.'];
        }

        // Step 1: Get account_id and account_type by account_number AND VERIFY IT BELONGS TO THE CUSTOMER
        $this->db->query("
            SELECT a.account_id, a.account_type_id, a.customer_id, a.is_active, a.is_locked
            FROM Accounts a
            WHERE a.account_number = :account_number
        ");
        $this->db->bind(':account_number', $data['account_number']);
        $account = $this->db->single();

        if (!$account) {
            return ['success' => false, 'error' => 'Account number "' . htmlspecialchars($data['account_number']) . '" not found in our system. Please check the account number and try again.'];
        }

        // SECURITY CHECK: Verify the account belongs to the logged-in customer
        if ((int)$account->customer_id !== (int)$data['customer_id']) {
            return ['success' => false, 'error' => 'This account does not belong to you. You can only add your own accounts.'];
        }

        // Check if account is locked
        if ((int)$account->is_locked === 1) {
            return ['success' => false, 'error' => 'This account is locked. Please contact the bank to unlock it before you can add it.'];
        }

        // Auto-activate account if it's inactive but not locked
        if ((int)$account->is_active !== 1) {
            $this->db->query("
                UPDATE Accounts 
                SET is_active = 1 
                WHERE account_id = :account_id
            ");
            $this->db->bind(':account_id', $account->account_id);
            $this->db->execute();
        }

        // Step 2: Verify account type matches user input
        $this->db->query("
            SELECT account_type_id, type_name 
            FROM Account_Types 
            WHERE type_name = :account_type
        ");
        $this->db->bind(':account_type', $data['account_type']);
        $type = $this->db->single();

        if (!$type) {
            return ['success' => false, 'error' => 'Invalid account type selected. Please select a valid account type.'];
        }

        if ((int)$account->account_type_id !== (int)$type->account_type_id) {
            // Get the actual account type name for better error message
            $this->db->query("SELECT type_name FROM Account_Types WHERE account_type_id = :id");
            $this->db->bind(':id', $account->account_type_id);
            $actualType = $this->db->single();
            $actualTypeName = $actualType ? $actualType->type_name : 'Unknown';
            
            return ['success' => false, 'error' => "Account type mismatch. This account is a '{$actualTypeName}', but you selected '{$data['account_type']}'. Please select the correct account type."];
        }

        $account_id = $account->account_id;

        // Step 3: Check if link already exists
        $this->db->query("
            SELECT * 
            FROM customer_linked_accounts
            WHERE customer_id = :customer_id AND account_id = :account_id
        ");
        $this->db->bind(':customer_id', $data['customer_id']);
        $this->db->bind(':account_id', $account_id);
        $existing = $this->db->single();

        if ($existing) {
            // Account link already exists
            if ((int)$existing->is_active === 0) {
                // Step 4: Reactivate if inactive
                $this->db->query("
                    UPDATE customer_linked_accounts
                    SET is_active = 1
                    WHERE customer_id = :customer_id AND account_id = :account_id
                ");
                $this->db->bind(':customer_id', $data['customer_id']);
                $this->db->bind(':account_id', $account_id);
                
                if ($this->db->execute()) {
                    return ['success' => true, 'message' => 'Account reactivated successfully.'];
                } else {
                    return ['success' => false, 'error' => 'Failed to reactivate account.'];
                }
            } else {
                return ['success' => false, 'error' => 'This account is already linked and active.'];
            }
        }

        // Step 5: Insert new link if it doesn't exist
        $this->db->query("
            INSERT INTO customer_linked_accounts (customer_id, account_id, is_active)
            VALUES (:customer_id, :account_id, 1)
        ");
        $this->db->bind(':customer_id', $data['customer_id']);
        $this->db->bind(':account_id', $account_id);

        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Account added successfully.'];
        }

        return ['success' => false, 'error' => 'Failed to add account to your dashboard. Please try again or contact support if the problem persists.'];
    }

    // -- CREATING ACCOUNT
    public function getAccountTypes(){
        // Limiting to IDs 1 and 2 based on the user's explicit requirement for Savings and Checking only.
        $this->db->query("SELECT account_type_id, type_name FROM Account_Types WHERE account_type_id IN (1, 2) ORDER BY account_type_id ASC");
        return $this->db->resultSet(); 
    }

    public function createBankAccount($customer_id, $account_type_id){
        $account_number = $this->generateUniqueAccountNumber($account_type_id);
        
        // Set interest rate for Savings accounts (account_type_id = 1)
        // 0.5% annual interest rate (0.50 in DECIMAL format)
        // Checking accounts (account_type_id = 2) have NULL interest_rate
        $interest_rate = ($account_type_id == 1) ? 0.50 : NULL;

        $this->db->query("
            INSERT INTO Accounts 
                (customer_id, account_number, account_type_id, interest_rate, is_locked, created_at)
            VALUES 
                (:customer_id, :account_number, :account_type_id, :interest_rate, 0, NOW())
        ");
        
        $this->db->bind(':customer_id', $customer_id);
        $this->db->bind(':account_number', $account_number);
        $this->db->bind(':account_type_id', $account_type_id);
        $this->db->bind(':interest_rate', $interest_rate);

        if ($this->db->execute()) {
             $account_id = $this->db->lastInsertId();
            return $account_number;
        }
        return false;
    }

    // AUTO INSERT
    public function autoInsertCustomerLinkedAccount($customer_id, $account_id){
        $this->db->query("
            INSERT INTO customer_linked_accounts 
                (customer_id, account_id, linked_at, is_active) 
            VALUES 
                (:customer_id, :account_id, NOW(), 1)
        ");
        $this->db->bind(':customer_id', $customer_id);
        $this->db->bind(':account_id', $account_id);

        return $this->db->execute();
    }

    private function generateUniqueAccountNumber($account_type_id) {
        $prefix = '';
        if ($account_type_id == 1) { 
            $prefix = 'SA'; // Savings Account (ID 1)
        } elseif ($account_type_id == 2) {
            $prefix = 'CHA'; // Checking Account (ID 2)
        } else {
            $prefix = 'GEN'; // Generic Account
        }

        // Generate a unique 4-digit random number
        // Format: PREFIX-XXXX-YEAR (e.g., CHA-1234-2024 or SA-5678-2024)
        $current_year = date('Y');
        $max_attempts = 100;
        $attempt = 0;
        
        do {
            $unique_digits = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $account_number = "{$prefix}-{$unique_digits}-{$current_year}";
            
            // Check if account number already exists
            $this->db->query("SELECT COUNT(*) as count FROM Accounts WHERE account_number = :account_number");
            $this->db->bind(':account_number', $account_number);
            $result = $this->db->single();
            
            $attempt++;
        } while ($result && $result->count > 0 && $attempt < $max_attempts);
        
        if ($attempt >= $max_attempts) {
            // Fallback: add timestamp to ensure uniqueness
            $account_number = "{$prefix}-{$unique_digits}-{$current_year}-" . time();
        }

        return $account_number;
    }

    public function getCustomerProfileData($customer_id){
        $this->db->query("
            SELECT 
                c.first_name, 
                c.middle_name,
                c.last_name,
                -- Account Info from separate tables
                (SELECT p.phone_number FROM Phones p WHERE p.customer_id = c.customer_id AND p.is_primary = 1 AND p.is_active = 1 LIMIT 1) AS mobile_number,
                (SELECT e.email FROM Emails e WHERE e.customer_id = c.customer_id AND e.is_primary = 1 AND e.is_active = 1 LIMIT 1) AS email_address,
                -- Personal Info
                cp.date_of_birth,
                cp.marital_status AS civil_status,
                cp.nationality AS citizenship,
                cp.occupation,
                cp.company_name AS name_of_employer,
                cp.income_range,
                g.gender_name AS gender,
                -- Address parts (primary address)
                a.address_line,
                a.city_id,
                a.barangay_id,
                ct.city_name,
                br.barangay_name,
                a.province_id,
                p.province_name,
                -- Legacy concatenated field for compatibility
                CONCAT_WS(', ', a.address_line, br.barangay_name, ct.city_name, p.province_name, 'Philippines') AS home_address
            FROM Customers c
            LEFT JOIN Customer_Profile cp ON c.customer_id = cp.customer_id
            LEFT JOIN Gender g ON cp.gender_id = g.gender_id
            LEFT JOIN Addresses a ON c.customer_id = a.customer_id AND a.is_primary = 1 AND a.is_active = 1
            LEFT JOIN Barangay br ON a.barangay_id = br.barangay_id
            LEFT JOIN City ct ON a.city_id = ct.city_id
            LEFT JOIN Province p ON a.province_id = p.province_id
            WHERE c.customer_id = :customer_id;
        ");
        
        $this->db->bind(':customer_id', $customer_id);
        
        return $this->db->single();
    }

    /**
     * Get list of provinces
     * @return array
     */
    public function getProvinces() {
        $this->db->query("SELECT province_id, province_name FROM Province ORDER BY province_name ASC");
        return $this->db->resultSet();
    }

    /**
     * Get list of cities by province
     * @param int $province_id
     * @return array
     */
    public function getCitiesByProvince($province_id) {
        $this->db->query("SELECT city_id, city_name, province_id FROM City WHERE province_id = :province_id ORDER BY city_name ASC");
        $this->db->bind(':province_id', $province_id);
        return $this->db->resultSet();
    }

    /**
     * Get list of barangays by city
     * @param int $city_id
     * @return array
     */
    public function getBarangaysByCity($city_id) {
        $this->db->query("SELECT barangay_id, barangay_name, city_id FROM Barangay WHERE city_id = :city_id ORDER BY barangay_name ASC");
        $this->db->bind(':city_id', $city_id);
        return $this->db->resultSet();
    }

    /**
     * Get all cities
     * @return array
     */
    public function getAllCities() {
        $this->db->query("SELECT city_id, city_name, province_id FROM City ORDER BY city_name ASC");
        return $this->db->resultSet();
    }

    /**
     * Get all emails for a customer
     * @param int $customer_id
     * @return array
     */
    public function getCustomerEmails($customer_id) {
        $this->db->query("SELECT email_id, email, is_primary, is_active 
                          FROM Emails 
                          WHERE customer_id = :customer_id AND is_active = 1 
                          ORDER BY is_primary DESC, created_at ASC");
        $this->db->bind(':customer_id', $customer_id);
        return $this->db->resultSet();
    }

    /**
     * Get all phones for a customer
     * @param int $customer_id
     * @return array
     */
    public function getCustomerPhones($customer_id) {
        $this->db->query("SELECT phone_id, phone_number, phone_type, is_primary, is_active 
                          FROM Phones 
                          WHERE customer_id = :customer_id AND is_active = 1 
                          ORDER BY is_primary DESC, created_at ASC");
        $this->db->bind(':customer_id', $customer_id);
        return $this->db->resultSet();
    }

    /**
     * Add a new email for a customer
     * @param int $customer_id
     * @param string $email
     * @param int $is_primary
     * @return bool
     */
    public function addCustomerEmail($customer_id, $email, $is_primary = 0) {
        try {
            // Check if email exists as inactive for THIS customer
            $this->db->query("SELECT email_id FROM Emails WHERE email = :email AND customer_id = :customer_id AND is_active = 0");
            $this->db->bind(':email', $email);
            $this->db->bind(':customer_id', $customer_id);
            $inactiveRecord = $this->db->single();
            
            if ($inactiveRecord) {
                // Reactivate the existing record for this customer
                if ($is_primary == 1) {
                    $this->db->query("UPDATE Emails SET is_primary = 0 WHERE customer_id = :customer_id AND is_active = 1");
                    $this->db->bind(':customer_id', $customer_id);
                    $this->db->execute();
                }
                
                // Update without updated_at if column doesn't exist
                $this->db->query("UPDATE Emails SET is_active = 1, is_primary = :is_primary 
                                  WHERE email_id = :email_id");
                $this->db->bind(':email_id', $inactiveRecord->email_id);
                $this->db->bind(':is_primary', $is_primary);
                $result = $this->db->execute();
                
                if ($result) {
                    error_log("Email reactivated successfully for customer $customer_id: $email");
                } else {
                    error_log("Failed to reactivate email for customer $customer_id: $email");
                }
                
                return $result;
            }
            
            // Check if email already exists as active for ANY customer
            $this->db->query("SELECT email_id, customer_id FROM Emails WHERE email = :email AND is_active = 1");
            $this->db->bind(':email', $email);
            $existingEmail = $this->db->single();
            
            if ($existingEmail) {
                error_log("Email already exists as active for customer " . $existingEmail->customer_id . ": $email");
                return false; // Email is already in use by this or another customer
            }
            
            // Check if email exists as inactive for ANOTHER customer
            $this->db->query("SELECT email_id FROM Emails WHERE email = :email AND customer_id != :customer_id AND is_active = 0");
            $this->db->bind(':email', $email);
            $this->db->bind(':customer_id', $customer_id);
            if ($this->db->single()) {
                error_log("Email belongs to another customer (inactive): $email");
                return false; // Email belongs to another customer (even if inactive)
            }

            // If setting as primary, unset other primary emails
            if ($is_primary == 1) {
                $this->db->query("UPDATE Emails SET is_primary = 0 WHERE customer_id = :customer_id");
                $this->db->bind(':customer_id', $customer_id);
                $this->db->execute();
            }

            // Insert new email
            $this->db->query("INSERT INTO Emails (customer_id, email, is_primary, is_active, created_at) 
                              VALUES (:customer_id, :email, :is_primary, 1, NOW())");
            $this->db->bind(':customer_id', $customer_id);
            $this->db->bind(':email', $email);
            $this->db->bind(':is_primary', $is_primary);
            
            $result = $this->db->execute();
            
            if ($result) {
                error_log("New email added successfully for customer $customer_id: $email");
            } else {
                error_log("Failed to insert new email for customer $customer_id: $email");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error adding email for customer $customer_id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add a new phone for a customer
     * @param int $customer_id
     * @param string $phone_number
     * @param int $is_primary
     * @return bool
     */
    public function addCustomerPhone($customer_id, $phone_number, $is_primary = 0) {
        try {
            // Check if phone exists as inactive for THIS customer
            $this->db->query("SELECT phone_id FROM Phones WHERE phone_number = :phone_number AND customer_id = :customer_id AND is_active = 0");
            $this->db->bind(':phone_number', $phone_number);
            $this->db->bind(':customer_id', $customer_id);
            $inactiveRecord = $this->db->single();
            
            if ($inactiveRecord) {
                // Reactivate the existing record for this customer
                if ($is_primary == 1) {
                    $this->db->query("UPDATE Phones SET is_primary = 0 WHERE customer_id = :customer_id AND is_active = 1");
                    $this->db->bind(':customer_id', $customer_id);
                    $this->db->execute();
                }
                
                // Update without updated_at if column doesn't exist
                $this->db->query("UPDATE Phones SET is_active = 1, is_primary = :is_primary 
                                  WHERE phone_id = :phone_id");
                $this->db->bind(':phone_id', $inactiveRecord->phone_id);
                $this->db->bind(':is_primary', $is_primary);
                $result = $this->db->execute();
                
                if ($result) {
                    error_log("Phone reactivated successfully for customer $customer_id: $phone_number");
                } else {
                    error_log("Failed to reactivate phone for customer $customer_id: $phone_number");
                }
                
                return $result;
            }
            
            // Check if phone already exists as active for ANY customer
            $this->db->query("SELECT phone_id FROM Phones WHERE phone_number = :phone_number AND is_active = 1");
            $this->db->bind(':phone_number', $phone_number);
            if ($this->db->single()) {
                error_log("Phone already exists as active: $phone_number");
                return false; // Phone is already in use
            }
            
            // Check if phone exists as inactive for ANOTHER customer
            $this->db->query("SELECT phone_id FROM Phones WHERE phone_number = :phone_number AND customer_id != :customer_id AND is_active = 0");
            $this->db->bind(':phone_number', $phone_number);
            $this->db->bind(':customer_id', $customer_id);
            if ($this->db->single()) {
                error_log("Phone belongs to another customer (inactive): $phone_number");
                return false; // Phone belongs to another customer (even if inactive)
            }

            // If setting as primary, unset other primary phones
            if ($is_primary == 1) {
                $this->db->query("UPDATE Phones SET is_primary = 0 WHERE customer_id = :customer_id");
                $this->db->bind(':customer_id', $customer_id);
                $this->db->execute();
            }

            // Insert new phone
            $this->db->query("INSERT INTO Phones (customer_id, phone_number, phone_type, is_primary, is_active, created_at) 
                              VALUES (:customer_id, :phone_number, 'mobile', :is_primary, 1, NOW())");
            $this->db->bind(':customer_id', $customer_id);
            $this->db->bind(':phone_number', $phone_number);
            $this->db->bind(':is_primary', $is_primary);
            
            $result = $this->db->execute();
            
            if ($result) {
                error_log("New phone added successfully for customer $customer_id: $phone_number");
            } else {
                error_log("Failed to insert new phone for customer $customer_id: $phone_number");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error adding phone for customer $customer_id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing email
     * @param int $email_id
     * @param int $customer_id
     * @param string $email
     * @param int $is_primary
     * @return bool
     */
    public function updateCustomerEmail($email_id, $customer_id, $email, $is_primary = 0) {
        try {
            // If setting as primary, unset other primary emails
            if ($is_primary == 1) {
                $this->db->query("UPDATE Emails SET is_primary = 0 WHERE customer_id = :customer_id");
                $this->db->bind(':customer_id', $customer_id);
                $this->db->execute();
            }

            // Update email
            $this->db->query("UPDATE Emails SET email = :email, is_primary = :is_primary 
                              WHERE email_id = :email_id AND customer_id = :customer_id");
            $this->db->bind(':email_id', $email_id);
            $this->db->bind(':customer_id', $customer_id);
            $this->db->bind(':email', $email);
            $this->db->bind(':is_primary', $is_primary);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Error updating email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing phone
     * @param int $phone_id
     * @param int $customer_id
     * @param string $phone_number
     * @param int $is_primary
     * @return bool
     */
    public function updateCustomerPhone($phone_id, $customer_id, $phone_number, $is_primary = 0) {
        try {
            // If setting as primary, unset other primary phones
            if ($is_primary == 1) {
                $this->db->query("UPDATE Phones SET is_primary = 0 WHERE customer_id = :customer_id");
                $this->db->bind(':customer_id', $customer_id);
                $this->db->execute();
            }

            // Update phone
            $this->db->query("UPDATE Phones SET phone_number = :phone_number, is_primary = :is_primary 
                              WHERE phone_id = :phone_id AND customer_id = :customer_id");
            $this->db->bind(':phone_id', $phone_id);
            $this->db->bind(':customer_id', $customer_id);
            $this->db->bind(':phone_number', $phone_number);
            $this->db->bind(':is_primary', $is_primary);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Error updating phone: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete an email (soft delete)
     * @param int $email_id
     * @param int $customer_id
     * @return bool
     */
    public function deleteCustomerEmail($email_id, $customer_id) {
        try {
            $this->db->query("UPDATE Emails SET is_active = 0 WHERE email_id = :email_id AND customer_id = :customer_id");
            $this->db->bind(':email_id', $email_id);
            $this->db->bind(':customer_id', $customer_id);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Error deleting email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a phone (soft delete)
     * @param int $phone_id
     * @param int $customer_id
     * @return bool
     */
    public function deleteCustomerPhone($phone_id, $customer_id) {
        try {
            $this->db->query("UPDATE Phones SET is_active = 0 WHERE phone_id = :phone_id AND customer_id = :customer_id");
            $this->db->bind(':phone_id', $phone_id);
            $this->db->bind(':customer_id', $customer_id);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Error deleting phone: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all accounts to process for maintaining balance checks
     * @return array
     */
    public function getAllAccountsForMaintainingProcessing() {
        $this->db->query("SELECT account_id, account_number, customer_id FROM Accounts WHERE is_active = 1");
        return $this->db->resultSet();
    }

    /**
     * Get transaction_type_id by name
     */
    public function getTransactionTypeId($type_name) {
        $this->db->query("SELECT transaction_type_id FROM Transaction_Type WHERE type_name = :type_name LIMIT 1");
        $this->db->bind(':type_name', $type_name);
        $row = $this->db->single();
        return $row ? (int)$row->transaction_type_id : null;
    }

    /**
     * Charge service fee: insert bank transaction and service_fee_charges record
     */
    public function chargeServiceFee($account_id, $fee_amount, $fee_type = 'monthly_service_fee') {
        $transactionTypeId = $this->getTransactionTypeId('Service Charge');
        if (!$transactionTypeId) {
            // fallback to a default known id (if migration not run)
            $transactionTypeId = 5; // best-effort
        }

        $transaction_ref = 'SF-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));

        // Get current balance BEFORE inserting transaction
        $balance_before = $this->getAccountBalance($account_id);
        $balance_after = $balance_before - $fee_amount; // Service charge reduces balance

        // Insert Transaction record for the fee (debit)
        $this->db->query("INSERT INTO Transaction (transaction_ref, account_id, transaction_type_id, amount, balance_after, description, created_at) VALUES (:transaction_ref, :account_id, :type_id, :amount, :balance_after, :description, NOW())");
        $this->db->bind(':transaction_ref', $transaction_ref);
        $this->db->bind(':account_id', $account_id);
        $this->db->bind(':type_id', $transactionTypeId);
        $this->db->bind(':amount', $fee_amount);
        $this->db->bind(':balance_after', $balance_after);
        $this->db->bind(':description', 'Service Charge - ' . $transaction_ref);
        $this->db->execute();

        $transaction_id = $this->db->lastInsertId();

        // Insert into service_fee_charges
        $this->db->query("INSERT INTO service_fee_charges (account_id, transaction_id, fee_amount, balance_before, balance_after, charge_date, fee_type, created_at) VALUES (:account_id, :transaction_id, :fee_amount, :balance_before, :balance_after, CURDATE(), :fee_type, NOW())");
        $this->db->bind(':account_id', $account_id);
        $this->db->bind(':transaction_id', $transaction_id);
        $this->db->bind(':fee_amount', $fee_amount);
        $this->db->bind(':balance_before', $balance_before);
        $this->db->bind(':balance_after', $balance_after);
        $this->db->bind(':fee_type', $fee_type);
        $this->db->execute();

        return ['transaction_id' => $transaction_id, 'balance_before' => $balance_before, 'balance_after' => $balance_after];
    }

    /**
     * Update account status and write to history
     */
    public function setAccountStatus($account_id, $new_status, $balance_at_change = 0.00, $reason = null, $changed_by = null, $extraFields = []) {
        // fetch previous status
        $this->db->query("SELECT is_active FROM Accounts WHERE account_id = :account_id LIMIT 1");
        $this->db->bind(':account_id', $account_id);
        $prev = $this->db->single();
        $previous_status = $prev ? $prev->account_status : null;

        // Build update query parts
        $updates = [];
        $params = [':account_id' => $account_id];
        if (isset($extraFields['below_maintaining_since'])) {
            $updates[] = "below_maintaining_since = :below_maintaining_since";
            $params[':below_maintaining_since'] = $extraFields['below_maintaining_since'];
        }
        if (isset($extraFields['last_service_fee_date'])) {
            $updates[] = "last_service_fee_date = :last_service_fee_date";
            $params[':last_service_fee_date'] = $extraFields['last_service_fee_date'];
        }
        if (isset($extraFields['closure_warning_date'])) {
            $updates[] = "closure_warning_date = :closure_warning_date";
            $params[':closure_warning_date'] = $extraFields['closure_warning_date'];
        }
        $updates[] = "account_status = :account_status";
        $params[':account_status'] = $new_status;

        $sql = "UPDATE Accounts SET " . implode(', ', $updates) . " WHERE account_id = :account_id";
        $this->db->query($sql);
        foreach ($params as $k => $v) {
            $this->db->bind($k, $v);
        }
        $this->db->execute();

        // Insert into account_status_history
        $this->db->query("INSERT INTO account_status_history (account_id, previous_status, new_status, balance_at_change, reason, changed_by, created_at) VALUES (:account_id, :previous_status, :new_status, :balance_at_change, :reason, :changed_by, NOW())");
        $this->db->bind(':account_id', $account_id);
        $this->db->bind(':previous_status', $previous_status);
        $this->db->bind(':new_status', $new_status);
        $this->db->bind(':balance_at_change', $balance_at_change);
        $this->db->bind(':reason', $reason);
        $this->db->bind(':changed_by', $changed_by);
        $this->db->execute();

        return true;
    }

    /**
     * Get all barangays
     * @return array
     */
    public function getAllBarangays() {
        $this->db->query("SELECT barangay_id, barangay_name, city_id FROM Barangay ORDER BY barangay_name ASC");
        return $this->db->resultSet();
    }

    // --- FOR THE CHANGE PASSWORD ---
    public function getCurrentPasswordHash($user_id){
        $this->db->query("
            SELECT password_hash
            FROM Customers
            WHERE customer_id = :id;
        ");
        $this->db->bind(':id', $user_id);
        $row = $this->db->single();
        return $row ? $row->password_hash : false;
    }

    public function updatePassword($user_id, $new_password_hash){
        $this->db->query("
            UPDATE Customers
            SET password_hash = :new_password_hash
            WHERE customer_id = :id;
        ");
        $this->db->bind(':new_password_hash', $new_password_hash);
        $this->db->bind(':id', $user_id);

        return $this->db->execute(); 
    }

    // --- UPDATE PROFILE ---
    public function updateCustomerProfile($customer_id, $profile_data) {
        $success = true;
        
        try {
            // Update email if provided - now uses Emails table
            if (isset($profile_data['email_address']) && !empty($profile_data['email_address'])) {
                // Check if primary email exists
                $this->db->query("SELECT email_id FROM Emails WHERE customer_id = :customer_id AND is_primary = 1 LIMIT 1");
                $this->db->bind(':customer_id', $customer_id);
                $existing_email = $this->db->single();
                
                if ($existing_email) {
                    // Update existing primary email
                    $this->db->query("UPDATE Emails SET email = :email WHERE email_id = :email_id");
                    $this->db->bind(':email', $profile_data['email_address']);
                    $this->db->bind(':email_id', $existing_email->email_id);
                    $result = $this->db->execute();
                } else {
                    // Insert new primary email
                    $this->db->query("INSERT INTO Emails (customer_id, email, is_primary, is_active, created_at) VALUES (:customer_id, :email, 1, 1, NOW())");
                    $this->db->bind(':customer_id', $customer_id);
                    $this->db->bind(':email', $profile_data['email_address']);
                    $result = $this->db->execute();
                }
                
                if (!$result) {
                    error_log("Failed to update email for customer_id: $customer_id");
                }
                $success = $result && $success;
            }
            
            // Update phone if provided - now uses Phones table
            if (isset($profile_data['mobile_number']) && !empty($profile_data['mobile_number'])) {
                // Check if primary phone exists
                $this->db->query("SELECT phone_id FROM Phones WHERE customer_id = :customer_id AND is_primary = 1 LIMIT 1");
                $this->db->bind(':customer_id', $customer_id);
                $existing_phone = $this->db->single();
                
                if ($existing_phone) {
                    // Update existing primary phone
                    $this->db->query("UPDATE Phones SET phone_number = :phone_number WHERE phone_id = :phone_id");
                    $this->db->bind(':phone_number', $profile_data['mobile_number']);
                    $this->db->bind(':phone_id', $existing_phone->phone_id);
                    $result = $this->db->execute();
                } else {
                    // Insert new primary phone
                    $this->db->query("INSERT INTO Phones (customer_id, phone_number, phone_type, is_primary, is_active, created_at) VALUES (:customer_id, :phone_number, 'mobile', 1, 1, NOW())");
                    $this->db->bind(':customer_id', $customer_id);
                    $this->db->bind(':phone_number', $profile_data['mobile_number']);
                    $result = $this->db->execute();
                }
                
                if (!$result) {
                    error_log("Failed to update phone for customer_id: $customer_id");
                }
                $success = $result && $success;
            }
            
            // Update address parts if provided
            if (isset($profile_data['address_line']) || isset($profile_data['city_id']) || isset($profile_data['barangay_id']) || isset($profile_data['province_id'])) {
                $address_line = isset($profile_data['address_line']) ? trim($profile_data['address_line']) : null;
                $city_id = isset($profile_data['city_id']) ? (is_numeric($profile_data['city_id']) ? (int)$profile_data['city_id'] : null) : null;
                $barangay_id = isset($profile_data['barangay_id']) ? (is_numeric($profile_data['barangay_id']) ? (int)$profile_data['barangay_id'] : null) : null;
                $province_id = isset($profile_data['province_id']) ? (is_numeric($profile_data['province_id']) ? (int)$profile_data['province_id'] : null) : null;

                // Check if primary address exists
                $this->db->query("SELECT address_id FROM Addresses WHERE customer_id = :customer_id AND is_primary = 1 AND is_active = 1 LIMIT 1");
                $this->db->bind(':customer_id', $customer_id);
                $addr_exists = $this->db->single();

                if ($addr_exists) {
                    // Build update dynamically
                    $set_clauses = [];
                    if ($address_line !== null) $set_clauses[] = "address_line = :address_line";
                    if ($city_id !== null) $set_clauses[] = "city_id = :city_id";
                    if ($barangay_id !== null) $set_clauses[] = "barangay_id = :barangay_id";
                    if ($province_id !== null) $set_clauses[] = "province_id = :province_id";

                    if (!empty($set_clauses)) {
                        $sql = "UPDATE Addresses SET " . implode(', ', $set_clauses) . " WHERE address_id = :address_id";
                        $this->db->query($sql);
                        if ($address_line !== null) $this->db->bind(':address_line', $address_line);
                        if ($city_id !== null) $this->db->bind(':city_id', $city_id);
                        if ($barangay_id !== null) $this->db->bind(':barangay_id', $barangay_id);
                        if ($province_id !== null) $this->db->bind(':province_id', $province_id);
                        $this->db->bind(':address_id', $addr_exists->address_id);
                        $result = $this->db->execute();
                        if (!$result) error_log("Failed to update address for customer_id: $customer_id");
                        $success = $result && $success;
                    }
                } else {
                    // Insert new primary address if at least one part provided
                    if ($address_line || $city_id || $barangay_id || $province_id) {
                        $this->db->query("INSERT INTO Addresses (customer_id, address_line, city_id, barangay_id, province_id, is_primary, is_active, created_at) VALUES (:customer_id, :address_line, :city_id, :barangay_id, :province_id, 1, 1, NOW())");
                        $this->db->bind(':customer_id', $customer_id);
                        $this->db->bind(':address_line', $address_line);
                        $this->db->bind(':city_id', $city_id);
                        $this->db->bind(':barangay_id', $barangay_id);
                        $this->db->bind(':province_id', $province_id);
                        $result = $this->db->execute();
                        if (!$result) error_log("Failed to insert address for customer_id: $customer_id");
                        $success = $result && $success;
                    }
                }
            }
            
            // Get gender_id if gender name is provided
            $gender_id = null;
            if (isset($profile_data['gender'])) {
                $this->db->query("SELECT gender_id FROM Gender WHERE gender_name = :gender_name LIMIT 1");
                $this->db->bind(':gender_name', $profile_data['gender']);
                $gender_result = $this->db->single();
                if ($gender_result) {
                    $gender_id = $gender_result->gender_id;
                }
            }
            
            // Update Customer_Profile table
            $update_fields = [];
            $bind_params = [':customer_id' => $customer_id];
            
            if (isset($profile_data['civil_status'])) {
                $update_fields[] = "marital_status = :marital_status";
                $bind_params[':marital_status'] = $profile_data['civil_status'];
            }
            
            if (isset($profile_data['citizenship'])) {
                $update_fields[] = "nationality = :nationality";
                $bind_params[':nationality'] = $profile_data['citizenship'];
            }
            
            if (isset($profile_data['occupation'])) {
                $update_fields[] = "occupation = :occupation";
                $bind_params[':occupation'] = $profile_data['occupation'];
            }
            
            if (isset($profile_data['name_of_employer'])) {
                $update_fields[] = "company_name = :company_name";
                $bind_params[':company_name'] = $profile_data['name_of_employer'];
            }
            
            if (isset($profile_data['income_range'])) {
                $update_fields[] = "income_range = :income_range";
                $bind_params[':income_range'] = $profile_data['income_range'];
            }
            
            if ($gender_id !== null) {
                $update_fields[] = "gender_id = :gender_id";
                $bind_params[':gender_id'] = $gender_id;
            }
            
            if (!empty($update_fields)) {
                // Update Customer_Profile table
                $sql = "UPDATE Customer_Profile SET " . implode(", ", $update_fields) . " WHERE customer_id = :customer_id";
                $this->db->query($sql);
                
                // Bind all parameters
                foreach ($bind_params as $param => $value) {
                    $this->db->bind($param, $value);
                }
                
                $result = $this->db->execute();
                
                // If no rows were updated, try to insert (in case profile doesn't exist)
                if ($result && $this->db->rowCount() === 0) {
                    // Build INSERT statement for missing profile
                    $insert_fields = ['customer_id'];
                    $insert_values = [':customer_id'];
                    $insert_params = [':customer_id' => $customer_id];
                    
                    foreach ($bind_params as $param => $value) {
                        if ($param !== ':customer_id') {
                            $field_name = str_replace(':', '', $param);
                            // Map parameter names to database field names
                            $field_mapping = [
                                'marital_status' => 'marital_status',
                                'nationality' => 'nationality',
                                'occupation' => 'occupation',
                                'company_name' => 'company_name',
                                'gender_id' => 'gender_id'
                            ];
                            
                            if (isset($field_mapping[$field_name])) {
                                $insert_fields[] = $field_mapping[$field_name];
                                $insert_values[] = $param;
                                $insert_params[$param] = $value;
                            }
                        }
                    }
                    
                    if (count($insert_fields) > 1) {
                        $insert_sql = "INSERT INTO Customer_Profile (" . implode(", ", $insert_fields) . ", created_at) VALUES (" . implode(", ", $insert_values) . ", NOW())";
                        $this->db->query($insert_sql);
                        
                        foreach ($insert_params as $param => $value) {
                            $this->db->bind($param, $value);
                        }
                        
                        $success = $this->db->execute() && $success;
                    }
                } else {
                    $success = $result && $success;
                }
            }
            
        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            return false;
        }
        
        return $success;
    }
    
    public function getGenderId($gender_name) {
        $this->db->query("SELECT gender_id FROM Gender WHERE gender_name = :gender_name LIMIT 1");
        $this->db->bind(':gender_name', $gender_name);
        $result = $this->db->single();
        return $result ? $result->gender_id : null;
    }
    
    public function getAccountByNumber($account_number){
        $this->db->query("
            SELECT *
            FROM Accounts
            WHERE account_number = :account_number;
        ");

        $this->db->bind(':account_number', $account_number);
        return $this->db->single();
    }

    public function validateRecipient($recipient_number, $recipient_name){
        $this->db->query("
            SELECT 
                a.account_number, 
                CONCAT_WS(' ', c.first_name, c.last_name) AS customer_name,
                c.customer_id
            FROM 
                Accounts a
            INNER JOIN 
                Customers c ON a.customer_id = c.customer_id
            WHERE 
                a.account_number = :recipient_number
                AND a.is_active = 1;
        ");
        $this->db->bind(':recipient_number', $recipient_number);
        $result = $this->db->single();

        if(empty($result)){
            return ['status' => false , 'error' => 'Invalid Account Number'];
        }

        if (strtolower(trim($result->customer_name)) !== strtolower(trim($recipient_name))) {
            return ['status' => false, 'error' => 'Recipient name does not match the account number.'];
        }

        return [
            'status' => true,
            'customer_id' => $result->customer_id,
            'account_number' => $result->account_number
        ];

    }

    public function validateAmount($account_number){
        $this->db->query("
            SELECT 
                a.account_number,
                COALESCE(SUM(
                    CASE tt.type_name 
                        WHEN 'Deposit' THEN t.amount
                        WHEN 'Transfer In' THEN t.amount
                        WHEN 'Interest Payment' THEN t.amount
                        WHEN 'Loan Disbursement' THEN t.amount
                        -- Debits (will show as negative in the SQL result)
                        WHEN 'Withdrawal' THEN -t.amount
                        WHEN 'Transfer Out' THEN -t.amount
                        WHEN 'Service Charge' THEN -t.amount
                        WHEN 'Loan Payment' THEN -t.amount
                        
                        -- If a transaction type isn't listed (e.g., system error), treat as 0
                        ELSE 0 
                    END
                ), 0) AS balance
            FROM 
                Accounts a
            LEFT JOIN 
                Transaction t ON a.account_id = t.account_id
            LEFT JOIN 
                Transaction_Type tt ON t.transaction_type_id = tt.transaction_type_id
            WHERE 
                a.account_number = :account_number
                AND a.is_active = 1
            GROUP BY 
                a.account_id, a.account_number;
        ");
        $this->db->bind('account_number', $account_number);

        return $this->db->single();
    }

    public function recordTransaction($transaction_ref, $sender, $receiver, $amount, $fee, $message){
        // Get sender's current balance
        $senderCurrentBalance = $this->getAccountBalance($sender);
        
        // Get receiver's current balance
        $receiverCurrentBalance = $this->getAccountBalance($receiver);
        
        // for sender (Transfer Out - subtract amount)
        $senderBalanceAfter = $senderCurrentBalance - $amount;
        $this->db->query("
        INSERT INTO Transaction (
            transaction_ref,
            account_id,
            transaction_type_id,
            amount,
            related_account_id,
            balance_after,
            description
        )
        VALUES (
            :transaction_ref,
            :sender,
            :transaction_type,
            :amount,
            :receiver,
            :balance_after,
            :message
        );
        ");
        $this->db->bind(':transaction_ref', $transaction_ref);
        $this->db->bind(':sender', $sender);
        $this->db->bind(':transaction_type', 8);
        $this->db->bind(':amount', $amount);
        $this->db->bind(':receiver', $receiver);
        $this->db->bind(':balance_after', $senderBalanceAfter);
        $this->db->bind(':message', $message);
        $this->db->execute();

        // For the fee (only if fee is greater than 0)
        if ($fee > 0) {
            $senderBalanceAfter = $senderBalanceAfter - $fee; // Subtract fee from balance
            $this->db->query("
            INSERT INTO Transaction (
                account_id,
                transaction_type_id,
                amount,
                balance_after,
                description
            )
            VALUES (
                :sender,
                :transaction_type,
                :amount,
                :balance_after,
                :message
            );
            ");
            $this->db->bind(':sender', $sender);
            $this->db->bind(':transaction_type', 5);
            $this->db->bind(':amount', $fee);
            $this->db->bind(':balance_after', $senderBalanceAfter);
            $this->db->bind(':message', 'Transaction Service Charge - ' . $transaction_ref);
            $this->db->execute();
        }

        // for the receiver (Transfer In - add amount)
        $receiverBalanceAfter = $receiverCurrentBalance + $amount;
        $this->db->query("
        INSERT INTO Transaction (
            account_id,
            transaction_type_id,
            amount,
            related_account_id,
            balance_after,
            description
        )
        VALUES (
            :sender,
            :transaction_type,
            :amount,
            :receiver,
            :balance_after,
            :message
        );
        ");
        $this->db->bind(':sender', $receiver);
        $this->db->bind(':transaction_type', 9);
        $this->db->bind(':amount', $amount);
        $this->db->bind(':receiver', $sender);
        $this->db->bind(':balance_after', $receiverBalanceAfter);
        $this->db->bind(':message', $message);
        $this->db->execute();
    }

    public function recordDummyTransfer($transaction_ref, $sender, $amount, $fee, $message){
        // Get sender's current balance
        $senderCurrentBalance = $this->getAccountBalance($sender);
        
        // for sender (debit only - no receiver credit for external transfer)
        $senderBalanceAfter = $senderCurrentBalance - $amount;
        $this->db->query("
        INSERT INTO Transaction (
            transaction_ref,
            account_id,
            transaction_type_id,
            amount,
            balance_after,
            description
        )
        VALUES (
            :transaction_ref,
            :sender,
            :transaction_type,
            :amount,
            :balance_after,
            :message
        );
        ");
        $this->db->bind(':transaction_ref', $transaction_ref);
        $this->db->bind(':sender', $sender);
        $this->db->bind(':transaction_type', 8);
        $this->db->bind(':amount', $amount);
        $this->db->bind(':balance_after', $senderBalanceAfter);
        $this->db->bind(':message', $message);
        $this->db->execute();

        // For the fee (only if fee is greater than 0)
        if ($fee > 0) {
            $senderBalanceAfter = $senderBalanceAfter - $fee; // Subtract fee
            $this->db->query("
            INSERT INTO Transaction (
                account_id,
                transaction_type_id,
                amount,
                balance_after,
                description
            )
            VALUES (
                :sender,
                :transaction_type,
                :amount,
                :balance_after,
                :message
            );
            ");
            $this->db->bind(':sender', $sender);
            $this->db->bind(':transaction_type', 5);
            $this->db->bind(':amount', $fee);
            $this->db->bind(':balance_after', $senderBalanceAfter);
            $this->db->bind(':message', 'Transaction Service Charge - ' . $transaction_ref);
            $this->db->execute();
        }
    }

    public function getDropDownByCustomerId($customer_id) {
        $this->db->query("
            SELECT 
                a.account_id, 
                a.account_number
            FROM 
                Accounts a
            WHERE 
                a.customer_id = :id
            AND
                a.is_active = 1
            AND
                (a.is_locked = 0 OR a.is_locked IS NULL)
        ");

        $this->db->bind(':id', $customer_id);
        return $this->db->resultSet();
    }

    // transaction 
    public function getTransactionTypes() {
        $this->db->query("SELECT type_name FROM Transaction_Type ORDER BY type_name");
        return $this->db->resultSet();
    }

    public function getLinkedAccountsForFilter($customer_id) {
        $this->db->query("
            SELECT
                a.account_id,
                a.account_number,
                act.type_name AS account_type
            FROM Accounts a
            LEFT JOIN Account_Types act ON a.account_type_id = act.account_type_id
            WHERE
                a.customer_id = :customer_id AND a.is_active = 1
            ORDER BY a.account_number
        ");
        $this->db->bind(':customer_id', $customer_id);
        return $this->db->resultSet();
    }

    public function getAllTransactionsByCustomerId($customer_id, $filters = [], $limit = 20, $offset = 0) {
        // SQL logic to determine if the transaction is a credit (in) or debit (out)
        $sql_signed_amount = "
            CASE tt.type_name
                WHEN 'Deposit' THEN t.amount
                WHEN 'Transfer In' THEN t.amount
                WHEN 'Interest Payment' THEN t.amount
                WHEN 'Loan Disbursement' THEN t.amount
                -- Debits (will show as negative in the SQL result)
                WHEN 'Withdrawal' THEN -t.amount
                WHEN 'Transfer Out' THEN -t.amount
                WHEN 'Service Charge' THEN -t.amount
                WHEN 'Loan Payment' THEN -t.amount
                ELSE 0
            END
        ";
        $sql_select = "
            SELECT
                t.transaction_id,
                t.transaction_ref,
                t.description,
                t.created_at,
                tt.type_name AS transaction_type,
                a.account_number,
                a.account_id,
                ({$sql_signed_amount}) AS signed_amount,
                t.amount AS raw_amount
            FROM Accounts a
            INNER JOIN Transaction t ON a.account_id = t.account_id
            LEFT JOIN Transaction_Type tt ON t.transaction_type_id = tt.transaction_type_id
            WHERE
                a.customer_id = :customer_id
                AND a.is_active = 1
        ";

        $params = [':customer_id' => $customer_id];
        $conditions = [];

        // Filter by Account ID
        if (!empty($filters['account_id']) && $filters['account_id'] !== 'all') {
            $conditions[] = "a.account_id = :account_id";
            $params[':account_id'] = $filters['account_id'];
        }
        
        // Filter by Transaction Type
        if (!empty($filters['type_name']) && $filters['type_name'] !== 'All') {
            $conditions[] = "tt.type_name = :type_name";
            $params[':type_name'] = $filters['type_name'];
        }

        // Filter by Date Range
        if (!empty($filters['start_date'])) {
            $conditions[] = "DATE(t.created_at) >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $conditions[] = "DATE(t.created_at) <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        // Append all WHERE conditions
        if (!empty($conditions)) {
            $sql_select .= " AND " . implode(" AND ", $conditions);
        }

        // --- PAGINATION COUNT ---
        $sql_count = "SELECT COUNT(*) AS total FROM ({$sql_select}) AS subquery";
        $this->db->query($sql_count);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $total_bank_transactions = $this->db->single()->total;

        // --- FETCH PAGINATED RESULTS ---
        $sql_order_limit = " ORDER BY t.created_at DESC LIMIT :limit OFFSET :offset";
        
        $this->db->query($sql_select . $sql_order_limit);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        // Re-bind all filter parameters
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $bank_transactions = $this->db->resultSet();

        return [
            'bank_transactions' => $bank_transactions,
            'total' => $total_bank_transactions,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    public function getAllFilteredTransactions($customer_id, $filters) {
        // SQL logic to determine if the transaction is a credit (in) or debit (out)
        // This logic should match the one in getAllTransactionsByCustomerId
        $sql_signed_amount = "
            CASE tt.type_name
                WHEN 'Deposit' THEN t.amount
                WHEN 'Transfer In' THEN t.amount
                WHEN 'Interest Payment' THEN t.amount
                WHEN 'Loan Disbursement' THEN t.amount
                -- Debits (will show as negative in the SQL result)
                WHEN 'Withdrawal' THEN -t.amount
                WHEN 'Transfer Out' THEN -t.amount
                WHEN 'Service Charge' THEN -t.amount
                WHEN 'Loan Payment' THEN -t.amount
                ELSE 0
            END
        ";

        $sql = "SELECT 
                    t.*, 
                    tt.type_name AS transaction_type, 
                    a.account_number, 
                    ({$sql_signed_amount}) AS signed_amount,
                    t.amount AS raw_amount
                FROM Transaction t
                JOIN Transaction_Type tt ON t.transaction_type_id = tt.transaction_type_id
                JOIN Accounts a ON t.account_id = a.account_id
                WHERE a.customer_id = :customer_id AND a.is_active = 1";

        $params = [':customer_id' => $customer_id];

        // Apply Filters
        if (!empty($filters['account_id']) && $filters['account_id'] !== 'all') {
            $sql .= ' AND a.account_id = :account_id';
            $params[':account_id'] = $filters['account_id'];
        }

        if (!empty($filters['type_name']) && $filters['type_name'] !== 'All') {
            $sql .= ' AND tt.type_name = :type_name';
            $params[':type_name'] = $filters['type_name'];
        }

        if (!empty($filters['start_date'])) {
            $sql .= ' AND DATE(t.created_at) >= :start_date';
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= ' AND DATE(t.created_at) <= :end_date';
            $params[':end_date'] = $filters['end_date'];
        }
        $sql .= ' ORDER BY t.created_at DESC';

        $this->db->query($sql);
        
        // Bind parameters
        foreach ($params as $param_name => $value) {
            $this->db->bind($param_name, $value);
        }

        return $this->db->resultSet();
    }

    // -- LOANS --
    public function getActiveLoanApplications($customerId)
    {
        $this->db->query("
            SELECT
                la.id AS application_id,
                la.account_number,
                la.loan_type,
                la.loan_amount AS remaining_balance,
                DATE_FORMAT(la.created_at, '%M %d, %Y') AS application_date,
                la.status
            FROM
                loan_applications la
            INNER JOIN
                Accounts a ON la.account_number = a.account_number
            WHERE
                a.customer_id = :customer_id
                AND la.status = 'Active'
                AND la.loan_amount > 0
            ORDER BY
                la.created_at DESC;
        ");
        $this->db->bind(':customer_id', $customerId);
        return $this->db->resultSet();
    }

    public function processApplicationPayment($applicationId, $paymentAmount, $sourceAccountNumber, $customerId){
        // Input validation
        if (!is_numeric($paymentAmount) || $paymentAmount <= 0) {
            return ['status' => false, 'error' => 'Invalid payment amount. Must be a positive number.'];
        }
        if (empty($applicationId) || empty($sourceAccountNumber) || empty($customerId)) {
            return ['status' => false, 'error' => 'Missing required parameters.'];
        }

        // 1. Validate that the loan application belongs to the customer and is active/approved
        $this->db->query("
            SELECT la.id, la.loan_amount, la.status
            FROM loan_applications la
            INNER JOIN Accounts a ON la.account_number = a.account_number
            WHERE la.id = :application_id AND a.customer_id = :customer_id AND la.status = 'Active'
        ");
        $this->db->bind(':application_id', $applicationId);
        $this->db->bind(':customer_id', $customerId);
        $loanApp = $this->db->single();

        if (!$loanApp) {
            return ['status' => false, 'error' => 'Loan application not found or does not belong to the customer.'];
        }

        $remainingBalance = (float)$loanApp->loan_amount;
        if ($remainingBalance <= 0) {
            return ['status' => false, 'error' => 'Loan is already fully paid or closed.'];
        }
        if ($paymentAmount > $remainingBalance) {
            return ['status' => false, 'error' => 'Payment amount exceeds remaining loan balance.'];
        }

        // 2. Validate that the source account belongs to the customer and is not locked
        $this->db->query("
            SELECT account_id
            FROM Accounts
            WHERE account_number = :account_number AND customer_id = :customer_id AND is_active = 1 AND (is_locked = 0 OR is_locked IS NULL)
        ");
        $this->db->bind(':account_number', $sourceAccountNumber);
        $this->db->bind(':customer_id', $customerId);
        $sourceAccount = $this->db->single();

        if (!$sourceAccount) {
            return ['status' => false, 'error' => 'Source account not found, not owned by the customer, or is locked.'];
        }
        $sourceAccountId = $sourceAccount->account_id;

        // 3. Check source account balance
        $balanceCheck = $this->validateAmount($sourceAccountNumber);
        $currentBalance = $balanceCheck ? (float)$balanceCheck->balance : 0.00;
        if ($currentBalance < $paymentAmount) {
            return ['status' => false, 'error' => 'Insufficient funds in the source account.'];
        }

        // 4. Begin transaction
        $this->db->beginTransaction();

        try {
            // 5. Update loan application balance (subtract payment)
            $this->db->query("
                UPDATE loan_applications
                SET loan_amount = loan_amount - :payment_amount
                WHERE id = :application_id
            ");
            $this->db->bind(':payment_amount', $paymentAmount);
            $this->db->bind(':application_id', $applicationId);

            if (!$this->db->execute()) {
                throw new Exception("Failed to update loan application balance.");
            }

            // 6. Insert bank transaction for the payment (debit from source account)
            $transactionTypeId = 7; // Assuming 7 is 'Loan Payment'
            $transactionRef = uniqid('LP-'); // Prefix for clarity
            $description = "Loan Payment - Ref: {$transactionRef}, Application ID: {$applicationId}, From: {$sourceAccountNumber}";

            // Get current balance BEFORE the payment
            $currentBalance = $this->getAccountBalance($sourceAccountId);
            $balance_after = $currentBalance - $paymentAmount; // Loan payment reduces balance

            $this->db->query("
                INSERT INTO Transaction (
                    transaction_ref,
                    account_id,
                    transaction_type_id,
                    amount,
                    balance_after,
                    description,
                    created_at
                )
                VALUES (
                    :transaction_ref,
                    :account_id,
                    :type_id,
                    :amount,
                    :balance_after,
                    :description,
                    NOW()
                )
            ");
            $this->db->bind(':transaction_ref', $transactionRef);
            $this->db->bind(':account_id', $sourceAccountId);
            $this->db->bind(':type_id', $transactionTypeId);
            $this->db->bind(':amount', $paymentAmount); // Positive for raw amount; signed logic handles debit
            $this->db->bind(':balance_after', $balance_after);
            $this->db->bind(':description', $description);

            if (!$this->db->execute()) {
                throw new Exception("Failed to record bank transaction.");
            }

            // 7. Check if loan is fully paid and close it
            $this->db->query("SELECT loan_amount FROM loan_applications WHERE id = :application_id");
            $this->db->bind(':application_id', $applicationId);
            $updatedLoan = $this->db->single();

            if ($updatedLoan && (float)$updatedLoan->loan_amount <= 0) {
                $this->db->query("
                    UPDATE loan_applications
                    SET status = 'Closed'
                    WHERE id = :application_id
                ");
                $this->db->bind(':application_id', $applicationId);
                if (!$this->db->execute()) {
                    throw new Exception("Failed to close the loan application.");
                }
            }

            // 8. Commit transaction
            $this->db->commit();
            return ['status' => true, 'message' => 'Loan payment processed successfully.', 'transaction_ref' => $transactionRef];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Loan Payment Error: " . $e->getMessage());
            return ['status' => false, 'error' => 'Payment processing failed: ' . $e->getMessage()];
        }
    }


    public function getPrimaryAccountNumber($customerId)
    {
        $this->db->query("SELECT account_number FROM Accounts WHERE customer_id = :customer_id AND is_active = 1 LIMIT 1");
        $this->db->bind(':customer_id', $customerId);
        $result = $this->db->single();
        return $result ? $result->account_number : null;
    }

    // -- REFERRAL SYSTEM --
    
    /**
     * Get customer's referral code
     */
    public function getReferralCode($customerId)
    {
        $this->db->query("
            SELECT referral_code, total_points 
            FROM Customers 
            WHERE customer_id = :customer_id
        ");
        $this->db->bind(':customer_id', $customerId);
        $result = $this->db->single();
        return $result;
    }

    /**
     * Get referral statistics for a customer
     */
    public function getReferralStats($customerId)
    {
        // Get total points
        $this->db->query("
            SELECT total_points 
            FROM Customers 
            WHERE customer_id = :customer_id
        ");
        $this->db->bind(':customer_id', $customerId);
        $customer = $this->db->single();
        $totalPoints = $customer ? $customer->total_points : 0;

        // Count number of referrals (people who used this customer's code)
        $this->db->query("
            SELECT COUNT(*) as referral_count
            FROM Customers
            WHERE referred_by_customer_id = :customer_id
        ");
        $this->db->bind(':customer_id', $customerId);
        $referralCount = $this->db->single();
        $count = $referralCount ? $referralCount->referral_count : 0;

        return [
            'total_points' => $totalPoints,
            'referral_count' => $count
        ];
    }

    /**
     * Apply a friend's referral code
     */
    public function applyReferralCode($customerId, $friendCode)
    {
        $friendCode = strtoupper(trim($friendCode));
        
        if (empty($friendCode)) {
            return ['success' => false, 'message' => 'Please enter a referral code'];
        }

        try {
            $this->db->beginTransaction();

            // Check if user already used a referral code
            $this->db->query("
                SELECT referred_by_customer_id 
                FROM Customers 
                WHERE customer_id = :customer_id AND referred_by_customer_id IS NOT NULL
            ");
            $this->db->bind(':customer_id', $customerId);
            $existing = $this->db->single();
            
            if ($existing && $existing->referred_by_customer_id) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'You have already used a referral code'];
            }

            // Get user's own referral code to prevent self-referral
            $this->db->query("
                SELECT referral_code 
                FROM Customers 
                WHERE customer_id = :customer_id
            ");
            $this->db->bind(':customer_id', $customerId);
            $user = $this->db->single();
            
            if ($user && $user->referral_code === $friendCode) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'You cannot use your own referral code'];
            }

            // Find the referrer
            $this->db->query("
                SELECT customer_id, first_name, last_name 
                FROM Customers 
                WHERE referral_code = :referral_code
            ");
            $this->db->bind(':referral_code', $friendCode);
            $referrer = $this->db->single();
            
            if (!$referrer) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Invalid referral code'];
            }

            $referrerId = $referrer->customer_id;
            
            // Check if trying to use own code (double check)
            if ($referrerId == $customerId) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'You cannot use your own referral code'];
            }

            // Points to award
            $referrerPoints = 50.00;
            $referredPoints = 25.00;

            // Update new customer with referrer info
            $this->db->query("
                UPDATE Customers 
                SET referred_by_customer_id = :referrer_id 
                WHERE customer_id = :customer_id
            ");
            $this->db->bind(':referrer_id', $referrerId);
            $this->db->bind(':customer_id', $customerId);
            $this->db->execute();

            // Award points to referrer
            $this->db->query("
                UPDATE Customers 
                SET total_points = total_points + :points 
                WHERE customer_id = :referrer_id
            ");
            $this->db->bind(':points', $referrerPoints);
            $this->db->bind(':referrer_id', $referrerId);
            $this->db->execute();

            // Award points to new customer
            $this->db->query("
                UPDATE Customers 
                SET total_points = total_points + :points 
                WHERE customer_id = :customer_id
            ");
            $this->db->bind(':points', $referredPoints);
            $this->db->bind(':customer_id', $customerId);
            $this->db->execute();

            $this->db->commit();

            return [
                'success' => true, 
                'message' => 'Referral code applied successfully! You and your friend earned bonus points!',
                'referrer_points' => $referrerPoints,
                'your_points' => $referredPoints
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Referral Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }

    /**
     * Calculate and apply interest to all Savings accounts
     * Interest is calculated monthly based on the account balance
     * @return array Results of interest application
     */
    public function calculateAndApplyInterest() {
        // Get transaction type ID for Interest Payment
        $this->db->query("SELECT transaction_type_id FROM Transaction_Type WHERE type_name = 'Interest Payment' LIMIT 1");
        $interestType = $this->db->single();
        
        if (!$interestType) {
            return ['success' => false, 'error' => 'Interest Payment transaction type not found'];
        }
        
        $interest_type_id = $interestType->transaction_type_id;
        $results = [];
        $total_interest_applied = 0;
        $accounts_processed = 0;
        
        // Get all active Savings accounts (account_type_id = 1) with interest rate
        $this->db->query("
            SELECT 
                a.account_id,
                a.account_number,
                a.interest_rate,
                a.last_interest_date,
                a.customer_id
            FROM Accounts a
            INNER JOIN Account_Types at ON a.account_type_id = at.account_type_id
            WHERE a.account_type_id = 1 
            AND a.interest_rate IS NOT NULL 
            AND a.interest_rate > 0
            AND a.is_active = 1
            AND (a.is_locked = 0 OR a.is_locked IS NULL)
        ");
        
        $savings_accounts = $this->db->resultSet();
        
        foreach ($savings_accounts as $account) {
            // Calculate current balance
            $balance = $this->getAccountBalance($account->account_id);
            
            if ($balance <= 0) {
                continue; // Skip accounts with zero or negative balance
            }
            
            // Calculate monthly interest (annual rate / 12)
            // interest_rate is stored as percentage (0.50 = 0.5%)
            $monthly_rate = ($account->interest_rate / 100) / 12;
            $interest_amount = $balance * $monthly_rate;
            
            // Round to 2 decimal places
            $interest_amount = round($interest_amount, 2);
            
            if ($interest_amount > 0) {
                // Record interest payment transaction
                $transaction_ref = 'INT-' . date('YmdHis') . '-' . $account->account_id;
                
                // Calculate balance_after (interest payment increases balance)
                $balance_after = $balance + $interest_amount;
                
                $this->db->query("
                    INSERT INTO Transaction 
                        (transaction_ref, account_id, transaction_type_id, amount, balance_after, description, created_at)
                    VALUES 
                        (:transaction_ref, :account_id, :transaction_type_id, :amount, :balance_after, :description, NOW())
                ");
                
                $this->db->bind(':transaction_ref', $transaction_ref);
                $this->db->bind(':account_id', $account->account_id);
                $this->db->bind(':transaction_type_id', $interest_type_id);
                $this->db->bind(':amount', $interest_amount);
                $this->db->bind(':balance_after', $balance_after);
                $this->db->bind(':description', 'Monthly interest payment - ' . date('F Y'));
                
                if ($this->db->execute()) {
                    // Update last_interest_date
                    $this->db->query("
                        UPDATE Accounts 
                        SET last_interest_date = CURDATE()
                        WHERE account_id = :account_id
                    ");
                    $this->db->bind(':account_id', $account->account_id);
                    $this->db->execute();
                    
                    $total_interest_applied += $interest_amount;
                    $accounts_processed++;
                    
                    $results[] = [
                        'account_number' => $account->account_number,
                        'balance' => $balance,
                        'interest_applied' => $interest_amount
                    ];
                }
            }
        }
        
        return [
            'success' => true,
            'accounts_processed' => $accounts_processed,
            'total_interest_applied' => $total_interest_applied,
            'details' => $results
        ];
    }

    /**
     * Get current account balance from transactions
     * @param int $account_id
     * @return float Current balance
     */
    private function getAccountBalance($account_id) {
        $this->db->query("
            SELECT
                COALESCE(SUM(
                    CASE tt.type_name
                        WHEN 'Deposit' THEN t.amount
                        WHEN 'Transfer In' THEN t.amount
                        WHEN 'Interest Payment' THEN t.amount
                        WHEN 'Loan Disbursement' THEN t.amount
                        WHEN 'Withdrawal' THEN -t.amount
                        WHEN 'Transfer Out' THEN -t.amount
                        WHEN 'Service Charge' THEN -t.amount
                        WHEN 'Loan Payment' THEN -t.amount
                        ELSE 0
                    END
                ), 0) AS current_balance
            FROM Transaction t
            INNER JOIN Transaction_Type tt ON t.transaction_type_id = tt.transaction_type_id
            WHERE t.account_id = :account_id
        ");
        
        $this->db->bind(':account_id', $account_id);
        $result = $this->db->single();
        
        return $result ? (float)$result->current_balance : 0.00;
    }

    /**
     * Calculate interest from a list of transactions using the daily-balance method.
     *
     * Assumptions:
     * - Each transaction's amount is positive for credits (deposits) and negative for debits (withdrawals).
     * - `opening_balance` is the balance at the start of `start_date` BEFORE transactions on that date are applied.
     * - Each transaction affects the balance starting on its `date` (inclusive).
     * - If `start_date` is omitted, calculation starts at the earliest transaction date (or today if none).
     * - If `end_date` is omitted, calculation runs up to today.
     *
     * @param array $transactions Array of ['date' => 'YYYY-MM-DD', 'amount' => float]
     * @param float $opening_balance Balance at start_date before transactions on that date
     * @param float $annual_interest_rate e.g. 0.03 for 3%
     * @param string|null $start_date inclusive, format 'YYYY-MM-DD'
     * @param string|null $end_date inclusive, format 'YYYY-MM-DD'
     * @param bool $return_breakdown when true returns per-period breakdown
     * @return array ['total_interest' => float, 'daily_rate' => float, 'breakdown' => array|null]
     */
    public function calculateInterestFromTransactions(array $transactions, float $opening_balance, float $annual_interest_rate, $start_date = null, $end_date = null, $return_breakdown = false) {
        if ($annual_interest_rate < 0) {
            throw new InvalidArgumentException('annual_interest_rate must be non-negative');
        }

        $daily_rate = $annual_interest_rate / 365.0;

        // Aggregate net change per date (YYYY-MM-DD)
        $net_changes = [];
        foreach ($transactions as $t) {
            if (!isset($t['date']) || !isset($t['amount'])) {
                continue;
            }
            try {
                $d = (new DateTime($t['date']))->format('Y-m-d');
            } catch (Exception $e) {
                // Skip invalid dates
                continue;
            }
            $amt = (float)$t['amount'];
            if (!isset($net_changes[$d])) {
                $net_changes[$d] = 0.0;
            }
            $net_changes[$d] += $amt;
        }

        if (!empty($net_changes)) {
            ksort($net_changes);
        }

        // Determine start and end
        if ($start_date) {
            $start = (new DateTime($start_date))->format('Y-m-d');
        } else {
            $start = !empty($net_changes) ? array_key_first($net_changes) : date('Y-m-d');
        }

        if ($end_date) {
            $end = (new DateTime($end_date))->format('Y-m-d');
        } else {
            $end = date('Y-m-d');
        }

        if ($end < $start) {
            throw new InvalidArgumentException('end_date must be on or after start_date');
        }

        // Build the ordered list of dates to process. Each listed date is a day when the balance may change
        $dates = [$start];
        foreach ($net_changes as $d => $_) {
            if ($d >= $start && $d <= $end && $d !== $start) {
                $dates[] = $d;
            }
        }

        // sentinel: the day after end (so periods end on end_date)
        $end_next = (new DateTime($end))->modify('+1 day')->format('Y-m-d');
        $dates[] = $end_next;

        // Ensure unique and sorted
        $dates = array_values(array_unique($dates));

        $current_balance = (float)$opening_balance;
        $total_interest = 0.0;
        $breakdown = [];

        for ($i = 0; $i < count($dates) - 1; $i++) {
            $date = $dates[$i];

            // Apply all transactions that occur on this date (their effect starts on this date)
            if (isset($net_changes[$date])) {
                $current_balance += $net_changes[$date];
            }

            $next_date = $dates[$i + 1];
            $dt_date = new DateTime($date);
            $dt_next = new DateTime($next_date);
            $days = (int)$dt_date->diff($dt_next)->days; // number of days balance is constant

            if ($days > 0) {
                $interest = $current_balance * $days * $daily_rate;
                $total_interest += $interest;
            } else {
                $interest = 0.0;
            }

            $period_end = (new DateTime($next_date))->modify('-1 day')->format('Y-m-d');

            $breakdown[] = [
                'period_start' => $date,
                'period_end' => $period_end,
                'days' => $days,
                'balance' => round($current_balance, 2),
                'interest' => round($interest, 2),
            ];
        }

        return [
            'total_interest' => round($total_interest, 2),
            'daily_rate' => $daily_rate,
            'breakdown' => $return_breakdown ? $breakdown : null,
        ];
    }

    /**
     * Get account applications by customer email
     * @param string $email Customer email address
     * @return array|false Array of applications or false on failure
     */
    public function getAccountApplicationsByEmail($email) {
        $this->db->query("
            SELECT 
                aa.application_id,
                aa.application_number,
                aa.application_status,
                aa.initial_deposit,
                aa.wants_passbook,
                aa.wants_atm_card,
                aa.terms_accepted_at,
                aa.privacy_accepted_at,
                aa.submitted_at,
                aa.reviewed_at,
                aa.rejection_reason,
                aa.created_at,
                c.customer_id,
                c.first_name,
                c.last_name,
                c.middle_name,
                cp.date_of_birth,
                e.email,
                p.phone_number,
                at.type_name as account_type,
                cp.employment_status,
                cp.company_name as employer_name,
                cp.occupation as job_title,
                cp.income_range as annual_income,
                a.address_line as street_address,
                a.postal_code as zip_code,
                b.barangay_name as barangay,
                ct.city_name as city,
                pr.province_name as state,
                idt.type_name as id_type,
                ci.id_number
            FROM Account_Applications aa
            INNER JOIN Customers c ON aa.customer_id = c.customer_id
            LEFT JOIN Emails e ON c.customer_id = e.customer_id AND e.is_primary = 1
            LEFT JOIN Phones p ON c.customer_id = p.customer_id AND p.is_primary = 1
            LEFT JOIN Account_Types at ON aa.account_type_id = at.account_type_id
            LEFT JOIN Customer_Profile cp ON c.customer_id = cp.customer_id
            LEFT JOIN Addresses a ON c.customer_id = a.customer_id AND a.is_primary = 1
            LEFT JOIN Barangay b ON a.barangay_id = b.barangay_id
            LEFT JOIN City ct ON a.city_id = ct.city_id
            LEFT JOIN Province pr ON a.province_id = pr.province_id
            LEFT JOIN Customer_IDs ci ON c.customer_id = ci.customer_id
            LEFT JOIN ID_Types idt ON ci.id_type_id = idt.id_type_id
            WHERE e.email = :email AND e.is_active = 1
            ORDER BY aa.submitted_at DESC
        ");
        
        $this->db->bind(':email', $email);
        return $this->db->resultSet();
    }

    /**
     * Get all active account types
     * @return array List of active account types
     */
    public function getActiveAccountTypes() {
        $this->db->query("
            SELECT 
                account_type_id,
                type_name,
                description,
                allows_passbook,
                allows_atm_card,
                requires_parent_guardian,
                minimum_age,
                base_interest_rate,
                currency,
                minimum_balance,
                monthly_fee
            FROM Account_Types 
            WHERE is_active = 1
            ORDER BY type_name ASC
        ");
        return $this->db->resultSet();
    }

    /**
     * Submit a new account application
     * @param array $data Application data
     * @return array Result with success status
     */
    public function submitAccountApplication($data) {
        try {
            // Validate account type exists and get minimum balance
            $this->db->query("
                SELECT minimum_balance, type_name 
                FROM Account_Types 
                WHERE account_type_id = :account_type_id AND is_active = 1
            ");
            $this->db->bind(':account_type_id', $data['account_type_id']);
            $accountType = $this->db->single();
            
            if (!$accountType) {
                return ['success' => false, 'error' => 'Invalid account type selected'];
            }
            
            // Generate application number
            $this->db->query("SELECT COUNT(*) as count FROM Account_Applications");
            $count = $this->db->single()->count;
            $year = date('Y');
            $applicationNumber = 'APP-' . $year . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
            
            // Check for duplicate application number
            $this->db->query("SELECT application_id FROM Account_Applications WHERE application_number = :app_num");
            $this->db->bind(':app_num', $applicationNumber);
            if ($this->db->single()) {
                // If duplicate, add timestamp to make unique
                $applicationNumber = 'APP-' . $year . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT) . '-' . time();
            }
            
            // Insert application
            $this->db->query("
                INSERT INTO Account_Applications (
                    application_number,
                    customer_id,
                    account_type_id,
                    initial_deposit,
                    wants_passbook,
                    wants_atm_card,
                    terms_accepted_at,
                    privacy_accepted_at,
                    application_status,
                    submitted_at
                ) VALUES (
                    :application_number,
                    :customer_id,
                    :account_type_id,
                    :initial_deposit,
                    :wants_passbook,
                    :wants_atm_card,
                    :terms_accepted_at,
                    :privacy_accepted_at,
                    'Pending',
                    NOW()
                )
            ");
            
            $this->db->bind(':application_number', $applicationNumber);
            $this->db->bind(':customer_id', $data['customer_id']);
            $this->db->bind(':account_type_id', $data['account_type_id']);
            $this->db->bind(':initial_deposit', $data['initial_deposit']);
            $this->db->bind(':wants_passbook', $data['wants_passbook']);
            $this->db->bind(':wants_atm_card', $data['wants_atm_card']);
            $this->db->bind(':terms_accepted_at', $data['terms_accepted_at']);
            $this->db->bind(':privacy_accepted_at', $data['privacy_accepted_at']);
            
            if ($this->db->execute()) {
                return [
                    'success' => true,
                    'application_number' => $applicationNumber,
                    'message' => 'Application submitted successfully'
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to submit application'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Get all genders from database
     * @return array List of genders
     */
    public function getGenders() {
        $this->db->query("SELECT gender_id, gender_name FROM Gender ORDER BY gender_id");
        return $this->db->resultSet();
    }

    /**
     * Check if email already exists in the system
     * @param string $email Email address to check
     * @return boolean True if email exists, false otherwise
     */
    public function checkEmailExists($email) {
        $this->db->query("SELECT COUNT(*) as count FROM Emails WHERE email = :email AND is_active = 1");
        $this->db->bind(':email', $email);
        $result = $this->db->single();
        return ($result && $result->count > 0);
    }

    /**
     * Register a new customer with complete profile
     * @param array $data Customer registration data
     * @param array $uploadedFiles Array of uploaded file paths
     * @return array Result with success status and message
     */
    public function registerCustomer($data, $uploadedFiles) {
        try {
            $this->db->beginTransaction();

            // 1. Create Customer record
            $this->db->query("
                INSERT INTO Customers (first_name, middle_name, last_name, password_hash, is_active, created_at)
                VALUES (:first_name, :middle_name, :last_name, :password_hash, 1, NOW())
            ");
            $this->db->bind(':first_name', $data['first_name']);
            $this->db->bind(':middle_name', $data['middle_name']);
            $this->db->bind(':last_name', $data['last_name']);
            $this->db->bind(':password_hash', password_hash($data['password'], PASSWORD_DEFAULT));
            
            if (!$this->db->execute()) {
                throw new Exception("Failed to create customer record");
            }
            
            $customer_id = $this->db->lastInsertId();

            // 2. Create Email record
            $this->db->query("
                INSERT INTO Emails (customer_id, email, is_primary, is_active, created_at)
                VALUES (:customer_id, :email, 1, 1, NOW())
            ");
            $this->db->bind(':customer_id', $customer_id);
            $this->db->bind(':email', $data['email']);
            
            if (!$this->db->execute()) {
                throw new Exception("Failed to create email record");
            }

            // 3. Create Phone record
            $this->db->query("
                INSERT INTO Phones (customer_id, phone_number, phone_type, is_primary, is_active, created_at)
                VALUES (:customer_id, :phone_number, 'mobile', 1, 1, NOW())
            ");
            $this->db->bind(':customer_id', $customer_id);
            $this->db->bind(':phone_number', $data['phone_number']);
            
            if (!$this->db->execute()) {
                throw new Exception("Failed to create phone record");
            }

            // 4. Create Customer_Profile record
            $this->db->query("
                INSERT INTO Customer_Profile (
                    customer_id, gender_id, date_of_birth, marital_status, occupation,
                    employment_status, company_name, income_range, nationality, created_at
                )
                VALUES (
                    :customer_id, :gender_id, :date_of_birth, :marital_status, :occupation,
                    :employment_status, :company_name, :income_range, :nationality, NOW()
                )
            ");
            $this->db->bind(':customer_id', $customer_id);
            $this->db->bind(':gender_id', $data['gender_id']);
            $this->db->bind(':date_of_birth', $data['date_of_birth']);
            $this->db->bind(':marital_status', $data['marital_status']);
            $this->db->bind(':occupation', $data['occupation']);
            $this->db->bind(':employment_status', $data['employment_status']);
            $this->db->bind(':company_name', $data['company_name']);
            $this->db->bind(':income_range', $data['income_range']);
            $this->db->bind(':nationality', $data['nationality']);
            
            if (!$this->db->execute()) {
                throw new Exception("Failed to create customer profile");
            }

            // 5. Create Address record
            $this->db->query("
                INSERT INTO Addresses (
                    customer_id, address_line, barangay_id, city_id, province_id,
                    postal_code, is_primary, is_active, created_at
                )
                VALUES (
                    :customer_id, :address_line, :barangay_id, :city_id, :province_id,
                    :postal_code, 1, 1, NOW()
                )
            ");
            $this->db->bind(':customer_id', $customer_id);
            $this->db->bind(':address_line', $data['address_line']);
            $this->db->bind(':barangay_id', $data['barangay_id']);
            $this->db->bind(':city_id', $data['city_id']);
            $this->db->bind(':province_id', $data['province_id']);
            $this->db->bind(':postal_code', $data['postal_code']);
            
            if (!$this->db->execute()) {
                throw new Exception("Failed to create address record");
            }

            // 6. Create Customer_IDs record
            $this->db->query("
                INSERT INTO Customer_IDs (
                    customer_id, id_type_id, id_number, issue_date, expiration_date, is_verified, created_at
                )
                VALUES (
                    :customer_id, :id_type_id, :id_number, :issue_date, :expiration_date, 0, NOW()
                )
            ");
            $this->db->bind(':customer_id', $customer_id);
            $this->db->bind(':id_type_id', $data['id_type_id']);
            $this->db->bind(':id_number', $data['id_number']);
            $this->db->bind(':issue_date', !empty($data['id_issue_date']) ? $data['id_issue_date'] : null);
            $this->db->bind(':expiration_date', !empty($data['id_expiration_date']) ? $data['id_expiration_date'] : null);
            
            if (!$this->db->execute()) {
                throw new Exception("Failed to create customer ID record");
            }

            // 7. Upload Customer Documents
            $documentTypes = [
                'id_front' => 3,      // ID Front
                'id_back' => 4,       // ID Back
                'profile_picture' => 1, // Profile Picture
                'signature' => 2      // E-Signature
            ];

            foreach ($documentTypes as $fileKey => $docTypeId) {
                if (isset($uploadedFiles[$fileKey])) {
                    $this->db->query("
                        INSERT INTO Customer_Documents (customer_id, doc_type_id, file_path, uploaded_at, is_active)
                        VALUES (:customer_id, :doc_type_id, :file_path, NOW(), 1)
                    ");
                    $this->db->bind(':customer_id', $customer_id);
                    $this->db->bind(':doc_type_id', $docTypeId);
                    $this->db->bind(':file_path', $uploadedFiles[$fileKey]);
                    
                    if (!$this->db->execute()) {
                        throw new Exception("Failed to upload document: $fileKey");
                    }
                }
            }

            // 8. Create Account Application
            $application_number = 'APP-' . date('Ymd') . '-' . str_pad($customer_id, 6, '0', STR_PAD_LEFT);
            
            $this->db->query("
                INSERT INTO Account_Applications (
                    application_number, customer_id, account_type_id, application_status,
                    initial_deposit, wants_passbook, wants_atm_card,
                    terms_accepted_at, privacy_accepted_at, submitted_at, created_at
                )
                VALUES (
                    :application_number, :customer_id, :account_type_id, 'Pending',
                    :initial_deposit, :wants_passbook, :wants_atm_card,
                    NOW(), NOW(), NOW(), NOW()
                )
            ");
            $this->db->bind(':application_number', $application_number);
            $this->db->bind(':customer_id', $customer_id);
            $this->db->bind(':account_type_id', $data['account_type_id']);
            $this->db->bind(':initial_deposit', $data['initial_deposit']);
            $this->db->bind(':wants_passbook', $data['wants_passbook']);
            $this->db->bind(':wants_atm_card', $data['wants_atm_card']);
            
            if (!$this->db->execute()) {
                throw new Exception("Failed to create account application");
            }

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'customer_id' => $customer_id,
                'application_number' => $application_number
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Registration Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
