<?php
/**
 * Check Journal Conversion Queue Status
 */

header('Content-Type: application/json');

try {
    $db_config = [
        'host' => 'localhost',
        'user' => 'u965322812_CITAS_Smart',
        'pass' => 'ErLv@g1e*',
        'name' => 'u965322812_thesis_db'
    ];
    
    $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }
    
    // Count items by status
    $statuses = ['queued', 'processing', 'completed', 'failed'];
    $counts = [];
    
    foreach ($statuses as $status) {
        $result = $conn->query("SELECT COUNT(*) as count FROM thesis WHERE journal_conversion_status = '$status'");
        if ($result) {
            $row = $result->fetch_assoc();
            $counts[$status] = (int)$row['count'];
        }
    }
    
    $conn->close();
    
    echo json_encode($counts);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
