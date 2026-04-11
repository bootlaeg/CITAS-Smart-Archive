<?php
$file = 'C:/Users/aki/Desktop/Placeholder/NeuroGuard.pdf';
$content = file_get_contents($file);

echo "=== LOOKING FOR TEXT CONTENT IN PDF ===\n\n";

// Find streams and decompress all of them
$matches_needed = preg_match_all('/\/Length\s+(\d+)[^\n]*\n[^\n]*stream\s*\n/s', $content, $matches, PREG_OFFSET_CAPTURE);

$allText = '';
$textSamples = [];

for ($i = 0; $i < min(10, $matches_needed); $i++) {
    $stream_length = intval($matches[1][$i][0]);
    $stream_offset = $matches[0][$i][1] + strlen($matches[0][$i][0]);
    $stream_data = substr($content, $stream_offset, $stream_length);
    
    $decompressed = @gzuncompress($stream_data);
    if (!$decompressed) {
        $decompressed = @gzinflate($stream_data);
    }
    
    if ($decompressed) {
        echo "=== STREAM " . ($i+1) . " ===\n";
        echo "Original size: $stream_length, Decompressed: " . strlen($decompressed) . "\n";
        
        // Look for all parenthesized strings
        $count = preg_match_all('/\(([^()\\\\]*(?:\\\\.[^()\\\\]*)*)\)/', $decompressed, $strings);
        if ($count > 0) {
            echo "Found $count text strings:\n";
            foreach (array_slice($strings[1], 0, 10) as $str) {
                $decoded = stripslashes($str);
                if (strlen(trim($decoded)) > 1) {
                    echo "  - '" . substr($decoded, 0, 60) . "'\n";
                    $textSamples[] = $decoded;
                }
            }
        }
        
        // Look for all text-like content (alphanumeric sequences)
        if (preg_match_all('/[A-Za-z]{5,}(?:\s+[A-Za-z]{3,})*/', $decompressed, $words)) {
            echo "Found text words: ";
            foreach (array_slice($words[0], 0, 10) as $word) {
                if (strlen($word) > 3 && !preg_match('/^(stream|BT|ET|Tf|Tm|Tj|TJ|gs|EMC|BDC)/', $word)) {
                    echo $word . " | ";
                }
            }
            echo "\n";
        }
        
        echo "\n";
    }
}

if (!empty($textSamples)) {
    echo "\n=== ALL TEXT SAMPLES FOUND ===\n";
    foreach (array_unique(array_filter($textSamples)) as $sample) {
        if (strlen(trim($sample)) > 2) {
            echo "• " . trim($sample) . "\n";
        }
    }
}

?>
