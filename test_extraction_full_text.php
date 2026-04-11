<?php
/**
 * Extract and dump full text from DOCX for manual analysis
 */

$test_file = 'C:/Users/aki/Desktop/Placeholder/NeuroGuard.docx';

if (!file_exists($test_file)) {
    echo "File not found: $test_file\n";
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($test_file) !== true) {
    echo "Failed to open DOCX\n";
    exit(1);
}

$xml_content = $zip->getFromName('word/document.xml');
$zip->close();

if (!$xml_content) {
    echo "No document.xml\n";
    exit(1);
}

// Extract text using regex
if (preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/u', $xml_content, $matches)) {
    $full_text = implode(' ', $matches[1]);
    
    // Write to file for easier analysis
    file_put_contents('extracted_text.txt', $full_text);
    
    echo "Total text length: " . strlen($full_text) . " chars\n";
    echo "Written to: extracted_text.txt\n";
    echo "\nFirst 2000 characters:\n";
    echo "=== START ===\n";
    echo substr($full_text, 0, 2000);
    echo "\n=== END ===\n";
    
    // Try to find author patterns
    echo "\n\n=== SEARCHING FOR AUTHOR PATTERNS ===\n";
    
    // Pattern 1: LASTNAME, FIRSTNAME
    if (preg_match_all('/([A-Z][A-Z]+),\s+([A-Z][a-z]+)/', $full_text, $matches)) {
        echo "Found " . count($matches[0]) . " names in 'LASTNAME, FIRSTNAME' format:\n";
        $unique = [];
        foreach ($matches[0] as $match) {
            if (!in_array($match, $unique)) {
                $unique[] = $match;
                if (count($unique) <= 10) {
                    echo "  - $match\n";
                }
            }
        }
    }
    
} else {
    echo "Failed to extract text\n";
}

?>
