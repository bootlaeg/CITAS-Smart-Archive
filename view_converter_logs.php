<?php
/**
 * View Apache Error Logs for JournalConverter
 */

$log_file = '/home/u965322812/logs/error.log';

if (!file_exists($log_file)) {
    die("Error log not found at: $log_file");
}

// Read last 100 lines
$lines = array_slice(file($log_file), -100);

// Filter for journal_converter entries
$converter_logs = array_filter($lines, function($line) {
    return strpos($line, 'journal_converter') !== false || 
           strpos($line, 'JournalConverter') !== false ||
           strpos($line, 'Ollama') !== false;
});

header('Content-Type: text/plain');
echo "=== Recent Journal Converter Logs ===\n\n";
echo implode('', $converter_logs);
?>
