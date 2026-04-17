<?php
/**
 * Journal Conversion Task Queue System
 * Instead of processing during the HTTP request, we queue the job
 * and let a background process (cron) handle it
 */

set_time_limit(10);  // Only 10 seconds for this request

ob_start();

header('Content-Type: application/json');

try {
    // Get POST data
    $input = file_get_contents('php://input');
    $post_data = json_decode($input, true);
    
    if (!$post_data) {
        throw new Exception("Invalid JSON");
    }
    
    $thesis_id = (int)($post_data['thesis_id'] ?? 0);
    
    if (!$thesis_id) {
        throw new Exception("Missing thesis_id");
    }
    
    // Connect to database
    $db_config = [
        'host' => 'localhost',
        'user' => 'u965322812_CITAS_Smart',
        'pass' => 'ErLv@g1e*',
        'name' => 'u965322812_thesis_db'
    ];
    
    $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    error_log("[queue_converter] Adding conversion task for thesis $thesis_id");
    
    // Update thesis to mark as "pending conversion"
    $update_sql = "UPDATE thesis SET journal_conversion_status = 'queued' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $thesis_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Update failed: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
    error_log("[queue_converter] Task queued successfully for thesis $thesis_id");
    
    // Send immediate response (< 1 second)
    ob_end_clean();
    
    http_response_code(200);
    $response = json_encode([
        'success' => true,
        'status' => 'queued',
        'thesis_id' => $thesis_id,
        'message' => 'Conversion queued. Processing will start shortly...'
    ]);
    
    echo $response;
    header('Content-Length: ' . strlen($response));
    
    flush();
    ob_flush();
    
} catch (Exception $e) {
    ob_end_clean();
    
    error_log("[queue_converter] ERROR: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

exit;
?>
