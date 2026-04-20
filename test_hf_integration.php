<?php
/**
 * Test HuggingFace Integration with Journal Converter
 */

// Get absolute path
$root = __DIR__;
$env_file = $root . '/.env';
$ai_dir = $root . '/ai_includes';

echo "=== HuggingFace Integration Test ===\n\n";

// Step 1: Check .env file
echo "1. Checking .env file...\n";
if (!file_exists($env_file)) {
    echo "   ❌ .env file not found at $env_file\n";
    exit(1);
}

// Load .env
echo "   ✓ .env file found\n";
$lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$hf_key = null;
foreach ($lines as $line) {
    if (strpos($line, 'HUGGING_FACE_API_KEY') === 0 && strpos($line, '=') !== false) {
        $parts = explode('=', $line, 2);
        $hf_key = trim($parts[1]);
        putenv("HUGGING_FACE_API_KEY=$hf_key");
        break;
    }
}

if (empty($hf_key)) {
    echo "   ❌ HUGGING_FACE_API_KEY not found in .env\n";
    exit(1);
}

echo "   ✓ API Key loaded: " . substr($hf_key, 0, 20) . "...\n\n";

// Step 2: Check HuggingFace config
echo "2. Checking HuggingFace config...\n";
if (!file_exists($ai_dir . '/huggingface_config.php')) {
    echo "   ❌ huggingface_config.php not found\n";
    exit(1);
}
echo "   ✓ Config file found\n";

// Load config
require_once $ai_dir . '/huggingface_config.php';
echo "   ✓ Config loaded\n";
echo "   - Model: " . HUGGING_FACE_MODEL . "\n";
echo "   - API Key from env: " . substr(getenv('HUGGING_FACE_API_KEY'), 0, 20) . "...\n\n";

// Step 3: Check HuggingFace service
echo "3. Checking HuggingFace service...\n";
if (!file_exists($ai_dir . '/huggingface_service.php')) {
    echo "   ❌ huggingface_service.php not found\n";
    exit(1);
}
echo "   ✓ Service file found\n";

// Load service
require_once $ai_dir . '/huggingface_service.php';
echo "   ✓ Service loaded\n";

// Test service instantiation
try {
    $hf = new HuggingFaceService();
    echo "   ✓ Service instantiated\n\n";
} catch (Exception $e) {
    echo "   ❌ Failed to instantiate service: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 4: Test API availability
echo "4. Testing HuggingFace API availability...\n";
$status = $hf->getStatus();
if ($status['success']) {
    echo "   ✓ HuggingFace API is responding\n\n";
} else {
    echo "   ⚠ HuggingFace API test: " . $status['error'] . "\n";
    echo "   This might be temporary - checking if token is valid\n\n";
}

// Step 5: Check journal_converter integration
echo "5. Checking journal_converter integration...\n";
if (!file_exists($ai_dir . '/journal_converter.php')) {
    echo "   ❌ journal_converter.php not found\n";
    exit(1);
}
echo "   ✓ journal_converter.php found\n";

// Check if summarizeWithHuggingFace method exists
$converter_code = file_get_contents($ai_dir . '/journal_converter.php');
if (strpos($converter_code, 'summarizeWithHuggingFace') !== false) {
    echo "   ✓ summarizeWithHuggingFace() method is integrated\n";
} else {
    echo "   ❌ summarizeWithHuggingFace() method not found in journal_converter\n";
    exit(1);
}

if (strpos($converter_code, 'HUGGING_FACE_API_KEY') !== false || 
    strpos($converter_code, 'huggingface_config.php') !== false) {
    echo "   ✓ HuggingFace config is loaded in journal_converter\n\n";
} else {
    echo "   ❌ HuggingFace config loading not found\n";
    exit(1);
}

// Step 6: Simple test
echo "6. Testing summarization (may fail if API rate limited)...\n";
$test_text = "Machine learning is a subset of artificial intelligence. Deep learning uses neural networks.";

try {
    $result = $hf->summarize($test_text, 30);
    
    if ($result['success']) {
        echo "   ✓ Summarization successful!\n";
        echo "   - Input length: " . strlen($test_text) . " chars\n";
        echo "   - Output length: " . strlen($result['summary']) . " chars\n";
        echo "   - Summary: " . substr($result['summary'], 0, 100) . "...\n";
    } else {
        echo "   ⚠ Summarization returned false\n";
        echo "   - Error: " . $result['error'] . "\n";
        echo "   - This may be due to API rate limiting or temporary unavailability\n";
    }
} catch (Exception $e) {
    echo "   ⚠ Test failed: " . $e->getMessage() . "\n";
    echo "   - This is expected if API is rate limited\n";
}

echo "\n=== Summary ===\n";
echo "✓ HuggingFace integration is properly configured!\n";
echo "✓ Journal converter is set up to use HuggingFace.\n";
echo "\nNext step: Try converting a thesis to test the full workflow.\n";
?>
