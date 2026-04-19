<?php
/**
 * Open New Account API
 * Opens a new Savings or Checking account for an existing customer
 */

// Start session before any output
session_start();

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in response
ini_set('log_errors', 1);
error_log("=== Account Opening API Called ===");

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

try {
    // Get form data (multipart/form-data for file upload)
    $input = $_POST;
    error_log("POST data: " . print_r($input, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    if (empty($input)) {
        error_log("ERROR: Empty input data");
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request data'
        ]);
        exit();
    }

    // Validate required fields
    $errors = [];
    
    // Validate existing account number
    if (empty($input['existing_account_number'])) {
        $errors['existing_account_number'] = 'Existing account number is required';
    }
    
    if (empty($input['account_type'])) {
        $errors['account_type'] = 'Account type is required';
    } else {
        // Normalize incoming type to canonical names
        $typeRaw = trim($input['account_type']);
        $typeNormalized = $typeRaw;
        if (strcasecmp($typeRaw, 'Savings') === 0) {
            $typeNormalized = 'Savings Account';
        } elseif (strcasecmp($typeRaw, 'Checking') === 0) {
            $typeNormalized = 'Checking Account';
        }

        // Accept both canonical and short forms
        $allowed = ['Savings', 'Checking', 'Savings Account', 'Checking Account'];
        if (!in_array($typeRaw, $allowed, true) && !in_array($typeNormalized, ['Savings Account', 'Checking Account'], true)) {
            $errors['account_type'] = 'Invalid account type. Must be Savings Account or Checking Account';
        } else {
            // Overwrite input with normalized canonical name for downstream usage
            $input['account_type'] = $typeNormalized;
        }
    }

    // Validate ID fields
    if (empty($input['id_type'])) {
        $errors['id_type'] = 'ID type is required';
    }
    
    if (empty($input['id_number'])) {
        $errors['id_number'] = 'ID number is required';
    }

    // Validate ID image uploads
    if (!isset($_FILES['id_front_image']) || $_FILES['id_front_image']['error'] !== UPLOAD_ERR_OK) {
        $errors['id_front_image'] = 'Front image of ID is required';
    }
    
    if (!isset($_FILES['id_back_image']) || $_FILES['id_back_image']['error'] !== UPLOAD_ERR_OK) {
        $errors['id_back_image'] = 'Back image of ID is required';
    }

    // Get passbook and ATM card preferences
    $wantsPassbook = isset($input['wants_passbook']) && $input['wants_passbook'] == '1' ? 1 : 0;
    $wantsAtmCard = isset($input['wants_atm_card']) && $input['wants_atm_card'] == '1' ? 1 : 0;
    
    // Validate initial deposit if provided
    $initialDeposit = null;
    $depositSource = null;
    $sourceAccountNumber = null;
    
    if (isset($input['initial_deposit']) && $input['initial_deposit'] !== null && $input['initial_deposit'] !== '') {
        $initialDeposit = floatval($input['initial_deposit']);
        if ($initialDeposit < 0) {
            $errors['initial_deposit'] = 'Initial deposit cannot be negative';
        }
        
        // If deposit amount is provided, validate deposit source
        if ($initialDeposit > 0) {
            $depositSource = $input['deposit_source'] ?? null;
            
            if (empty($depositSource)) {
                $errors['deposit_source'] = 'Please select a deposit source (Cash or Transfer)';
            } elseif ($depositSource === 'transfer') {
                $sourceAccountNumber = $input['source_account_number'] ?? null;
                if (empty($sourceAccountNumber)) {
                    $errors['source_account_number'] = 'Please select a source account for transfer';
                }
            }
        }
    }

    // Return validation errors if any
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ]);
        exit();
    }

    // account_type now holds canonical name; do not re-append
    $accountTypeName = $input['account_type'];

    // Connect to database
    $db = getDBConnection();
    if (!$db) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit();
    }

    // Get customer_id from existing account number OR from form data
    $customerId = null;
    
    // First check if customer_id was passed from frontend
    if (!empty($input['customer_id'])) {
        $customerId = intval($input['customer_id']);
        error_log("Using customer_id from form: " . $customerId);
    }
    
    // If not provided, fetch it from the account number
    if (!$customerId && !empty($input['existing_account_number'])) {
        $existingAccountNumber = trim($input['existing_account_number']);
        
        $stmt = $db->prepare("
            SELECT ca.account_id, ca.account_number, ca.customer_id, ca.is_locked
            FROM customer_accounts ca
            WHERE ca.account_number = :account_number
            LIMIT 1
        ");
        $stmt->bindParam(':account_number', $existingAccountNumber);
        $stmt->execute();
        $existingAccount = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingAccount) {
            echo json_encode([
                'success' => false,
                'message' => 'The existing account number was not found in the system.',
                'errors' => [
                    'existing_account_number' => 'This account number does not exist.'
                ]
            ]);
            exit();
        }
        
        // Check if existing account is locked
        if ($existingAccount['is_locked']) {
            echo json_encode([
                'success' => false,
                'message' => 'The existing account is locked. Please contact customer service.',
                'errors' => [
                    'existing_account_number' => 'This account is locked and cannot be used for verification.'
                ]
            ]);
            exit();
        }
        
        // Get customer_id from the existing account
        $customerId = $existingAccount['customer_id'];
    }

    // Handle file uploads for ID images
    $uploadDir = '../../uploads/id_images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $idFrontPath = null;
    $idBackPath = null;
    $baseUrl = 'http://localhost/basic-operation';

    // Upload front image
    if (isset($_FILES['id_front_image']) && $_FILES['id_front_image']['error'] === UPLOAD_ERR_OK) {
        $frontOriginalName = basename($_FILES['id_front_image']['name']);
        $frontSafeName = preg_replace('/[^A-Za-z0-9._\- ]/', '_', $frontOriginalName);
        $targetPath = $uploadDir . $frontSafeName;
        
        if (move_uploaded_file($_FILES['id_front_image']['tmp_name'], $targetPath)) {
            $idFrontPath = $baseUrl . '/uploads/id_images/' . $frontSafeName;
        }
    }

    // Upload back image
    if (isset($_FILES['id_back_image']) && $_FILES['id_back_image']['error'] === UPLOAD_ERR_OK) {
        $backOriginalName = basename($_FILES['id_back_image']['name']);
        $backSafeName = preg_replace('/[^A-Za-z0-9._\- ]/', '_', $backOriginalName);
        $targetPath = $uploadDir . $backSafeName;
        
        if (move_uploaded_file($_FILES['id_back_image']['tmp_name'], $targetPath)) {
            $idBackPath = $baseUrl . '/uploads/id_images/' . $backSafeName;
        }
    }

    // Begin transaction
    $db->beginTransaction();

    try {
        // Get employee ID from session (if available) or default to NULL
        $employeeId = $_SESSION['employee_id'] ?? null;
        
        // Generate unique application number
        $applicationNumber = 'APP-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 4, '0', STR_PAD_LEFT);
        
        // Verify application number is unique
        $stmt = $db->prepare("SELECT application_id FROM account_applications WHERE application_number = :app_num");
        $stmt->bindParam(':app_num', $applicationNumber);
        $stmt->execute();
        while ($stmt->fetch()) {
            // Generate new number if conflict
            $applicationNumber = 'APP-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 4, '0', STR_PAD_LEFT);
            $stmt->execute();
        }
        
        // Create a pending account_applications record for existing customer
        // Only store application-specific data, personal info already exists in customer tables
        if ($idFrontPath && $idBackPath) {
            
            // Map account type name to account_type_id
            $accountTypeId = null;
            if ($input['account_type'] === 'Savings Account') {
                $accountTypeId = 1; // Savings
            } elseif ($input['account_type'] === 'Checking Account') {
                $accountTypeId = 3; // Current
            }
            
            if (!$accountTypeId) {
                throw new Exception('Invalid account type');
            }
            
            $stmt = $db->prepare("
                INSERT INTO account_applications (
                    application_number,
                    customer_id,
                    account_type_id,
                    application_status,
                    wants_passbook,
                    wants_atm_card,
                    terms_accepted_at,
                    privacy_accepted_at,
                    submitted_at
                ) VALUES (
                    :application_number,
                    :customer_id,
                    :account_type_id,
                    'Pending',
                    :wants_passbook,
                    :wants_atm_card,
                    NOW(),
                    NOW(),
                    NOW()
                )
            ");
            
            $stmt->bindParam(':application_number', $applicationNumber);
            $stmt->bindParam(':customer_id', $customerId);
            $stmt->bindParam(':account_type_id', $accountTypeId, PDO::PARAM_INT);
            $stmt->bindParam(':wants_passbook', $wantsPassbook, PDO::PARAM_INT);
            $stmt->bindParam(':wants_atm_card', $wantsAtmCard, PDO::PARAM_INT);
            $stmt->execute();
            
            $newApplicationId = $db->lastInsertId();
            
            // Note: ID images are uploaded and stored in uploads/id_images/ folder
            // For existing customers, these are for verification purposes only
            // Customer's primary ID information is already in customer_ids table
        }
        
        // Commit transaction
        $db->commit();
        
        // Return success with application number
        echo json_encode([
            'success' => true,
            'message' => 'Account application submitted successfully! Your application is pending approval.',
            'application_number' => $applicationNumber,
            'application_id' => $newApplicationId,
            'account_type' => $input['account_type'],
            'status' => 'pending'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Open account error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while submitting your application. Please try again.',
        'debug' => $e->getMessage()
    ]);
}

