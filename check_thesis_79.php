<?php
require_once 'ai_includes/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection error: " . $conn->connect_error);
}

echo "<h2>Checking Thesis ID 79</h2>";

// SQL Query to show the thesis data
echo "<h3>SQL Query:</h3>";
$sql = "SELECT id, title, author, file_path, is_journal_converted, journal_file_path, journal_page_count, journal_conversion_status, created_at FROM thesis WHERE id = 79";
echo "<pre>$sql</pre>";

echo "<h3>Database Result:</h3>";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<pre>";
    foreach ($row as $key => $value) {
        echo "$key: " . ($value ?? 'NULL') . "\n";
    }
    echo "</pre>";
} else {
    echo "❌ Thesis ID 79 not found in database<br>";
    
    // Check what thesis IDs exist
    $check_sql = "SELECT id, title FROM thesis ORDER BY id DESC LIMIT 5";
    echo "<h3>Most recent thesis IDs in database:</h3>";
    $check_result = $conn->query($check_sql);
    if ($check_result && $check_result->num_rows > 0) {
        echo "<ul>";
        while ($row = $check_result->fetch_assoc()) {
            echo "<li>ID " . $row['id'] . ": " . $row['title'] . "</li>";
        }
        echo "</ul>";
    }
}

// Check if uploaded file exists
echo "<h3>File Checks:</h3>";
$check_files = [
    "uploads/thesis_files/thesis_69bll3bd139f_1776360125.docx",
    "uploads/thesis_files/thesis_69lllbdl339f_1776360125.docx"
];

foreach ($check_files as $file) {
    $exists = file_exists($file) ? "✅ EXISTS" : "❌ NOT FOUND";
    echo "$file: $exists<br>";
}

$conn->close();
?>