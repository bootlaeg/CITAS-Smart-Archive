<?php
/**
 * Approve Access Request Handler
 * Citas Smart Archive System
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../db_includes/db_connect.php';
require_once '../client_includes/create_notification.php';
require_login();
require_admin();

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$request_id = intval($_POST['request_id'] ?? 0);
$user_id = intval($_POST['user_id'] ?? 0);
$thesis_id = intval($_POST['thesis_id'] ?? 0);

if (empty($request_id) || empty($user_id) || empty($thesis_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Verify the access request exists
$stmt = $conn->prepare("SELECT id FROM thesis_access WHERE id = ? AND user_id = ? AND thesis_id = ?");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("iii", $request_id, $user_id, $thesis_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Access request not found']);
    exit;
}
$stmt->close();

// Update the access request status to 'approved'
$stmt = $conn->prepare("UPDATE thesis_access SET status = 'approved' WHERE id = ?");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $request_id);

if ($stmt->execute()) {
    $stmt->close();
    
    // CREATE NOTIFICATION: Notify requester about approval with thesis title
    try {
        $thesis_title = get_thesis_title($thesis_id);
        $notification_title = "Thesis Access Approved";
        $notification_message = "Your request to access \"" . htmlspecialchars($thesis_title) . "\" has been approved.";
        
        create_notification(
            $user_id,
            'access_approved',
            $notification_title,
            $notification_message,
            $thesis_id
        );
        
        error_log("Created access approval notification for user: " . $user_id . " on thesis: " . $thesis_id);
    } catch (Exception $e) {
        error_log("Failed to create access approval notification: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Access approved successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to approve access: ' . $stmt->error]);
}

$conn->close();
?>
