<?php
/**
 * Test Author Extraction - DOCX File
 */

require_once 'ai_includes/document_parser.php';
require_once 'ai_includes/keyword_analyzer.php';
require_once 'db_includes/db_connect.php';

$docxPath = 'C:\\Users\\aki\\Desktop\\New folder\\New folder (2)\\Placeholder\\How well can Digital Health and Artificial Intelligence (AI) improve the quality of care as well as reduce medical errors and improve patient safety.docx';

echo "=== TESTING AUTHOR EXTRACTION - DOCX ===\n\n";

// Extract text from DOCX
echo "Extracting text from DOCX...\n";
$extractResult = DocumentParser::extractText($docxPath);

if (!$extractResult['success'] || empty($extractResult['text'])) {
    echo "ERROR: Could not extract text from DOCX\n";
    exit;
}

$fullText = $extractResult['text'];
echo "✓ Text extracted. Length: " . strlen($fullText) . " characters\n\n";

// Show first 2000 characters
echo "--- First 2000 characters of DOCX ---\n";
echo substr($fullText, 0, 2000);
echo "\n\n--- End of preview ---\n\n";

// Test author extraction
echo "Testing KeywordAnalyzer::extractAuthorFromText()...\n";
$extractedAuthor = KeywordAnalyzer::extractAuthorFromText($fullText);
echo "✓ Extracted Author: " . ($extractedAuthor ?: '(empty)') . "\n\n";

// Search for author names
echo "Searching for 'Chou Kue' in DOCX...\n";
if (stripos($fullText, 'Chou') !== false) {
    echo "✓ 'Chou' found\n";
} else {
    echo "✗ 'Chou' NOT found\n";
}
?>
