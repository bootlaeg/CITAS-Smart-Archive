<?php
header('Content-Type: text/plain');
echo "Testing Ollama connection...\n\n";

$ch = curl_init('http://localhost:11434/api/generate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

$payload = json_encode([
    'model' => 'mistral:latest',
    'prompt' => 'Hello, who are you?',
    'stream' => false
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

echo "Sending request to Ollama...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$errno = curl_errno($ch);

curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "cURL Error Code: $errno\n";
echo "cURL Error: $error\n";
echo "\nResponse:\n";
echo $response . "\n";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    echo "\n✓ SUCCESS!\n";
    echo "Answer: " . $data['response'] . "\n";
} else {
    echo "\n✗ FAILED\n";
}
?>
