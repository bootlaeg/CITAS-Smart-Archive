<?php
/**
 * Chatbot System Verification Tool
 * Checks all aspects of chatbot functionality
 */

require_once 'db_includes/db_connect.php';

// Security check
if (!is_admin()) {
    die("This tool is for admins only");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot System Verification - CITAS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #E67E22;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .check-section {
            margin: 20px 0;
            padding: 20px;
            border-left: 4px solid #ddd;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .check-section.success {
            border-left-color: #27ae60;
            background: #f0fdf4;
        }
        .check-section.error {
            border-left-color: #e74c3c;
            background: #fef2f2;
        }
        .check-section.warning {
            border-left-color: #f39c12;
            background: #fffbea;
        }
        .check-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-badge.ok { background: #27ae60; color: white; }
        .status-badge.error { background: #e74c3c; color: white; }
        .status-badge.warning { background: #f39c12; color: white; }
        .check-content {
            margin-top: 10px;
            color: #555;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        .data-table th, .data-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .data-table th {
            background: #f0f0f0;
            font-weight: 600;
        }
        .data-table tr:hover {
            background: #f9f9f9;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #E67E22;
            color: white;
        }
        .btn-primary:hover {
            background: #D35400;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        .icon {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <span class="icon">🤖</span>
            Chatbot System Verification & Diagnostics
        </h1>

        <?php
        echo "<!-- Current User ID: {$_SESSION['user_id']} -->\n";

        // Check 1: Database Connection
        echo "<div class='check-section " . ($conn ? 'success' : 'error') . "'>";
        echo "<div class='check-title'>";
        echo "✓ Database Connection";
        echo "<span class='status-badge " . ($conn ? 'ok' : 'error') . "'>" . ($conn ? 'Connected' : 'Failed') . "</span>";
        echo "</div>";
        echo "<div class='check-content'>";
        echo $conn ? "Database connection is active and working." : "Failed to connect to database.";
        echo "</div>";
        echo "</div>";

        // Check 2: Chatbot Tables Exist
        $tables_check = ['chatbot_sessions', 'chatbot_messages', 'chatbot_access_requests'];
        $tables_ok = true;
        
        foreach ($tables_check as $table) {
            $result = $conn->query("SHOW TABLES LIKE '{$table}'");
            if (!$result || $result->num_rows === 0) {
                $tables_ok = false;
                break;
            }
        }

        echo "<div class='check-section " . ($tables_ok ? 'success' : 'error') . "'>";
        echo "<div class='check-title'>";
        echo "✓ Required Tables";
        echo "<span class='status-badge " . ($tables_ok ? 'ok' : 'error') . "'>" . ($tables_ok ? 'All Present' : 'Missing') . "</span>";
        echo "</div>";
        echo "<div class='check-content'>";
        foreach ($tables_check as $table) {
            $result = $conn->query("SELECT COUNT(*) as count FROM {$table}");
            $count = $result->fetch_assoc()['count'];
            echo "• {$table}: <strong>{$count}</strong> records<br>";
        }
        echo "</div>";
        echo "</div>";

        // Check 3: Invalid Records
        $invalid_sessions = $conn->query("SELECT COUNT(*) as count FROM chatbot_sessions WHERE id <= 0")->fetch_assoc()['count'];
        $invalid_messages = $conn->query("SELECT COUNT(*) as count FROM chatbot_messages WHERE id <= 0")->fetch_assoc()['count'];
        $invalid_access = $conn->query("SELECT COUNT(*) as count FROM chatbot_access_requests WHERE id <= 0")->fetch_assoc()['count'];

        $has_invalid = ($invalid_sessions > 0 || $invalid_messages > 0 || $invalid_access > 0);

        echo "<div class='check-section " . ($has_invalid ? 'error' : 'success') . "'>";
        echo "<div class='check-title'>";
        echo "✓ Data Integrity";
        echo "<span class='status-badge " . (!$has_invalid ? 'ok' : 'error') . "'>" . (!$has_invalid ? 'Clean' : 'Issues Found') . "</span>";
        echo "</div>";
        echo "<div class='check-content'>";
        echo "Invalid session IDs (id ≤ 0): <strong style='color: " . ($invalid_sessions > 0 ? 'red' : 'green') . "'>{$invalid_sessions}</strong><br>";
        echo "Invalid message IDs (id ≤ 0): <strong style='color: " . ($invalid_messages > 0 ? 'red' : 'green') . "'>{$invalid_messages}</strong><br>";
        echo "Invalid access request IDs (id ≤ 0): <strong style='color: " . ($invalid_access > 0 ? 'red' : 'green') . "'>{$invalid_access}</strong><br>";
        
        if ($has_invalid) {
            echo "<br><strong>⚠️ Found Issues!</strong> Run the SQL fix script to clean up invalid records.";
        }
        echo "</div>";
        echo "</div>";

        // Check 4: Orphaned Records
        $orphaned_messages = $conn->query("
            SELECT COUNT(*) as count FROM chatbot_messages cm
            WHERE NOT EXISTS (SELECT 1 FROM chatbot_sessions cs WHERE cs.id = cm.session_id)
        ")->fetch_assoc()['count'];

        echo "<div class='check-section " . ($orphaned_messages > 0 ? 'warning' : 'success') . "'>";
        echo "<div class='check-title'>";
        echo "✓ Orphaned Records";
        echo "<span class='status-badge " . ($orphaned_messages > 0 ? 'warning' : 'ok') . "'>" . ($orphaned_messages > 0 ? 'Found Orphans' : 'Clean') . "</span>";
        echo "</div>";
        echo "<div class='check-content'>";
        echo "Orphaned messages (no parent session): <strong>{$orphaned_messages}</strong><br>";
        if ($orphaned_messages > 0) {
            echo "These messages belong to deleted sessions and are safe to delete.";
        }
        echo "</div>";
        echo "</div>";

        // Check 5: Sessions Overview
        $sessions_result = $conn->query("
            SELECT cs.user_id, u.full_name, COUNT(cs.id) as session_count, 
                   COUNT(cm.id) as total_messages, cs.thesis_id
            FROM chatbot_sessions cs
            LEFT JOIN chatbot_messages cm ON cs.id = cm.session_id
            LEFT JOIN users u ON cs.user_id = u.id
            GROUP BY cs.user_id, cs.thesis_id
        ");

        echo "<div class='check-section success'>";
        echo "<div class='check-title'>";
        echo "📊 Sessions Overview";
        echo "</div>";
        echo "<div class='check-content'>";
        
        if ($sessions_result->num_rows > 0) {
            echo "<table class='data-table'>";
            echo "<tr><th>User</th><th>Sessions</th><th>Messages</th><th>Thesis</th></tr>";
            while ($row = $sessions_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['full_name'] ?? 'Unknown') . "</td>";
                echo "<td>" . $row['session_count'] . "</td>";
                echo "<td>" . ($row['total_messages'] ?? 0) . "</td>";
                echo "<td>" . $row['thesis_id'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No active sessions yet.";
        }
        echo "</div>";
        echo "</div>";

        // Check 6: Access Requests Overview
        $access_result = $conn->query("
            SELECT u.full_name, COUNT(*) as requests, 
                   SUM(IF(status='pending', 1, 0)) as pending,
                   SUM(IF(status='approved', 1, 0)) as approved,
                   SUM(IF(status='denied', 1, 0)) as denied
            FROM chatbot_access_requests car
            LEFT JOIN users u ON car.user_id = u.id
            GROUP BY car.user_id
        ");

        echo "<div class='check-section success'>";
        echo "<div class='check-title'>";
        echo "🔐 Access Requests Overview";
        echo "</div>";
        echo "<div class='check-content'>";
        
        if ($access_result->num_rows > 0) {
            echo "<table class='data-table'>";
            echo "<tr><th>User</th><th>Total</th><th>Pending</th><th>Approved</th><th>Denied</th></tr>";
            while ($row = $access_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['full_name'] ?? 'Unknown') . "</td>";
                echo "<td>" . $row['requests'] . "</td>";
                echo "<td><strong>" . $row['pending'] . "</strong></td>";
                echo "<td><strong>" . $row['approved'] . "</strong></td>";
                echo "<td><strong>" . $row['denied'] . "</strong></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No access requests yet.";
        }
        echo "</div>";
        echo "</div>";

        // Check 7: Recommended Actions
        echo "<div class='check-section warning'>";
        echo "<div class='check-title'>";
        echo "📋 Recommended Actions";
        echo "</div>";
        echo "<div class='check-content'>";
        
        if ($has_invalid) {
            echo "1. <strong>⚠️ CRITICAL:</strong> Clean up invalid records<br>";
            echo "<div class='code-block'>Run the SQL fix script in phpMyAdmin</div>";
            echo "File: <code>CHATBOT_FIX.sql</code>";
        }
        
        if ($orphaned_messages > 0) {
            echo "<br>2. <strong>Clean up orphaned messages</strong> (optional, but recommended)";
        }
        
        echo "<br><br>";
        echo "3. <strong>Test chatbot access</strong> - Visit a thesis detail page and verify chatbot works";
        echo "<br>";
        echo "4. <strong>Monitor sessions</strong> - Check session limits (max 5 per user per thesis)";
        
        echo "</div>";
        echo "</div>";
        ?>

        <div class="button-group">
            <a href="#" onclick="location.reload()" class="btn btn-primary">🔄 Refresh Check</a>
            <a href="admin.php" class="btn btn-primary">📊 Back to Admin</a>
        </div>
    </div>
</body>
</html>
