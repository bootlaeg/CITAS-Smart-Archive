<?php
require 'db_includes/db_connect.php';

echo "=== Checking Notifications Table ===\n\n";

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($result->num_rows > 0) {
    echo "✓ Table EXISTS\n\n";
    
    // Get table structure
    $cols = $conn->query("DESCRIBE notifications");
    echo "Table Structure:\n";
    while ($col = $cols->fetch_assoc()) {
        echo "  " . $col['Field'] . " - " . $col['Type'] . " (" . $col['Null'] . ")\n";
    }
    
    // Check if we can insert a test record
    echo "\n=== Testing Notification Insert ===\n";
    $test_stmt = $conn->prepare("
        INSERT INTO notifications (user_id, type, title, message, is_read, created_at) 
        VALUES (?, ?, ?, ?, FALSE, NOW())
    ");
    
    if (!$test_stmt) {
        echo "ERROR preparing statement: " . $conn->error . "\n";
    } else {
        $test_user = 1;
        $test_type = "test";
        $test_title = "Test Notification";
        $test_msg = "This is a test message";
        
        $test_stmt->bind_param("isss", $test_user, $test_type, $test_title, $test_msg);
        
        if ($test_stmt->execute()) {
            echo "✓ Test insert successful - ID: " . $test_stmt->insert_id . "\n";
            $test_stmt->close();
            
            // Delete test record
            $conn->query("DELETE FROM notifications WHERE id = " . $test_stmt->insert_id);
        } else {
            echo "ERROR executing: " . $test_stmt->error . "\n";
        }
    }
} else {
    echo "✗ Table DOES NOT EXIST\n";
    echo "Run: http://localhost/ctrws-fix/init_notifications.php\n";
}

$conn->close();
?>
