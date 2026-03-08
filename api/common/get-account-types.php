<?php
/**
 * Get Account Types API
 * Returns all active account types with their capabilities
 */

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
    $db = getDBConnection();
    if (!$db) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit();
    }

    // Get all active account types
    $stmt = $db->prepare("
        SELECT 
            account_type_id,
            type_name,
            description,
            allows_passbook,
            allows_atm_card,
            requires_parent_guardian,
            minimum_age,
            base_interest_rate,
            minimum_balance,
            monthly_fee
        FROM bank_account_types
        WHERE is_active = 1
        ORDER BY account_type_id
    ");
    
    $stmt->execute();
    $accountTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert numeric values to proper types
    foreach ($accountTypes as &$type) {
        $type['allows_passbook'] = (bool)$type['allows_passbook'];
        $type['allows_atm_card'] = (bool)$type['allows_atm_card'];
        $type['requires_parent_guardian'] = (bool)$type['requires_parent_guardian'];
        $type['base_interest_rate'] = (float)$type['base_interest_rate'];
        $type['minimum_balance'] = (float)$type['minimum_balance'];
        $type['monthly_fee'] = (float)$type['monthly_fee'];
    }

    echo json_encode([
        'success' => true,
        'account_types' => $accountTypes
    ]);

} catch (PDOException $e) {
    error_log("Get Account Types Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve account types',
        'debug' => $e->getMessage()
    ]);
}
