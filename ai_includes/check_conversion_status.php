<?php
/**
 * Check Journal Conversion Status
 * Polls the database to check if conversion is complete
 */

header('Content-Type: application/json');

try {
    // Get thesis_id from query parameter
    if (!isset($_GET['thesis_id'])) {
        throw new Exception("Missing thesis_id parameter");
    }
    
    $thesis_id = (int)$_GET['thesis_id'];
    
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
    
    // Check conversion status
    $query = "SELECT id, title, is_journal_converted, journal_file_path, journal_page_count, journal_conversion_status 
              FROM thesis WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $thesis_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $thesis = $result->fetch_assoc();
    $stmt->close();
    
    // Check if there are queued items and trigger processing
    $queue_result = $conn->query("SELECT COUNT(*) as count FROM thesis WHERE journal_conversion_status = 'queued'");
    $queue_row = $queue_result->fetch_assoc();
    $queued_count = (int)$queue_row['count'];
    
    if ($queued_count > 0) {
        // Trigger queue processor asynchronously
        error_log("[check_conversion_status] Found $queued_count queued items, triggering processor");
        
        $processor_url = 'http://localhost/ai_includes/process_queue.php';
        $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 2]]);
        @file_get_contents($processor_url, false, $ctx);
    }
    
    $conn->close();
    
    if (!$thesis) {
        throw new Exception("Thesis not found: $thesis_id");
    }
    
    // Return status
    $response = [
        'success' => true,
        'thesis_id' => $thesis_id,
        'is_converted' => (bool)$thesis['is_journal_converted'],
        'status' => $thesis['journal_conversion_status'] ?? 'unknown',
        'page_count' => (int)$thesis['journal_page_count'],
        'journal_file_path' => $thesis['journal_file_path'],
        'title' => $thesis['title']
    ];
    
    // If conversion is done, return full success response
    if ($thesis['is_journal_converted']) {
        $response['success'] = true;
        $response['message'] = 'Conversion complete!';
        $response['journal_page_count'] = $thesis['journal_page_count'];
    } else {
        $response['message'] = 'Still converting... please wait';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
