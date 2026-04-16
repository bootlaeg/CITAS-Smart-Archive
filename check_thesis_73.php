<?php
require 'db_includes/db_connect.php';

header('Content-Type: text/plain; charset=utf-8');

$thesis_id = 73;

$result = $conn->query("SELECT 
    id, 
    title, 
    file_path, 
    is_journal_converted, 
    journal_conversion_status, 
    journal_file_path, 
    journal_page_count,
    created_at,
    updated_at
FROM thesis WHERE id = $thesis_id");

if ($result && $result->num_rows > 0) {
    $thesis = $result->fetch_assoc();
    echo "=== THESIS $thesis_id STATUS ===\n\n";
    echo "Title: " . ($thesis['title'] ?? 'NULL') . "\n";
    echo "File Path: " . ($thesis['file_path'] ?? 'NULL') . "\n";
    echo "Is Journal Converted: " . ($thesis['is_journal_converted'] ? 'YES (1)' : 'NO (0)') . "\n";
    echo "Journal Conversion Status: " . ($thesis['journal_conversion_status'] ?? 'NULL') . "\n";
    echo "Journal File Path: " . ($thesis['journal_file_path'] ?? 'NULL') . "\n";
    echo "Journal Page Count: " . ($thesis['journal_page_count'] ?? '0') . "\n";
    echo "Created: " . $thesis['created_at'] . "\n";
    echo "Updated: " . $thesis['updated_at'] . "\n";
    
    // Check if file exists
    if (!empty($thesis['file_path'])) {
        $full_path = __DIR__ . '/' . $thesis['file_path'];
        echo "\nFile System Check:\n";
        echo "Expected path: $full_path\n";
        echo "File exists: " . (file_exists($full_path) ? 'YES' : 'NO') . "\n";
        if (file_exists($full_path)) {
            echo "File size: " . filesize($full_path) . " bytes\n";
            echo "File readable: " . (is_readable($full_path) ? 'YES' : 'NO') . "\n";
        }
    }
} else {
    echo "ERROR: Thesis $thesis_id not found\n";
}
?>
