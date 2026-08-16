<?php
// Debug script to check errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Information</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Working Directory: " . __DIR__ . "</p>";

// Check if files exist
$files = [
    'db_includes/db_connect.php',
    'ai_includes/simple_pdf.php',
    'ai_includes/final_ocr_processor.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    echo "<p>$file: " . (file_exists($path) ? 'EXISTS' : 'MISSING') . "</p>";
}

// Try to load
echo "<h3>Loading Files:</h3>";
try {
    require_once __DIR__ . '/db_includes/db_connect.php';
    echo "<p>✓ db_connect.php loaded</p>";
} catch (Exception $e) {
    echo "<p>✗ db_connect.php error: " . $e->getMessage() . "</p>";
}

try {
    require_once __DIR__ . '/ai_includes/simple_pdf.php';
    echo "<p>✓ simple_pdf.php loaded</p>";
} catch (Exception $e) {
    echo "<p>✗ simple_pdf.php error: " . $e->getMessage() . "</p>";
}

try {
    require_once __DIR__ . '/ai_includes/final_ocr_processor.php';
    echo "<p>✓ final_ocr_processor.php loaded</p>";
} catch (Exception $e) {
    echo "<p>✗ final_ocr_processor.php error: " . $e->getMessage() . "</p>";
}

echo "<h3>Session Check:</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p>Logged in: " . (isset($_SESSION['user_id']) ? 'YES' : 'NO') . "</p>";