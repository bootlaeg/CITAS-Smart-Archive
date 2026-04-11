<?php
/**
 * Load Chatbot Session
 * Retrieves all messages from a specific session
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
$verify = $conn->prepare("
    SELECT id, session_name, message_count, created_at 
    FROM chatbot_sessions 
    WHERE id = ? AND user_id = ?
");

$verify->bind_param("ii", $session_id, $user_id);
$verify->execute();
$verify_result = $verify->get_result();

if ($verify_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Session not found or unauthorized']);
    $verify->close();
    exit();
}

$session_info = $verify_result->fetch_assoc();
$verify->close();

// Load all messages from this session
$messages_stmt = $conn->prepare("
    SELECT user_message, bot_response, created_at
    FROM chatbot_messages
    WHERE session_id = ?
    ORDER BY created_at ASC
");

$messages_stmt->bind_param("i", $session_id);
$messages_stmt->execute();
$messages_result = $messages_stmt->get_result();

$messages = [];
while ($msg = $messages_result->fetch_assoc()) {
    $messages[] = $msg;
}
$messages_stmt->close();

// Update last accessed
$update_access = $conn->prepare("
    UPDATE chatbot_sessions 
    SET last_accessed = NOW()
    WHERE id = ?
");
$update_access->bind_param("i", $session_id);
$update_access->execute();
$update_access->close();

echo json_encode([
    'success' => true,
    'session' => $session_info,
    'messages' => $messages,
    'message_count' => count($messages)
]);

$conn->close();
?>
