<?php
/**
 * PHASE 1 Verification Script
 * Checks if both migrations executed successfully
 */

require_once 'db_includes/db_connect.php';

header('Content-Type: application/json');

$verification = [
    'phase1_status' => 'CHECKING',
    'chatbot_fix' => [],
    'phase1_migration' => [],
    'overall_status' => 'PENDING'
];

// ============================================================================
// Check CHATBOT_FIX Results
// ============================================================================
$chatbot_check = $conn->query("
    SELECT 
        'chatbot_sessions' as table_name, COUNT(*) as records, 
        SUM(IF(id <= 0, 1, 0)) as invalid_count
    FROM chatbot_sessions
    UNION ALL
    SELECT 'chatbot_messages', COUNT(*), SUM(IF(id <= 0, 1, 0))
    FROM chatbot_messages
    UNION ALL
    SELECT 'chatbot_access_requests', COUNT(*), SUM(IF(id <= 0, 1, 0))
    FROM chatbot_access_requests
");

if ($chatbot_check && $chatbot_check->num_rows > 0) {
    while ($row = $chatbot_check->fetch_assoc()) {
        $verification['chatbot_fix'][] = [
            'table' => $row['table_name'],
            'total_records' => $row['records'],
            'invalid_records' => $row['invalid_count'],
            'status' => ($row['invalid_count'] == 0) ? '✅ FIXED' : '❌ HAS ISSUES'
        ];
    }
}

// ============================================================================
// Check PHASE1_MIGRATION Results
// ============================================================================
$column_check = $conn->query("
    SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT, IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'thesis' AND TABLE_SCHEMA = DATABASE()
    AND (COLUMN_NAME = 'document_type' OR COLUMN_NAME = 'page_count')
    ORDER BY ORDINAL_POSITION
");

if ($column_check && $column_check->num_rows > 0) {
    while ($row = $column_check->fetch_assoc()) {
        $verification['phase1_migration'][] = [
            'column_name' => $row['COLUMN_NAME'],
            'type' => $row['COLUMN_TYPE'],
            'default' => $row['COLUMN_DEFAULT'],
            'nullable' => $row['IS_NULLABLE'],
            'status' => '✅ CREATED'
        ];
    }
} else {
    $verification['phase1_migration'][] = [
        'status' => '❌ COLUMNS NOT FOUND - Migration may have failed'
    ];
}

// ============================================================================
// Overall Status
// ============================================================================
$chatbot_ok = !empty($verification['chatbot_fix']) && 
              $verification['chatbot_fix'][0]['status'] === '✅ FIXED';
$phase1_ok = !empty($verification['phase1_migration']) && 
             $verification['phase1_migration'][0]['status'] === '✅ CREATED';

if ($chatbot_ok && $phase1_ok) {
    $verification['overall_status'] = '✅ PHASE 1 DEPLOYMENT SUCCESSFUL';
    $verification['phase1_complete'] = true;
    $verification['next_step'] = 'Proceed to PHASE 2: Validation & Blocking Rules';
} else {
    $verification['overall_status'] = '⚠️ PHASE 1 DEPLOYMENT INCOMPLETE';
    $verification['phase1_complete'] = false;
    $verification['issues'] = [];
    if (!$chatbot_ok) $verification['issues'][] = 'Chatbot fix may not have executed';
    if (!$phase1_ok) $verification['issues'][] = 'Phase 1 migration may not have executed';
}

$verification['checked_at'] = date('Y-m-d H:i:s');

echo json_encode($verification, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

$conn->close();
?>
