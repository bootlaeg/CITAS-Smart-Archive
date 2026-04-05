<?php
session_start();
require 'db_includes/db_connect.php';
require 'client_includes/create_notification.php';

echo "=== Testing Thesis Deletion ===\n\n";

// Get a thesis ID to test
$result = $conn->query("SELECT id, title FROM thesis LIMIT 1");
if ($result->num_rows === 0) {
    echo "No thesis found to test delete\n";
    exit;
}

$thesis = $result->fetch_assoc();
$thesis_id = $thesis['id'];
$thesis_title = $thesis['title'];

echo "Testing delete of thesis ID: $thesis_id - Title: $thesis_title\n\n";

// Test 1: Get thesis info
echo "TEST 1: Fetching thesis info...\n";
$stmt = $conn->prepare("SELECT file_path, title FROM thesis WHERE id = ?");
if (!$stmt) {
    echo "  ERROR: Failed to prepare - " . $conn->error . "\n";
    exit;
}
$stmt->bind_param("i", $thesis_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $thesis = $result->fetch_assoc();
    echo "  ✓ Fetched: " . $thesis['title'] . "\n";
} else {
    echo "  ✗ Thesis not found\n";
}
$stmt->close();

// Test 2: Get all users except admin
echo "\nTEST 2: Getting all non-admin users...\n";
try {
    $user_ids = get_all_users_except_admin();
    echo "  ✓ Found " . count($user_ids) . " users\n";
} catch (Exception $e) {
    echo "  ✗ ERROR: " . $e->getMessage() . "\n";
}

// Test 3: Try create_bulk_notification
echo "\nTEST 3: Testing notification creation...\n";
try {
    if (!empty($user_ids)) {
        $test_count = create_bulk_notification(
            $user_ids,
            'test',
            'Test Notification',
            'This is a test'
        );
        echo "  ✓ Created " . $test_count . " test notifications\n";
    } else {
        echo "  ⚠ No users to notify\n";
    }
} catch (Exception $e) {
    echo "  ✗ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== All tests completed ===\n";
$conn->close();
?>
