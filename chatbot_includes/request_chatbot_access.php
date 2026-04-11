<?php
/**
 * Request Chatbot Access
 * Initiates access request for chatbot functionality on thesis viewing
 */

header('Content-Type: application/json; charset=utf-8');

// Force error logging to file
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
    echo json_encode(['success' => false, 'message' => 'Database connection error: ' . $e->getMessage()]);
    exit(1);
}

// Check login status before including create_notification
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to request access']);
    exit();
}

require_once __DIR__ . '/create_notification.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$thesis_id = isset($_POST['thesis_id']) ? intval($_POST['thesis_id']) : 0;

if ($thesis_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid thesis ID']);
    exit();
}

// Verify database connection exists
if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if thesis exists
$thesis_check = $conn->prepare("SELECT title FROM thesis WHERE id = ?");
if (!$thesis_check) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

$thesis_check->bind_param("i", $thesis_id);
$thesis_check->execute();
$thesis_result = $thesis_check->get_result();

if ($thesis_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Thesis not found']);
    $thesis_check->close();
    exit();
}

$thesis_title = $thesis_result->fetch_assoc()['title'];
$thesis_check->close();

// Check if chatbot access request already exists and is still pending
$check_stmt = $conn->prepare("
    SELECT id, status FROM chatbot_access_requests 
    WHERE user_id = ? AND thesis_id = ? 
    ORDER BY requested_at DESC LIMIT 1
");
if (!$check_stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

$check_stmt->bind_param("ii", $_SESSION['user_id'], $thesis_id);
$check_stmt->execute();
$existing_request = $check_stmt->get_result()->fetch_assoc();
$check_stmt->close();

// If there's an existing request
if ($existing_request) {
    if ($existing_request['status'] === 'pending') {
        echo json_encode(['success' => false, 'message' => 'You have already requested chatbot access to this thesis. Please wait for admin approval.']);
        exit();
    } else if ($existing_request['status'] === 'approved') {
        echo json_encode(['success' => true, 'message' => 'You already have chatbot access approved.', 'already_approved' => true]);
        exit();
    } else if ($existing_request['status'] === 'denied') {
        // Allow re-requesting after denial
        // Continue to create new request
    }
}

// Insert new chatbot access request
$stmt = $conn->prepare("
    INSERT INTO chatbot_access_requests (user_id, thesis_id, status, requested_at) 
    VALUES (?, ?, 'pending', NOW())
");

if (!$stmt) {
    error_log("Prepare failed for chatbot request insert: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error: failed to prepare statement']);
    exit();
}

$stmt->bind_param("ii", $_SESSION['user_id'], $thesis_id);

error_log("About to insert chatbot access request for user: " . $_SESSION['user_id'] . ", thesis: " . $thesis_id);

if (!$stmt->execute()) {
    error_log("Execute failed for chatbot request insert: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to submit request: ' . $stmt->error]);
    $stmt->close();
    $conn->close();
    exit();
}

error_log("Insert successful for chatbot access request. Rows affected: " . $stmt->affected_rows);
$stmt->close();

// Create notification for admin (non-blocking)
try {
    $admin_id = get_admin_user_id();
    error_log("Admin ID retrieved: " . $admin_id);
    
    if ($admin_id > 0) {
        $user_info = get_user_info($_SESSION['user_id']);
        error_log("User info retrieved: " . json_encode($user_info));
        
        $requester_name = isset($user_info['full_name']) && !empty($user_info['full_name']) ? $user_info['full_name'] : 'A user';
        
        $notification_title = "Chatbot Access Request";
        $notification_message = $requester_name . " has requested chatbot access to \"" . htmlspecialchars($thesis_title) . "\"";
        
        error_log("Creating notification for admin with message: " . $notification_message);
        
        create_notification(
            $admin_id,
            'chatbot_access_request',
            $notification_title,
            $notification_message,
            $thesis_id
        );
        
        error_log("Notification created successfully");
    } else {
        error_log("No admin user found");
    }
} catch (Exception $e) {
    error_log("Notification error (non-blocking): " . $e->getMessage());
}

// Return success
echo json_encode(['success' => true, 'message' => 'Chatbot access request submitted! Please wait for admin approval.']);
$conn->close();
exit();
exit();
?>
