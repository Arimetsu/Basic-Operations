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

      $today = new DateTime('today');
      $maxDob = (clone $today)->modify('-18 years');
      $minDob = (clone $today)->modify('-120 years');

      $parseDate = function ($dateStr) {
        if (empty($dateStr)) {
          return null;
        }
        $dt = DateTime::createFromFormat('Y-m-d', $dateStr);
        if (!$dt || $dt->format('Y-m-d') !== $dateStr) {
          return false;
        }
        return $dt;
      };

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

      // Date validations (DOB and ID dates)
      $dob = $parseDate($data['date_of_birth']);
      if ($dob === false) {
        $errors[] = 'Date of birth must be a valid date.';
      } elseif ($dob === null) {
        $errors[] = 'Date of birth is required.';
      } else {
        if ($dob > $today) {
          $errors[] = 'Date of birth cannot be in the future.';
        }
        if ($dob < $minDob) {
          $errors[] = 'Date of birth is too far in the past.';
        }
        if ($dob > $maxDob) {
          $errors[] = 'You must be at least 18 years old to register.';
        }
      }

      $idIssueDate = $parseDate($data['id_issue_date']);
      if ($idIssueDate === false) {
        $errors[] = 'ID issue date must be a valid date.';
      } elseif ($idIssueDate !== null && $idIssueDate > $today) {
        $errors[] = 'ID issue date cannot be in the future.';
      }

      $idExpirationDate = $parseDate($data['id_expiration_date']);
      if ($idExpirationDate === false) {
        $errors[] = 'ID expiration date must be a valid date.';
      } elseif ($idExpirationDate !== null && $idExpirationDate < $today) {
        $errors[] = 'ID expiration date cannot be in the past.';
      }

      if ($idIssueDate instanceof DateTime && $idExpirationDate instanceof DateTime && $idIssueDate > $idExpirationDate) {
        $errors[] = 'ID issue date must be on or before the ID expiration date.';
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

      if(!isset($_FILES['id_front']) || $_FILES['id_front']['error'] !== 0) {
        $errors[] = 'ID front image is required';
      }

      if(!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== 0) {
        $errors[] = 'Profile picture is required';
      }

      if(!isset($_FILES['signature']) || $_FILES['signature']['error'] !== 0) {
        $errors[] = 'Signature image is required';
      }

      if(empty($errors)) {
        // Handle file uploads
        $uploadPath = ROOT_PATH . '/public/uploads/id_images/';
        if (!file_exists($uploadPath)) {
          mkdir($uploadPath, 0777, true);
        }

        $uploadedFiles = [];
        
        // Upload ID Front
        if(isset($_FILES['id_front']) && $_FILES['id_front']['error'] === 0) {
          $idFrontName = uniqid('id_front_image_') . '_' . basename($_FILES['id_front']['name']);
          if(move_uploaded_file($_FILES['id_front']['tmp_name'], $uploadPath . $idFrontName)) {
            $uploadedFiles['id_front'] = 'uploads/id_images/' . $idFrontName;
          }
        }

        // Upload ID Back
        if(isset($_FILES['id_back']) && $_FILES['id_back']['error'] === 0) {
          $idBackName = uniqid('id_back_image_') . '_' . basename($_FILES['id_back']['name']);
          if(move_uploaded_file($_FILES['id_back']['tmp_name'], $uploadPath . $idBackName)) {
            $uploadedFiles['id_back'] = 'uploads/id_images/' . $idBackName;
          }
        }

        // Upload Profile Picture
        if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
          $profileName = uniqid('selfie_image_') . '_' . basename($_FILES['profile_picture']['name']);
          if(move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath . $profileName)) {
            $uploadedFiles['profile_picture'] = 'uploads/id_images/' . $profileName;
          }
        }

        // Upload Signature
        if(isset($_FILES['signature']) && $_FILES['signature']['error'] === 0) {
          $signatureName = uniqid('signature_image_') . '_' . basename($_FILES['signature']['name']);
          if(move_uploaded_file($_FILES['signature']['tmp_name'], $uploadPath . $signatureName)) {
            $uploadedFiles['signature'] = 'uploads/id_images/' . $signatureName;
          }
        }

        // Stage signup data in session and require OTP verification before DB save
        $otp = sprintf('%06d', mt_rand(0, 999999));
        $_SESSION['signup_otp'] = $otp;
        $_SESSION['otp_signup_time'] = time();
        $_SESSION['signup_otp_verified'] = false;
        $pendingSignupData = $data;
        unset($pendingSignupData['error']);
        unset($pendingSignupData['genders']);
        unset($pendingSignupData['provinces']);
        unset($pendingSignupData['cities']);
        unset($pendingSignupData['barangays']);
        $_SESSION['pending_signup_data'] = $pendingSignupData;
        $_SESSION['pending_uploaded_files'] = $uploadedFiles;

        $emailSent = $this->sendSignupOtpEmail($data['email'], $data['first_name'], $otp, false);

        if ($emailSent['success']) {
          header('Location: ' . URLROOT . '/auth/verify_signup');
          exit;
        }

        $this->cleanupUploadedFiles($uploadedFiles);
        unset($_SESSION['signup_otp']);
        unset($_SESSION['otp_signup_time']);
        unset($_SESSION['signup_otp_verified']);
        unset($_SESSION['pending_signup_data']);
        unset($_SESSION['pending_uploaded_files']);
        $data['error'] = $emailSent['error'] ?? 'Failed to send verification code. Please try again.';
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
    
    if($result['success']) {
      // Clear signup session data
      unset($_SESSION['signup_otp']);
      unset($_SESSION['pending_signup_data']);
      unset($_SESSION['pending_uploaded_files']);
      unset($_SESSION['otp_signup_time']);
      unset($_SESSION['signup_otp_verified']);

      // Set success message in session
      $_SESSION['signup_success'] = 'Registration successful! Your email has been verified. Your application is pending review. You will receive an email once approved.';
      header('Location: ' . URLROOT . '/auth/login');
      exit;
    } else {
      $uploadedFiles = $_SESSION['pending_uploaded_files'] ?? [];
      $this->cleanupUploadedFiles($uploadedFiles);

      unset($_SESSION['signup_otp']);
      unset($_SESSION['pending_signup_data']);
      unset($_SESSION['pending_uploaded_files']);
      unset($_SESSION['otp_signup_time']);
      unset($_SESSION['signup_otp_verified']);

      $_SESSION['error'] = $result['error'] ?? 'Registration failed. Please try again.';
      header('Location: ' . URLROOT . '/auth/signup');
      exit;
    }
  }

  private function sendSignupOtpEmail($email, $firstName, $otp, $isResend = false) {
    try {
      $mail = new PHPMailer(true);
      $mail->isSMTP();
      $mail->Host       = 'smtp.gmail.com';
      $mail->SMTPAuth   = true;
      $mail->Username   = 'evrgrn.64@gmail.com';
      $mail->Password   = 'dourhhbymvjejuct';
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port       = 587;

      $mail->setFrom('evrgrn.64@gmail.com', 'Evergreen Banking');
      $mail->addAddress($email, $firstName);
      $mail->isHTML(true);
      $mail->Subject = $isResend ? 'Email Verification - New Code' : 'Email Verification Code';
      $mail->Body = '
      <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
          <div style="background: linear-gradient(135deg, #003631 0%, #1a6b62 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
              <h1 style="color: white; margin: 0;">' . ($isResend ? 'New Verification Code' : 'Verify Your Email') . '</h1>
          </div>
          <div style="background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px;">
              <p style="font-size: 16px; color: #333;">Hello <strong>' . htmlspecialchars($firstName) . '</strong>,</p>
              <p style="font-size: 14px; color: #666;">Use this code to continue your signup:</p>
              <div style="background: white; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; border: 2px dashed #003631;">
                  <h2 style="color: #003631; font-size: 32px; letter-spacing: 8px; margin: 0;">' . $otp . '</h2>
              </div>
              <p style="font-size: 13px; color: #666;">This code will expire in <strong>5 minutes</strong>.</p>
          </div>
      </div>
      ';

      $mail->send();
      return ['success' => true];
    } catch (Exception $e) {
      error_log('Signup OTP mail error: ' . $e->getMessage());
      return ['success' => false, 'error' => 'Failed to send verification code. Please try again.'];
    }
  }

  private function cleanupUploadedFiles($uploadedFiles) {
    foreach ($uploadedFiles as $relativePath) {
      $fullPath = ROOT_PATH . '/public/' . ltrim($relativePath, '/');
      if (is_file($fullPath)) {
        @unlink($fullPath);
      }
    }
  }

  public function activate() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // If already logged in, redirect to account
    if(isset($_SESSION['customer_id'])){
      header('Location: ' .URLROOT. '/customer/account');
      exit;
    }

    // Load the activate view
    $data = [];
    $this->view('auth/activate', $data);
  }

  public function activateVerify() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // If already logged in, redirect to account
    if(isset($_SESSION['customer_id'])){
      header('Location: ' .URLROOT. '/customer/account');
      exit;
    }

    // Load the activate verify view
    $data = [];
    $this->view('auth/activate_verify', $data);
  }

  public function activatePassword() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // If already logged in, redirect to account
    if(isset($_SESSION['customer_id'])){
      header('Location: ' .URLROOT. '/customer/account');
      exit;
    }

    // Load the activate password view
    $data = [];
    $this->view('auth/activate_password', $data);
  }
}
