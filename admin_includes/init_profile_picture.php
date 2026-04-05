<?php
/**
 * Initialize Database - Add Profile Picture Column if Missing
 * Run this once to add the profile_picture column to users table
 */

require_once __DIR__ . '/../db_includes/db_connect.php';

echo "Checking if profile_picture column exists in users table...\n";

try {
    // Check if profile_picture column exists
    $check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
    
    if ($check_column->num_rows === 0) {
        echo "Column not found. Adding profile_picture column...\n";
        // Column doesn't exist, add it
        $alter_query = "ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL AFTER user_role";
        
        if ($conn->query($alter_query)) {
            echo "✓ Profile picture column added successfully!\n";
        } else {
            echo "✗ Failed to add profile_picture column: " . $conn->error . "\n";
        }
    } else {
        echo "✓ Profile picture column already exists\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
