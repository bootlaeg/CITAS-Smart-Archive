<?php
/**
 * PHASE 1 Deployment Script
 * Executes both CHATBOT_FIX.sql and PHASE1_MIGRATION_2026_04_11.sql
 * Date: April 16, 2026
 */

require_once 'db_includes/db_connect.php';

error_log("========================================");
error_log("PHASE 1 DEPLOYMENT STARTED");
error_log("========================================");

$results = [];
$success = true;

// ============================================================================
// Step 1: Execute CHATBOT_FIX.sql
// ============================================================================
error_log("\n[STEP 1] Executing CHATBOT_FIX.sql...");

$chatbot_fix_queries = [
    "DELETE FROM chatbot_messages WHERE session_id = 0 OR session_id IS NULL",
    "DELETE FROM chatbot_sessions WHERE id = 0 OR id IS NULL",
    "DELETE FROM chatbot_access_requests WHERE id = 0 OR id IS NULL",
    "ALTER TABLE chatbot_sessions AUTO_INCREMENT = 100",
    "ALTER TABLE chatbot_messages AUTO_INCREMENT = 100",
    "ALTER TABLE chatbot_access_requests AUTO_INCREMENT = 100",
    "DELETE FROM chatbot_messages WHERE session_id NOT IN (SELECT id FROM chatbot_sessions) AND session_id IS NOT NULL"
];

foreach ($chatbot_fix_queries as $query) {
    if ($conn->query($query)) {
        error_log("  ✅ " . substr($query, 0, 60) . "...");
        $results[] = ['query' => substr($query, 0, 80), 'status' => 'SUCCESS'];
    } else {
        error_log("  ❌ FAILED: " . $conn->error);
        $results[] = ['query' => substr($query, 0, 80), 'status' => 'FAILED', 'error' => $conn->error];
        $success = false;
    }
}

// Verify chatbot tables are clean
$verify_chatbot = $conn->query("
    SELECT 'chatbot_sessions' as table_name, COUNT(*) as total_records, 
           SUM(IF(id <= 0, 1, 0)) as invalid_ids 
    FROM chatbot_sessions
    UNION ALL
    SELECT 'chatbot_messages', COUNT(*), SUM(IF(id <= 0, 1, 0))
    FROM chatbot_messages
    UNION ALL
    SELECT 'chatbot_access_requests', COUNT(*), SUM(IF(id <= 0, 1, 0))
    FROM chatbot_access_requests
");

if ($verify_chatbot && $verify_chatbot->num_rows > 0) {
    error_log("\n✅ CHATBOT_FIX verification:");
    while ($row = $verify_chatbot->fetch_assoc()) {
        error_log("  - {$row['table_name']}: {$row['total_records']} records, {$row['invalid_ids']} invalid");
    }
}

// ============================================================================
// Step 2: Execute PHASE1_MIGRATION_2026_04_11.sql
// ============================================================================
error_log("\n[STEP 2] Executing PHASE1_MIGRATION_2026_04_11.sql...");

$phase1_queries = [
    "ALTER TABLE thesis ADD COLUMN document_type ENUM('journal','book','thesis','report') DEFAULT 'thesis' AFTER file_type",
    "ALTER TABLE thesis ADD COLUMN page_count INT DEFAULT NULL COMMENT 'Number of pages extracted from uploaded file' AFTER document_type"
];

foreach ($phase1_queries as $query) {
    if ($conn->query($query)) {
        error_log("  ✅ " . substr($query, 0, 60) . "...");
        $results[] = ['query' => substr($query, 0, 80), 'status' => 'SUCCESS'];
    } else {
        // Column may already exist - that's OK
        if (strpos($conn->error, 'already exists') !== false) {
            error_log("  ⚠️  Column already exists (OK): " . substr($query, 0, 60));
            $results[] = ['query' => substr($query, 0, 80), 'status' => 'SKIPPED (EXISTS)'];
        } else {
            error_log("  ❌ FAILED: " . $conn->error);
            $results[] = ['query' => substr($query, 0, 80), 'status' => 'FAILED', 'error' => $conn->error];
            $success = false;
        }
    }
}

// Verify new columns exist
$verify_columns = $conn->query("
    SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'thesis' AND (COLUMN_NAME = 'document_type' OR COLUMN_NAME = 'page_count')
");

if ($verify_columns && $verify_columns->num_rows > 0) {
    error_log("\n✅ PHASE1_MIGRATION verification:");
    while ($row = $verify_columns->fetch_assoc()) {
        error_log("  - {$row['COLUMN_NAME']}: {$row['COLUMN_TYPE']} (DEFAULT: {$row['COLUMN_DEFAULT']})");
    }
}

// ============================================================================
// Summary
// ============================================================================
error_log("\n========================================");
error_log("PHASE 1 DEPLOYMENT " . ($success ? "✅ COMPLETED SUCCESSFULLY" : "⚠️ COMPLETED WITH WARNINGS"));
error_log("========================================");

$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'message' => $success ? 'PHASE 1 deployment completed successfully!' : 'PHASE 1 deployment completed with some warnings',
    'timestamp' => date('Y-m-d H:i:s'),
    'results' => $results
], JSON_PRETTY_PRINT);
?>
