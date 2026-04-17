<?php
require_once 'db_includes/db_connect.php';

echo "<h2>Chatbot Access Requests Debug</h2>";

$result = $conn->query("
    SELECT car.id, car.user_id, car.thesis_id, car.requested_at, car.status,
           u.full_name, 
           t.title
    FROM chatbot_access_requests car
    LEFT JOIN users u ON car.user_id = u.id
    LEFT JOIN thesis t ON car.thesis_id = t.id
    WHERE car.status = 'pending'
    LIMIT 5
");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Thesis ID</th><th>User Name</th><th>Thesis Title</th><th>Status</th><th>Requested At</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . ($row['user_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['thesis_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['full_name'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['title'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['requested_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No pending requests found</p>";
}

$conn->close();
?>
