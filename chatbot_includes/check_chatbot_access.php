<?php
/**
 * Check Chatbot Access Status
 * Returns user's chatbot access status for a specific thesis
 */

header('Content-Type: application/json; charset=utf-8');

// Force error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../debug_chatbot_error.log');
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/../db_includes/db_connect.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'has_access' => false, 'message' => 'Database error']);
    exit(1);
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'has_access' => false, 'message' => 'Not logged in']);
    exit();
}

$thesis_id = isset($_GET['thesis_id']) ? intval($_GET['thesis_id']) : 0;

if ($thesis_id <= 0) {
    echo json_encode(['success' => false, 'has_access' => false, 'message' => 'Invalid thesis ID']);
    exit();
}

// Verify database connection
if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'has_access' => false, 'message' => 'Database error']);
    exit();
}

// Check if user has approved thesis access to this thesis
// (Chatbot access is granted with thesis access approval)
$check_stmt = $conn->prepare("
    SELECT id, status, approved_at FROM thesis_access_requests 
    WHERE user_id = ? AND thesis_id = ? 
    ORDER BY requested_at DESC LIMIT 1
");

if (!$check_stmt) {
    error_log("Prepare failed in check_chatbot_access: " . $conn->error);
    echo json_encode(['success' => false, 'has_access' => false, 'message' => 'Database error']);
    exit();
}

$check_stmt->bind_param("ii", $_SESSION['user_id'], $thesis_id);

if (!$check_stmt->execute()) {
    error_log("Execute failed in check_chatbot_access: " . $check_stmt->error);
    echo json_encode(['success' => false, 'has_access' => false, 'message' => 'Database error']);
    $check_stmt->close();
    exit();
}

$result = $check_stmt->get_result();
$row = $result->fetch_assoc();
$check_stmt->close();

if (!$row) {
    // No request exists
    echo json_encode(['success' => true, 'has_access' => false, 'status' => 'no_request']);
    $conn->close();
    exit();
}

if ($row['status'] === 'approved') {
    // Has approved access
    echo json_encode([
        'success' => true, 
        'has_access' => true, 
        'status' => 'approved',
        'approved_at' => $row['approved_at']
    ]);
} else if ($row['status'] === 'pending') {
    // Request is pending
    echo json_encode(['success' => true, 'has_access' => false, 'status' => 'pending']);
} else if ($row['status'] === 'denied') {
    // Request was denied
    echo json_encode(['success' => true, 'has_access' => false, 'status' => 'denied']);
} else {
    // Unknown status
    echo json_encode(['success' => true, 'has_access' => false, 'status' => 'unknown']);
}

$conn->close();
?>
