<?php
/**
 * Debug: Check if Pattern 0A matches for first file
 */

require_once 'ai_includes/document_parser.php';

$pdfPath = 'C:\\Users\\aki\\Desktop\\New folder\\New folder (2)\\Placeholder\\How well can Digital Health and Artificial Intelligence (AI) improve the quality of care as well as reduce medical errors and improve patient safety.pdf';

$extractResult = DocumentParser::extractText($pdfPath);
$fullText = $extractResult['text'];
$lines = explode("\n", $fullText);
$beginningText = implode("\n", array_slice($lines, 0, 50));

echo "=== First File: Pattern 0A Test ===\n\n";
echo "First 500 chars of beginningText:\n";
echo substr($beginningText, 0, 500) . "\n\n";

$authorMatch = [];
if (preg_match('/authors?\s*:\s*(.+?)(?:affiliations?|journal|abstract|introduction|keywords)/is', $beginningText, $authorMatch)) {
    echo "✓ Pattern 0A MATCHED\n";
    echo "Captured (200 chars): " . substr($authorMatch[1], 0, 200) . "\n";
} else {
    echo "✗ Pattern 0A did NOT match\n";
    echo "Will fall through to other patterns\n";
}
?>
