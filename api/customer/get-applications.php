<?php
/**
 * Get All Account Applications
 * Retrieves all account applications with optional filtering
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
    // Optional: Check if employee is logged in
    // if (!isset($_SESSION['employee_id'])) {
    //     echo json_encode([
    //         'success' => false,
    //         'message' => 'Unauthorized. Please login as an employee.'
    //     ]);
    //     exit();
    // }

    // Get filter parameters from query string
    $status = $_GET['status'] ?? '';
    $accountType = $_GET['account_type'] ?? '';
    
    // Build query - join with customers, emails, phones and account types
    $query = "
        SELECT 
            aa.application_id,
            aa.application_number,
            aa.application_status,
            c.first_name,
            c.middle_name,
            c.last_name,
            CONCAT(c.first_name, ' ', COALESCE(c.middle_name, ''), ' ', c.last_name) as full_name,
            e.email,
            p.phone_number,
            bat.type_name as account_type,
            aa.wants_passbook,
            aa.wants_atm_card,
            aa.submitted_at,
            aa.reviewed_at
        FROM account_applications aa
        INNER JOIN bank_customers c ON aa.customer_id = c.customer_id
        LEFT JOIN emails e ON aa.customer_id = e.customer_id AND e.is_primary = 1
        LEFT JOIN phones p ON aa.customer_id = p.customer_id AND p.is_primary = 1
        INNER JOIN bank_account_types bat ON aa.account_type_id = bat.account_type_id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add status filter
    if (!empty($status)) {
        $query .= " AND aa.application_status = :status";
        $params[':status'] = $status;
    }
    
    // Add account type filter
    if (!empty($accountType)) {
        $query .= " AND bat.type_name = :account_type";
        $params[':account_type'] = $accountType;
    }
    
    // Order by submitted date (newest first)
    $query .= " ORDER BY aa.submitted_at DESC";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert application_status to lowercase for frontend consistency
    foreach ($applications as &$app) {
        if (isset($app['application_status'])) {
            $app['application_status'] = strtolower($app['application_status']);
        }
    }
    unset($app); // Break reference
    
    echo json_encode([
        'success' => true,
        'applications' => $applications,
        'count' => count($applications)
    ]);

} catch (PDOException $e) {
    error_log("Database error in get-applications.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in get-applications.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching applications',
        'error' => $e->getMessage()
    ]);
}
