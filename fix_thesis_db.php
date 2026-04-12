<?php
// Direct database update script
require 'db_includes/db_connect.php';

$log = array();
$log[] = "=== Database Update Script Started ===";

// The correct file that exists
$correct_file = 'uploads/thesis_files/thesis_693d4f783fd75.pdf';
$thesis_id = 66;

// Check current value
$check = $conn->query("SELECT file_path FROM thesis WHERE id = $thesis_id");
if ($check && $check->num_rows > 0) {
    $row = $check->fetch_assoc();
    $log[] = "Current file path in DB: " . $row['file_path'];
}

// Update the database
$update_sql = "UPDATE thesis SET file_path = '" . $conn->real_escape_string($correct_file) . "' WHERE id = $thesis_id";
$log[] = "Executing: " . $update_sql;

if ($conn->query($update_sql)) {
    $log[] = "UPDATE SUCCESS - Rows affected: " . $conn->affected_rows;
    
    // Verify the update
    $verify = $conn->query("SELECT file_path FROM thesis WHERE id = $thesis_id");
    if ($verify && $verify->num_rows > 0) {
        $row = $verify->fetch_assoc();
        $log[] = "New file path in DB: " . $row['file_path'];
    }
} else {
    $log[] = "UPDATE FAILED: " . $conn->error;
}

// Write log to file
file_put_contents(__DIR__ . '/fix_thesis_db.log', implode("\n", $log) . "\n", FILE_APPEND);

// Also output to screen
header('Content-Type: text/plain');
echo implode("\n", $log);

$conn->close();
?>
