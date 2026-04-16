<?php
/**
 * Diagnostic script to test Hugging Face API directly
 */

echo "=== HUGGING FACE API DIAGNOSTIC ===\n\n";

// Load API key from environment
$api_key = getenv('HUGGING_FACE_API_KEY');
if (!$api_key) {
    die("ERROR: HUGGING_FACE_API_KEY not set. Configure in .env file or environment variables.\n");
}

$model = 'facebook/bart-large-cnn';

echo "API Key (first 20 chars): " . substr($api_key, 0, 20) . "...\n";
echo "Model: $model\n\n";

// Test 1: Basic connectivity
echo "Test 1: Testing basic connectivity to HF API...\n";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => 'https://api-inference.huggingface.co/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_key]
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

echo "HTTP Code: $http_code\n";
echo "Response (first 200 chars): " . substr($response, 0, 200) . "\n\n";

// Test 2: Direct model call
echo "Test 2: Testing model-specific endpoint...\n";
echo "URL: https://api-inference.huggingface.co/models/$model\n\n";

$payload = json_encode([
    'inputs' => 'This is a test. Testing API access. This is important.'
]);

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api-inference.huggingface.co/models/$model",
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ],
    CURLOPT_VERBOSE => false
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$headers = curl_getinfo($curl, CURLINFO_HEADER_OUT);
$error = curl_error($curl);

echo "HTTP Code: $http_code\n";
echo "Request headers:\n" . $headers . "\n";

if ($error) {
    echo "cURL Error: $error\n";
} else {
    echo "Response:\n" . json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "\n";
}

curl_close($curl);

echo "\n";

// Test 3: Check token validity
echo "Test 3: Verifying token is valid...\n";
echo "Creating file with token info...\n";

$token_info = [
    'token_length' => strlen($api_key),
    'token_prefix' => substr($api_key, 0, 15),
    'created_at' => date('Y-m-d H:i:s')
];

echo json_encode($token_info, JSON_PRETTY_PRINT) . "\n";

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
echo "Next steps:\n";
echo "1. If HTTP 404: Model not found on this endpoint\n";
echo "2. If HTTP 401/403: Token invalid or insufficient permissions\n";
echo "3. If HTTP 429: Rate limited\n";
echo "4. If timeout: Network/firewall issue\n";
?>
