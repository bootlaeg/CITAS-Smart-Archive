<?php
/**
 * Simplified Journal Converter Test
 * Tests async response without complex file/Ollama logic
 */

set_time_limit(0);
ini_set('default_socket_timeout', 300);
ini_set('max_execution_time', 300);

ob_start();

error_log("[test_convert_simple] Script started");

// Function to close HTTP connection
function closeConnectionAndContinue($response) {
    $json_response = json_encode($response);
    
    ob_end_clean();
    
    http_response_code(200);
    header('Content-Type: application/json');
    header('Content-Length: ' . strlen($json_response));
    header('Connection: close');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    
    error_log("[test_convert_simple] Sending response: $json_response");
    echo $json_response;
    
    flush();
    ob_flush();
    
    if (function_exists('session_write_close')) {
        session_write_close();
    }
    
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    
    sleep(1);
    error_log("[test_convert_simple] Connection closed, continuing");
    ob_start();  // Start new buffer for background work
}

try {
    $input = file_get_contents('php://input');
    error_log("[test_convert_simple] Received " . strlen($input) . " bytes");
    
    $data = json_decode($input, true);
    if (!$data) {
        throw new Exception("Invalid JSON");
    }
    
    $thesis_id = (int)($data['thesis_id'] ?? 0);
    error_log("[test_convert_simple] Thesis ID: $thesis_id");
    
    // Send immediate response
    $response = [
        'success' => true,
        'status' => 'processing',
        'thesis_id' => $thesis_id,
        'message' => 'Test conversion started'
    ];
    
    error_log("[test_convert_simple] Calling closeConnectionAndContinue");
    closeConnectionAndContinue($response);
    
    // This runs AFTER connection is closed
    error_log("[test_convert_simple] Starting 3-second background work");
    sleep(3);
    error_log("[test_convert_simple] Background work complete");
    
} catch (Exception $e) {
    ob_end_clean();
    error_log("[test_convert_simple] ERROR: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

exit;
?>
