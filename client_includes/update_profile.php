<?php
/**
 * Update User Profile Handler - Simplified for Shared Hosting
 */

require_once __DIR__ . '/../db_includes/db_connect.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $contact_number = sanitize_input($_POST['contact_number'] ?? '');
    $course = sanitize_input($_POST['course'] ?? '');
    $year_level = sanitize_input($_POST['year_level'] ?? '');
    $profile_picture = null;

    // Basic validation
    if (empty($full_name) || empty($email) || empty($address) || empty($contact_number) || empty($course) || empty($year_level)) {
        throw new Exception('All fields are required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check email uniqueness
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    if (!$check_stmt) {
        throw new Exception('Database error');
    }
    $check_stmt->bind_param("si", $email, $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        throw new Exception('Email is already in use');
    }
    $check_stmt->close();

    // SKIP PROFILE PICTURE UPLOAD ON HOSTINGER
    // Profile picture upload is intentionally disabled for shared hosting compatibility
    
    // Update profile (without picture upload)
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, address = ?, contact_number = ?, course = ?, year_level = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("ssssssi", $full_name, $email, $address, $contact_number, $course, $year_level, $user_id);

    if (!$stmt->execute()) {
        throw new Exception('Failed to update profile');
    }

    $_SESSION['full_name'] = $full_name;
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully!'
    ]);
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    if (isset($conn)) {
        $conn->close();
    }
}
?>
