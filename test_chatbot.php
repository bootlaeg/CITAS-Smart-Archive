<?php
/**
 * Quick test script for chatbot debugging
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 CITAS Chatbot Debug Test</h1>";

// Test 1: Check PHP
echo "<h2>1. PHP Status</h2>";
echo "<p>✓ PHP is working</p>";
echo "<p>Version: " . phpversion() . "</p>";

// Test 2: Check Ollama connection
echo "<h2>2. Ollama Connection Test</h2>";
$ch = curl_init('http://localhost:11434/api/tags');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p style='color:red'>✗ Error: $error</p>";
} else if ($httpCode == 200) {
    echo "<p style='color:green'>✓ Connected! HTTP $httpCode</p>";
    $data = json_decode($response, true);
    if (isset($data['models'])) {
        echo "<p>Models available:</p><ul>";
        foreach ($data['models'] as $model) {
            echo "<li>" . $model['name'] . "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p style='color:red'>✗ HTTP Error: $httpCode</p>";
}

// Test 3: Test query to Ollama
echo "<h2>3. Ollama Query Test</h2>";
$test_prompt = "What is AI in one sentence?";
$payload = json_encode([
    'model' => 'mistral:latest',
    'prompt' => $test_prompt,
    'stream' => false,
    'temperature' => 0.5
]);

$ch = curl_init('http://localhost:11434/api/generate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p style='color:red'>✗ cURL Error: $error</p>";
} else if ($httpCode == 200) {
    echo "<p style='color:green'>✓ Query successful! HTTP $httpCode</p>";
    $data = json_decode($response, true);
    if (isset($data['response'])) {
        echo "<p><strong>Response:</strong></p>";
        echo "<pre style='background:#f5f5f5;padding:10px;border-radius:5px;'>" . htmlspecialchars($data['response']) . "</pre>";
    }
} else {
    echo "<p style='color:red'>✗ HTTP Error: $httpCode</p>";
    echo "<p>Response: " . htmlspecialchars($response) . "</p>";
}

// Test 4: Database check
echo "<h2>4. Database Connection</h2>";
try {
    require_once __DIR__ . '/db_includes/db_connect.php';
    
    if (isset($conn) && $conn !== null) {
        echo "<p style='color:green'>✓ Database connected</p>";
        
        // Check if tables exist
        $result = $conn->query("SHOW TABLES LIKE 'chatbot%'");
        if ($result && $result->num_rows > 0) {
            echo "<p>Chatbot tables found:</p><ul>";
            while ($row = $result->fetch_array()) {
                echo "<li>" . $row[0] . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color:orange'>⚠ No chatbot tables found</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Database not connected</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
}

// Test 5: Session check
echo "<h2>5. Session Info</h2>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "<p style='color:green'>✓ Logged in as user ID: " . $_SESSION['user_id'] . "</p>";
} else {
    echo "<p style='color:orange'>⚠ Not logged in</p>";
}

?>
