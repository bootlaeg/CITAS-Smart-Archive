<?php
/**
 * Direct HuggingFace API Test
 * Tests with explicit endpoint and debugging
 */

// Load config to get the key
require_once __DIR__ . '/ai_includes/huggingface_config.php';

echo "<h1>🔍 Direct HuggingFace API Test</h1>";

// Test parameters
$api_key = HUGGING_FACE_API_KEY;
$model = HUGGING_FACE_MODEL;
$api_url = HUGGING_FACE_API_URL . "/models/" . $model;
$test_text = "This is a test. HuggingFace API integration testing.";

echo "<h2>Request Details</h2>";
echo "<pre>";
echo "API URL: $api_url\n";
echo "API Key: " . (empty($api_key) ? "NOT SET" : substr($api_key, 0, 10) . "...") . "\n";
echo "Model: $model\n";
echo "Test Text Length: " . strlen($test_text) . " characters\n";
echo "</pre>";

echo "<h2>Making API Request...</h2>";

// Make the cURL request
$curl = curl_init();

$headers = [
    'Authorization: Bearer ' . $api_key,
    'Content-Type: application/json'
];

$payload = [
    'inputs' => $test_text
];

curl_setopt_array($curl, [
    CURLOPT_URL => $api_url,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_VERBOSE => false
]);

echo "<p>Sending request...</p>";
$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curl_error = curl_error($curl);
$curl_info = curl_getinfo($curl);
curl_close($curl);

echo "<h2>Response</h2>";
echo "<p><strong>HTTP Status Code:</strong> $http_code</p>";

if ($curl_error) {
    echo "<p><strong style='color: red;'>cURL Error:</strong> $curl_error</p>";
}

echo "<h3>Response Body</h3>";
echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ddd;'>";
echo htmlspecialchars($response);
echo "</pre>";

if ($http_code === 200) {
    $data = json_decode($response, true);
    if (isset($data[0]['summary_text'])) {
        echo "<h3>✅ Success!</h3>";
        echo "<p><strong>Summary:</strong> " . htmlspecialchars($data[0]['summary_text']) . "</p>";
    } else {
        echo "<h3>Response Structure</h3>";
        echo "<pre style='background: #f0f0f0; padding: 10px;'>";
        echo htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT));
        echo "</pre>";
    }
} else {
    echo "<h3 style='color: red;'>❌ API Error (HTTP $http_code)</h3>";
    $data = json_decode($response, true);
    if ($data) {
        echo "<p><strong>Error Details:</strong></p>";
        echo "<pre style='background: #fff5f5; padding: 10px; color: #c33;'>";
        echo htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT));
        echo "</pre>";
    }
}

echo "<h3>cURL Info</h3>";
echo "<pre style='background: #f0f0f0; padding: 10px; font-size: 12px;'>";
echo "Effective URL: " . ($curl_info['url'] ?? 'N/A') . "\n";
echo "HTTP Code: " . ($curl_info['http_code'] ?? 'N/A') . "\n";
echo "Total Time: " . ($curl_info['total_time'] ?? 'N/A') . "s\n";
echo "Connect Time: " . ($curl_info['connect_time'] ?? 'N/A') . "s\n";
echo "</pre>";
?>
