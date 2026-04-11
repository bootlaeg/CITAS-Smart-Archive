<?php
/**
 * Debug PDF decompression
 */

$pdf_file = 'C:/Users/aki/Desktop/Placeholder/NeuroGuard.pdf';
$content = file_get_contents($pdf_file);

echo "=== PDF DECOMPRESSION DEBUG ===\n\n";

// Find stream objects
preg_match_all('/<<(.+?)>>[\s\n]+stream[\s\n]+(.+?)[\s\n]+endstream/s', $content, $matches);

echo "Found " . count($matches[0]) . " stream objects\n";

// Check first few streams
for ($i = 0; $i < min(3, count($matches[0])); $i++) {
    $header = $matches[1][$i];
    $stream_data = $matches[2][$i];
    
    echo "\n--- Stream $i ---\n";
    echo "Header length: " . strlen($header) . "\n";
    echo "Stream data length: " . strlen($stream_data) . "\n";
    
    // Check if it has FlateDecode
    if (strpos($header, 'FlateDecode') !== false) {
        echo "✓ Has FlateDecode\n";
        
        // Try to decompress
        $decompressed = @gzinflate($stream_data);
        if ($decompressed) {
            echo "✓ Successfully decompressed with gzinflate\n";
            echo "  Decompressed length: " . strlen($decompressed) . "\n";
            
            // Show first 200 chars
            $first_200 = substr($decompressed, 0, 200);
            $visible = preg_replace('/[^\x20-\x7E\n\r\t]/', '.', $first_200);
            echo "  First 200 chars:\n";
            echo "  " . str_replace("\n", "\n  ", $visible) . "\n";
            
            // Look for text
            if (preg_match_all('/BT\s*(.+?)\s*ET/s', $decompressed, $text_matches)) {
                echo "  Found " . count($text_matches[0]) . " text blocks\n";
            }
            
            // Look for parentheses text
            if (preg_match_all('/\(([^()]{5,50})\)/', $decompressed, $paren_matches)) {
                echo "  Found " . count($paren_matches[1]) . " parenthesized strings\n";
                echo "  Samples:\n";
                for ($j = 0; $j < min(3, count($paren_matches[1])); $j++) {
                    $str = preg_replace('/[^\x20-\x7E]/', '?', $paren_matches[1][$j]);
                    echo "    [$j] $str\n";
                }
            }
        } else {
            echo "✗ Failed to decompress with gzinflate\n";
            
            // Try gzuncompress
            $decompressed = @gzuncompress($stream_data);
            if ($decompressed) {
                echo "✓ Successfully decompressed with gzuncompress\n";
                echo "  Decompressed length: " . strlen($decompressed) . "\n";
            }
        }
    } else {
        echo "○ No FlateDecode (uncompressed)\n";
        
        // Show first 100 chars
        $first_100 = substr($stream_data, 0, 100);
        $visible = preg_replace('/[^\x20-\x7E\n\r\t]/', '.', $first_100);
        echo "  First 100 chars: " . $visible . "\n";
    }
}
?>
