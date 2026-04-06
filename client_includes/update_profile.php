<?php
/**
 * Update User Profile Handler
 */

require_once __DIR__ . '/../db_includes/db_connect.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = sanitize_input($_POST['full_name'] ?? '');
$email = sanitize_input($_POST['email'] ?? '');
$address = sanitize_input($_POST['address'] ?? '');
$contact_number = sanitize_input($_POST['contact_number'] ?? '');
$course = sanitize_input($_POST['course'] ?? '');
$year_level = sanitize_input($_POST['year_level'] ?? '');
$profile_picture = null;

// Validation
$errors = [];
$warnings = []; // For non-critical errors like file upload

if (empty($full_name)) $errors[] = 'Full name is required';
if (empty($email)) $errors[] = 'Email is required';
if (empty($address)) $errors[] = 'Address is required';
if (empty($contact_number)) $errors[] = 'Contact number is required';
if (empty($course)) $errors[] = 'Course is required';
if (empty($year_level)) $errors[] = 'Year level is required';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

// Handle profile picture upload
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        $warnings[] = 'Profile picture upload error: ' . $_FILES['profile_picture']['error'];
    } else {
        // Validate file type using finfo or fallback to extension check
        $file_type = 'unknown';
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $file_type = @finfo_file($finfo, $_FILES['profile_picture']['tmp_name']);
                @finfo_close($finfo);
            }
        }
        
        // Fallback: Check file extension
        if ($file_type === 'unknown' || !in_array($file_type, $allowed_types)) {
            $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                $warnings[] = 'Invalid profile picture type. Only JPG, PNG, GIF, and WebP are allowed';
            }
        }
        
        // Validate file size (max 5MB)
        if ($_FILES['profile_picture']['size'] > 5 * 1024 * 1024) {
            $warnings[] = 'Profile picture size exceeds 5MB limit';
        }
        
        if (empty($warnings)) {
            // Create uploads directory if it doesn't exist
            $upload_dir = __DIR__ . '/../uploads/profile_pictures/';
            $parent_dir = __DIR__ . '/../uploads/';
            
            // Ensure parent uploads directory exists
            if (!is_dir($parent_dir)) {
                @mkdir($parent_dir, 0777, true);
                @chmod($parent_dir, 0777);
            }
            
            // Ensure profile_pictures subdirectory exists
            if (!is_dir($upload_dir)) {
                if (!@mkdir($upload_dir, 0777, true)) {
                    // Try with different permissions if first attempt fails
                    @mkdir($upload_dir, 0755, true);
                }
                @chmod($upload_dir, 0777);
            }
            
            // Check if directory is writable
            if (!is_writable($upload_dir)) {
                @chmod($upload_dir, 0777);
                if (!is_writable($upload_dir)) {
                    $warnings[] = 'Upload directory is not writable. Profile picture could not be updated.';
                }
            }
            
            if (empty($warnings)) {
                // Generate unique filename
                $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $filename;
                
                // Move uploaded file
                if (@move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                    @chmod($upload_path, 0644);
                    
                    // Delete old profile picture if it exists
                    $old_stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
                    $old_stmt->bind_param("i", $user_id);
                    $old_stmt->execute();
                    $old_result = $old_stmt->get_result()->fetch_assoc();
                    $old_stmt->close();
                    
                    if ($old_result && !empty($old_result['profile_picture'])) {
                        $old_file = __DIR__ . '/../' . $old_result['profile_picture'];
                        if (file_exists($old_file) && strpos($old_result['profile_picture'], 'uploads/profile_pictures/') !== false) {
                            @unlink($old_file);
                        }
                    }
                    
                    // Store relative path for web access
                    $profile_picture = 'uploads/profile_pictures/' . $filename;
                } else {
                    $warnings[] = 'Profile picture could not be uploaded. Other details have been saved.';
                }
            }
        }
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

// Check if email is already used by another user
$check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$check_stmt->bind_param("si", $email, $user_id);
$check_stmt->execute();

if ($check_stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email is already in use by another account']);
    $check_stmt->close();
    exit();
}
$check_stmt->close();

// Update profile
if ($profile_picture) {
    // Update with profile picture
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, address = ?, contact_number = ?, course = ?, year_level = ?, profile_picture = ? WHERE id = ?");
    $stmt->bind_param("sssssssi", $full_name, $email, $address, $contact_number, $course, $year_level, $profile_picture, $user_id);
} else {
    // Update without profile picture
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, address = ?, contact_number = ?, course = ?, year_level = ? WHERE id = ?");
    $stmt->bind_param("ssssssi", $full_name, $email, $address, $contact_number, $course, $year_level, $user_id);
}

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

if ($stmt->execute()) {
    // Update session variables
    $_SESSION['full_name'] = $full_name;
    
    $message = 'Profile updated successfully!';
    
    // Add warnings to message if any
    if (!empty($warnings)) {
        $message .= ' Note: ' . implode(' ', $warnings);
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update profile: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
