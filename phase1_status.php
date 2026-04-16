<?php
require 'db_includes/db_connect.php';

// Check if columns exist
$result = $conn->query("SHOW COLUMNS FROM thesis LIKE 'document_type'");
$doc_type_exists = $result && $result->num_rows > 0;

$result = $conn->query("SHOW COLUMNS FROM thesis LIKE 'page_count'");
$page_count_exists = $result && $result->num_rows > 0;

// Check chatbot tables for bad records
$result = $conn->query("SELECT COUNT(*) as bad_count FROM chatbot_sessions WHERE id <= 0");
$chatbot_bad = $result ? $result->fetch_assoc()['bad_count'] : -1;

?>
<!DOCTYPE html>
<html>
<head>
    <title>PHASE 1 Status</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; border-left: 5px solid #007bff; }
        .success { border-left-color: #28a745; color: #155724; }
        .warning { border-left-color: #ffc107; color: #856404; }
        .pending { border-left-color: #007bff; color: #004085; }
        h2 { margin-top: 0; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🚀 PHASE 1 DEPLOYMENT STATUS</h1>
    
    <div class="box <?php echo ($page_count_exists && $doc_type_exists) ? 'success' : 'warning'; ?>">
        <h2>✅ Database Schema Migration</h2>
        <p><strong>document_type column:</strong> <?php echo $doc_type_exists ? '✅ Created' : '❌ Not found'; ?></p>
        <p><strong>page_count column:</strong> <?php echo $page_count_exists ? '✅ Created' : '❌ Not found'; ?></p>
    </div>

    <div class="box <?php echo $chatbot_bad == 0 ? 'success' : 'warning'; ?>">
        <h2>✅ Chatbot Database Fix</h2>
        <p><strong>Invalid chatbot_sessions (id ≤ 0):</strong> <?php echo $chatbot_bad == 0 ? '✅ None found (Fixed!)' : "⚠️ {$chatbot_bad} found"; ?></p>
    </div>

    <div class="box <?php echo ($page_count_exists && $doc_type_exists && $chatbot_bad == 0) ? 'success' : 'pending'; ?>">
        <h2>📊 Overall Status</h2>
        <?php if ($page_count_exists && $doc_type_exists && $chatbot_bad == 0): ?>
            <h3 style="color: #28a745;">✅ PHASE 1 COMPLETE!</h3>
            <p>All migrations executed successfully. Ready for PHASE 2.</p>
            <p><strong>Next Step:</strong> Implement validation rules for document types</p>
        <?php else: ?>
            <h3 style="color: #ffc107;">⚠️ PHASE 1 INCOMPLETE</h3>
            <p>Some migrations did not execute. Running manual SQL execution...</p>
        <?php endif; ?>
    </div>
</body>
</html>
