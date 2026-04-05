<?php
/**
 * Suspend User Handler
 * Citas Smart Archive System
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../db_includes/db_connect.php';
require_login();
require_admin();

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = intval($_POST['user_id'] ?? 0);

if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

// Verify the user exists
$stmt = $conn->prepare("SELECT id, account_status FROM users WHERE id = ?");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Check if user is already suspended
if ($user['account_status'] === 'suspended') {
    echo json_encode(['success' => false, 'message' => 'User is already suspended']);
    exit;
}

// Update user account status to suspended
$stmt = $conn->prepare("UPDATE users SET account_status = 'suspended' WHERE id = ?");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'User account suspended successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to suspend user: ' . $stmt->error]);
    $stmt->close();
}

$conn->close();
?>
