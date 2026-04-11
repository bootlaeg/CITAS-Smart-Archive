<?php
$file = 'C:/Users/aki/Desktop/Placeholder/NeuroGuard.pdf';

echo "=== DETAILED PDF DEBUGGING ===\n\n";

$content = file_get_contents($file);
echo "File size: " . strlen($content) . " bytes\n";

// Find streams with /Length parameter
echo "\nFinding streams with /Length parameter...\n";
$count = preg_match_all('/\/Length\s+(\d+)/s', $content, $matches);
echo "Found $count /Length declarations\n\n";

if ($count > 0) {
    echo "First 5 lengths: " . implode(', ', array_slice($matches[1], 0, 5)) . "\n\n";
    
    // Try the /Length + stream pattern
    $count2 = preg_match_all('/\/Length\s+(\d+)[^\n]*\n[^\n]*stream\s*\n/s', $content, $matches2, PREG_OFFSET_CAPTURE);
    echo "Matches for /Length...stream pattern: $count2\n\n";
    
    if ($count2 > 0) {
        echo "First match details:\n";
        $first = $matches2[0][0];
        echo "  Full match text: " . substr($first[0], 0, 80) . "...\n";
        echo "  Offset: " . $first[1] . "\n";
        echo "  Length: " . strlen($first[0]) . " bytes\n\n";
        
        // Extract the first stream
        $stream_length = intval($matches2[1][0][0]);
        $stream_offset = $first[1] + strlen($first[0]);
        $stream_data = substr($content, $stream_offset, min($stream_length, 200));
        
        echo "First stream (first 200 bytes):\n";
        echo "  Raw hex: " . bin2hex(substr($stream_data, 0, 50)) . "\n";
        echo "  Text dump: " . substr($stream_data, 0, 50) . "\n\n";
        
        // Try decompression
        echo "Attempting decompression...\n";
        $full_stream = substr($content, $stream_offset, $stream_length);
        $decompressed = @gzuncompress($full_stream);
        if ($decompressed) {
            echo "✓ gzuncompress worked!\n";
            echo "  Decompressed size: " . strlen($decompressed) . " bytes\n";
            echo "  First 200 chars:\n  " . substr($decompressed, 0, 200) . "\n\n";
            
            // Look for text patterns
            if (preg_match_all('/BT\s*(.+?)\s*ET/s', $decompressed, $text_matches)) {
                echo "Found " . count($text_matches[0]) . " BT...ET blocks\n";
            } else {
                echo "No BT...ET blocks found\n";
            }
            
            if (preg_match_all('/\(([^)]{5,50})\)\s*Tj/', $decompressed, $text_matches)) {
                echo "Found " . count($text_matches[1]) . " Tj text operations\n";
                if (!empty($text_matches[1])) {
                    echo "  First 3: " . implode(", ", array_slice($text_matches[1], 0, 3)) . "\n";
                }
            } else {
                echo "No Tj operations found\n";
            }
        } else {
            echo "✗ gzuncompress failed\n";
            $inflated = @gzinflate($full_stream);
            if ($inflated) {
                echo "✓ gzinflate worked!\n";
                echo "  Decompressed size: " . strlen($inflated) . " bytes\n";
                echo "  First 200 chars:\n  " . substr($inflated, 0, 200) . "\n";
            } else {
                echo "✗ Both gzuncompress and gzinflate failed\n";
            }
        }
    }
}

?>
