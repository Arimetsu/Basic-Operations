<?php
/**
 * API Endpoint: Get all ID types
 * Returns list of valid ID types from database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';

try {
    $db = getDBConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    $stmt = $db->prepare(
        "SELECT id_type_id, type_name, description
         FROM id_types
         ORDER BY type_name ASC"
    );
    $stmt->execute();
    $idTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $idTypes,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log('get-id-types.php error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load ID types',
        'error' => $e->getMessage(),
    ]);
}
