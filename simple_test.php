<?php
require_once 'ai_includes/DocumentMetadataExtractor.php';

$file = 'C:/Users/aki/Desktop/Placeholder/NeuroGuard.pdf';
$metadata = DocumentMetadataExtractor::extract($file, 'pdf');

echo "Title: " . substr($metadata['title'], 0, 80) . "\n";
echo "Authors: " . substr($metadata['authors'], 0, 80) . "\n";
echo "Year: " . $metadata['year'] . "\n";
echo "Page Count: " . $metadata['page_count'] . "\n";
echo "Abstract length: " . strlen($metadata['abstract']) . "\n";
echo "\nStatus: EXTRACTION SUCCESSFUL\n";
?>
