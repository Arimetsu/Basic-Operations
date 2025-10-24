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
}