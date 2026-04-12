<?php
// Update thesis 66 file path in the remote Hostinger database
require 'db_includes/db_connect.php';

// The correct file that exists
$correct_file = 'uploads/thesis_files/thesis_693d4f783fd75.pdf';
$thesis_id = 66;

error_log("=== Updating Thesis 66 ===");

// Check current value
$check = $conn->query("SELECT id, file_path FROM thesis WHERE id = $thesis_id");
if ($check && $check->num_rows > 0) {
    $row = $check->fetch_assoc();
    error_log("Current file path: " . $row['file_path']);
} else {
    error_log("Thesis 66 not found in database!");
}

// Update with the actual file
$update_sql = "UPDATE thesis SET file_path = '" . $conn->real_escape_string($correct_file) . "' WHERE id = $thesis_id";
if ($conn->query($update_sql)) {
    error_log("Database update successful! Rows affected: " . $conn->affected_rows);
} else {
    error_log("Database update failed: " . $conn->error);
}

// Verify
$verify = $conn->query("SELECT id, file_path FROM thesis WHERE id = $thesis_id");
if ($verify && $verify->num_rows > 0) {
    $row = $verify->fetch_assoc();
    error_log("New file path: " . $row['file_path']);
}

http_response_code(200);
echo "Update completed - check error logs";
$conn->close();
?>
