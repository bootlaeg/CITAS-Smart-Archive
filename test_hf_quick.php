<?php
/**
 * Quick Test: HuggingFace Summarization
 * Tests the HF API with actual summarization
 */

// Load environment
$env_file = __DIR__ . '/.env';
if (!file_exists($env_file)) {
    $env_file = dirname(__DIR__) . '/.env';
}

if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || empty(trim($line))) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Load HF service
require_once __DIR__ . '/ai_includes/huggingface_config.php';
require_once __DIR__ . '/ai_includes/huggingface_service.php';

echo "=== HuggingFace Summarization Test ===\n\n";

// Check API key
$key = getenv('HUGGING_FACE_API_KEY') ?: ($_ENV['HUGGING_FACE_API_KEY'] ?? '');
if (empty($key)) {
    echo "❌ ERROR: API key not found\n";
    exit(1);
}

echo "✓ API Key loaded: " . substr($key, 0, 20) . "...\n";
echo "✓ Model: " . HUGGING_FACE_MODEL . "\n\n";

// Create service
try {
    $hf = new HuggingFaceService();
    echo "✓ Service instantiated\n\n";
    
    // Test summarization
    echo "Testing summarization with sample text...\n";
    echo "---------------------------------------\n\n";
    
    $sample_text = "Machine learning is a subset of artificial intelligence that focuses on the development of algorithms and statistical models that enable computers to improve their performance on tasks through experience. Deep learning, which uses neural networks with multiple layers, has revolutionized many fields including computer vision and natural language processing. These techniques are now being applied to solve complex real-world problems.";
    
    echo "Input text (" . strlen($sample_text) . " chars):\n";
    echo "\"" . substr($sample_text, 0, 100) . "...\"\n\n";
    
    echo "Calling HuggingFace API (this may take 10-30 seconds on first run)...\n";
    
    $start = microtime(true);
    $result = $hf->summarize($sample_text, 50);
    $elapsed = microtime(true) - $start;
    
    echo "\n✓ Response received in " . round($elapsed, 2) . " seconds\n\n";
    
    if ($result['success']) {
        echo "✓ SUMMARIZATION SUCCESSFUL!\n\n";
        echo "Output (" . strlen($result['summary']) . " chars):\n";
        echo "\"" . $result['summary'] . "\"\n\n";
        echo "Word count: " . str_word_count($result['summary']) . " words\n";
        echo "\n✅ HuggingFace is working perfectly!\n";
        echo "Your journal conversions will use this speed.\n";
    } else {
        echo "❌ Summarization failed\n";
        echo "Error: " . $result['error'] . "\n";
        echo "\nNote: First request may fail due to model loading.\n";
        echo "Try again in a few seconds - model will be cached.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
