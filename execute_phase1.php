<?php
/**
 * Direct Phase 1 Execution & Verification
 */

require 'db_includes/db_connect.php';

// Suppress warnings for columns that might already exist
mysqli_report(MYSQLI_REPORT_OFF);

$log = [];

// Execute CHATBOT_FIX
$log[] = "=== Executing CHATBOT_FIX ===";
$fix_queries = [
    "DELETE FROM chatbot_messages WHERE session_id = 0 OR session_id IS NULL",
    "DELETE FROM chatbot_sessions WHERE id = 0 OR id IS NULL",
    "DELETE FROM chatbot_access_requests WHERE id = 0 OR id IS NULL",
    "ALTER TABLE chatbot_sessions AUTO_INCREMENT = 100",
    "ALTER TABLE chatbot_messages AUTO_INCREMENT = 100",
    "ALTER TABLE chatbot_access_requests AUTO_INCREMENT = 100"
];

foreach ($fix_queries as $q) {
    $conn->query($q);
    $log[] = "Query: " . substr($q, 0, 50) . "...";
}

// Execute PHASE1_MIGRATION
$log[] = "\n=== Executing PHASE1_MIGRATION ===";

// Add document_type if not exists
if ($conn->query("SHOW COLUMNS FROM thesis LIKE 'document_type'")) {
    $result = $conn->query("SHOW COLUMNS FROM thesis LIKE 'document_type'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE thesis ADD COLUMN document_type ENUM('journal','book','thesis','report') DEFAULT 'thesis' AFTER file_type");
        $log[] = "Added document_type column ✅";
    } else {
        $log[] = "document_type column already exists ✅";
    }
} else {
    $conn->query("ALTER TABLE thesis ADD COLUMN document_type ENUM('journal','book','thesis','report') DEFAULT 'thesis' AFTER file_type");
    $log[] = "Added document_type column ✅";
}

// Add page_count if not exists
if ($conn->query("SHOW COLUMNS FROM thesis LIKE 'page_count'")) {
    $result = $conn->query("SHOW COLUMNS FROM thesis LIKE 'page_count'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE thesis ADD COLUMN page_count INT DEFAULT NULL COMMENT 'Number of pages extracted from uploaded file' AFTER document_type");
        $log[] = "Added page_count column ✅";
    } else {
        $log[] = "page_count column already exists ✅";
    }
} else {
    $conn->query("ALTER TABLE thesis ADD COLUMN page_count INT DEFAULT NULL COMMENT 'Number of pages extracted from uploaded file' AFTER document_type");
    $log[] = "Added page_count column ✅";
}

// Verify all changes
$log[] = "\n=== VERIFICATION ===";

$result = $conn->query("DESCRIBE thesis");
$has_doc_type = false;
$has_page_count = false;
while ($row = $result->fetch_assoc()) {
    if ($row['Field'] === 'document_type') $has_doc_type = true;
    if ($row['Field'] === 'page_count') $has_page_count = true;
}

$log[] = "document_type exists: " . ($has_doc_type ? "✅ YES" : "❌ NO");
$log[] = "page_count exists: " . ($has_page_count ? "✅ YES" : "❌ NO");

$result = $conn->query("SELECT COUNT(*) as bad FROM chatbot_sessions WHERE id <= 0");
$bad_count = $result->fetch_assoc()['bad'];
$log[] = "Bad chatbot_sessions: " . ($bad_count == 0 ? "✅ NONE" : "❌ {$bad_count}");

// Final status
$log[] = "\n=== FINAL STATUS ===";
if ($has_doc_type && $has_page_count && $bad_count == 0) {
    $log[] = "✅ PHASE 1 DEPLOYMENT SUCCESSFUL!";
    $status = "COMPLETE";
} else {
    $log[] = "⚠️ PHASE 1 NEEDS REVIEW";
    $status = "INCOMPLETE";
}

// Output
header('Content-Type: text/plain; charset=utf-8');
echo implode("\n", $log);

// Also save to file
file_put_contents('phase1_result.txt', implode("\n", $log) . "\n\nStatus: $status\nTime: " . date('Y-m-d H:i:s'));

$conn->close();
?>
