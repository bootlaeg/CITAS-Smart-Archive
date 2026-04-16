<?php
require 'db_includes/db_connect.php';

// Get latest theses
$result = $conn->query("SELECT id, title, file_path, is_journal_converted, journal_conversion_status FROM thesis ORDER BY created_at DESC LIMIT 5");

header('Content-Type: text/plain');
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}\n";
    echo "Title: {$row['title']}\n";
    echo "File Path: {$row['file_path']}\n";
    echo "Is Journal Converted: {$row['is_journal_converted']}\n";
    echo "Conversion Status: {$row['journal_conversion_status']}\n";
    echo "---\n";
}
?>
