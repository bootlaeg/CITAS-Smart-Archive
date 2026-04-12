<?php
/**
 * User Login Handler
 * CITAS Smart Archive System
 */

require_once __DIR__ . '/../db_includes/db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set response header to JSON
header('Content-Type: application/json');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get form data
$student_id = '';
$password = '';

if (isset($_POST['student_id']) && isset($_POST['password'])) {
    $student_id = sanitize_input($_POST['student_id']);
    $password = $_POST['password'];
} else {
    // Try to get from raw input
    $raw_input = file_get_contents('php://input');
    error_log("Raw input: " . $raw_input);
    
    parse_str($raw_input, $parsed_data);
    if (isset($parsed_data['student_id']) && isset($parsed_data['password'])) {
        $student_id = sanitize_input($parsed_data['student_id']);
        $password = $parsed_data['password'];
    }
}

// Validation
if (empty($student_id)) {
    error_log("Login failed: Account ID is empty");
    echo json_encode(['success' => false, 'message' => 'Account ID is required']);
    exit();
}

if (empty($password)) {
    error_log("Login failed: Password is empty");
    echo json_encode(['success' => false, 'message' => 'Password is required']);
    exit();
}

error_log("Attempting login for Account ID: " . $student_id);

// Prepare and execute query
$stmt = $conn->prepare("SELECT id, full_name, email, student_id, password, account_status, user_role FROM users WHERE student_id = ?");
if (!$stmt) {
    error_log("Login failed: Prepare statement error - " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    exit();
}

$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("Login failed: Account ID not found - " . $student_id);
    echo json_encode(['success' => false, 'message' => 'Invalid ID or Password']);
    $stmt->close();
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

error_log("User found: " . $user['full_name'] . " - Verifying password...");

// Verify password
if (!password_verify($password, $user['password'])) {
    error_log("Login failed: Invalid password for Account ID - " . $student_id);
    echo json_encode(['success' => false, 'message' => 'Invalid ID or Password']);
    exit();
}

error_log("Password verified successfully");

// Check account status - only suspended accounts are blocked from login
// Note: Pending accounts no longer exist as all new registrations are automatically activated

if ($user['account_status'] === 'suspended') {
    error_log("Login failed: Account suspended - " . $student_id);
    echo json_encode(['success' => false, 'message' => 'Your account has been suspended. Please contact the administrator.']);
    exit();
}

// Set session variables
$_SESSION['user_id'] = $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email'] = $user['email'];
$_SESSION['student_id'] = $user['student_id'];
$_SESSION['user_role'] = $user['user_role'];
$_SESSION['logged_in'] = true;

error_log("Login successful for: " . $user['full_name'] . " (Role: " . $user['user_role'] . ")");

$redirect_url = 'index.php';

echo json_encode([
    'success' => true, 
    'message' => 'Login successful! Redirecting...',
    'redirect' => $redirect_url,
    'user' => [
        'name' => $user['full_name'],
        'role' => $user['user_role']
    ]
]);

$conn->close();
?>
