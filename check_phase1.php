<?php
require 'db_includes/db_connect.php';

$output = [];
$output[] = "=== PHASE 1 STATUS CHECK ===\n";
$output[] = "Time: " . date('Y-m-d H:i:s') . "\n";

// Check thesis table columns
$result = $conn->query("DESCRIBE thesis");
$columns = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = $row['Type'];
    }
}

$output[] = "\n[1] Checking thesis table columns:";
$output[] = "- document_type exists: " . (isset($columns['document_type']) ? "YES ✅" : "NO ❌");
$output[] = "- page_count exists: " . (isset($columns['page_count']) ? "YES ✅" : "NO ❌");

if (isset($columns['document_type'])) {
    $output[] = "  Type: " . $columns['document_type'];
}
if (isset($columns['page_count'])) {
    $output[] = "  Type: " . $columns['page_count'];
}

// Check chatbot tables
$output[] = "\n[2] Checking chatbot tables:";
$result = $conn->query("SELECT COUNT(*) as cnt FROM chatbot_sessions WHERE id <= 0");
$badSessions = $result ? $result->fetch_assoc()['cnt'] : -1;
$output[] = "- Bad chatbot_sessions (id ≤ 0): " . ($badSessions == 0 ? "0 ✅" : "$badSessions ❌");

// Overall status
$output[] = "\n[3] OVERALL STATUS:";
$phase1_complete = isset($columns['document_type']) && isset($columns['page_count']) && $badSessions == 0;
$output[] = $phase1_complete ? "✅ PHASE 1 COMPLETE" : "⚠️ PHASE 1 INCOMPLETE";

// Write to file
file_put_contents(__DIR__ . '/PHASE1_STATUS.txt', implode("\n", $output));

// Also output as plain text
header('Content-Type: text/plain');
echo implode("\n", $output);

$conn->close();
?>
