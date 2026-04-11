<?php
/**
 * Approve Chatbot Access Request
 * Admin endpoint to approve user chatbot access to a thesis
 */

header('Content-Type: application/json; charset=utf-8');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db_includes/db_connect.php';

// Check if admin
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to approve access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$thesis_id = isset($_POST['thesis_id']) ? intval($_POST['thesis_id']) : 0;

if ($user_id <= 0 || $thesis_id <= 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid parameters',
        'debug' => [
            'request_id' => $request_id,
            'user_id' => $user_id,
            'thesis_id' => $thesis_id,
            'post_data' => $_POST
        ]
    ]);
    exit();
}

// Verify request exists - if request_id is 0, lookup by user_id and thesis_id
if ($request_id > 0) {
    $verify = $conn->prepare("SELECT id, user_id FROM thesis_access WHERE id = ?");
    $verify->bind_param("i", $request_id);
} else {
    // Fallback: lookup by user_id and thesis_id
    $verify = $conn->prepare("SELECT id, user_id FROM thesis_access WHERE user_id = ? AND thesis_id = ? ORDER BY requested_at DESC LIMIT 1");
    $verify->bind_param("ii", $user_id, $thesis_id);
}

$verify->execute();
$result = $verify->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Request not found']);
    $verify->close();
    exit();
}

// Get the actual request_id if we looked it up
$request = $result->fetch_assoc();
$request_id = $request['id'];
$verify->close();

// Update request status
$update = $conn->prepare("
    UPDATE thesis_access 
    SET status = 'approved', 
        approved_at = NOW(), 
        approved_by = ?
    WHERE id = ?
");

$admin_id = $_SESSION['user_id'];
$update->bind_param("ii", $admin_id, $request_id);

if ($update->execute()) {
    // Get user details for notification
    $user_query = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
    $user_query->bind_param("i", $user_id);
    $user_query->execute();
    $user_result = $user_query->get_result()->fetch_assoc();
    $user_query->close();
    
    // Get thesis details
    $thesis_query = $conn->prepare("SELECT title FROM thesis WHERE id = ?");
    $thesis_query->bind_param("i", $thesis_id);
    $thesis_query->execute();
    $thesis_result = $thesis_query->get_result()->fetch_assoc();
    $thesis_query->close();
    
    // Create notification for user
    require_once __DIR__ . '/../client_includes/create_notification.php';
    create_notification(
        $user_id,
        'chatbot_approved',
        'Chatbot Access Approved',
        'Your request for chatbot access to "' . htmlspecialchars($thesis_result['title']) . '" has been approved!',
        $thesis_id
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Chatbot access approved successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $update->error]);
}

$update->close();
$conn->close();
?>
