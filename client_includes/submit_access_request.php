<?php
/**
 * Submit Chatbot Access Request
 * Allows users to request access to the chatbot for a specific thesis
 */

session_start();
header('Content-Type: application/json');

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['thesis_id'])) {
        throw new Exception("Missing thesis_id");
    }
    
    $thesis_id = (int)$data['thesis_id'];
    $request_type = $data['request_type'] ?? 'chatbot';
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("You must be logged in to request access");
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Connect to database
    require_once __DIR__ . '/../db_includes/db_connect.php';
    
    // Check if thesis exists
    $thesis_check = $conn->query("SELECT id FROM thesis WHERE id = $thesis_id");
    if (!$thesis_check || $thesis_check->num_rows === 0) {
        throw new Exception("Thesis not found");
    }
    
    // Check if user already has access
    $access_check = $conn->query("
        SELECT id FROM chatbot_access_requests 
        WHERE user_id = $user_id AND thesis_id = $thesis_id AND status = 'approved'
    ");
    
    if ($access_check && $access_check->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'You already have access to this thesis'
        ]);
        exit;
    }
    
    // Check if request already pending
    $pending_check = $conn->query("
        SELECT id FROM chatbot_access_requests 
        WHERE user_id = $user_id AND thesis_id = $thesis_id AND status = 'pending'
    ");
    
    if ($pending_check && $pending_check->num_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Your request is already pending'
        ]);
        exit;
    }
    
    // Create new access request
    $insert_sql = "
        INSERT INTO chatbot_access_requests 
        (user_id, thesis_id, request_reason, status, requested_at) 
        VALUES (?, ?, ?, 'pending', NOW())
    ";
    
    $stmt = $conn->prepare($insert_sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $reason = "User requested access to view chatbot analysis for thesis " . $thesis_id;
    $stmt->bind_param("iis", $user_id, $thesis_id, $reason);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to submit request: " . $stmt->error);
    }
    
    $inserted_id = $conn->insert_id;
    error_log("Inserted access request with ID: $inserted_id for user $user_id on thesis $thesis_id");
    
    $stmt->close();
    
    error_log("[access_request] User $user_id requested access to thesis $thesis_id");
    
    echo json_encode([
        'success' => true,
        'message' => 'Access request submitted successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    
    error_log("[access_request] Error: " . $e->getMessage());
}
?>
