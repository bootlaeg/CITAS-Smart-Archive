<?php
/**
 * Debug decompressed PDF content
 */

$pdf_file = 'C:/Users/aki/Desktop/Placeholder/NeuroGuard.pdf';
$content = file_get_contents($pdf_file);

// Find stream objects
preg_match_all('/<<(.+?)>>[\s\n]+stream[\s\n]+(.+?)[\s\n]+endstream/s', $content, $matches);

echo "=== ANALYZING DECOMPRESSED STREAMS ===\n\n";

// Check first few streams
for ($i = 0; $i < min(5, count($matches[0])); $i++) {
    $header = $matches[1][$i];
    $stream_data = $matches[2][$i];
    
    if (strpos($header, 'FlateDecode') !== false) {
        $decompressed = @gzuncompress($stream_data);
        if ($decompressed) {
            echo "--- Stream $i ---\n";
            
            // Show first 500 chars
            $first_500 = substr($decompressed, 0, 500);
            $visible = preg_replace('/[^\x20-\x7E\n\r\t]/', '?', $first_500);
            echo "First 500 chars:\n";
            echo $visible . "\n\n";
            
            // Look for actual text patterns
            if (preg_match('/NeuroGuard|Artificial|Intelligence|abstract|title/i', $decompressed)) {
                echo "✓ Found title/abstract keywords in this stream!\n";
                
                // Extract surrounding context
                if (preg_match('/NeuroGuard[^\n\(]{0,200}/i', $decompressed, $match)) {
                    echo "  Found: " . $match[0] . "\n";
                }
            }
            
            echo "\n";
        }
    }
}
?>
