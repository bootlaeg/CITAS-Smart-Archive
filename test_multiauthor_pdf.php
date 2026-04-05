<?php
/**
 * Test Multi-Author PDF specifically
 */

require_once 'ai_includes/document_parser.php';
require_once 'ai_includes/keyword_analyzer.php';

$pdfPath = 'C:\\Users\\aki\\Desktop\\New folder\\New folder (2)\\Placeholder\\Design of Web-Based Student Academic Information System.pdf';

echo "=== TESTING: Design of Web-Based Student Academic Information System.pdf ===\n\n";

// Extract text
$extractResult = DocumentParser::extractText($pdfPath);
if (!$extractResult['success']) {
    echo "ERROR: Failed to extract text\n";
    exit;
}

$fullText = $extractResult['text'];

// Extract author
$author = KeywordAnalyzer::extractAuthorFromText($fullText);

echo "Authors Extracted: " . ($author ?: '(empty)') . "\n";
echo "Length: " . strlen($author ?? '') . " chars\n\n";

// Expected
$expected = "Andi Muh Reza B Makkaraka, Akbar Iskandar, Wang Yang";
if (stripos($author, "Andi Muh Reza B Makkaraka") !== false && 
    stripos($author, "Akbar Iskandar") !== false && 
    stripos($author, "Wang Yang") !== false) {
    echo "✅ SUCCESS: All 3 authors found!\n";
} else {
    echo "Current extraction: " . $author . "\n";
    echo "Expected: $expected\n";
}
?>
