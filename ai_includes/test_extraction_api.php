<?php
/**
 * Simple extraction test via POST (simulates browser upload)
 */
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    error_log("[TEST] File received: " . $file['name'] . " Size: " . $file['size']);
    
    // Include extractor
    require_once 'DocumentMetadataExtractor.php';
    
    try {
        $metadata = DocumentMetadataExtractor::extract($file['tmp_name'], 'pdf');
        
        echo json_encode([
            'success' => true,
            'data' => [
                'title' => $metadata['title'],
                'author' => $metadata['authors'],
                'year' => $metadata['year'],
                'abstract' => $metadata['abstract'],
                'page_count' => $metadata['page_count']
            ]
        ]);
    } catch (Exception $e) {
        error_log("[TEST ERROR] " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'No file uploaded'
    ]);
}

?>
