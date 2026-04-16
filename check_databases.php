<?php
// Simple database diagnostic

$conn = new mysqli('localhost', 'root', '');

if ($conn->connect_error) {
    die("❌ Cannot connect to MySQL: " . $conn->connect_error);
}

echo "<h2>✅ Connected to MySQL</h2>";

// List all databases
$sql = "SHOW DATABASES";
$result = $conn->query($sql);

echo "<h3>Available Databases:</h3>";
if ($result) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . $row['Database'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
