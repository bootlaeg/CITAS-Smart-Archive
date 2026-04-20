<?php
/**
 * Find .env file location on Hostinger server
 */

echo "=== Searching for .env file ===\n\n";

// List of potential .env locations
$paths = [
    // Possible locations
    __DIR__ . '/.env',
    dirname(__DIR__) . '/.env',
    dirname(dirname(__DIR__)) . '/.env',
    dirname(dirname(dirname(__DIR__))) . '/.env',
    '/home/.env',
    $_SERVER['DOCUMENT_ROOT'] . '/.env',
    dirname($_SERVER['DOCUMENT_ROOT']) . '/.env',
];

echo "Searching in these locations:\n";
echo "Current dir: " . __DIR__ . "\n";
echo "Parent dir: " . dirname(__DIR__) . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script file: " . __FILE__ . "\n\n";

$found = false;
foreach ($paths as $path) {
    $status = file_exists($path) ? '✓ FOUND' : '✗ not found';
    echo "$status: $path\n";
    
    if (file_exists($path)) {
        $found = true;
        echo "  Size: " . filesize($path) . " bytes\n";
        echo "  First line: " . trim(fgets(fopen($path, 'r'))) . "\n";
    }
}

echo "\n";

if (!$found) {
    echo "❌ .env file not found in any standard location\n";
    echo "\nPlease provide one of these:\n";
    echo "1. The actual path where your .env file is located\n";
    echo "2. Or I can help you create one\n";
} else {
    echo "\n✅ Found! Update the path in check_hf_status.php to this location.\n";
}

// Also check if file exists in current directory
echo "\n--- Files in current directory ---\n";
$files = scandir(__DIR__);
foreach ($files as $file) {
    if (strpos($file, 'env') !== false) {
        echo "Found: $file\n";
    }
}
?>
