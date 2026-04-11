<?php
require_once 'ai_includes/DocumentMetadataExtractor.php';

$pdf_file = 'C:/Users/aki/Desktop/Placeholder/NeuroGuard.pdf';

echo "=== TESTING PDF METADATA EXTRACTION ===\n";
echo "File: " . basename($pdf_file) . "\n\n";

$metadata = DocumentMetadataExtractor::extract($pdf_file, 'pdf');

echo "Title: " . (isset($metadata['title']) ? substr($metadata['title'], 0, 80) : 'NOT FOUND') . "\n";
echo "Authors: " . (isset($metadata['authors']) ? $metadata['authors'] : 'NOT FOUND') . "\n";
echo "Year: " . ($metadata['year'] ?? 'NOT FOUND') . "\n";
echo "Page Count: " . ($metadata['page_count'] ?? 'NOT FOUND') . "\n";
echo "Abstract Length: " . strlen($metadata['abstract'] ?? '') . " characters\n";

if (!empty($metadata['abstract'])) {
    echo "\nAbstract (first 200 chars):\n";
    echo substr($metadata['abstract'], 0, 200) . "...\n";
}

echo "\n=== FULL METADATA ===\n";
echo json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
