<?php
/**
 * Debug: Check actual character codes
 */

require_once 'ai_includes/document_parser.php';

$pdfPath = 'C:\\Users\\aki\\Desktop\\New folder\\New folder (2)\\Placeholder\\Design of Web-Based Student Academic Information System.pdf';

$extractResult = DocumentParser::extractText($pdfPath);
$fullText = $extractResult['text'];
$lines = explode("\n", $fullText);
$beginningText = implode("\n", array_slice($lines, 0, 5));

// Get the authors line
preg_match('/authors?\s*:\s*(.+?)(?:affiliations?)/is', $beginningText, $matches);
$authorStr = $matches[1] ?? '';

echo "=== Character Analysis ===\n\n";
echo "Raw string length: " . strlen($authorStr) . "\n";
echo "Raw bytes: ";
for ($i = 0; $i < min(strlen($authorStr), 150); $i++) {
    $char = $authorStr[$i];
    $code = ord($char);
    if ($code > 127 || $code < 32) {
        echo "[0x" . dechex($code) . "]";
    } else {
        echo $char;
    }
}
echo "\n\n";

// Try splitting by comma first
echo "Split by comma:\n";
$parts = explode(',', $authorStr);
foreach ($parts as $i => $part) {
    echo "Part $i: \"" . trim($part) . "\" (len=" . strlen(trim($part)) . ")\n";
}
?>
