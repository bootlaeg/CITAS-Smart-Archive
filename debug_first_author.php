<?php
/**
 * Debug: Track what happens to first author
 */

require_once 'ai_includes/document_parser.php';

$pdfPath = 'C:\\Users\\aki\\Desktop\\New folder\\New folder (2)\\Placeholder\\Design of Web-Based Student Academic Information System.pdf';

$extractResult = DocumentParser::extractText($pdfPath);
$fullText = $extractResult['text'];
$lines = explode("\n", $fullText);
$beginningText = implode("\n", array_slice($lines, 0, 50));

// Get authors section
preg_match('/authors?\s*:\s*(.+?)(?:affiliations?|journal|abstract|introduction|keywords)/is', $beginningText, $matches);
$authorSection = trim($matches[1]);
$authorList = array_map('trim', explode(',', $authorSection));

echo "=== Tracking First Author Processing ===\n\n";

$author = $authorList[0];
echo "ORIGINAL: \"$author\"\n";

// Simulate the character filtering
$cleanedAuthor = '';
for ($i = 0; $i < strlen($author); $i++) {
    $char = $author[$i];
    $byte = ord($char);
    
    if (($byte >= 32 && $byte < 127) || $byte >= 192) {
        $cleanedAuthor .= $char;
    }
}
$author = $cleanedAuthor;
echo "After char filtering: \"$author\"\n";

// Remove superscripts
$author = str_replace(['¹', '²', '³', '⁴', '⁵', '⁶', '⁷', '⁸', '⁹', '⁰'], '', $author);
echo "After removing superscripts: \"$author\"\n";

// First regex: lowercase to uppercase
$author = preg_replace('/([a-z])([A-Z])/', '$1 $2', $author);
echo "After first regex: \"$author\"\n";

// Second regex: handle "B Makkaraka" patterns
$author = preg_replace('/([a-z]\s[A-Z])([A-Z][a-z]+)/', '$1 $2', $author);
echo "After second regex: \"$author\"\n";

// Clean spaces
$author = preg_replace('/\s+/', ' ', $author);
$author = trim($author);
echo "After final cleanup: \"$author\"\n";

// Check filters
echo "\n--- Filter checks ---\n";
echo "Length: " . strlen($author) . " (need > 3)\n";

if (preg_match('/^(digital|health|scientist|professor|school|prof|dr|phd|md|msc|mba|information|system|education|university|institute|college|faculty|research|fellow|senior|junior|chair|email|tel|contact|via|line|break)/i', $author)) {
    echo "❌ Contains keyword\n";
} else {
    echo "✓ No keyword match\n";
}

if (preg_match('/^\d+$|^\d{2,4}$|^(january|february|march|april|may|june|july|august|september|october|november|december)$/i', $author)) {
    echo "❌ Is date/number\n";
} else {
    echo "✓ Not date/number\n";
}

if (preg_match('/^[A-Z][a-z]+(\s+[A-Z][a-z\']+)*(\s+[A-Z])?$/i', $author)) {
    echo "✓ Matches name pattern\n";
} else {
    echo "❌ Doesn't match name pattern: /^[A-Z][a-z]+(\\s+[A-Z][a-z\\']+)*(\\s+[A-Z])?$/i\n";
    echo "Will be REJECTED\n";
}
?>
