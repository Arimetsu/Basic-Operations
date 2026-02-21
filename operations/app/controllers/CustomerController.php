<?php

class CustomerController extends Controller {
    private $customerModel;

   public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Sync session from evergreen-marketing if logged in there
        // evergreen-marketing uses 'user_id' (which is customer_id) and 'first_name', 'last_name'
        // Basic-operation uses 'customer_id' and 'customer_first_name', 'customer_last_name'
        if (!isset($_SESSION['customer_id']) && isset($_SESSION['user_id'])) {
            // User is logged in via evergreen-marketing, sync session for Basic-operation
            $_SESSION['customer_id'] = $_SESSION['user_id'];
            
            // Sync name fields from evergreen-marketing session
            if (!isset($_SESSION['customer_first_name']) && isset($_SESSION['first_name'])) {
                $_SESSION['customer_first_name'] = $_SESSION['first_name'];
            }
            if (!isset($_SESSION['customer_last_name']) && isset($_SESSION['last_name'])) {
                $_SESSION['customer_last_name'] = $_SESSION['last_name'];
            }
        } elseif (isset($_SESSION['customer_id']) && !isset($_SESSION['customer_first_name'])) {
            // If customer_id exists but names are missing, try to get from evergreen-marketing session
            if (isset($_SESSION['first_name'])) {
                $_SESSION['customer_first_name'] = $_SESSION['first_name'];
            }
            if (isset($_SESSION['last_name'])) {
                $_SESSION['customer_last_name'] = $_SESSION['last_name'];
            }
        }

        // Redirect to login if not logged in
        if (!isset($_SESSION['customer_id'])) {
            header('Location: ' . URLROOT . '/auth/login');
            exit();
        }

        parent::__construct();
        $this->customerModel = $this->model('Customer');
    }

    // --- ACCOUNT ---

    public function account(){

        $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);
        $accountTypes = $this->customerModel->getActiveAccountTypes();

        $data = [
            'title' => "Accounts",
            'first_name' => $_SESSION['customer_first_name'],
            'last_name'  => $_SESSION['customer_last_name'],
            'accounts' => $accounts,
            'account_types' => $accountTypes
        ];
        $this->view('customer/account', $data);
    }

    // --- CHANGE PASSWORD ---

    public function change_password(){

        // Initial data load for the view
        $data = [
            'title' => "Change Password",
            'first_name' => $_SESSION['customer_first_name'],
            'last_name' => $_SESSION['customer_last_name'],
            'old_password' => '',
            'new_password' => '',
            'confirm_password' => '',
            'old_password_err' => '',
            'new_password_err' => '',
            'confirm_password_err' => '',
            'success_message' => '',
            'error_message' => '' // Ensure error_message is initialized
        ];

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'title' => "Change Password",
                'user_id' => $_SESSION['customer_id'],
                'old_password' => trim($_POST['old_password']),
                'new_password' => trim($_POST['new_password']),
                'confirm_password' => trim($_POST['confirm_password']),
                'old_password_err' => '',
                'new_password_err' => '',
                'confirm_password_err' => '',
                'success_message' => '',
                'error_message' => ''
            ];

            if(empty($data['old_password'])){
                $data['old_password_err'] = 'Please enter your current password.';
            } else {
                $current_hash = $this->customerModel->getCurrentPasswordHash($data['user_id']);
                
                if(!$current_hash || !password_verify($data['old_password'], $current_hash)){
                    $data['old_password_err'] = 'Incorrect current password.';
                }
            }

            if(empty($data['new_password'])){
                $data['new_password_err'] = 'Please enter a new password.';
            } elseif(strlen($data['new_password']) < 10){
                $data['new_password_err'] = 'Password must be at least 10 characters long.'; 
            }

            if(empty($data['confirm_password'])){
                $data['confirm_password_err'] = 'Please confirm the new password.';
            } elseif($data['new_password'] != $data['confirm_password']){
                $data['confirm_password_err'] = 'New passwords do not match.';
            }
            
            if (empty($data['old_password_err']) && $data['old_password'] === trim($_POST['new_password'])) {
                $data['new_password_err'] = 'New password cannot be the same as the current password.';
            }

            if(empty($data['old_password_err']) && empty($data['new_password_err']) && empty($data['confirm_password_err'])){
                $data['new_password'] = password_hash($data['new_password'], PASSWORD_DEFAULT);

                if($this->customerModel->updatePassword($data['user_id'], $data['new_password'])){
                    $data['old_password'] = $data['new_password'] = $data['confirm_password'] = '';
                    $data['success_message'] = 'Your password has been successfully updated!';
                } else {
                    $data['error_message'] = 'Something went wrong. Please try again.';
                }
            }
        }
        
        // The existing view call assumes $this is a controller object
        $this->view('customer/change_password', $data);
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
            $accountTypes = $this->customerModel->getActiveAccountTypes();

            $data = array_merge($data, [
                'title' => "Accounts",
                'first_name' => $_SESSION['customer_first_name'],
                'last_name'  => $_SESSION['customer_last_name'],
                'accounts' => $accounts,
                'account_types' => $accountTypes,
                'show_add_account_modal' => true  // Flag to auto-show the modal
            ]);

            $this->view('customer/account', $data);

        } else {
            $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);
            $accountTypes = $this->customerModel->getActiveAccountTypes();

            $data = [
                'title' => "Accounts",
                'first_name' => $_SESSION['customer_first_name'],
                'last_name'  => $_SESSION['customer_last_name'],
                'account_number' => '',
                'account_type'   => '',
                'account_number_error' => '',
                'account_type_error'   => '',
                'success_message'      => '',
                'accounts' => $accounts,
                'account_types' => $accountTypes,
                'show_add_account_modal' => false
            ];

            $this->view('customer/account', $data);
        }
    }

    public function toggleAccountVisibility() {
        // Only accept POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        $account_id = $input['account_id'] ?? null;
        $is_hidden = $input['is_hidden'] ?? null;

        // Validate input
        if ($account_id === null || $is_hidden === null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }

        // Verify account belongs to logged-in customer
        $account = $this->customerModel->getAccountById($account_id);
        if (!$account || $account->customer_id != $_SESSION['customer_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        // Convert is_hidden to integer (1 for hidden, 0 for visible)
        $is_hidden = $is_hidden ? 1 : 0;

        // Update in database
        if ($this->customerModel->toggleAccountVisibility($account_id, $is_hidden)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Account visibility updated',
                'is_hidden' => $is_hidden
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update account visibility']);
        }
        exit;
    }

    // --- PROFILE ---
    public function profile(){
        $customer_id = $_SESSION['customer_id'];
        
        // Handle POST request for profile update
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Handle adding new contact (email or phone)
            if (isset($_POST['contact_type']) && !empty($_POST['contact_type'])) {
                $contact_type = $_POST['contact_type'];
                $set_primary = isset($_POST['set_primary']) ? 1 : 0;
                
                if ($contact_type === 'email' && !empty($_POST['new_email'])) {
                    $new_email = trim($_POST['new_email']);
                    
                    // Validate email
                    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                        $_SESSION['profile_error'] = 'Invalid email address format.';
                    } else {
                        // Add email
                        $result = $this->customerModel->addCustomerEmail($customer_id, $new_email, $set_primary);
                        if ($result) {
                            $_SESSION['profile_success'] = 'Email added successfully!';
                        } else {
                            $_SESSION['profile_error'] = 'Failed to add email. It may already exist.';
                        }
                    }
                } elseif ($contact_type === 'phone' && !empty($_POST['new_phone'])) {
                    $new_phone = trim($_POST['new_phone']);
                    
                    // Validate phone (basic validation)
                    if (strlen($new_phone) < 10) {
                        $_SESSION['profile_error'] = 'Invalid phone number format.';
                    } else {
                        // Add phone
                        $result = $this->customerModel->addCustomerPhone($customer_id, $new_phone, $set_primary);
                        if ($result) {
                            $_SESSION['profile_success'] = 'Phone number added successfully!';
                        } else {
                            $_SESSION['profile_error'] = 'Failed to add phone number.';
                        }
                    }
                } else {
                    $_SESSION['profile_error'] = 'Please provide contact information.';
                }
                
                // Redirect to refresh the page
                header('Location: ' . URLROOT . '/customer/profile');
                exit();
            }
            
            $update_data = [];
            
            // Only allow updating specific fields (not name or birthday)
            if (isset($_POST['email_address'])) {
                $update_data['email_address'] = trim($_POST['email_address']);
            }
            if (isset($_POST['mobile_number'])) {
                $update_data['mobile_number'] = trim($_POST['mobile_number']);
            }
            if (isset($_POST['home_address'])) {
                $update_data['home_address'] = trim($_POST['home_address']);
            }
            if (isset($_POST['address_line'])) {
                $update_data['address_line'] = trim($_POST['address_line']);
            }
            if (isset($_POST['city'])) {
                $update_data['city'] = trim($_POST['city']);
            }
            if (isset($_POST['province_id'])) {
                $update_data['province_id'] = trim($_POST['province_id']);
            }
            if (isset($_POST['city_id'])) {
                $update_data['city_id'] = trim($_POST['city_id']);
            }
            if (isset($_POST['barangay_id'])) {
                $update_data['barangay_id'] = trim($_POST['barangay_id']);
            }
            if (isset($_POST['gender'])) {
                $update_data['gender'] = trim($_POST['gender']);
            }
            if (isset($_POST['civil_status'])) {
                $update_data['civil_status'] = trim($_POST['civil_status']);
            }
            if (isset($_POST['citizenship'])) {
                $update_data['citizenship'] = trim($_POST['citizenship']);
            }
            if (isset($_POST['occupation'])) {
                $update_data['occupation'] = trim($_POST['occupation']);
            }
            if (isset($_POST['name_of_employer'])) {
                $update_data['name_of_employer'] = trim($_POST['name_of_employer']);
            }
            if (isset($_POST['income_range'])) {
                $update_data['income_range'] = trim($_POST['income_range']);
            }
            
            // Validate email if provided
            if (!empty($update_data['email_address']) && !filter_var($update_data['email_address'], FILTER_VALIDATE_EMAIL)) {
                $_SESSION['profile_error'] = 'Invalid email address format.';
            } else {
                // Check if there's any data to update
                if (!empty($update_data)) {
                    // Update profile
                    $updated = $this->customerModel->updateCustomerProfile($customer_id, $update_data);
                    
                    if ($updated) {
                        $_SESSION['profile_success'] = 'Profile updated successfully!';
                    } else {
                        $_SESSION['profile_error'] = 'Failed to update profile. Please check your input and try again.';
                        error_log("Profile update failed for customer_id: $customer_id");
                    }
                } else {
                    $_SESSION['profile_error'] = 'No data provided to update.';
                }
            }
            
            // Redirect to refresh the page
            header('Location: ' . URLROOT . '/customer/profile');
            exit();
        }

        $profile_data = $this->customerModel->getCustomerProfileData($customer_id);
        $provinces = $this->customerModel->getProvinces();
        $cities = $this->customerModel->getAllCities();
        
        // Get all emails and phones for the customer
        $emails = $this->customerModel->getCustomerEmails($customer_id);
        $phones = $this->customerModel->getCustomerPhones($customer_id);
        
        // Get barangays - load ALL barangays for dynamic filtering on the frontend
        // Also get barangays for current city if available
        $barangays = $this->customerModel->getAllBarangays();
        $current_barangays = [];
        if (!empty($profile_data->city_id)) {
            $current_barangays = $this->customerModel->getBarangaysByCity($profile_data->city_id);
        }

        if (!$profile_data) {
             $profile_data = (object)[
                 'first_name' => 'N/A', 'last_name' => 'N/A', 'username' => 'N/A', 
                 'mobile_number' => 'N/A', 'email_address' => 'N/A', 'home_address' => 'N/A',
                 'address_line' => '', 'city' => '', 'province_id' => null, 'province_name' => '',
                 'city_id' => null, 'barangay_id' => null, 'barangay_name' => '',
                 'date_of_birth' => 'N/A', 'gender' => 'N/A', 'civil_status' => 'N/A', 
                 'citizenship' => 'N/A', 'occupation' => 'N/A', 'name_of_employer' => 'N/A'
             ];
        }

        $data = [
            'title' => "My Profile",
            'profile' => $profile_data,
            'provinces' => $provinces,
            'cities' => $cities,
            'barangays' => $barangays,
            'emails' => $emails,
            'phones' => $phones,
            'full_name' => trim($profile_data->first_name . ' ' . $profile_data->middle_name . ' ' . $profile_data->last_name),
            'source_of_funds' => $profile_data->occupation,
            'employment_status' => $profile_data->occupation ? 'Employed' : 'Unemployed',
            'place_of_birth' => 'Quezon City',
            'employer_address' => '123 Bldg, Metro Manila',
            'success_message' => $_SESSION['profile_success'] ?? '',
            'error_message' => $_SESSION['profile_error'] ?? '',
        ];
        
        // Clear session messages
        unset($_SESSION['profile_success']);
        unset($_SESSION['profile_error']);

        $this->view('customer/profile', $data);
    }

    /**
     * Update or delete contact information (email/phone)
     */
    public function updateContact() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/customer/profile');
            exit();
        }

        $customer_id = $_SESSION['customer_id'];
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        $contact_id = $_POST['contact_id'] ?? null;
        $contact_type = $_POST['contact_type'] ?? null;
        $delete_contact = isset($_POST['delete_contact']) && $_POST['delete_contact'] == '1';

        if (!$contact_id || !$contact_type) {
            $_SESSION['profile_error'] = 'Invalid request.';
            header('Location: ' . URLROOT . '/customer/profile');
            exit();
        }

        // Handle delete
        if ($delete_contact) {
            if ($contact_type === 'email') {
                $result = $this->customerModel->deleteCustomerEmail($contact_id, $customer_id);
                $message = $result ? 'Email deleted successfully!' : 'Failed to delete email.';
            } else {
                $result = $this->customerModel->deleteCustomerPhone($contact_id, $customer_id);
                $message = $result ? 'Phone deleted successfully!' : 'Failed to delete phone.';
            }

            $_SESSION[$result ? 'profile_success' : 'profile_error'] = $message;
            header('Location: ' . URLROOT . '/customer/profile');
            exit();
        }

        // Handle update
        $contact_value = trim($_POST['contact_value'] ?? '');
        $set_primary = isset($_POST['set_primary']) ? 1 : 0;

        if (empty($contact_value)) {
            $_SESSION['profile_error'] = 'Contact information cannot be empty.';
            header('Location: ' . URLROOT . '/customer/profile');
            exit();
        }

        if ($contact_type === 'email') {
            // Validate email
            if (!filter_var($contact_value, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['profile_error'] = 'Invalid email address format.';
                header('Location: ' . URLROOT . '/customer/profile');
                exit();
            }

            $result = $this->customerModel->updateCustomerEmail($contact_id, $customer_id, $contact_value, $set_primary);
            $message = $result ? 'Email updated successfully!' : 'Failed to update email.';
        } else {
            // Validate phone
            if (strlen($contact_value) < 10) {
                $_SESSION['profile_error'] = 'Invalid phone number format.';
                header('Location: ' . URLROOT . '/customer/profile');
                exit();
            }

            $result = $this->customerModel->updateCustomerPhone($contact_id, $customer_id, $contact_value, $set_primary);
            $message = $result ? 'Phone updated successfully!' : 'Failed to update phone.';
        }

        $_SESSION[$result ? 'profile_success' : 'profile_error'] = $message;
        header('Location: ' . URLROOT . '/customer/profile');
        exit();
    }


    // --- FUND TRANSFER ---

    public function fund_transfer(){

        if ($_SERVER['REQUEST_METHOD'] === 'POST'){
            
            $transfer_type = trim($_POST['transfer_type'] ?? 'another_account');
            $from_account = trim($_POST['from_account']);
            $amount = (float) trim($_POST['amount']);
            
            // Initialize based on transfer type
            if ($transfer_type === 'own_account') {
                $to_account = trim($_POST['to_account'] ?? '');
                $recipient_number = $to_account;
                $recipient_name = '';
                $message = '';
                $bank_name = '';
                $bank_account_number = '';
                $bank_account_name = '';
                $bank_code = '';
            } elseif ($transfer_type === 'other_bank') {
                $to_account = '';
                $recipient_number = '';
                $recipient_name = '';
                $message = '';
                $bank_name = trim($_POST['bank_name'] ?? '');
                $bank_account_number = trim($_POST['bank_account_number'] ?? '');
                $bank_account_name = trim($_POST['bank_account_name'] ?? '');
                $bank_code = trim($_POST['bank_code'] ?? '');
            } else {
                $to_account = '';
                $recipient_number = trim($_POST['recipient_number'] ?? '');
                $recipient_name = trim($_POST['recipient_name'] ?? '');
                $message = trim($_POST['message'] ?? '');
                $bank_name = '';
                $bank_account_number = '';
                $bank_account_name = '';
                $bank_code = '';
            }

            $data = [
                'customer_id' => $_SESSION['customer_id'],
                'transfer_type' => $transfer_type,
                'from_account' => $from_account,
                'to_account' => $to_account,
                'recipient_number' => $recipient_number,
                'recipient_name' => $recipient_name,
                'amount' => $amount,
                'message' => $message,
                'bank_name' => $bank_name,
                'bank_account_number' => $bank_account_number,
                'bank_account_name' => $bank_account_name,
                'bank_code' => $bank_code,
                'from_account_error' => '',
                'to_account_error' => '',
                'recipient_number_error' => '',
                'recipient_name_error' => '',
                'amount_error' => '',
                'message_error' => '',
                'other_error' => '',
                'bank_name_error' => '',
                'bank_account_number_error' => '',
                'bank_account_name_error' => '',
                'bank_code_error' => '',
            ];

            if (empty($from_account)){
                $data['from_account_error'] = 'Please select your account number.';
            }

            $sender = $this->customerModel->getAccountByNumber($data['from_account']);

            if(!$sender){
                $data['from_account_error'] = 'Please select your own account number.';
            }

            // Additional validation to ensure only Savings/Checking can send for other_bank
            if ($transfer_type === 'other_bank' && $sender) {
                if (!in_array((int)$sender->account_type_id, [1, 2])) {
                    $data['from_account_error'] = 'Only Savings and Checking accounts can transfer to other banks.';
                }
            }

            // Validation based on transfer type
           if ($transfer_type === 'own_account') {
                // Own account transfer validation
                if(empty($to_account)){
                    $data['to_account_error'] = 'Please select destination account.';
                }
                
                if($data['from_account'] == $to_account){
                    $data['other_error'] = 'You cannot transfer money to the same account.';
                }
                
                // Verify both accounts belong to the customer
                $receiver = $this->customerModel->getAccountByNumber($to_account);
                if ($receiver) {
                    // Check if to_account belongs to the same customer
                    $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);
                    $owns_receiver = false;
                    foreach($accounts as $acc) {
                        if($acc->account_number == $to_account) {
                            $owns_receiver = true;
                            break;
                        }
                    }
                    if (!$owns_receiver) {
                        $data['to_account_error'] = 'Invalid destination account.';
                    }
                } else {
                    $data['to_account_error'] = 'Destination account not found.';
                }
            } elseif ($transfer_type === 'other_bank') {
                // Other bank transfer validation
                if(empty($bank_name)){
                    $data['bank_name_error'] = 'Please enter the bank name.';
                }
                
                if(empty($bank_account_number)){
                    $data['bank_account_number_error'] = 'Please enter the account number.';
                }
                
                if(empty($bank_account_name)){
                    $data['bank_account_name_error'] = 'Please enter the account holder name.';
                }
                
                // Bank code is optional, no error if empty
            } else {
                // Another Evergreen account transfer validation
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
                
                if($data['from_account'] == $data['recipient_number']){
                    $data['other_error'] = 'You cannot transfer money to the same account.';
                }
            }

            if(empty($amount)){
                $data['amount_error'] = 'Please enter an amount.';
            }
            $amount_validation = $this->customerModel->validateAmount($data['from_account']);
            
            // Fee based on transfer type
            if ($transfer_type === 'own_account') {
                $fee = 0.00;
            } elseif ($transfer_type === 'other_bank') {
                $fee = 25.00;
            } else {
                $fee = 15.00;
            }
            $total = $data['amount'] + $fee;

            if((float)$amount_validation->balance < $total){
                $data['amount_error'] = 'Insufficient Funds';
            }

            // Check maintaining balance rule and require confirmation flag if this transfer will leave balance below minimum
            $senderAccount = $this->customerModel->getAccountByNumber($data['from_account']);
            $maintaining_required = isset($senderAccount->maintaining_balance_required) ? (float)$senderAccount->maintaining_balance_required : 500.00;
            $remaining_after = (float)$amount_validation->balance - $total;
            if ($remaining_after < $maintaining_required && $remaining_after >= 0) {
                // if confirm flag not present, set a flag so view can prompt/require confirmation
                if (empty($_POST['confirm_low_balance'])) {
                    $data['other_error'] = 'This transfer will bring your balance below the required maintaining balance of PHP ' . number_format($maintaining_required,2) . '. Please confirm to proceed.';
                }
            }

            // Message validation only for another_account transfers
            if ($transfer_type === 'another_account' && strlen($message) >= 100){
                $data['message_error'] = 'Please enter 100 characters only';
            }

            if(empty($data['from_account_error']) && empty($data['to_account_error']) && empty($data['recipient_number_error']) && empty($data['recipient_name_error']) && empty($data['amount_error']) && empty($data['message_error']) && empty($data['other_error']) && empty($data['bank_name_error']) && empty($data['bank_account_number_error']) && empty($data['bank_account_name_error'])){
                $temp_transaction_ref = 'TXN-PREVIEW-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $remaining_balance = (float)$amount_validation->balance - $total;
                $sender_name = $_SESSION['customer_first_name'] . ' ' . $_SESSION['customer_last_name'] ?? 'Sender Name Unknown';

                $data = array_merge($data, [
                    'temp_transaction_ref' => $temp_transaction_ref,
                    'fee' => $fee,
                    'total_payment' => $total,
                    'remaining_balance' => $remaining_balance,
                    'sender_name' => $sender_name,
                    'transfer_type' => $transfer_type,
                ]);

                // If remaining is below maintaining and confirmation not provided, re-render transfer page with warning
                if ($remaining_balance < $maintaining_required && empty($_POST['confirm_low_balance'])) {
                    $accounts = $this->customerModel->getAccountsByCustomerId($_SESSION['customer_id']);
                    $data = array_merge($data, [
                        'title' => 'Fund Transfer',
                        'accounts' => $accounts,
                        'low_balance_confirm_required' => true,
                        'maintaining_required' => $maintaining_required
                    ]);
                    $this->view('customer/fund_transfer', $data);
                } else {
                    $this->view('customer/receipt', $data);
                }
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
            $transfer_type = trim($_POST['transfer_type'] ?? 'another_account');
            $from_account = trim($_POST['from_account']);
            $amount = (float) trim($_POST['amount']);
            
            // Initialize based on transfer type
            if ($transfer_type === 'own_account') {
                $to_account = trim($_POST['to_account'] ?? '');
                $recipient_number = $to_account;
                $recipient_name = '';
                $message = '';
                $bank_name = '';
                $bank_account_number = '';
                $bank_account_name = '';
                $bank_code = '';
            } elseif ($transfer_type === 'other_bank') {
                $to_account = '';
                $recipient_number = '';
                $recipient_name = '';
                $message = trim($_POST['message'] ?? '');
                $bank_name = trim($_POST['bank_name'] ?? '');
                $bank_account_number = trim($_POST['bank_account_number'] ?? '');
                $bank_account_name = trim($_POST['bank_account_name'] ?? '');
                $bank_code = trim($_POST['bank_code'] ?? '');
            } else {
                $to_account = '';
                $recipient_number = trim($_POST['recipient_number'] ?? '');
                $recipient_name = trim($_POST['recipient_name'] ?? '');
                $message = trim($_POST['message'] ?? '');
                $bank_name = '';
                $bank_account_number = '';
                $bank_account_name = '';
                $bank_code = '';
            }

            $data = [
                'customer_id' => $_SESSION['customer_id'],
                'transfer_type' => $transfer_type,
                'from_account' => $from_account,
                'to_account' => $to_account,
                'recipient_number' => $recipient_number,
                'recipient_name' => $recipient_name,
                'amount' => $amount,
                'message' => $message,
                'bank_name' => $bank_name ?? '',
                'bank_account_number' => $bank_account_number ?? '',
                'bank_account_name' => $bank_account_name ?? '',
                'bank_code' => $bank_code ?? '',
                'from_account_error' => '',
                'to_account_error' => '',
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

            // Validation based on transfer type
            if ($transfer_type === 'own_account') {
                if(empty($to_account)){
                    $data['to_account_error'] = 'Please select destination account.';
                }
                
                if($data['from_account'] == $to_account){
                    $data['other_error'] = 'You cannot transfer money to the same account.';
                }
                
                $receiver = $this->customerModel->getAccountByNumber($to_account);
                if (!$receiver) {
                    $data['to_account_error'] = 'Destination account not found.';
                }
            } elseif ($transfer_type === 'other_bank') {
                if(empty($bank_name)){
                    $data['other_error'] = 'Please enter bank name.';
                }
                if(empty($bank_account_number)){
                    $data['other_error'] = 'Please enter bank account number.';
                }
                if(empty($bank_account_name)){
                    $data['other_error'] = 'Please enter account holder name.';
                }
                // For other_bank, receiver is null (dummy transfer)
                $receiver = null;
            } else {
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
                
                if($data['from_account'] == $data['recipient_number']){
                    $data['other_error'] = 'You cannot transfer money to the same account.';
                }
            }

            if(empty($amount)){
                $data['amount_error'] = 'Please enter an amount.';
            }
            $amount_validation = $this->customerModel->validateAmount($data['from_account']);
            
            // Fee based on transfer type
            if ($transfer_type === 'own_account') {
                $fee = 0.00;
            } elseif ($transfer_type === 'other_bank') {
                $fee = 25.00;
            } else {
                $fee = 15.00;
            }
            $total = $data['amount'] + $fee;

            if((float)$amount_validation->balance < $total){
                $data['amount_error'] = 'Insufficient Funds';
            }

            // Message validation
            if (in_array($transfer_type, ['another_account', 'other_bank']) && strlen($message) >= 100){
                $data['message_error'] = 'Please enter 100 characters only';
            }
            
            // Build transaction message
            if ($transfer_type === 'own_account') {
                $message = 'Transfer to own account ' . $to_account;
            } elseif ($transfer_type === 'other_bank') {
                $message = 'Transfer to ' . $bank_name . ' - ' . $bank_account_name . ' (' . $bank_account_number . ')';
                if (!empty($data['message'])) {
                    $message .= ' - ' . $data['message'];
                }
            } else {
                $message = 'Sent to ' . $data['recipient_name'] . ' (' . $data['recipient_number'] . ')';
                if (!empty($data['message'])) {
                    $message .= ' - ' . $data['message'];
                }
            }

            if(empty($data['from_account_error']) && empty($data['to_account_error']) && empty($data['recipient_number_error']) && empty($data['recipient_name_error']) && empty($data['amount_error']) && empty($data['message_error']) && empty($data['other_error'])){
                $transaction_ref = 'TXN-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));

                // For other_bank, receiver is null (just deduct from sender)
                $receiver_id = ($transfer_type === 'other_bank') ? null : $receiver->account_id;
                if ($transfer_type === 'other_bank') {
                    // Dummy transfer - only record debit from sender
                    $this->customerModel->recordDummyTransfer($transaction_ref, $sender->account_id, $data['amount'], $fee, $message);
                } else {
                    $result = $this->customerModel->recordTransaction($transaction_ref, $sender->account_id, $receiver->account_id, $data['amount'], $fee, $message);
                }

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

    public function transaction_history() {
        if (!isset($_SESSION['customer_id'])) {
            header('Location: ' . URLROOT . '/customer/login');
            exit();
        }

        $customer_id = $_SESSION['customer_id'];
        $limit = 20;

        $current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $current_page = max(1, $current_page);
        $offset = ($current_page - 1) * $limit;

        $filters = [
            'account_id' => isset($_GET['account_id']) ? filter_var($_GET['account_id'], FILTER_SANITIZE_STRING) : 'all',
            'type_name' => isset($_GET['type_name']) ? filter_var($_GET['type_name'], FILTER_SANITIZE_STRING) : 'All',
            'start_date' => isset($_GET['start_date']) ? filter_var($_GET['start_date'], FILTER_SANITIZE_STRING) : '',
            'end_date' => isset($_GET['end_date']) ? filter_var($_GET['end_date'], FILTER_SANITIZE_STRING) : '',
        ];

        $accounts = $this->customerModel->getLinkedAccountsForFilter($customer_id);
        $rawTransactionTypes = $this->customerModel->getTransactionTypes();
        $transactionTypes = array_merge(['All'], array_column($rawTransactionTypes, 'type_name'));

        $transactionData = $this->customerModel->getAllTransactionsByCustomerId(
            $customer_id, 
            $filters,
            $limit, 
            $offset
        );

        $total_transactions = $transactionData['total'];
        $total_pages = ceil($total_transactions / $limit);

        $data = [
            'title' => 'Transaction History',
            'accounts' => $accounts,
            'transactions' => $transactionData['bank_transactions'],
            'filters' => $filters,
            'transaction_types' => $transactionTypes,
            'pagination' => [
                'current_page' => $current_page,
                'total_pages' => $total_pages,
                'total_transactions' => $total_transactions,
                'limit' => $limit,
                'url_query' => http_build_query(array_filter($_GET, fn($key) => $key !== 'page', ARRAY_FILTER_USE_KEY))
            ]
        ];

        $this->view('customer/transaction_history', $data);
    }

    // -- FOR EXPORT --
    public function export_transactions() {
        if (!isset($_SESSION['customer_id'])) {
            header('Location: ' . URLROOT . '/customer/login');
            exit();
        }
        $customer_id = $_SESSION['customer_id'];

        // 1. Get the filter data (account_id, type_name, start_date, end_date)
        $filters = [
            'account_id' => $_GET['account_id'] ?? 'all',
            'type_name'  => $_GET['type_name'] ?? 'All',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date'   => $_GET['end_date'] ?? '',
        ];
        $exportType = strtolower($_GET['type'] ?? 'csv');

        // 2. Call a model method to fetch ALL transactions matching the filters
        // Pass customer_id and filters
        $transactions = $this->customerModel->getAllFilteredTransactions($customer_id, $filters); 

        // 3. Generate and output the file based on $exportType
        if ($exportType === 'csv') {
            $this->generateCSV($transactions);
        } elseif ($exportType === 'pdf') {
            // You would need a PDF library integrated for this (TCPDF seems to be set up)
            $this->generatePDF($transactions);
        } else {
            // Handle invalid type
            header('Location: ' . URLROOT . '/customer/transaction_history');
            exit;
        }
    }
    
    protected function generateCSV($transactions) {
        // Clean any output buffers
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="transactions_' . date('Ymd_His') . '.csv"');
        header('Pragma: no-cache');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        // Open a temporary stream for output
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Define CSV Column Headers (adjust these to match your data structure)
        $headers = ['Date', 'Time', 'Description', 'Reference', 'Account Number', 'Type', 'Amount (PHP)'];
        fputcsv($output, $headers);

        // Write transaction data
        foreach ($transactions as $t) {
            $dateTime = strtotime($t->created_at);
            $amountSign = $t->signed_amount < 0 ? '-' : '+';

            $row = [
                date('Y-m-d', $dateTime),
                date('h:i:s A', $dateTime),
                $t->description,
                $t->transaction_ref,
                $t->account_number,
                $t->transaction_type,
                $amountSign . number_format(abs($t->signed_amount), 2, '.', ''), // Use plain format for export
            ];
            fputcsv($output, $row);
        }

        // Close the stream and terminate script
        fclose($output);
        exit;
    }

    protected function generatePDF($transactions) {
        // Clean any output buffers
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        require_once ROOT_PATH . '/vendor/autoload.php';

        // --- Compute Date Range ---
        if (!empty($transactions)) {
            $dates = array_map(fn($t) => strtotime($t->created_at), $transactions);
            $startDate = min($dates);
            $endDate = max($dates);
            $dateRange = date('j M Y', $startDate) . ' - ' . date('j M Y', $endDate);
        } else {
            $dateRange = "No transactions";
        }

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Evergreen Bank');
        $pdf->SetTitle('Statement of Account');
        $pdf->SetSubject('Customer Statement');

        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();

        // Check if logo exists, use absolute path
        $logo = ROOT_PATH . '/public/img/logo.jpg';
        $logoExists = file_exists($logo);
        $logo = ROOT_PATH . '/public/img/logo.jpg';
        $logoExists = file_exists($logo);
        $customer_name = $_SESSION['customer_first_name'] . ' ' . $_SESSION['customer_last_name'];

        // Build logo HTML conditionally
        $logoHTML = $logoExists ? '<img src="' . $logo . '" height="40" />' : '';
        
        $headerHTML = '
            <table width="100%">
                <tr>
                    <!-- Logo -->
                    <td width="50%">
                        ' . $logoHTML . '
                        <span style="font-size:16px; font-weight:bold;">EVERGREEN</span>
                    </td>

                    <!-- Title + Statement Date -->
                    <td width="50%" align="right" style="text-align:right;">
                        <span style="font-size:16px; font-weight:bold;">Statement of Account</span><br>
                        <span style="font-size:10px;">Statement date: ' . date('j F Y') . '</span>
                    </td>
                </tr>
            </table>
            <br><hr><br>
        ';

        $pdf->writeHTML($headerHTML, true, false, true, false, '');

        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(40, 6, 'Customer Name:', 0, 0, 'L');  
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, htmlspecialchars($customer_name), 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 6, "Transactions (" . $dateRange . ")", 0, 1, 'L');
        $pdf->Ln(3);
        $html = '<table cellspacing="0" cellpadding="5" border="1" style="border-collapse: collapse;">';

        $html .= '
            <tr style="background-color:#f0f0f0; font-weight:bold;">
                <td width="25%">Date & Time</td>
                <td width="35%">Description</td>
                <td width="20%">Account</td>
                <td width="20%" align="right">Amount(PHP)</td>
            </tr>
        ';

        if (empty($transactions)) {
            $html .= '<tr><td colspan="5" align="center">No transactions found.</td></tr>';
        } else {
            foreach ($transactions as $t) {
                $isDebit = $t->signed_amount < 0;
                $formattedAmount = number_format(abs($t->signed_amount), 2);
                $color = $isDebit ? '#D9534F' : '#5CB85C';

                $html .= '
                    <tr>
                        <td>' . date('d M Y, h:i A', strtotime($t->created_at)) . '</td>
                        <td>' . htmlspecialchars($t->description) . '<br>
                            <span style="font-size:8pt;">Ref: ' . htmlspecialchars($t->transaction_ref) . '</span>
                        </td>
                        <td>' . htmlspecialchars($t->account_number) . '<br>
                            <span style="font-size:8pt;">' . htmlspecialchars($t->transaction_type) . '</span>
                        </td>
                        <td align="right" style="font-weight:bold; color:' . $color . ';">' 
                            . ($isDebit ? '-' : '+') . $formattedAmount . '</td>
                    </tr>
                ';
            }
        }

        $html .= '</table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output('statement_' . date('Ymd') . '.pdf', 'D');
        exit;
    }


    public function referral(){
        if (!isset($_SESSION['customer_id'])) {
            header('Location: ' . URLROOT . '/customer/login');
            exit();
        }

        $customerId = $_SESSION['customer_id'];
        
        // Get referral code and stats
        $referralData = $this->customerModel->getReferralCode($customerId);
        $referralStats = $this->customerModel->getReferralStats($customerId);
        
        $data = [
            'title' => 'Referral',
            'first_name' => $_SESSION['customer_first_name'],
            'last_name' => $_SESSION['customer_last_name'],
            'referral_code' => $referralData ? $referralData->referral_code : 'Not Available',
            'total_points' => $referralStats['total_points'],
            'referral_count' => $referralStats['referral_count'],
            'friend_code' => '',
            'friend_code_error' => '',
            'success_message' => '',
            'error_message' => ''
        ];

        // Handle POST request (apply referral code)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            $friendCode = trim($_POST['friend_code'] ?? '');
            $data['friend_code'] = $friendCode;

            if (empty($friendCode)) {
                $data['friend_code_error'] = 'Please enter a referral code';
            } else {
                $result = $this->customerModel->applyReferralCode($customerId, $friendCode);
                
                if ($result['success']) {
                    $data['success_message'] = $result['message'];
                    $data['friend_code'] = ''; // Clear the input
                    
                    // Refresh stats after successful referral
                    $referralStats = $this->customerModel->getReferralStats($customerId);
                    $data['total_points'] = $referralStats['total_points'];
                    $data['referral_count'] = $referralStats['referral_count'];
                } else {
                    $data['error_message'] = $result['message'];
                }
            }
        }

        $this->view('customer/referral', $data);
    }

    public function signup(){
        if (!isset($_SESSION['customer_id'])) {
            header('Location: ' . URLROOT . '/customer/login');
            exit();
        }

        $data = [
            'title' => 'Sign Up'
        ];

        $this->view('customer/signup', $data);
    }

    // -- LOANS --
    public function pay_loan()
    {
        $customerId = $_SESSION['customer_id'];
        $activeLoans = $this->customerModel->getActiveLoanApplications($customerId);
        // Get Savings and Checking accounts only for payment
        $accounts = $this->customerModel->getAccountsByCustomerId($customerId);
        $paymentAccounts = array_filter($accounts, function($account) {
            return in_array($account->account_type_id, [1, 2]); // Only Savings and Checking
        });

        $data = [
            'title' => "Pay Loan",
            'first_name' => $_SESSION['customer_first_name'] ?? 'Customer',
            'active_loans' => $activeLoans,
            'accounts' => $paymentAccounts,
            'message' => ''
        ];

        // Process form submission
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->processLoanPayment($data);
        } else {
            $this->view('customer/pay_loan', $data);
        }
    }

    private function processLoanPayment(&$data)
    {
        $customerId = $_SESSION['customer_id'];

        $applicationId = trim($_POST['loan_id'] ?? '');
        $paymentAmount = (float)trim($_POST['payment_amount'] ?? 0);
        $sourceAccount = trim($_POST['source_account'] ?? '');

        $data['loan_id'] = $applicationId;
        $data['payment_amount'] = $paymentAmount;
        $data['source_account'] = $sourceAccount;
        $data['loan_id_error'] = '';
        $data['payment_amount_error'] = '';
        $data['source_account_error'] = '';

        // Validation
        if (empty($applicationId)) {
            $data['loan_id_error'] = 'Please select a loan application.';
        }

        if (empty($sourceAccount)) {
            $data['source_account_error'] = 'Please select a payment account.';
        }

        if ($paymentAmount <= 0) {
            $data['payment_amount_error'] = 'Please enter a valid payment amount.';
        }

        // Get loan details
        $loanDetails = null;
        if (!empty($applicationId)) {
            foreach ($data['active_loans'] as $loan) {
                if ($loan->application_id == $applicationId) {
                    $loanDetails = $loan;
                    break;
                }
            }
            if (!$loanDetails) {
                $data['loan_id_error'] = 'Invalid loan application selected.';
            } elseif ($paymentAmount > $loanDetails->remaining_balance) {
                $data['payment_amount_error'] = 'Payment amount cannot exceed remaining balance of ₱' . number_format($loanDetails->remaining_balance, 2);
            }
        }

        // Check source account balance
        if (!empty($sourceAccount)) {
            $accountBalance = $this->customerModel->validateAmount($sourceAccount);
            if ($accountBalance && $paymentAmount > (float)$accountBalance->balance) {
                $data['source_account_error'] = 'Insufficient funds in selected account.';
            }
        }

        // If no errors, show receipt confirmation
        if (empty($data['loan_id_error']) && empty($data['payment_amount_error']) && empty($data['source_account_error'])) {
            $data['loan_details'] = $loanDetails;
            $data['customer_name'] = $_SESSION['customer_first_name'] . ' ' . $_SESSION['customer_last_name'];
            $data['temp_transaction_ref'] = 'LP-PREVIEW-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
            
            $this->view('customer/loan_receipt', $data);
        } else {
            $data['active_loans'] = $this->customerModel->getActiveLoanApplications($customerId);
            $accounts = $this->customerModel->getAccountsByCustomerId($customerId);
            $data['accounts'] = array_filter($accounts, function($account) {
                return in_array($account->account_type_id, [1, 2]);
            });
            $this->view('customer/pay_loan', $data);
        }
    }

    public function confirm_loan_payment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/customer/pay_loan');
            exit();
        }

        $customerId = $_SESSION['customer_id'];
        $applicationId = trim($_POST['loan_id'] ?? '');
        $paymentAmount = (float)trim($_POST['payment_amount'] ?? 0);
        $sourceAccount = trim($_POST['source_account'] ?? '');

        // Re-validate before processing
        if (empty($applicationId) || $paymentAmount <= 0 || empty($sourceAccount)) {
            header('Location: ' . URLROOT . '/customer/pay_loan');
            exit();
        }

        // Process the payment
        $result = $this->customerModel->processApplicationPayment(
            $applicationId,
            $paymentAmount,
            $sourceAccount,
            $customerId
        );

        if ($result['status'] === true) {
            $_SESSION['payment_success'] = 'Loan payment of ₱' . number_format($paymentAmount, 2) . ' processed successfully!';
        } else {
            $_SESSION['payment_error'] = $result['error'] ?? 'Payment failed. Please try again.';
        }

        header('Location: ' . URLROOT . '/customer/pay_loan');
        exit();
    }

    /**
     * Apply interest to all Savings accounts (Admin/System function)
     * This can be called manually or via cron job
     * Access via: /customer/apply_interest
     */
    public function apply_interest() {
        // Optional: Add admin authentication check here if needed
        // if (!isset($_SESSION['employee_id']) || $_SESSION['role'] !== 'admin') {
        //     header('Content-Type: application/json');
        //     echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        //     exit;
        // }
        
        $result = $this->customerModel->calculateAndApplyInterest();
        
        header('Content-Type: application/json');
        echo json_encode($result, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Show Account Application Form
     * Access via: /customer/account_application
     */
    public function account_application() {
        // Check if logged in
        if (!isset($_SESSION['customer_id'])) {
            header('Location: ' . URLROOT . '/auth/login');
            exit;
        }

        // Get customer data
        $customer_id = $_SESSION['customer_id'];
        $profile = $this->customerModel->getCustomerProfileData($customer_id);
        
        // Get primary email and phone
        $emails = $this->customerModel->getCustomerEmails($customer_id);
        $phones = $this->customerModel->getCustomerPhones($customer_id);
        
        $primaryEmail = '';
        $primaryPhone = '';
        
        foreach ($emails as $email) {
            if ($email->is_primary == 1) {
                $primaryEmail = $email->email;
                break;
            }
        }
        
        foreach ($phones as $phone) {
            if ($phone->is_primary == 1) {
                $primaryPhone = $phone->phone_number;
                break;
            }
        }
        
        // Get available account types
        $account_types = $this->customerModel->getActiveAccountTypes();
        
        $data = [
            'title' => 'Apply for New Account',
            'full_name' => trim($profile->first_name . ' ' . $profile->middle_name . ' ' . $profile->last_name),
            'email' => $primaryEmail,
            'phone' => $primaryPhone,
            'account_types' => $account_types,
            'error' => $_SESSION['application_error'] ?? '',
            'success' => $_SESSION['application_success'] ?? ''
        ];
        
        unset($_SESSION['application_error']);
        unset($_SESSION['application_success']);
        
        $this->view('customer/account_application', $data);
    }

    /**
     * Submit Account Application
     * Access via: POST /customer/submitAccountApplication
     */
    public function submitAccountApplication() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/customer/account_application');
            exit;
        }

        // Check if logged in
        if (!isset($_SESSION['customer_id'])) {
            header('Location: ' . URLROOT . '/auth/login');
            exit;
        }

        $customer_id = $_SESSION['customer_id'];
        
        // Validate input
        if (empty($_POST['account_type_id'])) {
            $_SESSION['application_error'] = 'Please select an account type';
            header('Location: ' . URLROOT . '/customer/account_application');
            exit;
        }
        
        if (empty($_POST['accept_terms'])) {
            $_SESSION['application_error'] = 'You must accept the terms and conditions';
            header('Location: ' . URLROOT . '/customer/account_application');
            exit;
        }
        
        // Prepare application data
        $applicationData = [
            'customer_id' => $customer_id,
            'account_type_id' => intval($_POST['account_type_id']),
            'initial_deposit' => 0.00, // Will be deposited at physical branch
            'wants_passbook' => isset($_POST['wants_passbook']) ? 1 : 0,
            'wants_atm_card' => isset($_POST['wants_atm_card']) ? 1 : 0,
            'terms_accepted_at' => date('Y-m-d H:i:s'),
            'privacy_accepted_at' => date('Y-m-d H:i:s')
        ];
        
        // Submit application
        $result = $this->customerModel->submitAccountApplication($applicationData);
        
        if ($result['success']) {
            $_SESSION['application_success'] = 'Application submitted successfully! Application Number: ' . $result['application_number'];
            header('Location: ' . URLROOT . '/customer/account_applications');
        } else {
            $_SESSION['application_error'] = $result['error'] ?? 'Failed to submit application. Please try again.';
            header('Location: ' . URLROOT . '/customer/account_application');
        }
        exit;
    }

    /**
     * View Account Applications Status
     * Shows all account applications for the logged-in customer
     * Access via: /customer/account_applications
     */
    public function account_applications() {
        // Get customer email from session or customer data
        $customerEmail = $_SESSION['customer_email'] ?? null;
        
        // If email not in session, try to get it from customer data
        if (!$customerEmail && isset($_SESSION['customer_id'])) {
            // Get email from Emails table using the Customer model
            $this->db->query("
                SELECT email FROM Emails 
                WHERE customer_id = :customer_id AND is_active = 1
                ORDER BY is_primary DESC, created_at ASC 
                LIMIT 1
            ");
            $this->db->bind(':customer_id', $_SESSION['customer_id']);
            $emailResult = $this->db->single();
            $customerEmail = $emailResult->email ?? null;
        }

        $applications = [];
        if ($customerEmail) {
            $applications = $this->customerModel->getAccountApplicationsByEmail($customerEmail);
        }

        // Format applications data
        $formattedApplications = [];
        foreach ($applications as $app) {
            // Format account type
            $accountTypeDisplay = $app->account_type ?? 'N/A';
            
            // Format dates
            $submittedAt = $app->submitted_at ? date('M d, Y h:i A', strtotime($app->submitted_at)) : 'N/A';
            $reviewedAt = $app->reviewed_at ? date('M d, Y h:i A', strtotime($app->reviewed_at)) : null;
            $dateOfBirth = $app->date_of_birth ? date('M d, Y', strtotime($app->date_of_birth)) : 'N/A';
            
            // Format annual income (it's a range string like "250,000 - 500,000")
            $annualIncome = $app->annual_income ? '₱' . $app->annual_income : 'N/A';
            
            // Status badge class
            $statusClass = 'warning';
            if ($app->application_status === 'approved') {
                $statusClass = 'success';
            } elseif ($app->application_status === 'rejected') {
                $statusClass = 'danger';
            }
            
            $formattedApplications[] = [
                'application_id' => $app->application_id,
                'application_number' => $app->application_number,
                'application_status' => ucfirst($app->application_status),
                'status_class' => $statusClass,
                'first_name' => $app->first_name,
                'last_name' => $app->last_name,
                'full_name' => $app->first_name . ' ' . $app->last_name,
                'email' => $app->email,
                'phone_number' => $app->phone_number,
                'date_of_birth' => $dateOfBirth,
                'street_address' => $app->street_address ?? '',
                'barangay' => $app->barangay ?? '',
                'city' => $app->city ?? '',
                'state' => $app->state ?? '',
                'zip_code' => $app->zip_code ?? '',
                'full_address' => trim(implode(', ', array_filter([
                    $app->street_address ?? '',
                    $app->barangay ?? '',
                    $app->city ?? '',
                    $app->state ?? '',
                    $app->zip_code ?? ''
                ]))),
                'id_type' => $app->id_type ?? 'N/A',
                'id_number' => $app->id_number ?? 'N/A',
                'employment_status' => $app->employment_status ?? 'N/A',
                'employer_name' => $app->employer_name ?? 'N/A',
                'job_title' => $app->job_title ?? 'N/A',
                'annual_income' => $annualIncome,
                'account_type' => $accountTypeDisplay,
                'submitted_at' => $submittedAt,
                'reviewed_at' => $reviewedAt,
                'wants_passbook' => $app->wants_passbook ?? 0,
                'wants_atm_card' => $app->wants_atm_card ?? 0
            ];
        }

        $data = [
            'title' => 'Account Applications',
            'first_name' => $_SESSION['customer_first_name'] ?? '',
            'last_name' => $_SESSION['customer_last_name'] ?? '',
            'applications' => $formattedApplications,
            'total_applications' => count($formattedApplications),
            'pending_count' => count(array_filter($formattedApplications, fn($app) => strtolower($app['application_status']) === 'pending')),
            'approved_count' => count(array_filter($formattedApplications, fn($app) => strtolower($app['application_status']) === 'approved')),
            'rejected_count' => count(array_filter($formattedApplications, fn($app) => strtolower($app['application_status']) === 'rejected'))
        ];

        $this->view('customer/account_applications', $data);
    }
}