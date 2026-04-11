<?php
/**
 * Fix Chatbot Sessions Auto Increment
 * Fixes sessions with id = 0 and resets auto_increment counter
 */

require_once 'db_includes/db_connect.php';

echo "=== FIXING CHATBOT SESSIONS AUTO INCREMENT ===\n\n";

// 1. Check current state
echo "1. Checking for sessions with id = 0:\n";
$check = $conn->query("SELECT COUNT(*) as count FROM chatbot_sessions WHERE id = 0 OR id IS NULL");
$result = $check->fetch_assoc();
echo "   Found: " . $result['count'] . " sessions with id = 0\n\n";

// 2. Delete invalid sessions
if ($result['count'] > 0) {
    echo "2. Deleting invalid sessions...\n";
    $delete = $conn->query("DELETE FROM chatbot_sessions WHERE id = 0 OR id IS NULL");
    if ($delete) {
        echo "   ✅ Deleted invalid sessions\n\n";
    } else {
        echo "   ❌ Error: " . $conn->error . "\n\n";
    }
}

// 3. Get max current id
echo "3. Finding max session ID:\n";
$max_check = $conn->query("SELECT MAX(id) as max_id FROM chatbot_sessions");
$max_result = $max_check->fetch_assoc();
$max_id = $max_result['max_id'] ?? 0;
echo "   Current max ID: " . ($max_id ?: "None") . "\n\n";

// 4. Reset auto_increment
echo "4. Resetting auto_increment:\n";
$new_auto_increment = ($max_id > 0) ? $max_id + 1 : 1;
$alter = $conn->query("ALTER TABLE chatbot_sessions AUTO_INCREMENT = " . $new_auto_increment);
if ($alter) {
    echo "   ✅ Auto increment set to: " . $new_auto_increment . "\n\n";
} else {
    echo "   ❌ Error: " . $conn->error . "\n\n";
}

// 5. Do the same for chatbot_messages table
echo "5. Checking chatbot_messages table:\n";
$check_msg = $conn->query("SELECT COUNT(*) as count FROM chatbot_messages WHERE id = 0 OR id IS NULL");
$msg_result = $check_msg->fetch_assoc();
echo "   Messages with id = 0: " . $msg_result['count'] . "\n\n";

if ($msg_result['count'] > 0) {
    echo "6. Deleting invalid messages...\n";
    $delete_msg = $conn->query("DELETE FROM chatbot_messages WHERE id = 0 OR id IS NULL");
    if ($delete_msg) {
        echo "   ✅ Deleted invalid messages\n\n";
    } else {
        echo "   ❌ Error: " . $conn->error . "\n\n";
    }
}

// 7. Reset messages auto_increment
echo "7. Resetting chatbot_messages auto_increment:\n";
$max_msg_check = $conn->query("SELECT MAX(id) as max_id FROM chatbot_messages");
$max_msg_result = $max_msg_check->fetch_assoc();
$max_msg_id = $max_msg_result['max_id'] ?? 0;
$new_msg_auto_increment = ($max_msg_id > 0) ? $max_msg_id + 1 : 1;
$alter_msg = $conn->query("ALTER TABLE chatbot_messages AUTO_INCREMENT = " . $new_msg_auto_increment);
if ($alter_msg) {
    echo "   ✅ Messages auto_increment set to: " . $new_msg_auto_increment . "\n\n";
} else {
    echo "   ❌ Error: " . $conn->error . "\n\n";
}

// 8. Verify
echo "8. Verification:\n";
$verify_sessions = $conn->query("SELECT COUNT(*) as count FROM chatbot_sessions WHERE id > 0");
$verify_result = $verify_sessions->fetch_assoc();
echo "   Valid sessions: " . $verify_result['count'] . "\n";

$verify_messages = $conn->query("SELECT COUNT(*) as count FROM chatbot_messages WHERE id > 0");
$verify_msg_result = $verify_messages->fetch_assoc();
echo "   Valid messages: " . $verify_msg_result['count'] . "\n\n";

echo "=== FIX COMPLETE ===\n";
echo "Sessions with id = 0 have been deleted and auto_increment has been reset.\n";
echo "New sessions should now be created with proper IDs.\n";

$conn->close();
?>
