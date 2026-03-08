<?php
/**
 * Get Application Details
 * Retrieves detailed information for a specific application
 */

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';

try {
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

    // Get application ID from query parameter
    $applicationId = $_GET['id'] ?? null;
    
    if (!$applicationId) {
        echo json_encode([
            'success' => false,
            'message' => 'Application ID is required'
        ]);
        exit();
    }
    
    // Fetch application details with customer address information
    $stmt = $db->prepare("
        SELECT 
            aa.*,
            bc.first_name,
            bc.middle_name,
            bc.last_name,
            CONCAT(bc.first_name, ' ', COALESCE(bc.middle_name, ''), ' ', bc.last_name) as full_name,
            e.email,
            p.phone_number,
            a.address_line as street_address,
            pr.province_name,
            c.city_name,
            b.barangay_name,
            a.postal_code,
            bat.type_name as account_type,
            cp.date_of_birth,
            cp.marital_status as civil_status,
            cp.nationality,
            cp.employment_status,
            cp.company_name as employer_name,
            cp.occupation,
            g.gender_name as gender,
            idt.type_name as id_type,
            ci.id_number
        FROM account_applications aa
        INNER JOIN bank_customers bc ON aa.customer_id = bc.customer_id
        LEFT JOIN emails e ON aa.customer_id = e.customer_id AND e.is_primary = 1
        LEFT JOIN phones p ON aa.customer_id = p.customer_id AND p.is_primary = 1
        LEFT JOIN addresses a ON aa.customer_id = a.customer_id AND a.is_primary = 1
        LEFT JOIN provinces pr ON a.province_id = pr.province_id
        LEFT JOIN cities c ON a.city_id = c.city_id
        LEFT JOIN barangays b ON a.barangay_id = b.barangay_id
        LEFT JOIN bank_account_types bat ON aa.account_type_id = bat.account_type_id
        LEFT JOIN customer_profile cp ON bc.customer_id = cp.customer_id
        LEFT JOIN gender g ON cp.gender_id = g.gender_id
        LEFT JOIN customer_ids ci ON bc.customer_id = ci.customer_id
        LEFT JOIN id_types idt ON ci.id_type_id = idt.id_type_id
        WHERE aa.application_id = :application_id
    ");
    
    $stmt->bindParam(':application_id', $applicationId, PDO::PARAM_INT);
    $stmt->execute();
    
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($application) {
        // Convert application_status to lowercase for frontend consistency
        if (isset($application['application_status'])) {
            $application['application_status'] = strtolower($application['application_status']);
        }
        
        $documents = [];

        // Source 1: application_documents (legacy/alternate flow)
        if ($tableExists('application_documents')) {
            try {
                $docStmt = $db->prepare("
                    SELECT document_id, document_type, file_name, file_path, mime_type, uploaded_at
                    FROM application_documents
                    WHERE application_id = :application_id
                ");
                $docStmt->bindParam(':application_id', $applicationId, PDO::PARAM_INT);
                $docStmt->execute();
                $documents = array_merge($documents, $docStmt->fetchAll(PDO::FETCH_ASSOC));
            } catch (PDOException $e) {
                error_log('application_documents fetch error: ' . $e->getMessage());
            }
        }

        // Source 2: customer_documents (new onboarding flow)
        if ($tableExists('customer_documents') && $tableExists('document_types')) {
            try {
                $docStmt = $db->prepare("
                    SELECT
                        cd.document_id,
                        CASE
                            WHEN dt.type_name = 'ID Front' THEN 'id_front'
                            WHEN dt.type_name = 'ID Back' THEN 'id_back'
                            WHEN dt.type_name = 'E-Signature' THEN 'e_signature'
                            WHEN dt.type_name = 'Profile Picture' THEN 'selfie'
                            ELSE LOWER(REPLACE(dt.type_name, ' ', '_'))
                        END AS document_type,
                        dt.type_name AS file_name,
                        cd.file_path,
                        NULL AS mime_type,
                        cd.uploaded_at
                    FROM customer_documents cd
                    LEFT JOIN document_types dt ON cd.doc_type_id = dt.doc_type_id
                    WHERE cd.customer_id = :customer_id AND cd.is_active = 1
                ");
                $docStmt->bindParam(':customer_id', $application['customer_id'], PDO::PARAM_INT);
                $docStmt->execute();
                $documents = array_merge($documents, $docStmt->fetchAll(PDO::FETCH_ASSOC));
            } catch (PDOException $e) {
                error_log('customer_documents fetch error: ' . $e->getMessage());
            }
        }

        $application['documents'] = $documents;
    }
    
    if (!$application) {
        echo json_encode([
            'success' => false,
            'message' => 'Application not found'
        ]);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'application' => $application
    ]);

} catch (PDOException $e) {
    error_log("Database error in get-application-details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in get-application-details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching application details',
        'error' => $e->getMessage()
    ]);
}
