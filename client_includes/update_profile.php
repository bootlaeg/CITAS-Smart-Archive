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

    // HANDLE PROFILE PICTURE UPLOAD
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        try {
            $file = $_FILES['profile_picture'];
            
            // Check for file upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File upload error code: ' . $file['error']);
            }
            
            // Validate file size (5MB max)
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception('File size exceeds 5MB limit');
            }
            
            // Validate file extension
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_ext, $allowed_ext)) {
                throw new Exception('Invalid file type');
            }
            
            // Prepare upload directory
            $upload_dir = __DIR__ . '/../uploads/profile_pictures/';
            
            // Create directory if needed
            if (!is_dir($upload_dir)) {
                @mkdir(__DIR__ . '/../uploads/', 0755, true);
                @mkdir($upload_dir, 0755, true);
                @chmod($upload_dir, 0777);
            }
            
            // Generate unique filename
            $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
            $filepath = $upload_dir . $filename;
            
            // Move uploaded file
            if (@move_uploaded_file($file['tmp_name'], $filepath)) {
                @chmod($filepath, 0644);
                
                // Delete old picture if exists
                $check = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
                if ($check) {
                    $check->bind_param("i", $user_id);
                    $check->execute();
                    $result = $check->get_result()->fetch_assoc();
                    $check->close();
                    
                    if ($result && !empty($result['profile_picture'])) {
                        $old_path = __DIR__ . '/../' . $result['profile_picture'];
                        if (file_exists($old_path)) {
                            @unlink($old_path);
                        }
                    }
                }
                
                $profile_picture = 'uploads/profile_pictures/' . $filename;
            } else {
                throw new Exception('Could not move uploaded file to storage');
            }
        } catch (Exception $e) {
            // Log error but don't stop profile update
            error_log('Profile picture upload failed: ' . $e->getMessage());
        }
    }

    // Update profile
    if ($profile_picture) {
        // Update with profile picture
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, address = ?, contact_number = ?, course = ?, year_level = ?, profile_picture = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        $stmt->bind_param("sssssssi", $full_name, $email, $address, $contact_number, $course, $year_level, $profile_picture, $user_id);
    } else {
        // Update without profile picture
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, address = ?, contact_number = ?, course = ?, year_level = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        $stmt->bind_param("ssssssi", $full_name, $email, $address, $contact_number, $course, $year_level, $user_id);
    }

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
