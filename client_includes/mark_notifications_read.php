<?php
/**
 * Mark Notifications as Read
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

// Check if specific notification IDs are provided
$notification_ids = isset($_POST['notification_ids']) ? $_POST['notification_ids'] : [];

if (!empty($notification_ids) && is_array($notification_ids)) {
    // Mark specific notifications as read
    $placeholders = implode(',', array_fill(0, count($notification_ids), '?'));
    $types = str_repeat('i', count($notification_ids) + 1); // +1 for user_id
    
    $ids = array_values($notification_ids);
    array_unshift($ids, $user_id); // Add user_id at the beginning
    
    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND id IN ($placeholders)");
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    
    // Bind parameters dynamically
    $stmt->bind_param($types, ...$ids);
} else {
    // Mark all unread notifications as read for the user
    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("i", $user_id);
}

if ($stmt->execute()) {
    $updated_count = $stmt->affected_rows;
    $stmt->close();
    echo json_encode([
        'success' => true, 
        'message' => 'Notifications marked as read',
        'updated_count' => $updated_count
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark as read: ' . $stmt->error]);
    $stmt->close();
}

$conn->close();
?>