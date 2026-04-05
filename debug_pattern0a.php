<?php
/**
 * Debug: Test Pattern 0A Directly
 */

require_once 'ai_includes/document_parser.php';

$pdfPath = 'C:\\Users\\aki\\Desktop\\New folder\\New folder (2)\\Placeholder\\Design of Web-Based Student Academic Information System.pdf';

$extractResult = DocumentParser::extractText($pdfPath);
$fullText = $extractResult['text'];
$lines = explode("\n", $fullText);
$beginningText = implode("\n", array_slice($lines, 0, 50));

echo "=== Test Pattern 0A ===\n\n";

$authorMatch = [];
if (preg_match('/authors?\s*:\s*(.+?)(?:affiliations?|journal|abstract|introduction|keywords)/is', $beginningText, $authorMatch)) {
    echo "✓ Pattern 0A matched!\n";
    echo "Raw captured (100 chars): " . substr($authorMatch[1], 0, 100) . "\n\n";
    
    $authorSection = trim($authorMatch[1]);
    echo "After trim: \"" . $authorSection . "\"\n\n";
    
    // Split by comma
    $authorList = array_map('trim', explode(',', $authorSection));
    echo "After split by comma (" . count($authorList) . " parts):\n";
    
    foreach ($authorList as $i => $item) {
        echo "  [$i] \"" . $item . "\"\n";
    }
    
    echo "\n--- Processing each part ---\n";
    foreach ($authorList as $i => $author) {
        $author = trim($author);
        echo "\nPart $i before processing: \"$author\"\n";
        
        // Remove superscripts
        $author = preg_replace('/[¹²³⁴⁵⁶⁷⁸⁹⁰†‡§¶\*]/u', '', $author);
        $author = preg_replace('/[\xb0-\xb9]/u', '', $author);
        echo "  After removing superscripts: \"$author\"\n";
        
        // Add spaces between camelCase
        $author = preg_replace('/([a-z])([A-Z])/', '$1 $2', $author);
        echo "  After camelCase spacing: \"$author\"\n";
        
        // Clean spaces
        $author = preg_replace('/\s+/', ' ', $author);
        $author = trim($author);
        echo "  After normalizing spaces: \"$author\"\n";
        
        // Check filters
        if (strlen($author) < 3) {
            echo "  ❌ SKIP: Too short\n";
            continue;
        }
        
        if (preg_match('/^(digital|health|scientist|professor|school|prof|dr|phd|md|msc|mba|information|system|education|university|institute|college|faculty|research|fellow|senior|junior|chair|email|tel|contact|via|line|break)/i', $author)) {
            echo "  ❌ SKIP: Contains keyword\n";
            continue;
        }
        
        if (preg_match('/^\d+$|^\d{2,4}$|^(january|february|march|april|may|june|july|august|september|october|november|december)$/i', $author)) {
            echo "  ❌ SKIP: Is date/number\n";
            continue;
        }
        
        if (strlen($author) >= 200) {
            echo "  ❌ SKIP: Too long\n";
            continue;
        }
        
        if (preg_match('/^[A-Z][a-z]+(\s+[A-Z][a-z\']+)*(\s+[A-Z])?$/i', $author)) {
            echo "  ✅​ ACCEPT: Looks like author name\n";
        } else {
            echo "  ❌ SKIP: Doesn't match name pattern\n";
        }
    }
} else {
    echo "✗ Pattern 0A did NOT match\n";
}
?>
