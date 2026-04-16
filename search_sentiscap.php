<?php
require 'db_includes/db_connect.php';

header('Content-Type: text/plain; charset=utf-8');

$result = $conn->query("SELECT id, title, file_path, created_at, updated_at FROM thesis WHERE title LIKE '%SentiScape%' OR title LIKE '%sentiscap%' ORDER BY id DESC");

if ($result && $result->num_rows > 0) {
    echo "Found theses with 'SentiScape':\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}\n";
        echo "Title: {$row['title']}\n";
        echo "File Path: {$row['file_path']}\n";
        echo "Created: {$row['created_at']}\n";
        echo "Updated: {$row['updated_at']}\n";
        echo "---\n";
    }
} else {
    echo "No SentiScape theses found\n";
}

// Also check latest 5 theses
echo "\n===============================\nLatest 5 theses:\n";
$result2 = $conn->query("SELECT id, title, file_path, created_at FROM thesis ORDER BY id DESC LIMIT 5");
while ($row = $result2->fetch_assoc()) {
    echo "[ID: {$row['id']}] {$row['title']} | File: {$row['file_path']} | Created: {$row['created_at']}\n";
}
?>
