<?php
/**
 * Initialize Chatbot Sessions Tables
 * Creates tables for persistent session storage with 5-session limit
 */

require_once 'db_includes/db_connect.php';

echo "=== CREATING CHATBOT TABLES ===\n\n";

$sql1 = "CREATE TABLE IF NOT EXISTS chatbot_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    thesis_id INT NOT NULL,
    session_name VARCHAR(255) NOT NULL,
    message_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (thesis_id) REFERENCES thesis(id) ON DELETE CASCADE,
    INDEX idx_user_thesis (user_id, thesis_id)
)";

$sql2 = "CREATE TABLE IF NOT EXISTS chatbot_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    user_message TEXT NOT NULL,
    bot_response TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES chatbot_sessions(id) ON DELETE CASCADE,
    INDEX idx_session (session_id)
)";

if ($conn->query($sql1)) {
    echo "✅ chatbot_sessions table created/exists\n";
} else {
    echo "❌ Error creating chatbot_sessions: " . $conn->error . "\n";
}

if ($conn->query($sql2)) {
    echo "✅ chatbot_messages table created/exists\n";
} else {
    echo "❌ Error creating chatbot_messages: " . $conn->error . "\n";
}

echo "\n=== SETUP COMPLETE ===\n";
echo "Max sessions per user: 5\n";
echo "When limit exceeded: User must delete oldest session\n";

$conn->close();
?>
