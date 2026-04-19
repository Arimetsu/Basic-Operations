<?php
/**
 * Create New Customer from Onboarding
 * Inserts data into normalized database tables
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';

try {
    // Accept both multipart/form-data (from FormData) and raw JSON
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'multipart/form-data') !== false) {
        $data = $_POST;

        // Decode JSON-like fields sent through FormData
        foreach ($data as $key => $value) {
            if (is_string($value) && ($value !== '') && (($value[0] === '[') || ($value[0] === '{'))) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data[$key] = $decoded;
                }
            }
        }
    } else {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
    }

    if (!$data || !is_array($data)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request payload']);
        exit();
    }

    // Normalize optional fields to expected structure
    if (!isset($data['emails'])) {
        $data['emails'] = [];
    }
    if (!isset($data['phones'])) {
        $data['phones'] = [];
    }
    
    error_log("Customer onboarding data received: " . json_encode($data));
    
    // Validate required fields
    $required = ['first_name', 'last_name', 'date_of_birth', 'gender', 'marital_status', 
                 'nationality', 'employment_status', 'income_range', 'address_line', 
                 'province_id', 'city_id', 'barangay_id', 'postal_code'];
    
    $errors = [];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " is required";
        }
    }
    
    // Check at least one contact method
    $hasEmail = !empty($data['emails']) && is_array($data['emails']) && !empty($data['emails'][0]);
    $hasPhone = !empty($data['phones']) && is_array($data['phones']) && !empty($data['phones'][0]['number']);
    
    if (!$hasEmail && !$hasPhone) {
        $errors['contact'] = "At least one contact method (email or phone) is required";
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit();
    }
    
    // Connect to database
    $db = getDBConnection();
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }
    
    // Check for duplicate email
    if ($hasEmail) {
        $email = trim($data['emails'][0]);
        $stmt = $db->prepare("SELECT customer_id FROM emails WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode(['success' => false, 'message' => 'Email already registered', 'errors' => ['email' => 'Email already exists']]);
            exit();
        }
    }
    
    // Check for duplicate phone
    if ($hasPhone) {
        $phone = trim((string)$data['phones'][0]['number']);
        $stmt = $db->prepare("SELECT customer_id FROM phones WHERE phone_number = :phone LIMIT 1");
        $stmt->bindParam(':phone', $phone);
        $stmt->execute();
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode(['success' => false, 'message' => 'Phone already registered', 'errors' => ['phone' => 'Phone already exists']]);
            exit();
        }
    }
    
    // Resolve ID type (prefer explicit id_type_id, fallback to id_type name)
    $resolvedIdTypeId = null;
    if (!empty($data['id_type_id'])) {
        $resolvedIdTypeId = (int)$data['id_type_id'];
    } elseif (!empty($data['id_type'])) {
        $idTypeName = trim((string)$data['id_type']);
        $typeStmt = $db->prepare("SELECT id_type_id FROM id_types WHERE type_name = :type_name LIMIT 1");
        $typeStmt->bindParam(':type_name', $idTypeName);
        $typeStmt->execute();
        $typeRow = $typeStmt->fetch(PDO::FETCH_ASSOC);
        if ($typeRow) {
            $resolvedIdTypeId = (int)$typeRow['id_type_id'];
        }
    }

    if (empty($resolvedIdTypeId) || empty($data['id_number'])) {
        $errors = [
            'id_type' => 'Valid ID type is required',
            'id_number' => 'ID number is required'
        ];
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit();
    }

    // Helper to check if a table exists in current database
    $tableExists = function (string $tableName) use ($db): bool {
        $stmt = $db->prepare(
            "SELECT COUNT(*) AS cnt
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :table_name"
        );
        $stmt->bindParam(':table_name', $tableName);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($row['cnt']);
    };

    // Helper to get document type ID by name (nullable)
    $getDocTypeId = function (string $typeName) use ($db): ?int {
        $stmt = $db->prepare("SELECT doc_type_id FROM document_types WHERE type_name = :type_name LIMIT 1");
        $stmt->bindParam(':type_name', $typeName);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['doc_type_id'] : null;
    };

    // Begin transaction
    $db->beginTransaction();
    
    // Step 1: Create bank_customers record
    $stmt = $db->prepare("
        INSERT INTO bank_customers (first_name, middle_name, last_name, password_hash, is_active)
        VALUES (:first_name, :middle_name, :last_name, :password_hash, 0)
    ");
    
    // Keep password empty until customer completes activation/password setup.
    $passwordHash = isset($data['password_hash']) && $data['password_hash'] !== null
        ? (string)$data['password_hash']
        : '';
    $stmt->bindParam(':first_name', $data['first_name']);
    $stmt->bindParam(':middle_name', $data['middle_name']);
    $stmt->bindParam(':last_name', $data['last_name']);
    $stmt->bindParam(':password_hash', $passwordHash);
    $stmt->execute();
    
    $customerId = $db->lastInsertId();
    error_log("Created bank_customers record with ID: " . $customerId);
    
    // Step 2: Create customer_profile
    $stmt = $db->prepare("
        INSERT INTO customer_profile (
            customer_id, gender_id, date_of_birth, marital_status, nationality,
            employment_status, company_name, occupation, income_range
        ) VALUES (
            :customer_id, :gender_id, :date_of_birth, :marital_status, :nationality,
            :employment_status, :company_name, :occupation, :income_range
        )
    ");
    
    $genderMap = ['Male' => 1, 'Female' => 2, 'Other' => 3];
    $genderId = $genderMap[$data['gender']] ?? 3;
    $birthDate = date('Y-m-d', strtotime($data['date_of_birth']));
    
    $stmt->bindParam(':customer_id', $customerId);
    $stmt->bindParam(':gender_id', $genderId);
    $stmt->bindParam(':date_of_birth', $birthDate);
    $stmt->bindParam(':marital_status', $data['marital_status']);
    $stmt->bindParam(':nationality', $data['nationality']);
    $employmentStatus = $data['employment_status'] ?? null;
    $companyName = $data['employer_name'] ?? null;
    $occupation = $data['job_title'] ?? null;
    $stmt->bindParam(':employment_status', $employmentStatus);
    $stmt->bindParam(':company_name', $companyName);
    $stmt->bindParam(':occupation', $occupation);
    $stmt->bindParam(':income_range', $data['income_range']);
    $stmt->execute();
    
    error_log("Created customer_profile record");
    
    // Step 3: Insert email(s)
    if ($hasEmail) {
        foreach ($data['emails'] as $index => $email) {
            if (!empty($email)) {
                $stmt = $db->prepare("
                    INSERT INTO emails (customer_id, email, is_primary, is_active)
                    VALUES (:customer_id, :email, :is_primary, 1)
                ");
                $stmt->bindParam(':customer_id', $customerId);
                $stmt->bindParam(':email', $email);
                $isPrimary = ($index === 0) ? 1 : 0;
                $stmt->bindParam(':is_primary', $isPrimary);
                $stmt->execute();
            }
        }
        error_log("Created email record(s)");
    }
    
    // Step 4: Insert phone(s)
    if ($hasPhone) {
        foreach ($data['phones'] as $index => $phoneData) {
            if (!empty($phoneData['number'])) {
                $stmt = $db->prepare("
                    INSERT INTO phones (customer_id, phone_number, phone_type, is_primary, is_active)
                    VALUES (:customer_id, :phone_number, 'Mobile', :is_primary, 1)
                ");
                $stmt->bindParam(':customer_id', $customerId);
                $stmt->bindParam(':phone_number', $phoneData['number']);
                $isPrimary = ($index === 0) ? 1 : 0;
                $stmt->bindParam(':is_primary', $isPrimary);
                $stmt->execute();
            }
        }
        error_log("Created phone record(s)");
    }
    
    // Step 5: Insert address
    $stmt = $db->prepare("
        INSERT INTO addresses (
            customer_id, address_line, barangay_id, city_id, province_id,
            postal_code, is_primary, is_active
        ) VALUES (
            :customer_id, :address_line, :barangay_id, :city_id, :province_id,
            :postal_code, 1, 1
        )
    ");
    $stmt->bindParam(':customer_id', $customerId);
    $stmt->bindParam(':address_line', $data['address_line']);
    $stmt->bindParam(':barangay_id', $data['barangay_id']);
    $stmt->bindParam(':city_id', $data['city_id']);
    $stmt->bindParam(':province_id', $data['province_id']);
    $stmt->bindParam(':postal_code', $data['postal_code']);
    $stmt->execute();
    
    error_log("Created address record");

    // Step 6: Insert customer ID details
    $stmt = $db->prepare(
        "INSERT INTO customer_ids (
            customer_id, id_type_id, id_number, is_verified
        ) VALUES (
            :customer_id, :id_type_id, :id_number, 0
        )"
    );
    $idNumber = trim((string)$data['id_number']);
    $stmt->bindParam(':customer_id', $customerId);
    $stmt->bindParam(':id_type_id', $resolvedIdTypeId);
    $stmt->bindParam(':id_number', $idNumber);
    $stmt->execute();

    error_log("Created customer_ids record");

    // Step 7: Save uploaded onboarding files into documents table
    $uploadDir = '../../uploads/id_images/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $hasCustomerDocumentsTable = $tableExists('customer_documents');
    $hasApplicationDocumentsTable = $tableExists('application_documents');

    if (!$hasCustomerDocumentsTable && !$hasApplicationDocumentsTable) {
        error_log('Warning: No documents table found (customer_documents/application_documents).');
    }

    // Step 8: Create account application first (needed if fallback to application_documents)
    $accountTypeId = !empty($data['account_type_id']) ? (int)$data['account_type_id'] : 1;
    $application_number = 'APP-' . date('Ymd') . '-' . str_pad($customerId, 6, '0', STR_PAD_LEFT);

    $stmt = $db->prepare(
        "INSERT INTO account_applications (
            application_number, customer_id, account_type_id, application_status,
            wants_passbook, wants_atm_card, terms_accepted_at, privacy_accepted_at, submitted_at
        ) VALUES (
            :application_number, :customer_id, :account_type_id, 'Pending',
            :wants_passbook, :wants_atm_card, NOW(), NOW(), NOW()
        )"
    );

    $wantsPassbook = !empty($data['wants_passbook']) ? 1 : 0;
    $wantsAtmCard = !empty($data['wants_atm_card']) ? 1 : 0;

    $stmt->bindParam(':application_number', $application_number);
    $stmt->bindParam(':customer_id', $customerId);
    $stmt->bindParam(':account_type_id', $accountTypeId);
    $stmt->bindParam(':wants_passbook', $wantsPassbook);
    $stmt->bindParam(':wants_atm_card', $wantsAtmCard);
    $stmt->execute();

    $applicationId = $db->lastInsertId();
    error_log("Created account_applications record with ID: " . $applicationId);

    $saveCustomerDocument = function (string $fileKey, ?int $docTypeId, string $docTypeName, string $defaultName) use ($db, $customerId, $applicationId, $uploadDir, $hasCustomerDocumentsTable, $hasApplicationDocumentsTable, $tableHasColumn) {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            return;
        }

        $file = $_FILES[$fileKey];
        $originalName = preg_replace('/[^A-Za-z0-9._\- ]/', '_', $file['name'] ?: $defaultName);
        $storedPath = $uploadDir . $originalName;

        if (!move_uploaded_file($file['tmp_name'], $storedPath)) {
            throw new Exception("Failed to save uploaded file: {$fileKey}");
        }

        $baseUrl = 'http://localhost/basic-operation';
        $fullUrl = $baseUrl . '/uploads/id_images/' . $originalName;

        if ($hasCustomerDocumentsTable) {
            $docStmt = $db->prepare(
                "INSERT INTO customer_documents (customer_id, doc_type_id, file_path, is_active)
                 VALUES (:customer_id, :doc_type_id, :file_path, 1)"
            );
            $docStmt->bindParam(':customer_id', $customerId);
            if ($docTypeId === null) {
                $docStmt->bindValue(':doc_type_id', null, PDO::PARAM_NULL);
            } else {
                $docStmt->bindValue(':doc_type_id', $docTypeId, PDO::PARAM_INT);
            }
            $docStmt->bindParam(':file_path', $fullUrl);
            $docStmt->execute();
            return;
        }

        if ($hasApplicationDocumentsTable) {
            $fileSize = isset($file['size']) ? (int)$file['size'] : 0;
            $mimeType = $file['type'] ?? 'application/octet-stream';
            $docStmt = $db->prepare(
                "INSERT INTO application_documents
                    (application_id, document_type, file_name, file_path, file_size, mime_type)
                 VALUES
                    (:application_id, :document_type, :file_name, :file_path, :file_size, :mime_type)"
            );
            $docStmt->bindParam(':application_id', $applicationId);
            $docStmt->bindParam(':document_type', $docTypeName);
            $docStmt->bindParam(':file_name', $file['name']);
            $docStmt->bindParam(':file_path', $fullUrl);
            $docStmt->bindParam(':file_size', $fileSize);
            $docStmt->bindParam(':mime_type', $mimeType);
            $docStmt->execute();
        }
    };

    $docTypeProfile = $getDocTypeId('Profile Picture');
    $docTypeESig = $getDocTypeId('E-Signature');
    $docTypeFront = $getDocTypeId('ID Front');
    $docTypeBack = $getDocTypeId('ID Back');

    $saveCustomerDocument('id_front_image', $docTypeFront, 'id_front', 'id_front.jpg');
    $saveCustomerDocument('id_back_image', $docTypeBack, 'id_back', 'id_back.jpg');
    $saveCustomerDocument('e_signature_image', $docTypeESig, 'e_signature', 'e_signature.png');
    $saveCustomerDocument('selfie_image', $docTypeProfile, 'selfie', 'selfie.jpg');
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Customer account created successfully',
        'customer_id' => $customerId,
        'application_id' => $applicationId,
        'application_number' => $application_number,
        'account_number' => $application_number
    ]);
    
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage()
    ]);
}
