<?php
/**
 * Get Notifications for Real-Time Updates
 * Returns unread notifications for the current user
 */

require_once __DIR__ . '/../db_includes/db_connect.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Verify user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get unread notification count
$count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$unread_count = $count_result['count'];
$count_stmt->close();

// Get recent notifications (unread first, then most recent)
$notif_stmt = $conn->prepare("SELECT id, type, title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY is_read ASC, created_at DESC LIMIT 10");
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();

$notifications = [];
while ($notif = $notif_result->fetch_assoc()) {
    $notifications[] = [
        'id' => $notif['id'],
        'type' => $notif['type'],
        'title' => $notif['title'],
        'message' => $notif['message'],
        'is_read' => (bool)$notif['is_read'],
        'created_at' => $notif['created_at'],
        'time_ago' => time_ago(strtotime($notif['created_at']))
    ];
}

$notif_stmt->close();

echo json_encode([
    'success' => true,
    'unread_count' => $unread_count,
    'notifications' => $notifications
]);

$conn->close();

/**
 * Helper function to format time ago
 */
function time_ago($time) {
    $time_diff = time() - $time;
    
    if ($time_diff < 60) {
        return "just now";
    } elseif ($time_diff < 3600) {
        $mins = floor($time_diff / 60);
        return $mins . " min" . ($mins > 1 ? "s" : "") . " ago";
    } elseif ($time_diff < 86400) {
        $hours = floor($time_diff / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($time_diff < 604800) {
        $days = floor($time_diff / 86400);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    } else {
        return date('M d, Y', $time);
    }
}
?>
