<?php
/**
 * Test PDF extraction via DocumentParser (used by keyword extraction)
 */

require_once 'ai_includes/document_parser.php';

$file = 'C:/Users/aki/Desktop/Placeholder/NeuroGuard.pdf';

echo "=== TESTING DOCUMENT PARSER WITH PDF ===\n\n";

$result = DocumentParser::extractText($file);

if ($result['success']) {
    echo "✅ Extraction successful!\n";
    echo "Text length: " . strlen($result['text']) . " bytes\n";
    echo "First 300 chars:\n";
    echo substr($result['text'], 0, 300) . "\n";
} else {
    echo "❌ Extraction failed!\n";
    echo "Error: " . $result['error'] . "\n";
}

?>
