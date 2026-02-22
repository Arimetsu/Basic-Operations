<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AuthController extends Controller{
  private $customerModel;

  public function __construct(){
    parent::__construct();
    $this->customerModel = $this->model('Customer');
  }
  
  public function login(){
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if(isset($_SESSION['customer_id'])){
      header('Location: ' .URLROOT. '/customer/account');
      exit;
    }

    // Get remembered identifier from cookie
    $remembered_identifier = isset($_COOKIE['remember_identifier']) ? $_COOKIE['remember_identifier'] : '';

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
      $identifier = trim($_POST['identifier']);
      $password = trim($_POST['password']);
      $remember_me = isset($_POST['remember_me']) ? true : false;

      $data = [
        'identifier' => $identifier,
        'password' => $password,
        'remember_me' => $remember_me,
        'identifier_error' => '',
        'password_error' => '',
        'login_error' => ''
      ];

      if(empty($data['identifier'])){
        $data['identifier_error'] = 'Please enter your email or account number.';
      }

      if(empty($data['password'])){
        $data['password_error'] = 'Please enter your password.';
      }

      if(empty($data['identifier_error']) && empty($data['password_error'])){
        $loggedInCustomer = $this->customerModel->loginCustomer($data['identifier'], $data['password']);
        if($loggedInCustomer){
          $_SESSION['customer_id'] = $loggedInCustomer->customer_id;
          $_SESSION['customer_first_name'] = $loggedInCustomer->first_name;
          $_SESSION['customer_last_name'] = $loggedInCustomer->last_name;

          // Handle remember me checkbox
          if ($remember_me) {
            // Set cookie to remember identifier for 30 days
            setcookie('remember_identifier', $identifier, time() + (30 * 24 * 60 * 60), '/');
          } else {
            // Clear remember me cookie if unchecked
            if (isset($_COOKIE['remember_identifier'])) {
              setcookie('remember_identifier', '', time() - 3600, '/');
            }
          }

          header('Location: ' .URLROOT. '/customer/account');
          exit;
        } else {
            $data['login_error'] = 'Invalid credentials or account not found.';
            $this->view('auth/login', $data);
            return;
        }
      } else {
            $this->view('auth/login', $data);
            return;
        }
    }
     $data = [
            'identifier' => $remembered_identifier,
            'password' => '',
            'remember_me' => !empty($remembered_identifier),
            'identifier_error' => '',
            'password_error' => '',
            'login_error' => ''
        ];
        $this->view('auth/login', $data);
  }

  public function account() {
    // This method redirects to the customer account page
    // Used when accessing /auth/account
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if(isset($_SESSION['customer_id'])){
      header('Location: ' .URLROOT. '/customer/account');
      exit;
    } else {
      header('Location: ' .URLROOT. '/auth/login');
      exit;
    }
  }

  public function logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Clear all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // Clear remember me cookie if it exists
    if (isset($_COOKIE['remember_identifier'])) {
        setcookie('remember_identifier', '', time() - 3600, '/');
    }
    
    // Redirect to login page
    header('Location: ' . URLROOT . '/auth/login');
    exit();
  }

  public function signup() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // If already logged in, redirect to account
    if(isset($_SESSION['customer_id'])){
      header('Location: ' .URLROOT. '/customer/account');
      exit;
    }

    // Prepare data for the view
    $data = [
      'first_name' => '',
      'middle_name' => '',
      'last_name' => '',
      'email' => '',
      'phone_number' => '',
      'date_of_birth' => '',
      'gender_id' => '',
      'marital_status' => '',
      'nationality' => 'Filipino',
      'employment_status' => '',
      'occupation' => '',
      'company_name' => '',
      'income_range' => '',
      'province_id' => '',
      'city_id' => '',
      'barangay_id' => '',
      'postal_code' => '',
      'address_line' => '',
      'id_type_id' => '',
      'id_number' => '',
      'id_issue_date' => '',
      'id_expiration_date' => '',
      'account_type_id' => '',
      'error' => '',
      'genders' => $this->customerModel->getGenders(),
      'provinces' => $this->customerModel->getProvinces(),
      'cities' => $this->customerModel->getAllCities(),
      'barangays' => $this->customerModel->getAllBarangays()
    ];

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
      // Sanitize POST data
      $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

      // Get all form data
      $data = array_merge($data, [
        'first_name' => trim($_POST['first_name']),
        'middle_name' => trim($_POST['middle_name']),
        'last_name' => trim($_POST['last_name']),
        'email' => trim($_POST['email']),
        'phone_number' => trim($_POST['phone_number']),
        'date_of_birth' => trim($_POST['date_of_birth']),
        'gender_id' => trim($_POST['gender_id']),
        'marital_status' => trim($_POST['marital_status']),
        'nationality' => trim($_POST['nationality']),
        'employment_status' => trim($_POST['employment_status']),
        'occupation' => trim($_POST['occupation'] ?? ''),
        'company_name' => trim($_POST['company_name'] ?? ''),
        'income_range' => trim($_POST['income_range']),
        'province_id' => trim($_POST['province_id']),
        'city_id' => trim($_POST['city_id']),
        'barangay_id' => trim($_POST['barangay_id']),
        'postal_code' => trim($_POST['postal_code'] ?? ''),
        'address_line' => trim($_POST['address_line']),
        'id_type_id' => trim($_POST['id_type_id']),
        'id_number' => trim($_POST['id_number']),
        'id_issue_date' => trim($_POST['id_issue_date'] ?? ''),
        'id_expiration_date' => trim($_POST['id_expiration_date'] ?? ''),
        'account_type_id' => trim($_POST['account_type_id']),
        'initial_deposit' => 0,
        'password' => trim($_POST['password']),
        'confirm_password' => trim($_POST['confirm_password']),
        'wants_passbook' => isset($_POST['wants_passbook']) ? 1 : 0,
        'wants_atm_card' => isset($_POST['wants_atm_card']) ? 1 : 0,
        'terms_accepted' => isset($_POST['terms_accepted']) ? 1 : 0,
        'privacy_accepted' => isset($_POST['privacy_accepted']) ? 1 : 0
      ]);

      // Validation
      $errors = [];

      if(empty($data['first_name'])) {
        $errors[] = 'First name is required';
      }

      if(empty($data['last_name'])) {
        $errors[] = 'Last name is required';
      }

      if(empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
      }

      if(empty($data['phone_number'])) {
        $errors[] = 'Phone number is required';
      } else {
        // Add +63 prefix to phone number if not already present
        $phone = $data['phone_number'];
        if(substr($phone, 0, 3) !== '+63') {
          $data['phone_number'] = '+63' . $phone;
        }
      }

      // Enhanced password validation
      if(empty($data['password'])) {
        $errors[] = 'Password is required';
      } else {
        if(strlen($data['password']) < 10) {
          $errors[] = 'Password must be at least 10 characters';
        }
        if(!preg_match('/[A-Z]/', $data['password'])) {
          $errors[] = 'Password must contain at least one uppercase letter';
        }
        if(!preg_match('/[a-z]/', $data['password'])) {
          $errors[] = 'Password must contain at least one lowercase letter';
        }
        if(!preg_match('/[0-9]/', $data['password'])) {
          $errors[] = 'Password must contain at least one number';
        }
        if(!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>\/?]/', $data['password'])) {
          $errors[] = 'Password must contain at least one special character';
        }
      }

      if($data['password'] !== $data['confirm_password']) {
        $errors[] = 'Passwords do not match';
      }

      if(!$data['terms_accepted'] || !$data['privacy_accepted']) {
        $errors[] = 'You must accept the terms and conditions';
      }

      // Check if email already exists
      if($this->customerModel->checkEmailExists($data['email'])) {
        $errors[] = 'Email address is already registered';
      }

      if(empty($errors)) {
        // Handle file uploads
        $uploadPath = ROOT_PATH . '/public/uploads/customer_documents/';
        if (!file_exists($uploadPath)) {
          mkdir($uploadPath, 0777, true);
        }

        $uploadedFiles = [];
        
        // Upload ID Front
        if(isset($_FILES['id_front']) && $_FILES['id_front']['error'] === 0) {
          $idFrontName = uniqid('id_front_') . '_' . basename($_FILES['id_front']['name']);
          if(move_uploaded_file($_FILES['id_front']['tmp_name'], $uploadPath . $idFrontName)) {
            $uploadedFiles['id_front'] = 'uploads/customer_documents/' . $idFrontName;
          }
        }

        // Upload ID Back
        if(isset($_FILES['id_back']) && $_FILES['id_back']['error'] === 0) {
          $idBackName = uniqid('id_back_') . '_' . basename($_FILES['id_back']['name']);
          if(move_uploaded_file($_FILES['id_back']['tmp_name'], $uploadPath . $idBackName)) {
            $uploadedFiles['id_back'] = 'uploads/customer_documents/' . $idBackName;
          }
        }

        // Upload Profile Picture
        if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
          $profileName = uniqid('profile_') . '_' . basename($_FILES['profile_picture']['name']);
          if(move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath . $profileName)) {
            $uploadedFiles['profile_picture'] = 'uploads/customer_documents/' . $profileName;
          }
        }

        // Upload Signature
        if(isset($_FILES['signature']) && $_FILES['signature']['error'] === 0) {
          $signatureName = uniqid('signature_') . '_' . basename($_FILES['signature']['name']);
          if(move_uploaded_file($_FILES['signature']['tmp_name'], $uploadPath . $signatureName)) {
            $uploadedFiles['signature'] = 'uploads/customer_documents/' . $signatureName;
          }
        }

        // Register the customer directly without OTP verification
        $result = $this->customerModel->registerCustomer($data, $uploadedFiles);
        
        if($result['success']) {
          // Set success message in session
          $_SESSION['signup_success'] = 'Registration successful! Your application is pending review. You will receive an email once approved.';
          header('Location: ' . URLROOT . '/auth/login');
          exit;
        } else {
          $data['error'] = $result['error'] ?? 'Registration failed. Please try again.';
        }
      } else {
        $data['error'] = implode('<br>', $errors);
      }
    }

    $this->view('auth/signup', $data);
  }

  public function verify_signup() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Redirect if no pending signup
    if (!isset($_SESSION['signup_otp']) || !isset($_SESSION['pending_signup_data'])) {
        header('Location: ' . URLROOT . '/auth/signup');
        exit;
    }
    
    $this->view('auth/signup_verify');
  }

  public function complete_signup() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Redirect if OTP not verified or no pending signup
    if (!isset($_SESSION['signup_otp_verified']) || !isset($_SESSION['pending_signup_data'])) {
        header('Location: ' . URLROOT . '/auth/signup');
        exit;
    }
    
    // Get signup data from session
    $data = $_SESSION['pending_signup_data'];
    $uploadedFiles = $_SESSION['pending_uploaded_files'] ?? [];
    
    // Register the customer
    $result = $this->customerModel->registerCustomer($data, $uploadedFiles);
    
    // Clear signup session data
    unset($_SESSION['signup_otp']);
    unset($_SESSION['pending_signup_data']);
    unset($_SESSION['pending_uploaded_files']);
    unset($_SESSION['otp_signup_time']);
    unset($_SESSION['signup_otp_verified']);
    
    if($result['success']) {
      // Set success message in session
      $_SESSION['signup_success'] = 'Registration successful! Your email has been verified. Your application is pending review. You will receive an email once approved.';
      header('Location: ' . URLROOT . '/auth/login');
      exit;
    } else {
      $_SESSION['error'] = $result['error'] ?? 'Registration failed. Please try again.';
      header('Location: ' . URLROOT . '/auth/signup');
      exit;
    }
  }
}
