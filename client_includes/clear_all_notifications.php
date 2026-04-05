<?php
/**
 * Clear All Notifications for Current User
 */

require_once __DIR__ . '/../db_includes/db_connect.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Verify user is logged in and has a valid user_id
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Delete all notifications for the current user
$stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    $deleted_count = $stmt->affected_rows;
    $stmt->close();
    echo json_encode([
        'success' => true, 
        'message' => 'All notifications cleared successfully',
        'cleared_count' => $deleted_count
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to clear notifications: ' . $stmt->error]);
    $stmt->close();
}

$conn->close();
?>