<?php
/**
 * Debug PDF text extraction
 */

$pdf_file = 'C:/Users/aki/Desktop/Placeholder/NeuroGuard.pdf';

if (!file_exists($pdf_file)) {
    echo "PDF file not found: $pdf_file\n";
    exit(1);
}

$content = file_get_contents($pdf_file);
$size = filesize($pdf_file);

echo "=== PDF FILE DEBUG ===\n";
echo "File: " . basename($pdf_file) . "\n";
echo "Size: " . $size . " bytes\n";
echo "First 500 bytes (hex):\n";
echo bin2hex(substr($content, 0, 500)) . "\n\n";

echo "First 500 chars (text):\n";
$first_500 = substr($content, 0, 500);
// Replace non-printable with dots
$visible = preg_replace('/[^\x20-\x7E\n\r\t]/', '.', $first_500);
echo $visible . "\n\n";

// Check for PDF signature
echo "=== PDF SIGNATURE ===\n";
if (strpos($content, '%PDF') === 0) {
    echo "✓ Valid PDF signature found at start\n";
} else {
    echo "✗ PDF signature NOT found at start\n";
}

// Look for text streams
echo "\n=== TEXT CONTENT ANALYSIS ===\n";

// Count BT...ET blocks (text objects)
preg_match_all('/BT\s+(.+?)\s+ET/s', $content, $matches);
echo "Text blocks (BT...ET): " . count($matches[0]) . "\n";

// Try to extract from stream objects
preg_match_all('/stream\s+(.+?)\s+endstream/s', $content, $stream_matches);
echo "Stream objects: " . count($stream_matches[0]) . "\n";

// Look for text strings in parentheses
preg_match_all('/\(([^()]+)\)/', $content, $string_matches);
echo "Parenthesized strings found: " . count($string_matches[1]) . "\n";
echo "Sample strings:\n";
for ($i = 0; $i < min(10, count($string_matches[1])); $i++) {
    $str = substr($string_matches[1][$i], 0, 50);
    $str = preg_replace('/[^\x20-\x7E]/', '?', $str);
    echo "  [$i] $str\n";
}

// Try a different approach - look for text content stream
echo "\n=== LOOKING FOR TEXT PATTERNS ===\n";

// Pattern 1: Text in Tj, TJ, ' operators
if (preg_match_all('/\(([^()]+)\)\s*(?:Tj|TJ|\'|")/', $content, $text_ops)) {
    echo "Text in Tj/TJ operators: " . count($text_ops[1]) . "\n";
}

// Pattern 2: Td/TD positioning operators (indicate text move)
if (preg_match_all('/Td|TD|T\*/', $content, $pos_ops)) {
    echo "Text positioning operators: " . count($pos_ops[0]) . "\n";
}

// Try to detect encoding
echo "\n=== ENCODING DETECTION ===\n";
if (strpos($content, '/FlateDecode') !== false) {
    echo "✓ Content is FlateDecode compressed\n";
}
if (strpos($content, '/Encrypt') !== false) {
    echo "⚠ PDF is encrypted/password protected\n";
}

?>
