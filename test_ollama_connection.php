<?php
/**
 * Test Ollama Connection
 * Verifies if Ollama is running and mistral model is available
 */

echo "<h2>Ollama Connection Test</h2>";
echo "<p>Testing connection to: <strong>http://localhost:11434</strong></p>";

// Test if Ollama is responding
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:11434/api/tags');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_FAILONERROR, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<h3>Connection Status:</h3>";
if ($curl_error) {
    echo "<p style='color: red;'><strong>❌ ERROR:</strong> " . htmlspecialchars($curl_error) . "</p>";
    echo "<p>Ollama might not be running. Start it with: <code>ollama serve</code></p>";
} else if ($http_code !== 200) {
    echo "<p style='color: red;'><strong>❌ HTTP " . $http_code . "</strong></p>";
    echo "<p>Ollama is not responding properly</p>";
} else {
    echo "<p style='color: green;'><strong>✅ Ollama is running!</strong></p>";
    
    $models = json_decode($response, true);
    echo "<h3>Available Models:</h3>";
    
    if (!empty($models['models'])) {
        echo "<ul>";
        $mistral_found = false;
        foreach ($models['models'] as $model) {
            echo "<li>" . htmlspecialchars($model['name']) . "</li>";
            if (strpos($model['name'], 'mistral') !== false) {
                $mistral_found = true;
            }
        }
        echo "</ul>";
        
        if ($mistral_found) {
            echo "<p style='color: green;'><strong>✅ Mistral model is installed</strong></p>";
        } else {
            echo "<p style='color: orange;'><strong>⚠️ Mistral NOT found</strong></p>";
            echo "<p>Install it with: <code>ollama pull mistral</code></p>";
        }
    } else {
        echo "<p style='color: orange;'><strong>⚠️ No models available</strong></p>";
        echo "<p>Install mistral with: <code>ollama pull mistral</code></p>";
    }
}

echo "<h3>Quick Test:</h3>";

// Try a quick summarization test
$test_prompt = "This is a test. Summarize this sentence.";
$test_request = [
    'model' => 'mistral',
    'prompt' => $test_prompt,
    'stream' => false,
    'temperature' => 0.3
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:11434/api/generate');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_request));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

echo "<p>Sending test prompt to mistral...</p>";
$test_response = curl_exec($ch);
$test_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$test_error = curl_error($ch);
curl_close($ch);

if ($test_error) {
    echo "<p style='color: red;'><strong>❌ Test failed:</strong> " . htmlspecialchars($test_error) . "</p>";
} else if ($test_http !== 200) {
    echo "<p style='color: red;'><strong>❌ Test HTTP " . $test_http . "</strong></p>";
} else {
    $test_result = json_decode($test_response, true);
    if ($test_result && isset($test_result['response'])) {
        echo "<p style='color: green;'><strong>✅ Mistral responded:</strong></p>";
        echo "<blockquote>" . htmlspecialchars(trim($test_result['response'])) . "</blockquote>";
    } else {
        echo "<p style='color: red;'><strong>❌ Invalid response format</strong></p>";
        echo "<pre>" . htmlspecialchars(substr($test_response, 0, 500)) . "</pre>";
    }
}
?>
