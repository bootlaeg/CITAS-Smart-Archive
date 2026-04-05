<?php
/**
 * Registration Test Page
 * Diagnostic tool to verify registration system
 */

require_once 'db_includes/db_connect.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Registration Test</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .test { margin: 15px 0; padding: 10px; border: 1px solid #ccc; }
        .pass { background-color: #d4edda; color: #155724; }
        .fail { background-color: #f8d7da; color: #721c24; }
        .info { background-color: #d1ecf1; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Registration System Diagnostic</h1>
    <hr>";

// Test 1: Database Connection
echo "<div class='test info'><h3>1. Database Connection</h3>";
if ($conn->connect_error) {
    echo "<div class='fail'><strong>❌ FAILED:</strong> " . $conn->connect_error . "</div>";
} else {
    echo "<div class='pass'><strong>✓ SUCCESS:</strong> Connected to database</div>";
    echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
    echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
}
echo "</div>";

// Test 2: Check Users Table
echo "<div class='test info'><h3>2. Users Table Structure</h3>";
$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "<div class='pass'><strong>✓ SUCCESS:</strong> Users table exists</div>";
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? '-') . "</td>";
        echo "<td>" . ($row['Extra'] ?? '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='fail'><strong>❌ FAILED:</strong> " . $conn->error . "</div>";
}
echo "</div>";

// Test 3: Count existing users
echo "<div class='test info'><h3>3. Existing Users</h3>";
$count_result = $conn->query("SELECT COUNT(*) as total FROM users");
if ($count_result) {
    $count = $count_result->fetch_assoc()['total'];
    echo "<div class='pass'><strong>Total Users in Database:</strong> " . $count . "</div>";
} else {
    echo "<div class='fail'><strong>❌ FAILED:</strong> " . $conn->error . "</div>";
}
echo "</div>";

// Test 4: Test INSERT (simulate registration)
echo "<div class='test info'><h3>4. Test INSERT Statement</h3>";
$test_full_name = 'Test User ' . time();
$test_email = 'test' . time() . '@example.com';
$test_student_id = 'TEST' . time();
$test_address = '123 Test Street';
$test_contact = '09123456789';
$test_course = 'BSIT';
$test_year = '1st Year';
$test_password = password_hash('password123', PASSWORD_DEFAULT);

$test_stmt = $conn->prepare("INSERT INTO users (full_name, email, student_id, address, contact_number, course, year_level, password, account_status, user_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 'student')");

if (!$test_stmt) {
    echo "<div class='fail'><strong>❌ FAILED:</strong> Could not prepare statement - " . $conn->error . "</div>";
} else {
    $bind_result = $test_stmt->bind_param("ssssssss", $test_full_name, $test_email, $test_student_id, $test_address, $test_contact, $test_course, $test_year, $test_password);
    
    if (!$bind_result) {
        echo "<div class='fail'><strong>❌ FAILED:</strong> Could not bind parameters - " . $test_stmt->error . "</div>";
    } else {
        if ($test_stmt->execute()) {
            echo "<div class='pass'><strong>✓ SUCCESS:</strong> Test INSERT executed successfully</div>";
            echo "<p><strong>New User ID:</strong> " . $test_stmt->insert_id . "</p>";
            echo "<p><strong>Test Email:</strong> " . $test_email . "</p>";
            echo "<p><em>This test user was created to verify the registration system is working. You can delete it from the database if needed.</em></p>";
        } else {
            echo "<div class='fail'><strong>❌ FAILED:</strong> Could not execute INSERT - " . $test_stmt->error . "</div>";
        }
    }
    $test_stmt->close();
}
echo "</div>";

// Test 5: Display last 5 users
echo "<div class='test info'><h3>5. Last 5 Registered Users</h3>";
$users_result = $conn->query("SELECT id, full_name, email, student_id, account_status, created_at FROM users ORDER BY id DESC LIMIT 5");
if ($users_result && $users_result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Full Name</th><th>Email</th><th>Student ID</th><th>Status</th><th>Created At</th></tr>";
    while ($row = $users_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
        echo "<td>" . $row['account_status'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='info'><strong>No users found in database</strong></div>";
}
echo "</div>";

// Test 6: Check sanitize_input function
echo "<div class='test info'><h3>6. Helper Functions</h3>";
if (function_exists('sanitize_input')) {
    echo "<div class='pass'><strong>✓ SUCCESS:</strong> sanitize_input() function exists</div>";
} else {
    echo "<div class='fail'><strong>❌ FAILED:</strong> sanitize_input() function not found</div>";
}

if (function_exists('is_logged_in')) {
    echo "<div class='pass'><strong>✓ SUCCESS:</strong> is_logged_in() function exists</div>";
} else {
    echo "<div class='fail'><strong>❌ FAILED:</strong> is_logged_in() function not found</div>";
}
echo "</div>";

// Test 7: Check error logs
echo "<div class='test info'><h3>7. Error Log Location</h3>";
echo "<p><strong>PHP Error Log:</strong> " . ini_get('error_log') . "</p>";
echo "<p>Check this file for detailed registration errors and debugging information.</p>";
echo "</div>";

echo "<hr>
    <p><a href='index.php'>← Back to Home</a></p>
</body>
</html>";

$conn->close();
?>
