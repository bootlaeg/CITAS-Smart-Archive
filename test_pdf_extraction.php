<?php
require_once 'ai_includes/DocumentMetadataExtractor.php';

$file = 'C:/Users/aki/Desktop/Placeholder/NeuroGuard.pdf';

if (!file_exists($file)) {
    echo "File not found: $file\n";
    exit(1);
}

echo "=== TESTING PDF EXTRACTION ===\n";
echo "File: " . basename($file) . "\n";
echo "File size: " . filesize($file) . " bytes\n\n";

$metadata = DocumentMetadataExtractor::extract($file, 'pdf');

echo "=== EXTRACTION RESULTS ===\n";
echo "Title: " . (isset($metadata['title']) ? substr($metadata['title'], 0, 80) : 'NOT FOUND') . "\n";
echo "Authors: " . (isset($metadata['authors']) ? substr($metadata['authors'], 0, 80) : 'NOT FOUND') . "\n";
echo "Year: " . ($metadata['year'] ?? 'NOT FOUND') . "\n";
echo "Page Count: " . ($metadata['page_count'] ?? 'NOT FOUND') . "\n";
echo "Abstract Length: " . strlen($metadata['abstract'] ?? '') . " characters\n";

if (!empty($metadata['error'])) {
    echo "\n❌ ERROR: " . $metadata['error'] . "\n";
}

echo "\n=== DEBUGGING ===\n";
echo "Testing PDF structure...\n";

$content = file_get_contents($file);
echo "Raw file size: " . strlen($content) . " bytes\n";

// Check for compressed streams
if (preg_match_all('/stream.*?FlateDecode/s', $content, $matches)) {
    echo "Found " . count($matches[0]) . " FlateDecode compressed streams\n";
}

// Try to extract raw text before decompression
if (preg_match_all('/\(([^()]{1,200})\)/', $content, $text_matches)) {
    echo "Found " . count($text_matches[0]) . " text strings\n";
    echo "First 10 strings:\n";
    for ($i = 0; $i < min(10, count($text_matches[1])); $i++) {
        echo "  " . trim(substr($text_matches[1][$i], 0, 70)) . "\n";
    }
}

?>

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
