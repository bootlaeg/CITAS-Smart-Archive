<?php
// Diagnose document_parser.php loading
ob_start();

echo "Step 1: Checking file exists...\n";
$parserPath = __DIR__ . '/document_parser.php';
echo ($file_exists = file_exists($parserPath)) ? "✓ File exists\n" : "✗ File missing at $parserPath\n";

if (!$file_exists) {
    ob_end_clean();
    header('Content-Type: text/plain');
    exit('File not found: ' . $parserPath);
}

echo "Step 2: Loading document_parser.php...\n";
$errorsBefore = error_get_last();

try {
    include_once $parserPath;
    echo "✓ Loaded successfully\n";
} catch (Throwable $e) {
    ob_end_clean();
    header('Content-Type: text/plain');
    exit("Error loading file: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
}

echo "Step 3: Checking class exists...\n";
$classExists = class_exists('DocumentParser');
echo ($classExists ? "✓ DocumentParser class found\n" : "✗ Class not found\n");

if (!$classExists) {
    ob_end_clean();
    header('Content-Type: text/plain');
    exit("Class not found after including file");
}

echo "Step 4: Checking class methods...\n";
$methods = get_class_methods('DocumentParser');
echo "Found methods:\n";
foreach ($methods as $method) {
    echo "  - " . $method . "\n";
}

$content = ob_get_clean();
header('Content-Type: text/plain');
echo $content;
?>
