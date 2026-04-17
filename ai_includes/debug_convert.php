<?php
/**
 * Debug Conversion Endpoint
 * Tests if data is being received correctly
 */

header('Content-Type: application/json');

error_log("[debug_convert] Request received at " . date('Y-m-d H:i:s'));

$input = file_get_contents('php://input');
error_log("[debug_convert] Raw input: " . substr($input, 0, 200));

$data = json_decode($input, true);
error_log("[debug_convert] Parsed JSON: " . json_encode($data ? array_keys($data) : 'null'));

if (!$data || !isset($data['thesis_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing thesis_id']);
    exit;
}

$thesis_id = (int)$data['thesis_id'];
error_log("[debug_convert] Thesis ID: $thesis_id");

// Simulate async close
http_response_code(200);
header('Content-Length: 100');
header('Connection: close');

$response = json_encode([
    'success' => true,
    'status' => 'processing',
    'thesis_id' => $thesis_id,
    'message' => 'Debug: Conversion started'
]);

echo $response;
flush();
ob_flush();

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

error_log("[debug_convert] Response sent, connection closed");
sleep(2);
error_log("[debug_convert] Background work complete");
exit;
?>
