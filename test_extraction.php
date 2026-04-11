<?php
/**
 * Manual Metadata Extraction Test with DETAILED DEBUG
 */

require_once __DIR__ . '/ai_includes/DocumentMetadataExtractor.php';

$test_file = isset($argv[1]) ? $argv[1] : 'C:/Users/aki/Desktop/Placeholder/NeuroGuard.docx';

if (!file_exists($test_file)) {
    echo "Error: File not found: $test_file\n";
    exit(1);
}

echo "=== DETAILED DEBUG ===\n\n";

// Manual extraction
$zip = new ZipArchive();
if ($zip->open($test_file) !== true) {
    echo "Failed to open DOCX ZIP\n";
    exit(1);
}

$xml_content = $zip->getFromName('word/document.xml');

if (!$xml_content) {
    echo "No document.xml\n";
    exit(1);
}

echo "XML size: " . strlen($xml_content) . " bytes\n";
echo "First 500 chars of XML:\n";
echo substr($xml_content, 0, 500) . "\n\n";

// Try all extraction methods
echo "=== METHOD 1: Regex for <w:t> tags ===\n";
if (preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/u', $xml_content, $matches)) {
    echo "Found " . count($matches[1]) . " text elements\n";
    $text = implode(' ', $matches[1]);
    echo "Preview: " . substr($text, 0, 200) . "\n";
} else {
    echo "No <w:t> tags found\n";
}

echo "\n=== METHOD 2: Parse XML with namespace ===\n";
libxml_use_internal_errors(true);

$dom = new DOMDocument();
if (@$dom->loadXML($xml_content)) {
    echo "XML parsed successfully\n";
    
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    
    $text_nodes = $xpath->query('//w:t');
    echo "Found " . $text_nodes->length . " w:t nodes\n";
    
    if ($text_nodes->length > 0) {
        $text = '';
        foreach ($text_nodes as $node) {
            $text .= $node->nodeValue . ' ';
        }
        echo "Preview: " . substr($text, 0, 200) . "\n";
    }
} else {
    echo "Failed to parse XML\n";
    echo "Errors: " . print_r(libxml_get_errors(), true) . "\n";
}

libxml_use_internal_errors(false);

// Now run full extraction
echo "\n=== FULL EXTRACTION ===\n";
$metadata = DocumentMetadataExtractor::extract($test_file, 'docx');

echo "Title: " . (empty($metadata['title']) ? '(EMPTY)' : $metadata['title']) . "\n";
echo "Authors: " . (empty($metadata['authors']) ? '(EMPTY)' : $metadata['authors']) . "\n";
echo "Year: " . (empty($metadata['year']) ? '(EMPTY)' : $metadata['year']) . "\n";
echo "Abstract: " . substr($metadata['abstract'] ?? '', 0, 150) . "...\n";

$zip->close();

?>


