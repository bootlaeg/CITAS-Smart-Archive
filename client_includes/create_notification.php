<?php
/**
 * Create Notification Helper
 * Centralized function to create notifications for various events
 */

require_once __DIR__ . '/../db_includes/db_connect.php';

/**
 * Create notification for a user
 * @param int $user_id - User to notify
 * @param string $type - Notification type (e.g., 'thesis_upload', 'thesis_deleted', 'access_approved')
 * @param string $title - Notification title
 * @param string $message - Notification message
 * @param int $thesis_id - Optional: Related thesis ID
 * @return bool - Success or failure
 */
function create_notification($user_id, $type, $title, $message, $thesis_id = null) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, type, title, message, thesis_id, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, FALSE, NOW())
        ");
        
        if (!$stmt) {
            error_log("Failed to prepare notification statement: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("isssi", $user_id, $type, $title, $message, $thesis_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            error_log("Failed to create notification: " . $stmt->error);
            $stmt->close();
            return false;
        }
    } catch (Exception $e) {
        error_log("Exception creating notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Create notifications for multiple users
 * @param array $user_ids - Array of user IDs to notify
 * @param string $type - Notification type
 * @param string $title - Notification title
 * @param string $message - Notification message
 * @param int $thesis_id - Optional: Related thesis ID
 * @return int - Number of notifications created
 */
function create_bulk_notification($user_ids, $type, $title, $message, $thesis_id = null) {
    $count = 0;
    if (!is_array($user_ids) || empty($user_ids)) {
        return 0;
    }
    
    foreach ($user_ids as $user_id) {
        if (create_notification($user_id, $type, $title, $message, $thesis_id)) {
            $count++;
        }
    }
    
    return $count;
}

/**
 * Get all non-admin users (to notify about new approved thesis)
 * @return array - Array of user IDs
 */
function get_all_users_except_admin() {
    global $conn;
    
    $result = $conn->query("SELECT id FROM users WHERE user_role != 'admin' AND account_status = 'active'");
    
    $user_ids = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $user_ids[] = $row['id'];
        }
    }
    
    return $user_ids;
}

/**
 * Get admin user ID
 * @return int - Admin user ID or 0
 */
function get_admin_user_id() {
    global $conn;
    
    $result = $conn->query("SELECT id FROM users WHERE user_role = 'admin' LIMIT 1");
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['id'];
    }
    
    return 0;
}

/**
 * Get thesis title by ID
 * @param int $thesis_id
 * @return string - Thesis title or empty string
 */
function get_thesis_title($thesis_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT title FROM thesis WHERE id = ?");
    $stmt->bind_param("i", $thesis_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['title'];
    }
    
    $stmt->close();
    return '';
}

/**
 * Get user info by ID
 * @param int $user_id
 * @return array - User info or empty array
 */
function get_user_info($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row;
    }
    
    $stmt->close();
    return [];
}

?>
