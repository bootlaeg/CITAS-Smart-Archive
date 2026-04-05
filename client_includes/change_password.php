<?php
/**
 * Change Password Handler
 */

require_once __DIR__ . '/../db_includes/db_connect.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
$errors = [];
if (empty($current_password)) $errors[] = 'Current password is required';
if (empty($new_password)) $errors[] = 'New password is required';
if (empty($confirm_password)) $errors[] = 'Confirm password is required';

if ($new_password !== $confirm_password) {
    $errors[] = 'New passwords do not match';
}

if (strlen($new_password) < 6) {
    $errors[] = 'New password must be at least 6 characters long';
}

if ($current_password === $new_password) {
    $errors[] = 'New password cannot be the same as current password';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

// Get current password hash from database
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    $stmt->close();
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Verify current password
if (!password_verify($current_password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit();
}

// Hash new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update password
$update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$update_stmt->bind_param("si", $hashed_password, $user_id);

if ($update_stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Password changed successfully!'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to change password: ' . $update_stmt->error]);
}

$update_stmt->close();
$conn->close();
?>
