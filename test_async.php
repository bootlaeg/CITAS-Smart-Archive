<?php
/**
 * Test Async Conversion
 * Simple test without actual Ollama call
 */

header('Content-Type: application/json');

// Log that test started
error_log("[test_async] Test started at " . date('Y-m-d H:i:s'));

$thesis_id = isset($_GET['id']) ? (int)$_GET['id'] : 999;

// Simulate immediate response
$immediate_response = json_encode([
    'success' => true,
    'status' => 'processing',
    'thesis_id' => $thesis_id,
    'message' => 'Test conversion started in background'
]);

// Send response
http_response_code(200);
header('Content-Length: ' . strlen($immediate_response));
header('Connection: close');

echo $immediate_response;
flush();
ob_flush();

// Log that connection closed
error_log("[test_async] Initial response sent, connection closed");

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// Simulate background processing
sleep(3);

// Log completion
error_log("[test_async] Background processing complete at " . date('Y-m-d H:i:s'));

// Update database (simulate)
$db_config = [
    'host' => 'localhost',
    'user' => 'u965322812_CITAS_Smart',
    'pass' => 'ErLv@g1e*',
    'name' => 'u965322812_thesis_db'
];

try {
    $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
    
    if (!$conn->connect_error) {
        // Check if we can query
        $result = $conn->query("SELECT COUNT(*) as count FROM thesis");
        if ($result) {
            $row = $result->fetch_assoc();
            error_log("[test_async] Database OK, thesis count: " . $row['count']);
        }
        $conn->close();
    }
} catch (Exception $e) {
    error_log("[test_async] Database error: " . $e->getMessage());
}

error_log("[test_async] Test completed");
exit;
?>
