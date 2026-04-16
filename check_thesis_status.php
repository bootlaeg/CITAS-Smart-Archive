<?php
require 'db_includes/db_connect.php';

$result = $conn->query("SELECT id, title, is_journal_converted, journal_conversion_status, journal_page_count, journal_file_path, file_path FROM thesis WHERE id = 72 LIMIT 1");
$thesis = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($thesis, JSON_PRETTY_PRINT);
?>
