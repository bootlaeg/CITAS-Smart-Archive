<?php
/**
 * Password Hash Generator
 * Use this file to generate password hashes for your users
 */

echo "<h2>Password Hash Generator</h2>";
echo "<p>Use these hashes to update your database users</p>";
echo "<hr>";

// Generate hash for admin123
$admin_password = "admin123";
$admin_hash = password_hash($admin_password, PASSWORD_DEFAULT);

echo "<h3>Admin Password</h3>";
echo "<strong>Plain text:</strong> admin123<br>";
echo "<strong>Hash:</strong> " . $admin_hash . "<br><br>";

echo "<strong>SQL Update Command:</strong><br>";
echo "<code>UPDATE users SET password = '$admin_hash' WHERE student_id = 'ADMIN001';</code>";
echo "<hr>";

// Generate hash for student123
$student_password = "student123";
$student_hash = password_hash($student_password, PASSWORD_DEFAULT);

echo "<h3>Student Password</h3>";
echo "<strong>Plain text:</strong> student123<br>";
echo "<strong>Hash:</strong> " . $student_hash . "<br><br>";

echo "<strong>SQL Update Commands:</strong><br>";
echo "<code>UPDATE users SET password = '$student_hash' WHERE student_id = '2020-00001';</code><br>";
echo "<code>UPDATE users SET password = '$student_hash' WHERE student_id = '2021-00002';</code><br>";
echo "<code>UPDATE users SET password = '$student_hash' WHERE student_id = '2022-00003';</code><br>";
echo "<hr>";

// Test verification
echo "<h3>Password Verification Test</h3>";
echo "Testing if 'admin123' matches the hash: ";
echo password_verify("admin123", $admin_hash) ? "<span style='color:green;'>✓ PASS</span>" : "<span style='color:red;'>✗ FAIL</span>";
echo "<br>";

echo "Testing if 'student123' matches the hash: ";
echo password_verify("student123", $student_hash) ? "<span style='color:green;'>✓ PASS</span>" : "<span style='color:red;'>✗ FAIL</span>";

echo "<hr>";
echo "<h3>Instructions:</h3>";
echo "<ol>";
echo "<li>Copy the SQL UPDATE commands above</li>";
echo "<li>Open phpMyAdmin</li>";
echo "<li>Select 'thesis_db' database</li>";
echo "<li>Click on 'SQL' tab</li>";
echo "<li>Paste and run each UPDATE command</li>";
echo "<li>Try logging in again</li>";
echo "</ol>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 900px;
    margin: 50px auto;
    padding: 20px;
    background: #f5f5f5;
}
h2, h3 {
    color: #7c4d00;
}
code {
    background: #fff;
    padding: 10px;
    display: block;
    margin: 10px 0;
    border-left: 4px solid #ff8c00;
    word-wrap: break-word;
}
hr {
    margin: 30px 0;
}
</style>