<?php
/**
 * Test the full metadata extraction endpoint response
 */
header('Content-Type: application/json');

require_once 'DocumentMetadataExtractor.php';

// Simulate file upload
$test_file = $_GET['file'] ?? 'C:/Users/aki/Desktop/Placeholder/NeuroGuard_BSIT.pdf';

if (!file_exists($test_file)) {
    echo json_encode(['error' => 'File not found']);
    exit;
}

$metadata = DocumentMetadataExtractor::extract($test_file, 'pdf');

echo json_encode([
    'file' => basename($test_file),
    'extraction_result' => [
        'title' => $metadata['title'],
        'authors' => $metadata['authors'],
        'degree' => $metadata['degree'],
        'year' => $metadata['year'],
        'page_count' => $metadata['page_count'],
        'abstract_length' => strlen($metadata['abstract'])
    ],
    'endpoint_response_format' => [
        'success' => true,
        'data' => [
            'title' => $metadata['title'],
            'author' => $metadata['authors'],
            'year' => $metadata['year'],
            'degree' => $metadata['degree'],
            'abstract' => substr($metadata['abstract'], 0, 100) . '...',
            'page_count' => $metadata['page_count']
        ]
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

?>
