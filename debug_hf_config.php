<?php
/**
 * Debug script to troubleshoot HuggingFace config loading
 */

echo "<h2>🔍 HuggingFace Config Debug</h2>";

// Check what paths we're looking for
$env_paths = [
    __DIR__ . '/.env',
    __DIR__ . '/../.env',           
    $_SERVER['DOCUMENT_ROOT'] . '/.env',  
    '/home/citas/public_html/.env', 
];

echo "<h3>Checking .env paths:</h3>";
echo "<pre>";
foreach ($env_paths as $path) {
    $exists = file_exists($path) ? '✅ EXISTS' : '❌ Not found';
    echo "$exists: $path\n";
}
echo "</pre>";

// Try to load the .env file manually
echo "<h3>Manual .env Loading Test:</h3>";
echo "<pre>";

$loaded = false;
foreach ($env_paths as $env_file) {
    if (file_exists($env_file)) {
        echo "Loading from: $env_file\n";
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, 'HUGGING_FACE_API_KEY') !== false) {
                $line_display = substr($line, 0, 30) . '...';
                echo "Found: $line_display\n";
                $loaded = true;
            }
        }
        if ($loaded) break;
    }
}

if (!$loaded) {
    echo "❌ Could not find HUGGING_FACE_API_KEY in any .env file\n";
}

echo "</pre>";

// Check what the config file sees
echo "<h3>After loading huggingface_config.php:</h3>";
echo "<pre>";

require_once __DIR__ . '/ai_includes/huggingface_config.php';

echo "HUGGING_FACE_API_KEY constant: " . (defined('HUGGING_FACE_API_KEY') ? 'DEFINED' : 'NOT DEFINED') . "\n";
if (defined('HUGGING_FACE_API_KEY')) {
    $key = HUGGING_FACE_API_KEY;
    $display = empty($key) ? '(empty string)' : (strlen($key) > 10 ? substr($key, 0, 10) . '...' : $key);
    echo "Value: $display\n";
}

echo "getenv('HUGGING_FACE_API_KEY'): " . (getenv('HUGGING_FACE_API_KEY') ?: '(not set)') . "\n";
echo "\$_ENV['HUGGING_FACE_API_KEY']: " . ($_ENV['HUGGING_FACE_API_KEY'] ?? '(not set)') . "\n";

echo "</pre>";

// Show current directory info
echo "<h3>Directory Info:</h3>";
echo "<pre>";
echo "__DIR__: " . __DIR__ . "\n";
echo "__FILE__: " . __FILE__ . "\n";
echo "\$_SERVER['DOCUMENT_ROOT']: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "getcwd(): " . getcwd() . "\n";
echo "</pre>";
?>
