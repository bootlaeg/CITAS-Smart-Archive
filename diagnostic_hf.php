<?php
/**
 * Comprehensive HF Config Diagnostic
 */

echo "<h1>🔧 HuggingFace Configuration Diagnostic</h1>";

// Step 1: Check if .env file exists and show its content
echo "<h2>Step 1: .env File Content</h2>";
$env_file = __DIR__ . '/.env';
echo "<p>Looking for: <code>$env_file</code></p>";

if (file_exists($env_file)) {
    echo "<p>✅ File exists</p>";
    $content = file_get_contents($env_file);
    echo "<pre style='background:#f0f0f0; padding:10px;'>";
    echo htmlspecialchars($content);
    echo "</pre>";
} else {
    echo "<p>❌ File not found at: <code>$env_file</code></p>";
}

// Step 2: Check what huggingface_config.php sets
echo "<h2>Step 2: Load HuggingFace Config</h2>";
require_once __DIR__ . '/ai_includes/huggingface_config.php';

if (defined('HUGGING_FACE_API_KEY')) {
    $key = HUGGING_FACE_API_KEY;
    echo "<p>✅ Constant HUGGING_FACE_API_KEY is defined</p>";
    echo "<p>Value length: " . strlen($key) . " characters</p>";
    if (empty($key)) {
        echo "<p style='color: red;'>❌ But the value is EMPTY!</p>";
    } else {
        echo "<p>✅ Value starts with: <code>" . substr($key, 0, 10) . "</code></p>";
    }
} else {
    echo "<p>❌ Constant HUGGING_FACE_API_KEY is NOT defined</p>";
}

// Step 3: Manual test - directly read and parse .env
echo "<h2>Step 3: Direct .env Parse Test</h2>";
$manual_key = '';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        if (strpos($line, 'HUGGING_FACE_API_KEY=') === 0) {
            $manual_key = trim(substr($line, 21));
            break;
        }
    }
}

if (empty($manual_key)) {
    echo "<p>❌ Could not extract key from .env</p>";
} else {
    echo "<p>✅ Manually extracted: <code>" . substr($manual_key, 0, 10) . "</code></p>";
    echo "<p>Matches constant? " . ($manual_key === HUGGING_FACE_API_KEY ? '✅ YES' : '❌ NO') . "</p>";
}

// Step 4: Try using the service
echo "<h2>Step 4: HuggingFace Service Status</h2>";
require_once __DIR__ . '/ai_includes/huggingface_service.php';

if (class_exists('HuggingFaceService')) {
    $service = new HuggingFaceService();
    echo "<p>✅ HuggingFaceService class loaded</p>";
    
    // Try a simple API call
    echo "<p>Attempting API status check...</p>";
    $response = $service->getStatus();
    echo "<pre style='background:#f0f0f0; padding:10px;'>";
    echo htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT));
    echo "</pre>";
} else {
    echo "<p>❌ HuggingFaceService class not found</p>";
}

?>
