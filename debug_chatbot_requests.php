<?php
/**
 * Debug Chatbot Access Requests
 * Check what's in the database
 */

require_once 'db_includes/db_connect.php';

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'chatbot_access_requests'");
$table_exists = $result->num_rows > 0;

echo "<h2>Chatbot Access Requests Table Debug</h2>";
echo "<p>Table exists: " . ($table_exists ? "YES" : "NO") . "</p>";

if (!$table_exists) {
    echo "<p style='color: red;'>Table does not exist. Please run init_chatbot_table.php first.</p>";
    echo "<a href='chatbot_includes/init_chatbot_table.php'>Initialize Table</a>";
} else {
    // Get all pending requests
    echo "<h3>All Pending Requests:</h3>";
    
    $pending = $conn->query("SELECT * FROM chatbot_access_requests WHERE status = 'pending'");
    
    if ($pending->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Thesis ID</th><th>Status</th><th>Requested At</th></tr>";
        while ($row = $pending->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . $row['thesis_id'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . $row['requested_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No pending requests in database</p>";
    }
    
    // Test the JOIN query that admin uses
    echo "<h3>Testing Admin Query with JOIN:</h3>";
    $test_query = $conn->query("
        SELECT car.id, car.user_id, car.thesis_id, car.requested_at,
               u.full_name, u.student_id,
               t.title
        FROM chatbot_access_requests car
        JOIN users u ON car.user_id = u.id
        JOIN thesis t ON car.thesis_id = t.id
        WHERE car.status = 'pending'
        ORDER BY car.requested_at DESC
        LIMIT 20
    ");
    
    if ($test_query->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr>";
        foreach ($test_query->fetch_assoc() as $col => $val) {
            echo "<th>$col</th>";
        }
        echo "</tr>";
        
        $test_query = $conn->query("
            SELECT car.id, car.user_id, car.thesis_id, car.requested_at,
                   u.full_name, u.student_id,
                   t.title
            FROM chatbot_access_requests car
            JOIN users u ON car.user_id = u.id
            JOIN thesis t ON car.thesis_id = t.id
            WHERE car.status = 'pending'
            ORDER BY car.requested_at DESC
            LIMIT 20
        ");
        
        while ($row = $test_query->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $col => $val) {
                echo "<td>" . htmlspecialchars($val) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>Query returned no results</p>";
    }
}

$conn->close();
?>
