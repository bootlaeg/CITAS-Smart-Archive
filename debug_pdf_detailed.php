<?php
/**
 * Debug PDF extraction step-by-step
 */

$pdf_file = 'C:/Users/aki/Desktop/Placeholder/NeuroGuard.pdf';
$content = file_get_contents($pdf_file);

echo "=== DETAILED PDF EXTRACTION DEBUG ===\n\n";

// Find stream objects
preg_match_all('/<<(.+?)>>[\s\n]+stream[\s\n]+(.+?)[\s\n]+endstream/s', $content, $matches);

$all_text = '';
$processed_streams = 0;
$decompressed_count = 0;

// Check first few streams
for ($i = 0; $i < min(10, count($matches[0])); $i++) {
    $header = $matches[1][$i];
    $stream_data = $matches[2][$i];
    
    if (strpos($header, 'FlateDecode') !== false) {
        $decompressed = @gzuncompress($stream_data);
        if ($decompressed) {
            $decompressed_count++;
            
            // Extract strings from decompressed content
            if (preg_match_all("/\\(([^()\\\\]*(?:\\\\.[^()\\\\]*)*)\\)/", $decompressed, $strings)) {
                echo "Stream $i: Found " . count($strings[1]) . " strings\n";
                
                for ($j = 0; $j < min(10, count($strings[1])); $j++) {
                    $str = $strings[1][$j];
                    // Decode PDF escape sequences
                    $decoded = preg_replace('/\\\\([0-7]{1,3})/', chr(intval('$1', 8)), $str);
                    $decoded = str_replace(['\\\\n', '\\\\r', '\\\\t'], ["\n", "\r", "\t"], $decoded);
                    $decoded = str_replace('\\\\', '', $decoded);
                    
                    if (trim($decoded) !== '') {
                        echo "  [$j] " . substr(trim($decoded), 0, 80) . "\n";
                        $all_text .= trim($decoded) . ' ';
                    }
                }
            }
        }
    }
    $processed_streams++;
}

echo "\nProcessed streams: $processed_streams\n";
echo "Successfully decompressed: $decompressed_count\n";
echo "Total text collected: " . strlen($all_text) . " characters\n";
echo "\nFirst 500 chars of collected text:\n";
echo substr($all_text, 0, 500) . "\n";

// Try to find title and authors
if (preg_match('/NeuroGuard/i', $all_text, $m)) {
    echo "\n✓ Found 'NeuroGuard' in text\n";
}

if (preg_match('/ABSTRACT/i', $all_text)) {
    echo "✓ Found 'ABSTRACT' in text\n";
}

if (preg_match('/Acknowledgment|Acknowledgement/i', $all_text)) {
    echo "✓ Found 'Acknowledgment' in text\n";
}
?>
