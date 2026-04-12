<?php
require 'db_includes/db_connect.php';

$thesis_id = 66;
$result = $conn->query("SELECT id, title, file_path, file_type FROM thesis WHERE id = $thesis_id");

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    header('Content-Type: text/plain');
    echo "=== Thesis 66 Database Info ===\n";
    echo "ID: " . $row['id'] . "\n";
    echo "Title: " . $row['title'] . "\n";
    echo "File Path: " . $row['file_path'] . "\n";
    echo "File Type: " . $row['file_type'] . "\n";
    echo "\n=== Actual File Check ===\n";
    $full_path = __DIR__ . '/' . $row['file_path'];
    echo "Full Path: " . $full_path . "\n";
    echo "File Exists: " . (file_exists($full_path) ? "YES" : "NO") . "\n";
    
    // Also list what files actually exist
    echo "\n=== Files in uploads/thesis_files ===\n";
    $files = scandir(__DIR__ . '/uploads/thesis_files/');
    foreach ($files as $file) {
        if (!in_array($file, ['.', '..'])) {
            echo $file . "\n";
        }
    }
} else {
    echo "No thesis found";
}
?>
