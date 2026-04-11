<?php
/**
 * Debug script to check user's access status to a thesis
 * Access via: check_access_status.php?thesis_id=123
 */

header('Content-Type: application/json; charset=utf-8');

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'error' => 'Not logged in',
        'user_id' => null
    ]);
    exit();
}

require_once __DIR__ . '/db_includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$thesis_id = isset($_GET['thesis_id']) ? intval($_GET['thesis_id']) : 0;

if ($thesis_id <= 0) {
    echo json_encode([
        'error' => 'Invalid thesis_id',
        'thesis_id' => $thesis_id
    ]);
    exit();
}

// Check access status
$stmt = $conn->prepare("
    SELECT id, status, requested_at, approved_at, denied_at
    FROM thesis_access
    WHERE user_id = ? AND thesis_id = ?
");

if (!$stmt) {
    echo json_encode([
        'error' => 'Database error: ' . $conn->error
    ]);
    exit();
}

$stmt->bind_param("ii", $user_id, $thesis_id);
if (!$stmt->execute()) {
    echo json_encode([
        'error' => 'Query failed: ' . $stmt->error
    ]);
    exit();
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'user_id' => $user_id,
        'thesis_id' => $thesis_id,
        'has_record' => false,
        'message' => 'No access request found. User must request access first.'
    ]);
} else {
    $row = $result->fetch_assoc();
    echo json_encode([
        'user_id' => $user_id,
        'thesis_id' => $thesis_id,
        'has_record' => true,
        'status' => $row['status'],
        'requested_at' => $row['requested_at'],
        'approved_at' => $row['approved_at'],
        'denied_at' => $row['denied_at'],
        'can_use_chatbot' => $row['status'] === 'approved'
    ]);
}

$stmt->close();
$conn->close();
?>
