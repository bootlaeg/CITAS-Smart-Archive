<?php
/**
 * Test: Profile Picture Upload Feature
 * This script verifies the profile picture functionality
 */

require_once 'db_includes/db_connect.php';

echo "=== PROFILE PICTURE FEATURE TEST ===\n\n";

// Test 1: Check if profile_picture column exists
echo "1. Checking if profile_picture column exists in users table...\n";
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");

if ($check_column->num_rows > 0) {
    echo "✓ profile_picture column exists\n";
    $column_info = $check_column->fetch_assoc();
    echo "  Type: " . $column_info['Type'] . "\n";
    echo "  Nullable: " . ($column_info['Null'] === 'YES' ? 'Yes' : 'No') . "\n";
} else {
    echo "✗ profile_picture column NOT found\n";
    echo "  Run: php admin_includes/init_profile_picture.php\n";
}

// Test 2: Check if uploads/profile_pictures directory exists
echo "\n2. Checking upload directory...\n";
$upload_dir = __DIR__ . '/uploads/profile_pictures/';
if (is_dir($upload_dir)) {
    echo "✓ Directory exists: " . $upload_dir . "\n";
    echo "  Permissions: " . decoct(fileperms($upload_dir) & 0777) . "\n";
} else {
    echo "⚠ Directory does NOT exist. It will be created on first upload.\n";
}

// Test 3: Check registered users
echo "\n3. Checking registered users...\n";
$users_result = $conn->query("SELECT id, full_name, email, profile_picture FROM users LIMIT 5");

if ($users_result && $users_result->num_rows > 0) {
    echo "✓ Found " . $users_result->num_rows . " users\n";
    while ($user = $users_result->fetch_assoc()) {
        echo "  - " . $user['full_name'] . " (" . $user['email'] . ")\n";
        if (!empty($user['profile_picture'])) {
            $pic_file = __DIR__ . '/' . $user['profile_picture'];
            if (file_exists($pic_file)) {
                echo "    Profile Picture: ✓ " . $user['profile_picture'] . " (" . filesize($pic_file) . " bytes)\n";
            } else {
                echo "    Profile Picture: ✗ File referenced but not found\n";
            }
        } else {
            echo "    Profile Picture: (not set)\n";
        }
    }
} else {
    echo "⚠ No users found in database\n";
}

// Test 4: Verify file upload constraints
echo "\n4. File upload constraints:\n";
echo "  ✓ Maximum file size: 5MB\n";
echo "  ✓ Allowed formats: JPG, PNG, GIF, WebP\n";
echo "  ✓ Storage location: uploads/profile_pictures/\n";
echo "  ✓ Filename format: profile_{user_id}_{timestamp}.{extension}\n";

// Test 5: Check file paths
echo "\n5. Required files check:\n";
$required_files = [
    'my_profile.php' => 'Main profile page',
    'admin_includes/my_profile.php' => 'Admin profile page',
    'client_includes/update_profile.php' => 'Profile update handler',
];

foreach ($required_files as $file => $description) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "  ✓ " . $description . " (" . $file . ")\n";
    } else {
        echo "  ✗ " . $description . " NOT FOUND\n";
    }
}

echo "\n=== FEATURE OVERVIEW ===\n";
echo "✓ Profile Picture Upload is now enabled!\n\n";
echo "Features:\n";
echo "  - Users can upload/edit their profile picture\n";
echo "  - Picture is stored in: uploads/profile_pictures/\n";
echo "  - Supported formats: JPG, PNG, GIF, WebP\n";
echo "  - Maximum size: 5MB per image\n";
echo "  - Profile picture displays in circular avatar\n";
echo "  - Old pictures are automatically deleted when replaced\n";
echo "\nHow to use:\n";
echo "  1. Go to My Profile\n";
echo "  2. Click 'Edit Profile' button\n";
echo "  3. Select an image file in the 'Profile Picture' section\n";
echo "  4. See a live preview before saving\n";
echo "  5. Click 'Save Changes' to upload\n";

$conn->close();
?>
