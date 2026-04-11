<?php
/**
 * Save Message to Session
 * Adds a user message and bot response to a session
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
$user_message = isset($_POST['user_message']) ? $_POST['user_message'] : '';
$bot_response = isset($_POST['bot_response']) ? $_POST['bot_response'] : '';

if ($session_id <= 0 || empty($user_message) || empty($bot_response)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

// Verify session belongs to this user
$verify = $conn->prepare("SELECT user_id FROM chatbot_sessions WHERE id = ?");
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
$verify->close();

// Insert message
$insert = $conn->prepare("
    INSERT INTO chatbot_messages (session_id, user_message, bot_response)
    VALUES (?, ?, ?)
");

$insert->bind_param("iss", $session_id, $user_message, $bot_response);

if ($insert->execute()) {
    // Update message count
    $count_stmt = $conn->prepare("
        UPDATE chatbot_sessions 
        SET message_count = (SELECT COUNT(*) FROM chatbot_messages WHERE session_id = ?),
            last_accessed = NOW()
        WHERE id = ?
    ");
    $count_stmt->bind_param("ii", $session_id, $session_id);
    $count_stmt->execute();
    $count_stmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Message saved']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $insert->error]);
}

$insert->close();
$conn->close();
?>
