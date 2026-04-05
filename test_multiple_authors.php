<?php
/**
 * Test Author Extraction - Design of Web-Based Student Academic Information System
 */

require_once 'ai_includes/document_parser.php';
require_once 'ai_includes/keyword_analyzer.php';

$pdfPath = 'C:\\Users\\aki\\Desktop\\New folder\\New folder (2)\\Placeholder\\Design of Web-Based Student Academic Information System.pdf';

echo "=== TESTING AUTHOR EXTRACTION - MULTIPLE AUTHORS ===\n\n";

// Extract text from PDF
echo "Extracting text from PDF...\n";
$extractResult = DocumentParser::extractText($pdfPath);

if (!$extractResult['success'] || empty($extractResult['text'])) {
    echo "ERROR: Could not extract text from PDF\n";
    exit;
}

$fullText = $extractResult['text'];
echo "✓ Text extracted. Length: " . strlen($fullText) . " characters\n\n";

// Show first 3000 characters to see what's in it
echo "--- First 3000 characters of PDF ---\n";
echo substr($fullText, 0, 3000);
echo "\n\n--- End of preview ---\n\n";

// Test current author extraction
echo "Testing current KeywordAnalyzer::extractAuthorFromText()...\n";
$extractedAuthor = KeywordAnalyzer::extractAuthorFromText($fullText);
echo "✓ Extracted Author: " . ($extractedAuthor ?: '(empty)') . "\n\n";

// Search for the expected authors
echo "Searching for expected authors in PDF...\n";
$expectedAuthors = ['Andi Muh Reza B Makkaraka', 'Akbar Iskandar', 'Wang Yang'];
foreach ($expectedAuthors as $author) {
    if (stripos($fullText, $author) !== false) {
        echo "✓ Found: '$author'\n";
        // Show context
        $pos = stripos($fullText, $author);
        echo "   Context: " . substr($fullText, max(0, $pos - 100), 200) . "\n";
    } else {
        echo "✗ NOT found: '$author'\n";
    }
}

// Show first 50 lines to find author section
echo "\n--- First 50 lines of PDF (looking for author section) ---\n";
$lines = explode("\n", $fullText);
for ($i = 0; $i < min(50, count($lines)); $i++) {
    echo "Line " . ($i + 1) . ": " . trim($lines[$i]) . "\n";
}
?>
