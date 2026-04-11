<?php
/**
 * Test the extract_metadata.php endpoint to simulate form upload
 */

// Simulate the AJAX request to extract_metadata.php
$_FILES = [
    'document' => [
        'tmp_name' => 'C:/Users/aki/Desktop/Placeholder/NeuroGuard.pdf',
        'name' => 'NeuroGuard.pdf',
        'type' => 'application/pdf',
        'size' => filesize('C:/Users/aki/Desktop/Placeholder/NeuroGuard.pdf'),
        'error' => 0
    ]
];

echo "=== TESTING EXTRACT_METADATA.PHP ENDPOINT ===\n\n";
echo "Simulating AJAX upload of: " . $_FILES['document']['name'] . "\n";
echo "File size: " . $_FILES['document']['size'] . " bytes\n\n";

// Store current directory and include the extraction script
$current_dir = getcwd();
chdir(dirname(__FILE__) . '/ai_includes');

// Include the metadata extractor
require_once 'DocumentMetadataExtractor.php';

// Simulate the endpoint logic
if (isset($_FILES['document'])) {
    $file = $_FILES['document'];
    
    // Determine the file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    // Map MIME type to document type
    $mime_to_type = [
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/msword' => 'doc'
    ];
    
    $doc_type = $mime_to_type[$mime] ?? null;
    
    echo "MIME type: $mime\n";
    echo "Document type: $doc_type\n\n";
    
    if (!$doc_type) {
        echo "❌ Error: Unsupported file type\n";
    } else {
        try {
            // Extract metadata using the DocumentMetadataExtractor
            $metadata = DocumentMetadataExtractor::extract($file['tmp_name'], $doc_type);
            
            echo "✅ Extraction successful!\n\n";
            echo "Extracted metadata:\n";
            echo json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            
            // Check response format
            echo "\n=== RESPONSE FORMAT CHECK ===\n";
            echo "Title present: " . (isset($metadata['title']) && !empty($metadata['title']) ? "✅" : "❌") . "\n";
            echo "Authors present: " . (isset($metadata['authors']) && !empty($metadata['authors']) ? "✅" : "❌") . "\n";
            echo "Year present: " . (isset($metadata['year']) && !empty($metadata['year']) ? "✅" : "❌") . "\n";
            echo "Abstract present: " . (isset($metadata['abstract']) && !empty($metadata['abstract']) ? "✅" : "❌") . "\n";
            echo "Page count present: " . (isset($metadata['page_count']) && !empty($metadata['page_count']) ? "✅" : "❌") . "\n";
            
        } catch (Exception $e) {
            echo "❌ Error during extraction: " . $e->getMessage() . "\n";
        }
    }
}

chdir($current_dir);

?>
