<?php
require_once 'ai_includes/DocumentMetadataExtractor.php';

$file = 'C:/Users/aki/Desktop/Placeholder/NeuroGuard.docx';

echo "=== TESTING PAGE COUNT EXTRACTION ===\n";
echo "File: " . basename($file) . "\n\n";

$metadata = DocumentMetadataExtractor::extract($file, 'docx');

echo "Title: " . (isset($metadata['title']) ? substr($metadata['title'], 0, 70) : 'NOT FOUND') . "\n";
echo "Authors: " . (isset($metadata['authors']) ? $metadata['authors'] : 'NOT FOUND') . "\n";
echo "Year: " . ($metadata['year'] ?? 'NOT FOUND') . "\n";
echo "Page Count: " . ($metadata['page_count'] ?? 'NOT FOUND') . "\n";
echo "Abstract Length: " . strlen($metadata['abstract'] ?? '') . " characters\n";

echo "\n=== FULL METADATA ===\n";
echo json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
