<?php
/**
 * Simple API test - tests Hugging Face service directly
 */

echo "=== HUGGING FACE API SIMPLE TEST ===\n\n";

// Load service
echo "Loading Hugging Face service...\n";
require_once 'ai_includes/huggingface_service.php';

$hf = new HuggingFaceService();

echo "\nTest 1: Testing with sample text...\n";
echo str_repeat("=", 50) . "\n";

$sample_text = "Artificial intelligence is transforming modern technology. " .
               "Machine learning algorithms can process vast amounts of data. " .
               "Deep neural networks achieve high accuracy on complex tasks. " .
               "We developed a new algorithm for image classification. " .
               "Our method uses convolutional neural networks. " .
               "Testing showed 95 percent accuracy on the dataset. " .
               "Previous methods achieved only 85 percent accuracy. " .
               "Our approach is faster to train and requires less memory. " .
               "We compared our method with five existing approaches. " .
               "The results demonstrate the effectiveness of our technique. " .
               "Future work will focus on improving generalization. " .
               "We plan to test on additional datasets. " .
               "This research has applications in healthcare and finance.";

echo "Input: " . strlen($sample_text) . " characters\n\n";

$result = $hf->summarize($sample_text, 150);

echo "Output:\n";
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

echo "\n";
echo str_repeat("=", 50) . "\n";
echo "Analysis:\n";

if ($result['success']) {
    echo "✓ SUCCESS\n";
    echo "  Method: " . ($result['method'] ?? 'API') . "\n";
    echo "  Word count: " . ($result['word_count'] ?? '?') . "\n";
} else {
    echo "✗ FAILED\n";
    echo "  Error: " . $result['error'] . "\n";
}

echo "\n";
echo "Log file contents:\n";
echo str_repeat("=", 50) . "\n";

$log_file = 'logs/huggingface_api.log';
if (file_exists($log_file)) {
    $contents = file_get_contents($log_file);
    // Show last 2000 characters
    echo substr($contents, -2000);
} else {
    echo "No log file found yet\n";
}
?>
<?php
/**
 * Test Hugging Face API directly without database dependency
 */

echo "=== HUGGING FACE API DIRECT TEST ===\n\n";

// Load service
echo "Loading Hugging Face service...\n";
require_once 'ai_includes/huggingface_service.php';

$hf = new HuggingFaceService();

echo "\nTest 1: Testing with sample text...\n";
echo str_repeat("=", 50) . "\n";

$sample_text = "Artificial intelligence is transforming modern technology. " .
               "Machine learning algorithms can process vast amounts of data. " .
               "Deep neural networks achieve high accuracy on complex tasks. " .
               "We developed a new algorithm for image classification. " .
               "Our method uses convolutional neural networks. " .
               "Testing showed 95 percent accuracy on the dataset. " .
               "Previous methods achieved only 85 percent accuracy. " .
               "Our approach is faster to train and requires less memory. " .
               "We compared our method with five existing approaches. " .
               "The results demonstrate the effectiveness of our technique. " .
               "Future work will focus on improving generalization. " .
               "We plan to test on additional datasets. " .
               "This research has applications in healthcare and finance.";

echo "Input: " . strlen($sample_text) . " characters\n\n";

$result = $hf->summarize($sample_text, 150);

echo "Output:\n";
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

echo "\n";
echo str_repeat("=", 50) . "\n";
echo "Analysis:\n";

if ($result['success']) {
    echo "✓ SUCCESS\n";
    echo "  Method: " . ($result['method'] ?? 'API') . "\n";
    echo "  Word count: " . ($result['word_count'] ?? '?') . "\n";
} else {
    echo "✗ FAILED\n";
    echo "  Error: " . $result['error'] . "\n";
}

echo "\n";
echo "Log file contents:\n";
echo str_repeat("=", 50) . "\n";

$log_file = 'logs/huggingface_api.log';
if (file_exists($log_file)) {
    $contents = file_get_contents($log_file);
    // Show last 2000 characters
    echo substr($contents, -2000);
} else {
    echo "No log file found yet\n";
}
?>
