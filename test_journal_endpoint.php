<?php
/**
 * Test: Call Journal Converter Endpoint
 * Tests the actual journal_converter.php endpoint like the frontend does
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

function debug_log($msg) {
    $timestamp = date('Y-m-d H:i:s');
    echo "<pre>[$timestamp] $msg</pre>";
    flush();
}

debug_log("=== TESTING JOURNAL CONVERTER ENDPOINT ===");
debug_log("Calling: ai_includes/journal_converter.php");

// Create a test thesis with simple text
$test_text = "Introduction: This thesis studies the effectiveness of deep learning in medical imaging. The problem is that manual diagnosis is time-consuming and error-prone. Our objective is to develop an automated system that can classify diseases with 95% accuracy.

Methods: We collected 5000 medical images from hospitals. We used a convolutional neural network (CNN) with ResNet-50 architecture. The dataset was split into 80% training and 20% testing.

Results: Our model achieved 94.2% accuracy on the test set. The sensitivity was 96% and specificity was 92%. The average processing time was 2.3 seconds per image.

Discussion: Our results show that deep learning can effectively assist radiologists. The 94.2% accuracy is comparable to expert human performance. However, we note that the model sometimes struggles with rare diseases.

Conclusions: This study demonstrates that automated diagnosis systems have great potential in clinical practice. Future work should include testing on larger and more diverse datasets.";

$post_data = [
    'thesis_id' => 999,  // Dummy ID
    'document_text' => $test_text,
    'title' => 'Test Thesis - Deep Learning in Medical Imaging',
    'author' => 'Test Author',
    'abstract' => 'A test abstract',
    'year' => 2026
];

debug_log("POST Data size: " . strlen(json_encode($post_data)) . " bytes");
debug_log("Document text length: " . strlen($test_text) . " characters");

// Call the journal converter
$url = 'https://citas-smart-archive.com/ai_includes/journal_converter.php';
debug_log("Calling: $url");

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

debug_log("Sending POST request (may take 30-60 seconds, waiting for Ollama to process)...");
$start_time = microtime(true);
ob_flush();

$response = curl_exec($ch);
$elapsed = microtime(true) - $start_time;
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);

curl_close($ch);

debug_log("⏱ Elapsed time: " . round($elapsed, 2) . " seconds");
debug_log("HTTP Code: $http_code");
debug_log("Curl Error: $curl_error");

if ($response) {
    debug_log("✅ Response received (" . strlen($response) . " bytes)");
    
    // Try to decode as JSON
    $result = json_decode($response, true);
    if ($result) {
        debug_log("Response is valid JSON:");
        debug_log(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        if (isset($result['success'])) {
            if ($result['success']) {
                debug_log("✅ Journal conversion SUCCESSFUL!");
                if (isset($result['journal_page_count'])) {
                    debug_log("   Pages generated: " . $result['journal_page_count']);
                }
            } else {
                debug_log("❌ Journal conversion FAILED!");
                if (isset($result['error'])) {
                    debug_log("   Error: " . $result['error']);
                }
            }
        }
    } else {
        debug_log("❌ Response is NOT valid JSON (invalid format)");
        debug_log("First 500 chars of response:");
        debug_log(substr($response, 0, 500));
    }
} else {
    debug_log("❌ EMPTY RESPONSE - No response from journal_converter.php");
    debug_log("Curl Error: $curl_error");
}

debug_log("\n=== ANALYSIS ===");
debug_log("If HTTP Code is 200 and success=true: Everything is working!");
debug_log("If success=false: Check the error message above");
debug_log("If the response is not JSON: There's a PHP error in journal_converter.php");
debug_log("If no response: The endpoint may be timing out or crashing");
?>
