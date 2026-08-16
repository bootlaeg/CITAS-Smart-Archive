<?php
/**
 * GET metadata test for HF model
 */
require_once __DIR__ . '/ai_includes/huggingface_config.php';

$model = HUGGING_FACE_MODEL;
$api_url = 'https://api-inference.huggingface.co/models/' . $model;
$api_key = HUGGING_FACE_API_KEY;

echo "<h1>GET model metadata test</h1>";
echo "<p>Model: <strong>$model</strong></p>";

$curl = curl_init();
$headers = [
    'Authorization: Bearer ' . $api_key
];

curl_setopt_array($curl, [
    CURLOPT_URL => $api_url,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curl_error = curl_error($curl);
curl_close($curl);

echo "<p><strong>HTTP Status:</strong> $http_code</p>";
if ($curl_error) echo "<p><strong>cURL error:</strong> $curl_error</p>";

if ($response) {
    echo "<h3>Response body:</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

?>