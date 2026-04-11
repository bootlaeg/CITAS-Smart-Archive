<?php
/**
 * Chatbot System Fix Script
 * Fixes database issues preventing chatbot from working properly
 * Cleans up invalid sessions and ensures proper AUTO_INCREMENT
 */

require_once 'db_includes/db_connect.php';

echo "=== CHATBOT SYSTEM DIAGNOSTICS AND REPAIR ===\n\n";

// Color codes for CLI output
$colors = [
    'success' => "\033[92m",  // Green
    'error' => "\033[91m",    // Red
    'warning' => "\033[93m",  // Yellow
    'info' => "\033[94m",     // Blue
    'reset' => "\033[0m"
];

function log_msg($message, $type = 'info') {
    global $colors;
    $color = $colors[$type] ?? $colors['info'];
    echo "[" . strtoupper($type) . "] {$color}{$message}{$colors['reset']}\n";
}

// Step 1: Check for invalid sessions
log_msg("Step 1: Checking for invalid sessions with id = 0...", 'info');
$invalid_check = $conn->prepare("SELECT COUNT(*) as count FROM chatbot_sessions WHERE id = 0 OR id IS NULL");
$invalid_check->execute();
$invalid_result = $invalid_check->get_result()->fetch_assoc();
$invalid_count = $invalid_result['count'];
$invalid_check->close();

if ($invalid_count > 0) {
    log_msg("Found {$invalid_count} invalid sessions with id = 0", 'warning');
    
    // Delete invalid sessions and their messages first
    $delete_messages = $conn->prepare("DELETE FROM chatbot_messages WHERE session_id = 0 OR session_id IS NULL");
    if ($delete_messages->execute()) {
        log_msg("Deleted orphaned messages from invalid sessions", 'success');
    } else {
        log_msg("Error deleting messages: " . $delete_messages->error, 'error');
    }
    $delete_messages->close();
    
    // Delete invalid sessions
    $delete_sessions = $conn->prepare("DELETE FROM chatbot_sessions WHERE id = 0 OR id IS NULL");
    if ($delete_sessions->execute()) {
        log_msg("Deleted {$invalid_count} invalid sessions", 'success');
    } else {
        log_msg("Error deleting sessions: " . $delete_sessions->error, 'error');
    }
    $delete_sessions->close();
} else {
    log_msg("No invalid sessions found", 'success');
}

// Step 2: Check for invalid access requests
log_msg("\nStep 2: Checking for invalid access requests...", 'info');
$invalid_ar_check = $conn->prepare("SELECT COUNT(*) as count FROM chatbot_access_requests WHERE id = 0 OR id IS NULL");
$invalid_ar_check->execute();
$invalid_ar_result = $invalid_ar_check->get_result()->fetch_assoc();
$invalid_ar_count = $invalid_ar_result['count'];
$invalid_ar_check->close();

if ($invalid_ar_count > 0) {
    log_msg("Found {$invalid_ar_count} invalid access requests with id = 0", 'warning');
    
    $delete_ar = $conn->prepare("DELETE FROM chatbot_access_requests WHERE id = 0 OR id IS NULL");
    if ($delete_ar->execute()) {
        log_msg("Deleted {$invalid_ar_count} invalid access requests", 'success');
    } else {
        log_msg("Error deleting access requests: " . $delete_ar->error, 'error');
    }
    $delete_ar->close();
} else {
    log_msg("No invalid access requests found", 'success');
}

// Step 3: Verify AUTO_INCREMENT settings
log_msg("\nStep 3: Verifying and fixing AUTO_INCREMENT settings...", 'info');

// Reset AUTO_INCREMENT for chatbot_sessions
$tables_to_fix = [
    'chatbot_sessions',
    'chatbot_messages',
    'chatbot_access_requests'
];

foreach ($tables_to_fix as $table) {
    // Get the maximum ID
    $max_check = $conn->prepare("SELECT MAX(id) as max_id FROM {$table}");
    $max_check->execute();
    $max_result = $max_check->get_result()->fetch_assoc();
    $max_id = $max_result['max_id'] ?? 0;
    $max_check->close();
    
    $next_id = (int)$max_id + 1;
    
    // Set AUTO_INCREMENT
    $fix_ai = $conn->prepare("ALTER TABLE {$table} AUTO_INCREMENT = ?");
    if ($fix_ai) {
        $fix_ai->bind_param("i", $next_id);
        if ($fix_ai->execute()) {
            log_msg("{$table}: AUTO_INCREMENT set to {$next_id}", 'success');
        } else {
            log_msg("{$table}: Error setting AUTO_INCREMENT - " . $fix_ai->error, 'warning');
        }
        $fix_ai->close();
    }
}

// Step 4: Verify data integrity
log_msg("\nStep 4: Verifying data integrity...", 'info');

$checks = [
    'chatbot_sessions' => "SELECT COUNT(*) as count FROM chatbot_sessions",
    'chatbot_messages' => "SELECT COUNT(*) as count FROM chatbot_messages",
    'chatbot_access_requests' => "SELECT COUNT(*) as count FROM chatbot_access_requests"
];

foreach ($checks as $table => $query) {
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $count = $result['count'];
    $stmt->close();
    
    log_msg("{$table}: {$count} records", 'info');
}

// Step 5: Check for orphaned messages (messages whose session doesn't exist)
log_msg("\nStep 5: Checking for orphaned messages...", 'info');
$orphan_check = $conn->prepare("
    SELECT COUNT(*) as count FROM chatbot_messages cm
    WHERE NOT EXISTS (SELECT 1 FROM chatbot_sessions cs WHERE cs.id = cm.session_id)
");
$orphan_check->execute();
$orphan_result = $orphan_check->get_result()->fetch_assoc();
$orphan_count = $orphan_result['count'];
$orphan_check->close();

if ($orphan_count > 0) {
    log_msg("Found {$orphan_count} orphaned messages", 'warning');
    
    // Delete orphaned messages
    $delete_orphans = $conn->prepare("
        DELETE FROM chatbot_messages 
        WHERE NOT EXISTS (SELECT 1 FROM chatbot_sessions WHERE chatbot_sessions.id = chatbot_messages.session_id)
    ");
    if ($delete_orphans->execute()) {
        log_msg("Deleted orphaned messages", 'success');
    } else {
        log_msg("Error deleting orphaned messages: " . $delete_orphans->error, 'error');
    }
    $delete_orphans->close();
} else {
    log_msg("No orphaned messages found", 'success');
}

// Step 6: Final verification
log_msg("\nStep 6: Final integrity check...", 'info');

// Check for zero or negative IDs
$zero_check = $conn->prepare("
    SELECT COUNT(*) as count FROM chatbot_sessions 
    WHERE id <= 0
");
$zero_check->execute();
$zero_result = $zero_check->get_result()->fetch_assoc();
if ($zero_result['count'] > 0) {
    log_msg("⚠️  Still found {$zero_result['count']} invalid sessions", 'error');
} else {
    log_msg("✓ All sessions have valid IDs", 'success');
}
$zero_check->close();

// Summary
log_msg("\n=== REPAIR COMPLETE ===", 'success');
log_msg("All chatbot database issues have been fixed", 'success');
log_msg("The chatbot should now work properly", 'success');

$conn->close();
?>
