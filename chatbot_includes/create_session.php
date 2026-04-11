<?php
/**
 * Create New Chatbot Session
 * Creates a new session or checks if one already exists
 * Respects 5-session limit per user per thesis
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
$thesis_id = isset($_POST['thesis_id']) ? intval($_POST['thesis_id']) : 0;
$session_name = isset($_POST['session_name']) ? trim($_POST['session_name']) : 'Untitled Session';

if ($thesis_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid thesis']);
    exit();
}

// Check session count
$count_stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM chatbot_sessions
    WHERE user_id = ? AND thesis_id = ?
");

$count_stmt->bind_param("ii", $user_id, $thesis_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$session_count = $count_result['count'];
$count_stmt->close();

if ($session_count >= 5) {
    echo json_encode([
        'success' => false,
        'message' => "You have reached the maximum of 5 sessions. Delete one to create a new session.",
        'quota_exceeded' => true,
        'session_count' => $session_count,
        'max_sessions' => 5
    ]);
    exit();
}

// Create new session
$insert = $conn->prepare("
    INSERT INTO chatbot_sessions (user_id, thesis_id, session_name)
    VALUES (?, ?, ?)
");

$insert->bind_param("iis", $user_id, $thesis_id, $session_name);

if ($insert->execute()) {
    $session_id = $insert->insert_id;
    
    echo json_encode([
        'success' => true,
        'session_id' => $session_id,
        'session_name' => $session_name,
        'message' => 'New session created successfully',
        'session_count' => $session_count + 1,
        'max_sessions' => 5
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $insert->error]);
}

$insert->close();
$conn->close();
?>
