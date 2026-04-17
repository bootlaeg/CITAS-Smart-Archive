<?php
/**
 * Debug: Test Journal Converter Ollama Connection
 * Tests the exact same request the journal converter makes
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log to both file and display
function debug_log($msg) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $msg");
    echo "<pre>[$timestamp] $msg</pre>";
}

debug_log("=== JOURNAL CONVERTER DEBUG TEST ===");
debug_log("Testing Ollama connection via Cloudflare tunnel");

// Test 1: Simple connectivity test
debug_log("\n--- TEST 1: Basic HTTPS Connectivity ---");
$test_url = 'https://ollama.CITAS-smart-archive.com/api/tags';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$verbose = fopen('php://memory', 'rw+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_errno = curl_errno($ch);
$curl_error = curl_error($ch);
curl_close($ch);

debug_log("URL: $test_url");
debug_log("HTTP Code: $http_code");
debug_log("Curl Errno: $curl_errno");
debug_log("Curl Error: $curl_error");
if ($response) {
    debug_log("Response (first 200 chars): " . substr($response, 0, 200));
} else {
    debug_log("Response: EMPTY");
}

// Test 2: Test the exact request journal converter makes
debug_log("\n--- TEST 2: Test Ollama /api/generate Request ---");
$ollama_url = 'https://ollama.CITAS-smart-archive.com/api/generate';

$request_body = [
    'model' => 'mistral',
    'prompt' => 'Write a short summary in 100 words: The quick brown fox jumps over the lazy dog.',
    'stream' => false,
    'temperature' => 0.3,
    'num_predict' => 130
];

debug_log("URL: $ollama_url");
debug_log("Request body: " . json_encode($request_body, JSON_PRETTY_PRINT));

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $ollama_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_body));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

debug_log("Sending request (this may take 30-60 seconds)...");
$start_time = microtime(true);

$response = curl_exec($ch);
$elapsed = microtime(true) - $start_time;
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_errno = curl_errno($ch);
$curl_error = curl_error($ch);
$total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);

curl_close($ch);

debug_log("⏱ Elapsed time: " . round($elapsed, 2) . " seconds");
debug_log("HTTP Code: $http_code");
debug_log("Curl Errno: $curl_errno");
debug_log("Curl Error: $curl_error");
debug_log("Total time from curl_getinfo: " . round($total_time, 2) . " seconds");

if ($response) {
    debug_log("✅ Response received (" . strlen($response) . " bytes)");
    $result = json_decode($response, true);
    if ($result) {
        if (isset($result['response'])) {
            debug_log("Response text: " . substr($result['response'], 0, 300));
        } else {
            debug_log("Response (raw): " . substr($response, 0, 500));
        }
    } else {
        debug_log("Failed to decode JSON. Raw response: " . substr($response, 0, 500));
    }
} else {
    debug_log("❌ EMPTY RESPONSE - Ollama did not respond");
}

// Test 3: Check if mistral model variant matters
debug_log("\n--- TEST 3: Test with 'mistral:latest' model name ---");
$request_body['model'] = 'mistral:latest';
$request_body['prompt'] = 'Test';
$request_body['num_predict'] = 10;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $ollama_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_body));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$start_time = microtime(true);
$response = curl_exec($ch);
$elapsed = microtime(true) - $start_time;
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

debug_log("Model: mistral:latest");
debug_log("HTTP Code: $http_code");
debug_log("Curl Error: $curl_error");
debug_log("Elapsed: " . round($elapsed, 2) . " seconds");
if ($response && $http_code == 200) {
    debug_log("✅ mistral:latest works");
}

debug_log("\n=== SUMMARY ===");
debug_log("Check the results above to see where the issue is:");
debug_log("- If TEST 1 fails: Tunnel is not accessible from server");
debug_log("- If TEST 2 times out: Ollama is not responding to requests");
debug_log("- If TEST 2 returns empty: Ollama returned no response");
debug_log("- If TEST 2 returns error: Check the error message");
debug_log("- If TEST 2 succeeds: Ollama is working, check PHP journal_converter.php logs");
?>
