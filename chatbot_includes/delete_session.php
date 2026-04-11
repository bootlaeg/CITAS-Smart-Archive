<?php
/**
 * Delete Chatbot Session
 * Permanently deletes a session and all its messages from database
 * This is required when user exceeds the 5-session limit
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db_includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$user_id = $_SESSION['user_id'];
$session_id = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;

if ($session_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid session']);
    exit();
}

// Verify session belongs to this user
$verify = $conn->prepare("SELECT user_id, session_name FROM chatbot_sessions WHERE id = ?");
$verify->bind_param("i", $session_id);
$verify->execute();
$verify_result = $verify->get_result();

if ($verify_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Session not found']);
    $verify->close();
    exit();
}

$session_owner = $verify_result->fetch_assoc();
if ($session_owner['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    $verify->close();
    exit();
}
$session_name = $session_owner['session_name'];
$verify->close();

// Delete all messages first (ON DELETE CASCADE will handle this, but explicit is safer)
$delete_messages = $conn->prepare("DELETE FROM chatbot_messages WHERE session_id = ?");
$delete_messages->bind_param("i", $session_id);
$delete_messages->execute();
$delete_messages->close();

// Delete session
$delete_session = $conn->prepare("DELETE FROM chatbot_sessions WHERE id = ?");
$delete_session->bind_param("i", $session_id);

if ($delete_session->execute()) {
    error_log("Session deleted: ID=$session_id, User=$user_id, Name=$session_name");
    
    echo json_encode([
        'success' => true,
        'message' => "Session '{$session_name}' deleted successfully. You can now save a new session.",
        'session_id' => $session_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $delete_session->error]);
}

$delete_session->close();
$conn->close();
?>
