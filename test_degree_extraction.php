<?php
require_once 'ai_includes/DocumentMetadataExtractor.php';

$files = [
    'C:/Users/aki/Desktop/Placeholder/NeuroGuard_BSIT.pdf',
    'C:/Users/aki/Desktop/Placeholder/SentiScape_BMMA.pdf',
];

echo "=== TESTING DEGREE EXTRACTION ===\n\n";

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "❌ File not found: $file\n\n";
        continue;
    }
    
    echo "📄 Testing: " . basename($file) . "\n";
    
    $metadata = DocumentMetadataExtractor::extract($file, 'pdf');
    
    echo "   Title: " . substr($metadata['title'], 0, 60) . (strlen($metadata['title']) > 60 ? '...' : '') . "\n";
    echo "   Authors: " . substr($metadata['authors'], 0, 50) . (strlen($metadata['authors']) > 50 ? '...' : '') . "\n";
    echo "   Year: " . $metadata['year'] . "\n";
    echo "   Degree: " . ($metadata['degree'] ?? 'NOT FOUND') . "\n";
    echo "   Page Count: " . ($metadata['page_count'] ?? 'NOT FOUND') . "\n";
    echo "   Abstract: " . strlen($metadata['abstract']) . " chars\n";
    echo "\n";
}

?>
