<?php
/**
 * List User's Chatbot Sessions
 * Returns all sessions for current user with count and quota info
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

$user_id = $_SESSION['user_id'];
$thesis_id = isset($_POST['thesis_id']) ? intval($_POST['thesis_id']) : 0;

// Get all sessions for this user on this thesis
$stmt = $conn->prepare("
    SELECT id, session_name, message_count, created_at, last_accessed
    FROM chatbot_sessions
    WHERE user_id = ? AND thesis_id = ?
    ORDER BY last_accessed DESC
");

$stmt->bind_param("ii", $user_id, $thesis_id);
$stmt->execute();
$result = $stmt->get_result();

$sessions = [];
while ($row = $result->fetch_assoc()) {
    $sessions[] = $row;
}
$stmt->close();

// Check quota
$session_count = count($sessions);
$max_sessions = 5;
$quota_exceeded = $session_count >= $max_sessions;

echo json_encode([
    'success' => true,
    'sessions' => $sessions,
    'session_count' => $session_count,
    'max_sessions' => $max_sessions,
    'quota_exceeded' => $quota_exceeded,
    'can_save' => !$quota_exceeded,
    'message' => $quota_exceeded ? 
        "You have {$session_count}/{$max_sessions} sessions. Delete one to save new." : 
        "You have {$session_count}/{$max_sessions} sessions available."
]);

$conn->close();
?>
