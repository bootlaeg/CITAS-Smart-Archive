<?php
/**
 * Initialize Chatbot Access Requests Table
 * Creates the necessary database table for chatbot access control
 */

require_once __DIR__ . '/../db_includes/db_connect.php';

// Check if chatbot_access_requests table exists
$result = $conn->query("SHOW TABLES LIKE 'chatbot_access_requests'");

if ($result->num_rows === 0) {
    // Table doesn't exist, create it
    $create_table = "
    CREATE TABLE chatbot_access_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        thesis_id INT NOT NULL,
        status ENUM('pending', 'approved', 'denied') DEFAULT 'pending',
        requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        approved_at TIMESTAMP NULL,
        approved_by INT NULL,
        denial_reason VARCHAR(255) NULL,
        denied_at TIMESTAMP NULL,
        denied_by INT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (thesis_id) REFERENCES thesis(id) ON DELETE CASCADE,
        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (denied_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user_thesis (user_id, thesis_id),
        INDEX idx_status (status),
        INDEX idx_requested_at (requested_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    if ($conn->query($create_table)) {
        echo json_encode(['success' => true, 'message' => 'Chatbot access requests table created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create table: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => true, 'message' => 'Chatbot access requests table already exists']);
}

$conn->close();
?>
