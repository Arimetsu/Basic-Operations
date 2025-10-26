<?php

class CustomerController extends Controller {
    private $customerModel;

   public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Redirect to login if not logged in
        if (!isset($_SESSION['customer_id'])) {
            header('Location: ' . URLROOT . '/auth/login');
            exit();
        }

        parent::__construct();
        $this->customerModel = $this->model('Customer');
    }

    public function dashboard() {
        $data = [
            'title' => "Dashboard",
            'first_name' => $_SESSION['customer_first_name'],
            'last_name'  => $_SESSION['customer_last_name'],
            'customer_id' => $_SESSION['customer_id']  
        ];

        $this->view('customer/dashboard', $data);
    }

    // --- ACCOUNT ---

    public function account(){

        $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);

        $data = [
            'title' => "Accounts",
            'first_name' => $_SESSION['customer_first_name'],
            'last_name'  => $_SESSION['customer_last_name'],
            'accounts' => $accounts
        ];
        $this->view('customer/account', $data);
    }

    public function removeAccount()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $account_id = trim($_POST['account_id']);

            $data = [
                'customer_id'      => $_SESSION['customer_id'],
                'account_id'       => $account_id,
                'account_id_error' => '',
                'success_message'  => '',
            ];

            // Validate
            if (empty($account_id)) {
                $data['account_id_error'] = 'Please enter your account ID.';
            } else {
                // Check if account exists and belongs to the logged-in customer
                $account = $this->customerModel->getAccountById($account_id);

                if (!$account) {
                    $data['account_id_error'] = 'Account not found.';
                } elseif ($account->customer_id != $_SESSION['customer_id']) {
                    $data['account_id_error'] = 'You do not have permission to remove this account.';
                }
            }

            // If validation passes
            if (empty($data['account_id_error'])) {
                if ($this->customerModel->deleteAccountById($account_id)) {
                    $_SESSION['flash_success'] = 'Account removed successfully.';
                    header('Location: ' . URLROOT . '/customer/account');
                    exit();
                } else {
                    $data['account_id_error'] = 'Something went wrong while deleting the account.';
                }
            }

            // Get updated account list for view
            $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);

            $data = array_merge($data, [
                'title' => "Accounts",
                'first_name' => $_SESSION['customer_first_name'],
                'last_name'  => $_SESSION['customer_last_name'],
                'accounts' => $accounts
            ]);

            $this->view('customer/account', $data);

        } else {
            // Default data when page is first loaded
            $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);

            $data = [
                'customer_id' => $_SESSION['customer_id'],
                'account_id' => '',
                'account_id_error' => '',
                'success_message' => '',
                'title' => "Accounts",
                'first_name' => $_SESSION['customer_first_name'],
                'last_name'  => $_SESSION['customer_last_name'],
                'accounts' => $accounts
            ];

            $this->view('customer/account', $data);
        }
    }

    public function addAccount(){

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Get the user inputs
            $account_number = trim($_POST['account_number']);
            $account_type   = trim($_POST['account_type']);

            $data = [
                'customer_id'    => $_SESSION['customer_id'],
                'account_number' => $account_number,
                'account_type'   => $account_type,
                'account_number_error' => '',
                'account_type_error'   => '',
                'success_message'      => '',
            ];
            if (empty($account_number)) {
                $data['account_number_error'] = 'Please enter your account number.';
            }

            if (empty($account_type)) {
                $data['account_type_error'] = 'Please select your account type.';
            }

            // If no local errors, call the model
            if (empty($data['account_number_error']) && empty($data['account_type_error'])) {
                $result = $this->customerModel->addAccount($data);

                if ($result['success']) {
                    $_SESSION['flash_success'] = $result['message'];
                    header('Location: ' . URLROOT . '/customer/account');
                    exit;
                } else {
                    $data['account_number_error'] = $result['error'];
                }
            }

            $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);

            $data = array_merge($data, [
                'title' => "Accounts",
                'first_name' => $_SESSION['customer_first_name'],
                'last_name'  => $_SESSION['customer_last_name'],
                'accounts' => $accounts
            ]);

            $this->view('customer/account', $data);

        } else {
            $data = [
                'account_number' => '',
                'account_type'   => '',
                'account_number_error' => '',
                'account_type_error'   => '',
                'success_message'      => '',
            ];

            $this->view('customer/account', $data);
        }
    }


    // --- FUND TRANSFER ---

    public function fund_transfer(){

        if ($_SERVER['REQUEST_METHOD'] === 'POST'){
            
            $from_account = trim($_POST['from_account']);
            $recipient_number = trim($_POST['recipient_number']);
            $recipient_name = trim($_POST['recipient_name']);
            $amount = (float) trim($_POST['amount']);
            $message = trim($_POST['message']);

            $data = [
                'customer_id' => $_SESSION['customer_id'],
                'from_account' => $from_account,
                'recipient_number' => $recipient_number,
                'recipient_name' => $recipient_name,
                'amount' => $amount,
                'message' => $message,
                'from_account_error' => '',
                'recipient_number_error' => '',
                'recipient_name_error' => '',
                'amount_error' => '',
                'message_error' => '',
                'other_error' => '',
            ];

            if (empty($from_account)){
                $data['from_account_error'] = 'Please select your account number.';
            }

            $sender = $this->customerModel->getAccountByNumber($data['from_account']);

            if(!$sender){
                $data['from_account_error'] = 'Please select your own account number.';
            }

            if(empty($recipient_number)){
                $data['recipient_number_error'] = 'Please enter recipient account number.';
            }

            $recipient_validation = $this->customerModel->validateRecipient($data['recipient_number'], $data['recipient_name']);

            if(!$recipient_validation['status']){
                $data = array_merge($data, [
                    'recipient_number_error' => 'Invalid recipient account number or account name',
                    'recipient_name_error' => 'Invalid recipient account number or account name'
                ]);
            }

            $receiver = $this->customerModel->getAccountByNumber($data['recipient_number']);

            if(empty($recipient_name)){
                $data['recipient_name_error'] = 'Please enter recipient name.';
            }

            if(empty($amount)){
                $data['amount_error'] = 'Please enter an amount.';
            }
            $amount_validation = $this->customerModel->validateAmount($data['from_account']);
            $fee = 15.00;
            $total = $data['amount'] + $fee;

            if((float)$amount_validation->balance < $total){
                $data['amount_error'] = 'Insufficient Funds';
            }

            if(strlen($message) >= 100){
                $data['message_error'] = 'Pleaser enter 100 characters only';
            }

            if($data['from_account'] == $data['recipient_number']){
                $data['other_error'] = 'You cannot transfer money to the same account fool.';
            }

            if(empty($data['from_account_error']) && empty($data['recipient_number_error']) && empty($data['recipient_name_error']) && empty($data['amount_error']) && empty($data['message_error']) && empty($data['other_error'])){
                $temp_transaction_ref = 'TXN-PREVIEW-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $remaining_balance = (float)$amount_validation->balance - $total;
                $sender_name = $_SESSION['customer_first_name'] . ' ' . $_SESSION['customer_last_name'] ?? 'Sender Name Unknown';

                $data = array_merge($data, [
                    'temp_transaction_ref' => $temp_transaction_ref,
                    'fee' => $fee,
                    'total_payment' => $total,
                    'remaining_balance' => $remaining_balance,
                    'sender_name' => $sender_name,
                ]);

                $this->view('customer/receipt', $data);
            } else {
                $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);
                $data = array_merge($data, [
                    'title' => 'Fund Transfer',
                    'accounts' => $accounts
                ]);
                $this->view('customer/fund_transfer', $data);
            }
        } else {
             $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);
             $data = [
                'title' => 'Fund Transfer',
                'accounts' => $accounts
            ];
            $this->view('customer/fund_transfer', $data);
        }
    }

    public function receipt(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
         $from_account = trim($_POST['from_account']);
            $recipient_number = trim($_POST['recipient_number']);
            $recipient_name = trim($_POST['recipient_name']);
            $amount = (float) trim($_POST['amount']);
            $message = trim($_POST['message']);

            $data = [
                'customer_id' => $_SESSION['customer_id'],
                'from_account' => $from_account,
                'recipient_number' => $recipient_number,
                'recipient_name' => $recipient_name,
                'amount' => $amount,
                'message' => $message,
                'from_account_error' => '',
                'recipient_number_error' => '',
                'recipient_name_error' => '',
                'amount_error' => '',
                'message_error' => '',
                'other_error' => '',
            ];

            if (empty($from_account)){
                $data['from_account_error'] = 'Please select your account number.';
            }

            $sender = $this->customerModel->getAccountByNumber($data['from_account']);

            if(!$sender){
                $data['from_account_error'] = 'Please select your own account number.';
            }

            if(empty($recipient_number)){
                $data['recipient_number_error'] = 'Please enter recipient account number.';
            }

            $recipient_validation = $this->customerModel->validateRecipient($data['recipient_number'], $data['recipient_name']);

            if(!$recipient_validation['status']){
                $data = array_merge($data, [
                    'recipient_number_error' => 'Invalid recipient account number or account name',
                    'recipient_name_error' => 'Invalid recipient account number or account name'
                ]);
            }

            $receiver = $this->customerModel->getAccountByNumber($data['recipient_number']);

            if(empty($recipient_name)){
                $data['recipient_name_error'] = 'Please enter recipient name.';
            }

            if(empty($amount)){
                $data['amount_error'] = 'Please enter an amount.';
            }
            $amount_validation = $this->customerModel->validateAmount($data['from_account']);
            $fee = 15.00;
            $total = $data['amount'] + $fee;

            if((float)$amount_validation->balance < $total){
                $data['amount_error'] = 'Insufficient Funds';
            }

            if(strlen($message) >= 100){
                $data['message_error'] = 'Pleaser enter 100 characters only';
            }

            if($data['from_account'] == $data['recipient_number']){
                $data['other_error'] = 'You cannot transfer money to the same account fool.';
            }

            if(empty($data['from_account_error']) && empty($data['recipient_number_error']) && empty($data['recipient_name_error']) && empty($data['amount_error']) && empty($data['message_error']) && empty($data['other_error'])){
                $transaction_ref = 'TXN-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));

                $result = $this->customerModel->recordTransaction($transaction_ref, $sender->account_id, $receiver->account_id, $data['amount'], $fee, $data['message']);

                header('Location: ' . URLROOT . '/customer/dashboard');
                exit();
            } else {
                $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);
                $data = array_merge($data, [
                    'title' => 'Fund Transfer',
                    'accounts' => $accounts
                ]);
                $this->view('customer/fund_transfer', $data);
            }
        } else {
             $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);
             $data = [
                'title' => 'Fund Transfer',
                'accounts' => $accounts
            ];
            $this->view('customer/fund_transfer', $data);
        }
    }
}