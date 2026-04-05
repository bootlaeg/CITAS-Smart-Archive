<?php
/**
 * Initialize Notifications Table
 * Ensures all necessary columns exist for the enhanced notification system
 */

require_once 'db_includes/db_connect.php';

echo "Initializing Notifications Table...\n\n";

// Check if notifications table exists
$check_table = $conn->query("SHOW TABLES LIKE 'notifications'");

if ($check_table->num_rows === 0) {
    echo "Creating notifications table...\n";
    
    $create_sql = "
    CREATE TABLE notifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        thesis_id INT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (thesis_id) REFERENCES thesis(id) ON DELETE CASCADE,
        INDEX (user_id),
        INDEX (is_read),
        INDEX (created_at)
    )
    ";
    
    if ($conn->query($create_sql)) {
        echo "✓ Notifications table created successfully\n\n";
    } else {
        echo "✗ Failed to create notifications table: " . $conn->error . "\n\n";
    }
} else {
    echo "✓ Notifications table already exists\n\n";
    
    // Check for missing columns
    echo "Checking for necessary columns...\n";
    
    $columns_to_check = [
        'id' => 'INT',
        'user_id' => 'INT',
        'type' => 'VARCHAR(50)',
        'title' => 'VARCHAR(255)',
        'message' => 'TEXT',
        'thesis_id' => 'INT',
        'is_read' => 'BOOLEAN',
        'created_at' => 'TIMESTAMP'
    ];
    
    $columns_result = $conn->query("SHOW COLUMNS FROM notifications");
    $existing_columns = [];
    
    while ($col = $columns_result->fetch_assoc()) {
        $existing_columns[] = $col['Field'];
    }
    
    $columns_added = 0;
    
    // Check and add thesis_id column if missing
    if (!in_array('thesis_id', $existing_columns)) {
        echo "  Adding missing 'thesis_id' column...\n";
        $alter_sql = "ALTER TABLE notifications ADD COLUMN thesis_id INT NULL AFTER message";
        if ($conn->query($alter_sql)) {
            echo "  ✓ thesis_id column added\n";
            $columns_added++;
        } else {
            echo "  ✗ Failed to add thesis_id column: " . $conn->error . "\n";
        }
    }
    
    // Check and add type column if missing
    if (!in_array('type', $existing_columns)) {
        echo "  Adding missing 'type' column...\n";
        $alter_sql = "ALTER TABLE notifications ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'general' AFTER user_id";
        if ($conn->query($alter_sql)) {
            echo "  ✓ type column added\n";
            $columns_added++;
        } else {
            echo "  ✗ Failed to add type column: " . $conn->error . "\n";
        }
    }
    
    // Check and add created_at column if missing
    if (!in_array('created_at', $existing_columns)) {
        echo "  Adding missing 'created_at' column...\n";
        $alter_sql = "ALTER TABLE notifications ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER is_read";
        if ($conn->query($alter_sql)) {
            echo "  ✓ created_at column added\n";
            $columns_added++;
        } else {
            echo "  ✗ Failed to add created_at column: " . $conn->error . "\n";
        }
    }
    
    if ($columns_added === 0) {
        echo "  ✓ All necessary columns exist\n\n";
    } else {
        echo "  ✓ Added $columns_added missing column(s)\n\n";
    }
}

echo "=== NOTIFICATION SYSTEM READY ===\n";
echo "Enhanced notifications are now active:\n";
echo "  • New thesis notifications when approved\n";
echo "  • Deletion notifications when thesis is removed\n";
echo "  • Access request notifications to admin\n";
echo "  • Access approval notifications to requester\n";

$conn->close();
?>
