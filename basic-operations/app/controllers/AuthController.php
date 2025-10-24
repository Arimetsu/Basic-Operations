<?php

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
      header('Location: ' .URLROOT. '/customer/dashboard');
      exit;
    }

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
      $identifier = trim($_POST['identifier']);
      $password = trim($_POST['password']);

      $data = [
        'identifier' => $identifier,
        'password' => $password,
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

          header('Location: ' .URLROOT. '/customer/dashboard');
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
            'identifier' => '',
            'password' => '',
            'identifier_error' => '',
            'password_error' => '',
            'login_error' => ''
        ];
        $this->view('auth/login', $data);
  }

  public function logout() {
    session_start();
    unset($_SESSION['customer_id']);
    unset($_SESSION['customer_first_name']);
    unset($_SESSION['customer_last_name']);
    session_destroy();
    header('Location: '. URLROOT . '/auth/login');
      exit();
    }
}