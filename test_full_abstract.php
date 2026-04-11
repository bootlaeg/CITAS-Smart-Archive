<?php
/**
 * Test to show full abstract length
 */

require_once __DIR__ . '/ai_includes/DocumentMetadataExtractor.php';

$test_file = 'C:/Users/aki/Desktop/Placeholder/NeuroGuard.docx';

if (!file_exists($test_file)) {
    echo "File not found: $test_file\n";
    exit(1);
}

$metadata = DocumentMetadataExtractor::extract($test_file, 'docx');

echo "=== FULL METADATA EXTRACTION ===\n\n";
echo "Title: " . $metadata['title'] . "\n";
echo "Authors: " . $metadata['authors'] . "\n";
echo "Year: " . $metadata['year'] . "\n";
echo "\nAbstract Length: " . strlen($metadata['abstract']) . " characters\n";
echo "Abstract Starts at: " . substr($metadata['abstract'], 0, 100) . "...\n";
echo "Abstract Ends at: ..." . substr($metadata['abstract'], -100) . "\n";
echo "\n=== FULL ABSTRACT ===\n";
echo $metadata['abstract'];
echo "\n\n=== END ===\n";

?>
