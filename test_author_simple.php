<?php
/**
 * Simple Author Extraction Test - Step 1 Only
 */

require_once 'ai_includes/document_parser.php';
require_once 'ai_includes/keyword_analyzer.php';

$pdfPath = 'C:\\Users\\aki\\Desktop\\New folder\\New folder (2)\\Placeholder\\How well can Digital Health and Artificial Intelligence (AI) improve the quality of care as well as reduce medical errors and improve patient safety.pdf';

echo "=== STEP 1: AUTHOR EXTRACTION TEST ===\n\n";

// Extract text
$extractResult = DocumentParser::extractText($pdfPath);
if (!$extractResult['success']) {
    echo "ERROR: Failed to extract text\n";
    exit;
}

$fullText = $extractResult['text'];

// Extract author
$author = KeywordAnalyzer::extractAuthorFromText($fullText);

echo "PDF File: How well can Digital Health and AI improve patient safety.pdf\n";
echo "Author Extracted: " . ($author ?: '(empty)') . "\n";

// Verify
if ($author === 'Chou Kue') {
    echo "\n✅ SUCCESS: Correctly extracted 'Chou Kue'\n";
} else {
    echo "\n❌ FAILED: Expected 'Chou Kue' but got: '" . ($author ?: 'empty') . "'\n";
}

// Test DOCX as well
echo "\n---\n";
$docxPath = 'C:\\Users\\aki\\Desktop\\New folder\\New folder (2)\\Placeholder\\How well can Digital Health and Artificial Intelligence (AI) improve the quality of care as well as reduce medical errors and improve patient safety.docx';
$extractResult = DocumentParser::extractText($docxPath);

if ($extractResult['success']) {
    $author2 = KeywordAnalyzer::extractAuthorFromText($extractResult['text']);
    echo "\nDOCX File: How well can Digital Health and AI improve patient safety.docx\n";
    echo "Author Extracted: " . ($author2 ?: '(empty)') . "\n";
    
    if ($author2 === 'Chou Kue') {
        echo "\n✅ SUCCESS: Correctly extracted 'Chou Kue'\n";
    } else {
        echo "\n❌ FAILED: Expected 'Chou Kue' but got: '" . ($author2 ?: 'empty') . "'\n";
    }
}
?>
