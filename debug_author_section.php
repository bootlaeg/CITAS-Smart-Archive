<?php
/**
 * Debug Author Section Extraction
 */

require_once 'ai_includes/document_parser.php';

$pdfPath = 'C:\\Users\\aki\\Desktop\\New folder\\New folder (2)\\Placeholder\\Design of Web-Based Student Academic Information System.pdf';

echo "=== DEBUG: Author Section Extraction ===\n\n";

// Extract text
$extractResult = DocumentParser::extractText($pdfPath);
$fullText = $extractResult['text'];
$lines = explode("\n", $fullText);
$beginningText = implode("\n", array_slice($lines, 0, 50));

echo "--- Raw beginning text (first 1000 chars) ---\n";
echo substr($beginningText, 0, 1000);
echo "\n\n";

// Try the regex
echo "--- Testing regex pattern ---\n";
if (preg_match('/authors?\s*:\s*(.+?)(?:affiliations?|journal|abstract|introduction|keywords)/is', $beginningText, $matches)) {
    echo "✓ Regex matched!\n";
    echo "Captured text (200 chars):\n";
    echo substr($matches[1], 0, 200);
    echo "\n\n";
    
    // Now show what happens after processing
    $authorSection = trim($matches[1]);
    echo "After trim: " . substr($authorSection, 0, 100) . "\n\n";
    
    // Remove special characters
    $authorSection = preg_replace('/[¹²³⁴⁵⁶⁷⁸⁹⁰†‡§¶\*†‡╣▓│┐└├┤─\x80-\xFF]/u', '', $authorSection);
    echo "After removing specials: " . substr($authorSection, 0, 100) . "\n\n";
    
    // Add spaces between camelCase
    $authorSection = preg_replace('/([a-z])([A-Z])/', '$1 $2', $authorSection);
    echo "After adding spaces: " . substr($authorSection, 0, 100) . "\n\n";
    
    // Clean up spaces
    $authorSection = preg_replace('/[\s\n\r]+/', ' ', $authorSection);
    echo "After normalizing spaces: " . substr($authorSection, 0, 100) . "\n";
    echo "Full: " . $authorSection . "\n\n";
    
    // Split by comma
    $authorList = preg_split('/[\,;]+/', $authorSection);
    echo "Split into " . count($authorList) . " parts:\n";
    foreach (array_slice($authorList, 0, 10) as $i => $author) {
        echo "  [$i] \"" . trim($author) . "\"\n";
    }
} else {
    echo "✗ Regex did NOT match\n";
}
?>
