<?php
require 'db_includes/db_connect.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>THESIS 72 STATUS CHECK</h2>";

// Check database
$result = $conn->query("SELECT id, title, file_path, is_journal_converted, journal_conversion_status, journal_file_path FROM thesis WHERE id = 72 LIMIT 1");
if ($result && $result->num_rows > 0) {
    $thesis = $result->fetch_assoc();
    echo "<h3>Database Status:</h3>";
    echo "Title: " . htmlspecialchars($thesis['title']) . "<br>";
    echo "File Path: " . htmlspecialchars($thesis['file_path'] ?? 'NULL') . "<br>";
    echo "Is Journal Converted: " . ($thesis['is_journal_converted'] ? 'YES' : 'NO') . "<br>";
    echo "Journal Conversion Status: " . htmlspecialchars($thesis['journal_conversion_status']) . "<br>";
    echo "Journal File Path: " . htmlspecialchars($thesis['journal_file_path'] ?? 'NULL') . "<br>";
    
    // Check if file exists
    echo "<h3>File System Status:</h3>";
    if (!empty($thesis['file_path'])) {
        $full_path = __DIR__ . '/' . $thesis['file_path'];
        echo "Checking: " . htmlspecialchars($full_path) . "<br>";
        if (file_exists($full_path)) {
            echo "✅ Original file EXISTS (" . filesize($full_path) . " bytes)<br>";
        } else {
            echo "❌ Original file NOT FOUND<br>";
        }
    } else {
        echo "❌ No file_path in database<br>";
    }
    
    if (!empty($thesis['journal_file_path'])) {
        $full_path = __DIR__ . '/' . $thesis['journal_file_path'];
        echo "Checking Journal: " . htmlspecialchars($full_path) . "<br>";
        if (file_exists($full_path)) {
            echo "✅ Journal file EXISTS (" . filesize($full_path) . " bytes)<br>";
        } else {
            echo "❌ Journal file NOT FOUND<br>";
        }
    } else {
        echo "❌ No journal_file_path in database<br>";
    }
} else {
    echo "❌ Thesis 72 NOT FOUND in database<br>";
}

// List recent files
echo "<h3>Recent Files in uploads/thesis_files/:</h3>";
$uploads_dir = __DIR__ . '/uploads/thesis_files';
if (is_dir($uploads_dir)) {
    $files = array_diff(scandir($uploads_dir, SCANDIR_SORT_DESCENDING), ['.', '..']);
    $recent = array_slice($files, 0, 5);
    foreach ($recent as $file) {
        $full_path = $uploads_dir . '/' . $file;
        echo htmlspecialchars($file) . " (" . filesize($full_path) . " bytes, " . filemtime($full_path) . ")<br>";
    }
} else {
    echo "❌ uploads/thesis_files directory not found<br>";
}

?>
