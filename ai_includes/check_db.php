<?php
/**
 * Quick Database Connection Checker
 * Helps diagnose database connection issues
 */

require_once __DIR__ . ('/../db_includes/db_connect.php');

header('Content-Type: application/json');

try {
    // Check if global mysqli is available
    if (!isset($GLOBALS['mysqli'])) {
        throw new Exception("Global mysqli not set in GLOBALS");
    }
    
    $mysqli = $GLOBALS['mysqli'];
    
    if (!$mysqli) {
        throw new Exception("Database connection is NULL");
    }
    
    if ($mysqli->connect_error) {
        throw new Exception("Connection Error: " . $mysqli->connect_error);
    }
    
    // Test a simple query
    $result = $mysqli->query("SELECT 1");
    if (!$result) {
        throw new Exception("Query failed: " . $mysqli->error);
    }
    
    // Check if thesis_classification table exists
    $tableCheck = $mysqli->query("SHOW TABLES LIKE 'thesis_classification'");
    $tableExists = $tableCheck->num_rows > 0;
    
    echo json_encode([
        'success' => true,
        'connection' => 'OK',
        'database' => $mysqli->get_server_info(),
        'client_info' => $mysqli->client_info,
        'thesis_classification_table_exists' => $tableExists,
        'message' => 'Database connection is working correctly'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Database connection check failed'
    ]);
}
?>
