<?php
/**
 * Deny Access Request Handler
 * CITAS Smart Archive System
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../db_includes/db_connect.php';
require_login();
require_admin();

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$request_id = intval($_POST['request_id'] ?? 0);

if (empty($request_id)) {
    error_log("admin_deny_access.php: Empty request_id, POST data: " . json_encode($_POST));
    echo json_encode(['success' => false, 'message' => 'Request ID is required']);
    exit;
}

// Verify the access request exists
$stmt = $conn->prepare("SELECT id FROM chatbot_access_requests WHERE id = ?");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Access request not found']);
    exit;
}
$stmt->close();

// Update the access request status to 'denied'
$stmt = $conn->prepare("UPDATE chatbot_access_requests SET status = 'denied', denied_at = NOW(), denied_by = ? WHERE id = ?");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$admin_id = $_SESSION['user_id'];
$stmt->bind_param("ii", $admin_id, $request_id);

if ($stmt->execute()) {
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Access request denied successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to deny access: ' . $stmt->error]);
    $stmt->close();
}

$conn->close();
?>
