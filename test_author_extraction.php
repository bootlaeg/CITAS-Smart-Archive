<?php
/**
 * Test Author Extraction
 * Test script to debug author extraction from the specific PDF file
 */

require_once 'ai_includes/document_parser.php';
require_once 'ai_includes/keyword_analyzer.php';
require_once 'db_includes/db_connect.php';

$pdfPath = 'C:\\Users\\aki\\Desktop\\New folder\\New folder (2)\\Placeholder\\How well can Digital Health and Artificial Intelligence (AI) improve the quality of care as well as reduce medical errors and improve patient safety.pdf';

echo "=== TESTING AUTHOR EXTRACTION ===\n\n";

// Step 1: Extract text from PDF
echo "Step 1: Extracting text from PDF...\n";
$extractResult = DocumentParser::extractText($pdfPath);

if (!$extractResult['success'] || empty($extractResult['text'])) {
    echo "ERROR: Could not extract text from PDF\n";
    echo "Error details: " . ($extractResult['error'] ?? 'Unknown error') . "\n";
    exit;
}

$fullText = $extractResult['text'];
echo "✓ Text extracted. Length: " . strlen($fullText) . " characters\n\n";

// Show first 2000 characters to see what's in it
echo "--- First 2000 characters of PDF ---\n";
echo substr($fullText, 0, 2000);
echo "\n\n--- End of preview ---\n\n";

// Step 2: Test author extraction using the KeywordAnalyzer method
echo "Step 2: Testing KeywordAnalyzer::extractAuthorFromText()...\n";
$extractedAuthor = KeywordAnalyzer::extractAuthorFromText($fullText);
echo "✓ Extracted Author: " . ($extractedAuthor ?: '(empty)') . "\n\n";

// Step 3: Show what patterns matched
echo "Step 3: Testing individual extraction patterns...\n";

// Pattern 1: Author: or By: labels
if (preg_match('/(?:author|by):\s*([^\n]+)/i', $fullText, $matches)) {
    echo "✓ Pattern 1 (Author/By label) found: " . trim($matches[1]) . "\n";
} else {
    echo "✗ Pattern 1 (Author/By label) not found\n";
}

// Pattern 2: Superscript numbers
if (preg_match('/([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*(?:\s*[¹²³⁴⁵⁶⁷⁸⁹⁰]+)+)/u', $fullText, $matches)) {
    echo "✓ Pattern 2 (Superscript names) found: " . trim($matches[1]) . "\n";
} else {
    echo "✗ Pattern 2 (Superscript names) not found\n";
}

// Pattern 3: Capitalized names
$lines = explode("\n", $fullText);
foreach (array_slice($lines, 0, 20) as $line) {
    if (preg_match('/^([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)\s*$/', trim($line), $matches)) {
        echo "✓ Pattern 3 (Capitalized name) found in early lines: " . trim($matches[1]) . "\n";
        break;
    }
}

// Step 4: Search for "Chou Kue" specifically
echo "\nStep 4: Searching for 'Chou Kue' in the PDF...\n";
if (stripos($fullText, 'Chou') !== false) {
    echo "✓ 'Chou' found in PDF\n";
    
    // Find context around it
    $pos = stripos($fullText, 'Chou');
    echo "   Context: " . substr($fullText, max(0, $pos - 50), 150) . "\n";
} else {
    echo "✗ 'Chou' NOT found in PDF\n";
}

if (stripos($fullText, 'Kue') !== false) {
    echo "✓ 'Kue' found in PDF\n";
    
    // Find context around it
    $pos = stripos($fullText, 'Kue');
    echo "   Context: " . substr($fullText, max(0, $pos - 50), 150) . "\n";
} else {
    echo "✗ 'Kue' NOT found in PDF\n";
}

// Step 5: Search for "June" (what was extracted)
echo "\nStep 5: Searching for 'June' in the PDF...\n";
if (stripos($fullText, 'June') !== false) {
    echo "✓ 'June' found in PDF\n";
    $pos = stripos($fullText, 'June');
    echo "   Context: " . substr($fullText, max(0, $pos - 100), 200) . "\n";
} else {
    echo "✗ 'June' NOT found in PDF\n";
}

echo "\n";
?>
