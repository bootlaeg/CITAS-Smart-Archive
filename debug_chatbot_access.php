<?php
session_start();
require_once 'db_includes/db_connect.php';

$user_id = $_SESSION['user_id'] ?? 1;
$thesis_id = $_GET['thesis_id'] ?? 2;

echo "<h2>Debug: Chatbot Access Check</h2>";
echo "<p><strong>User ID:</strong> $user_id</p>";
echo "<p><strong>Thesis ID:</strong> $thesis_id</p>";

// Check what's in the database
$result = $conn->query("
    SELECT id, user_id, thesis_id, status, requested_at, approved_at, approved_by
    FROM chatbot_access_requests 
    WHERE user_id = $user_id AND thesis_id = $thesis_id
    ORDER BY requested_at DESC
");

echo "<h3>Database Records:</h3>";
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Thesis ID</th><th>Status</th><th>Requested At</th><th>Approved At</th><th>Approved By</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['thesis_id'] . "</td>";
        echo "<td><strong>" . $row['status'] . "</strong></td>";
        echo "<td>" . $row['requested_at'] . "</td>";
        echo "<td>" . ($row['approved_at'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['approved_by'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No records found</p>";
}

// Now test the check_chatbot_access.php response
echo "<h3>API Response from check_chatbot_access.php:</h3>";
$response = file_get_contents("http://" . $_SERVER['HTTP_HOST'] . "/chatbot_includes/check_chatbot_access.php?thesis_id=$thesis_id");
echo "<pre>" . htmlspecialchars($response) . "</pre>";

$conn->close();
?>
