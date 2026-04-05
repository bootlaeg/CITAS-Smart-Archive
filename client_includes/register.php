<?php
/**
 * User Registration Handler
 * Citas Smart Archive System
 */

require_once __DIR__ . '/../db_includes/db_connect.php';

header('Content-Type: application/json');

// Enable error logging
error_log("=== Registration Request Started ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get form data
$full_name = sanitize_input($_POST['full_name'] ?? '');
$email = sanitize_input($_POST['email'] ?? '');
$student_id = sanitize_input($_POST['student_id'] ?? '');
$user_role = strtolower(sanitize_input($_POST['user_role'] ?? 'student'));
$address = sanitize_input($_POST['address'] ?? '');
$contact_number = sanitize_input($_POST['contact_number'] ?? '');
$course = sanitize_input($_POST['course'] ?? '');
$year_level = sanitize_input($_POST['year_level'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

error_log("Form Data Received: Name=$full_name, Email=$email, StudentID=$student_id, Role=$user_role");

// Validation
$errors = [];
$allowed_roles = ['student', 'instructor', 'admin'];

if (!in_array($user_role, $allowed_roles, true)) {
    $errors[] = 'Invalid account type selected';
}

// Required field checks
if (empty($full_name)) {
    $errors[] = 'Full name is required';
} elseif (strlen($full_name) < 3) {
    $errors[] = 'Full name must be at least 3 characters';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if (empty($student_id)) {
    $errors[] = 'Student ID is required';
} elseif (strlen($student_id) < 3) {
    $errors[] = 'Student ID is too short';
}

if ($user_role !== 'admin') {
    if (empty($address)) {
        $errors[] = 'Address is required';
    } elseif (strlen($address) < 5) {
        $errors[] = 'Address is too short';
    }

    if (empty($contact_number)) {
        $errors[] = 'Contact number is required';
    } elseif (!preg_match('/^[0-9\-\+\(\)\s]+$/', $contact_number)) {
        $errors[] = 'Invalid contact number format';
    }

    if (empty($course)) {
        $errors[] = 'Course is required';
    }

    if (empty($year_level)) {
        $errors[] = 'Year level is required';
    }
}

if (empty($password)) {
    $errors[] = 'Password is required';
} elseif (strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters';
}

if (empty($confirm_password)) {
    $errors[] = 'Please confirm your password';
} elseif ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match';
}

// If validation errors exist, return them
if (!empty($errors)) {
    error_log("Validation Errors: " . implode(", ", $errors));
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

// Handle instructor verification file upload
$loadsheet_file = '';
if ($user_role === 'instructor') {
    if (!isset($_FILES['loadsheet_file']) || $_FILES['loadsheet_file']['size'] <= 0) {
        $errors[] = 'Instructor loadsheet verification is required';
    }
}

if (!empty($errors)) {
    error_log("Validation Errors: " . implode(", ", $errors));
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

if ($user_role === 'instructor' && isset($_FILES['loadsheet_file']) && $_FILES['loadsheet_file']['size'] > 0) {
    error_log("Processing instructor loadsheet file upload...");
    
    $file = $_FILES['loadsheet_file'];
    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $max_file_size = 10 * 1024 * 1024; // 10MB
    
    // Get file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file
    if (!in_array($file_extension, $allowed_extensions)) {
        error_log("Invalid file type: $file_extension");
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, JPG, JPEG, and PNG are allowed']);
        exit();
    }
    
    if ($file['size'] > $max_file_size) {
        error_log("File too large: " . $file['size'] . " bytes");
        echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 10MB']);
        exit();
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log("File upload error: " . $file['error']);
        echo json_encode(['success' => false, 'message' => 'File upload error']);
        exit();
    }
    
    // Create uploads directory if it doesn't exist
    $uploads_dir = __DIR__ . '/../uploads/Loadsheet';
    if (!is_dir($uploads_dir)) {
        if (!mkdir($uploads_dir, 0755, true)) {
            error_log("Failed to create uploads directory: $uploads_dir");
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
            exit();
        }
        error_log("Created uploads directory: $uploads_dir");
    }
    
    // Generate unique filename
    $filename = 'instructor_loadsheet_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_extension;
    $filepath = $uploads_dir . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        error_log("Failed to move uploaded file to: $filepath");
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        exit();
    }
    
    $loadsheet_file = 'uploads/Loadsheet/' . $filename;
    error_log("File uploaded successfully: $loadsheet_file");
}

// Check if student ID already exists
error_log("Checking if Student ID exists: $student_id");
$check_stmt = $conn->prepare("SELECT id FROM users WHERE student_id = ?");

if (!$check_stmt) {
    error_log("Prepare Error (student_id check): " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again later.']);
    exit();
}

$check_stmt->bind_param("s", $student_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    error_log("Student ID already exists: $student_id");
    echo json_encode(['success' => false, 'message' => 'Student ID already registered']);
    $check_stmt->close();
    exit();
}
$check_stmt->close();

// Check if email already exists
error_log("Checking if Email exists: $email");
$email_check = $conn->prepare("SELECT id FROM users WHERE email = ?");

if (!$email_check) {
    error_log("Prepare Error (email check): " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again later.']);
    exit();
}

$email_check->bind_param("s", $email);
$email_check->execute();
$email_result = $email_check->get_result();

if ($email_result->num_rows > 0) {
    error_log("Email already exists: $email");
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    $email_check->close();
    exit();
}
$email_check->close();

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
error_log("Password hashed successfully");

// Insert user
error_log("Attempting to insert new user: $full_name");
$stmt = $conn->prepare("INSERT INTO users (full_name, email, student_id, address, contact_number, course, year_level, password, loadsheet_file, account_status, user_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)");

if (!$stmt) {
    error_log("Prepare Error (insert): " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    $conn->close();
    exit();
}

if (!$stmt->bind_param("ssssssssss", $full_name, $email, $student_id, $address, $contact_number, $course, $year_level, $hashed_password, $loadsheet_file, $user_role)) {
    error_log("Bind Error: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    $stmt->close();
    $conn->close();
    exit();
}

error_log("Executing insert statement...");
if ($stmt->execute()) {
    $new_user_id = $stmt->insert_id;
    error_log("User registered successfully! New User ID: $new_user_id");
    error_log("INSERT affected rows: " . $stmt->affected_rows);
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Account created successfully! You can now log in to your account.',
        'user_id' => $new_user_id
    ]);
} else {
    error_log("Execute Error (insert): " . $stmt->error);
    error_log("Execute Error errno: " . $stmt->errno);
    
    $stmt->close();
    $conn->close();
    
    echo json_encode(['success' => false, 'message' => 'Failed to create account: ' . $stmt->error]);
}

error_log("=== Registration Request Ended ===");;
?>
