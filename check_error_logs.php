<?php
/**
 * Check Apache Error Log
 * Shows recent errors from PHP execution
 */

echo "<h2>Checking Apache Error Logs</h2><pre>";

// Possible log locations on Hostinger
$possible_logs = [
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log',
    '/home/*/logs/error.log',
    '/hostedfiles/*/error.log',
];

foreach ($possible_logs as $log_file) {
    if (file_exists($log_file)) {
        echo "Found log: $log_file\n";
        echo str_repeat("=", 80) . "\n";
        
        // Get last 50 lines
        $lines = file($log_file);
        $recent = array_slice($lines, -50);
        
        foreach ($recent as $line) {
            if (strpos($line, '[JournalConverter]') !== false || 
                strpos($line, 'journal_converter') !== false ||
                strpos($line, 'MySQL') !== false ||
                strpos($line, 'gone away') !== false) {
                echo $line;
            }
        }
        echo "\n";
    }
}

// Try to see PHP ini error log setting
echo "\n--- PHP Configuration ---\n";
echo "error_log = " . ini_get('error_log') . "\n";
echo "display_errors = " . ini_get('display_errors') . "\n";

// Try to read from set error log location
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    echo "\nReading from ini_get('error_log'):\n";
    echo str_repeat("=", 80) . "\n";
    
    $lines = file($error_log);
    $recent = array_slice($lines, -30);
    
    foreach ($recent as $line) {
        if (strpos($line, 'journal') !== false || 
            strpos($line, 'MySQL') !== false ||
            strpos($line, 'gone') !== false) {
            echo $line;
        }
    }
}

echo "</pre>";
?>
