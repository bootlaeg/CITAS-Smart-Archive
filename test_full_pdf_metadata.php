<?php
require_once 'ai_includes/DocumentMetadataExtractor.php';

$file = 'C:/Users/aki/Desktop/Placeholder/NeuroGuard.pdf';

echo "=== FULL PDF METADATA EXTRACTION ===\n\n";

$metadata = DocumentMetadataExtractor::extract($file, 'pdf');

echo "TITLE:\n" . $metadata['title'] . "\n\n";

echo "AUTHORS:\n" . $metadata['authors'] . "\n\n";

echo "YEAR:\n" . $metadata['year'] . "\n\n";

echo "PAGE COUNT:\n" . $metadata['page_count'] . "\n\n";

echo "ABSTRACT (first 300 chars):\n" . substr($metadata['abstract'], 0, 300) . "...\n\n";

echo "=== JSON OUTPUT ===\n";
echo json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

?>
