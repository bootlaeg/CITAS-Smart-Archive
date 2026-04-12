<?php
// Check thesis 66 database record
require 'db_includes/db_connect.php';

header('Content-Type: application/json');

$result = $conn->query("SELECT id, title, file_path, file_type FROM thesis WHERE id = 66 LIMIT 1");

if ($result && $result->num_rows > 0) {
    $thesis = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'thesis' => $thesis,
        'database_file_path' => $thesis['file_path'],
        'actual_files' => scandir(__DIR__ . '/uploads/thesis_files/')
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode(['success' => false, 'message' => 'No thesis found']);
}

$conn->close();
?>
