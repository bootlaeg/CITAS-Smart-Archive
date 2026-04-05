<?php
/**
 * Delete Thesis Handler
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

$thesis_id = intval($_POST['thesis_id'] ?? 0);

if (empty($thesis_id)) {
    echo json_encode(['success' => false, 'message' => 'Thesis ID is required']);
    exit;
}

// Get thesis file path before deletion
$stmt = $conn->prepare("SELECT file_path, title FROM thesis WHERE id = ?");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Thesis not found']);
    exit;
}

$thesis = $result->fetch_assoc();
$file_path = $thesis['file_path'];
$thesis_title = $thesis['title'];
$stmt->close();

// Delete the file if it exists
if (!empty($file_path)) {
    $base_dir = dirname(__DIR__); // Goes to c:\xampp\htdocs\ctrws-fix
    $full_path = $base_dir . '/' . $file_path;
    
    if (file_exists($full_path)) {
        @unlink($full_path);
    }
}

// Delete thesis from database
$stmt = $conn->prepare("DELETE FROM thesis WHERE id = ?");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $thesis_id);

if ($stmt->execute()) {
    $stmt->close();
    
    // CREATE NOTIFICATION: Notify all active users about thesis deletion
    // Do this asynchronously to avoid blocking the response
    if (!empty($thesis_title)) {
        try {
            $user_ids = get_all_users_except_admin();
            if (!empty($user_ids)) {
                $notification_title = "Thesis Deleted";
                $notification_message = "The thesis \"" . htmlspecialchars($thesis_title) . "\" has been removed from the system.";
                
                $notified_count = create_bulk_notification(
                    $user_ids,
                    'thesis_deleted',
                    $notification_title,
                    $notification_message
                );
                
                error_log("Thesis Delete: Created " . $notified_count . " notifications for thesis ID: " . $thesis_id);
            }
        } catch (Exception $e) {
            error_log("Thesis Delete: Notification error: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Thesis deleted successfully'
    ]);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete thesis: ' . $stmt->error]);
    $stmt->close();
}

$conn->close();
?>
