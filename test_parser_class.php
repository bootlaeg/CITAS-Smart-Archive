<?php
// Clear any opcode cache
if (function_exists('opcache_reset')) {
    opcache_reset();
}

require_once 'ai_includes/document_parser.php';

echo "DocumentParser class methods:\n";
$methods = get_class_methods('DocumentParser');
foreach ($methods as $method) {
    echo "  - " . $method . "\n";
}

// Check if methods exist
echo "\nMethod existence check:\n";
echo "  extractText: " . (method_exists('DocumentParser', 'extractText') ? 'YES' : 'NO') . "\n";
echo "  extractStructuredDocument: " . (method_exists('DocumentParser', 'extractStructuredDocument') ? 'YES' : 'NO') . "\n";
echo "  cleanText: " . (method_exists('DocumentParser', 'cleanText') ? 'YES' : 'NO') . "\n";

echo "\nTest file: " . __FILE__ . "\n";
?>
