<?php
set_time_limit(0);
ini_set('default_socket_timeout', 300);

echo "<h2>Testing Ollama Tunnel Connectivity</h2>";
echo "<hr>";

$start = microtime(true);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://ollama.citas-smart-archive.com/api/generate');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'mistral',
    'prompt' => 'What is 2+2? Answer in one sentence only.',
    'stream' => false
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 300);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

echo "Sending request at: <strong>" . date('Y-m-d H:i:s') . "</strong><br>";
echo "Waiting for Ollama response...";

$response = curl_exec($ch);
$elapsed = microtime(true) - $start;
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<br><br>";
echo "Response time: <strong>" . round($elapsed, 2) . " seconds</strong><br>";
echo "HTTP Code: <strong>" . $http_code . "</strong><br>";

if ($curl_error) {
    echo "cURL Error: <strong style='color: red;'>" . $curl_error . "</strong><br>";
} else {
    if ($http_code == 200) {
        echo "<span style='color: green;'><strong>✅ SUCCESS!</strong></span><br>";
        echo "Response: " . substr($response, 0, 500) . "<br>";
    } else {
        echo "<span style='color: red;'><strong>❌ ERROR (HTTP " . $http_code . ")</strong></span><br>";
        echo "Response: " . substr($response, 0, 500) . "<br>";
    }
}
?>
