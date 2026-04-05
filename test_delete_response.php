<?php
session_start();
require 'db_includes/db_connect.php';

// Get test admin user
$result = $conn->query("SELECT id FROM users WHERE user_role = 'admin' LIMIT 1");
if ($result->num_rows === 0) {
    echo "No admin user found\n";
    exit;
}
$admin = $result->fetch_assoc();
$_SESSION['user_id'] = $admin['id'];
$_SESSION['user_role'] = 'admin';

// Get a test thesis
$result = $conn->query("SELECT id FROM thesis LIMIT 1");
if ($result->num_rows === 0) {
    echo "No thesis found\n";
    exit;
}
$thesis = $result->fetch_assoc();
$thesis_id = $thesis['id'];

echo "Testing delete response for thesis ID: $thesis_id\n\n";

// Simulate the delete request
$_POST['thesis_id'] = $thesis_id;
$_SERVER['REQUEST_METHOD'] = 'POST';

// Output what would be sent
ob_start();
include 'admin_includes/admin_delete_thesis.php';
$response = ob_get_clean();

echo "Raw response:\n";
echo "---\n";
echo $response;
echo "\n---\n\n";

// Try to parse it
$json = json_decode($response, true);
if ($json) {
    echo "✓ Valid JSON\n";
    echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
    echo "Message: " . $json['message'] . "\n";
} else {
    echo "✗ Invalid JSON\n";
    echo "Error: " . json_last_error_msg() . "\n";
}

$conn->close();
?>
