<?php
/**
 * Quick Check: What's stored in the database vs file system?
 */

require_once 'db_includes/db_connect.php';

echo "=== PROFILE PICTURE STORAGE VERIFICATION ===\n\n";

// Check database structure
echo "1. DATABASE STRUCTURE\n";
echo str_repeat("─", 50) . "\n";

$result = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
if ($row = $result->fetch_assoc()) {
    echo "Column name: " . $row['Field'] . "\n";
    echo "Data type: " . $row['Type'] . "\n";
    echo "Nullable: " . $row['Null'] . "\n";
    echo "Default: " . ($row['Default'] ?? 'NULL') . "\n\n";
} else {
    echo "⚠ profile_picture column NOT found\n\n";
}

// Check what's stored in database
echo "2. DATABASE RECORDS\n";
echo str_repeat("─", 50) . "\n";

$result = $conn->query("SELECT id, full_name, email, profile_picture FROM users");

if ($result->num_rows > 0) {
    echo "Found " . $result->num_rows . " users:\n\n";
    while ($user = $result->fetch_assoc()) {
        echo "User ID: " . $user['id'] . "\n";
        echo "Name: " . $user['full_name'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Database stores: ";
        
        if (empty($user['profile_picture'])) {
            echo "(not set)\n";
        } else {
            echo "\"" . $user['profile_picture'] . "\"\n";
            
            // Check if file exists
            $file_path = __DIR__ . '/' . $user['profile_picture'];
            if (file_exists($file_path)) {
                $file_size = filesize($file_path);
                $file_size_kb = round($file_size / 1024, 2);
                echo "File exists: ✓ YES (" . $file_size_kb . " KB)\n";
            } else {
                echo "File exists: ✗ NO (referenced but not found)\n";
            }
        }
        echo "\n";
    }
} else {
    echo "No users in database\n\n";
}

// Check file system
echo "3. FILE SYSTEM STORAGE\n";
echo str_repeat("─", 50) . "\n";

$upload_dir = __DIR__ . '/uploads/profile_pictures/';
if (is_dir($upload_dir)) {
    echo "Upload directory: ✓ EXISTS\n";
    echo "Location: " . $upload_dir . "\n";
    
    $files = scandir($upload_dir);
    $image_files = array_diff($files, ['.', '..']);
    
    if (!empty($image_files)) {
        echo "Files stored: " . count($image_files) . "\n\n";
        foreach ($image_files as $file) {
            if (is_file($upload_dir . $file)) {
                $file_size = filesize($upload_dir . $file);
                $file_size_kb = round($file_size / 1024, 2);
                echo "  - " . $file . " (" . $file_size_kb . " KB)\n";
            }
        }
    } else {
        echo "No files currently stored\n";
    }
} else {
    echo "Upload directory: ✗ DOES NOT EXIST\n";
    echo "It will be created on first upload\n";
}

echo "\n4. SUMMARY\n";
echo str_repeat("─", 50) . "\n";

$db_count = $conn->query("
    SELECT COUNT(*) as count 
    FROM users 
    WHERE profile_picture IS NOT NULL AND profile_picture != ''
")->fetch_assoc()['count'];

echo "Users with profile pictures in database: " . $db_count . "\n";

if (is_dir($upload_dir)) {
    $files = array_diff(scandir($upload_dir), ['.', '..']);
    $file_count = count(array_filter($files, function($f) use ($upload_dir) {
        return is_file($upload_dir . $f);
    }));
    echo "Image files on server: " . $file_count . "\n";
} else {
    echo "Image files on server: 0 (directory doesn't exist yet)\n";
}

echo "\n5. WHAT'S STORED WHERE?\n";
echo str_repeat("─", 50) . "\n";
echo "DATABASE (users table):\n";
echo "  Column: profile_picture\n";
echo "  Stores: FILE PATH only (text, 255 chars max)\n";
echo "  Example: \"uploads/profile_pictures/profile_1_1704067200.jpg\"\n";
echo "  NOT the actual image data!\n\n";

echo "FILE SYSTEM (server disk):\n";
echo "  Location: uploads/profile_pictures/\n";
echo "  Stores: ACTUAL IMAGE FILES\n";
echo "  Example: profile_1_1704067200.jpg (actual image data)\n\n";

echo "HOW IT WORKS:\n";
echo "  1. Image file saved to: uploads/profile_pictures/\n";
echo "  2. File path saved to database\n";
echo "  3. PHP retrieves path from database\n";
echo "  4. Browser loads image from file system\n";
echo "  When you deploy to Hostinger, this works exactly the same!\n";

$conn->close();
?>
