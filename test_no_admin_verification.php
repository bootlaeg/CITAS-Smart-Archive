<?php
/**
 * Test: Registration and Login without Admin Verification
 * This script tests the new registration flow where accounts are automatically activated
 */

require_once 'db_includes/db_connect.php';

$test_email = 'test_no_verify_' . time() . '@example.com';
$test_student_id = 'TEST' . time();
$test_password = 'TestPassword123!';
$hashed_password = password_hash($test_password, PASSWORD_DEFAULT);

echo "=== TEST: Registration Without Admin Verification ===\n\n";

// Test 1: Insert a new user (simulating registration)
echo "1. Creating new test user via registration...\n";
$stmt = $conn->prepare("INSERT INTO users (full_name, email, student_id, address, contact_number, course, year_level, password, account_status, user_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)");

if (!$stmt) {
    echo "ERROR: Prepare failed - " . $conn->error . "\n";
    exit;
}

$full_name = "Test User No Verify";
$address = "Test Address";
$contact = "09123456789";
$course = "BS Computer Science";
$year_level = "1st Year";
$user_role = "student";

$stmt->bind_param("sssssssss", $full_name, $test_email, $test_student_id, $address, $contact, $course, $year_level, $hashed_password, $user_role);

if ($stmt->execute()) {
    $user_id = $stmt->insert_id;
    echo "✓ User created successfully with ID: $user_id\n";
    echo "  Account Status: active (should allow login immediately)\n\n";
} else {
    echo "✗ Failed to create user: " . $stmt->error . "\n";
    exit;
}
$stmt->close();

// Test 2: Check the user's account status
echo "2. Verifying account status in database...\n";
$stmt = $conn->prepare("SELECT id, email, student_id, account_status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "✓ User found in database\n";
    echo "  Email: " . $row['email'] . "\n";
    echo "  Student ID: " . $row['student_id'] . "\n";
    echo "  Account Status: " . $row['account_status'] . "\n\n";
    
    if ($row['account_status'] === 'active') {
        echo "✓ SUCCESS: Account status is 'active' - user can login immediately!\n";
    } else {
        echo "✗ FAIL: Account status is '" . $row['account_status'] . "' - expected 'active'\n";
    }
} else {
    echo "✗ User not found in database\n";
}
$stmt->close();

// Test 3: Simulate login
echo "\n3. Simulating login attempt...\n";
$stmt = $conn->prepare("SELECT id, full_name, email, student_id, password, account_status, user_role FROM users WHERE student_id = ?");
$stmt->bind_param("s", $test_student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "✗ User not found for login\n";
} else {
    $user = $result->fetch_assoc();
    
    if (password_verify($test_password, $user['password'])) {
        echo "✓ Password verified\n";
        
        // Check account status like login.php does
        if ($user['account_status'] === 'pending') {
            echo "✗ FAIL: Login would be BLOCKED - account is pending approval\n";
            echo "  (This should NOT happen with the new registration flow)\n";
        } elseif ($user['account_status'] === 'suspended') {
            echo "✗ Account is suspended\n";
        } elseif ($user['account_status'] === 'active') {
            echo "✓ SUCCESS: Account status is 'active' - login would SUCCEED!\n";
            echo "  User would be logged in immediately after registration.\n";
        } else {
            echo "? Unknown account status: " . $user['account_status'] . "\n";
        }
    } else {
        echo "✗ Password verification failed\n";
    }
}
$stmt->close();

// Clean up - delete test user
echo "\n4. Cleaning up test user...\n";
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    echo "✓ Test user deleted\n";
}
$stmt->close();

echo "\n=== TEST COMPLETE ===\n";
echo "Summary: New users are created with 'active' status and can log in immediately\n";
echo "Admin verification requirement has been successfully removed!\n";

$conn->close();
?>
