<?php
/**
 * Test the extract_metadata endpoint directly
 */

require_once __DIR__ . '/db_includes/db_connect.php';

// Simulate file upload
if (!file_exists('C:/Users/aki/Desktop/Placeholder/NeuroGuard.docx')) {
    echo "File not found\n";
    exit(1);
}

$_FILES['file'] = [
    'name' => 'NeuroGuard.docx',
    'tmp_name' => 'C:/Users/aki/Desktop/Placeholder/NeuroGuard.docx',
    'type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'size' => filesize('C:/Users/aki/Desktop/Placeholder/NeuroGuard.docx'),
    'error' => 0
];

// Call the endpoint
include 'ai_includes/extract_metadata.php';

?>
