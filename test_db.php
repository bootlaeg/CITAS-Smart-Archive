<?php
$_SERVER['SERVER_NAME'] = 'localhost';
require 'db_includes/db_connect.php';

echo "=== DATABASE SCHEMA ===\n\n";

$tables = $conn->query("SHOW TABLES");
while ($table = $tables->fetch_array()) {
    $tableName = $table[0];
    echo "TABLE: $tableName\n";
    $cols = $conn->query("SHOW COLUMNS FROM `$tableName`");
    while ($col = $cols->fetch_assoc()) {
        echo "  - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']} {$col['Default']}\n";
    }
    $count = $conn->query("SELECT COUNT(*) as c FROM `$tableName`")->fetch_assoc()['c'];
    echo "  Row count: $count\n\n";
}

echo "\n=== CHATBOT ACCESS REQUESTS ===\n";
$res = $conn->query("SELECT * FROM chatbot_access_requests ORDER BY id DESC LIMIT 10");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No rows\n";
}

echo "\n=== THESIS ACCESS ===\n";
$res2 = $conn->query("SELECT * FROM thesis_access ORDER BY id DESC LIMIT 10");
if ($res2 && $res2->num_rows > 0) {
    while ($row = $res2->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No rows\n";
}

echo "\n=== USERS (roles) ===\n";
$res3 = $conn->query("SELECT id, full_name, user_role, account_status FROM users ORDER BY id DESC LIMIT 10");
if ($res3 && $res3->num_rows > 0) {
    while ($row = $res3->fetch_assoc()) {
        echo "ID:{$row['id']} | Name:{$row['full_name']} | Role:{$row['user_role']} | Status:{$row['account_status']}\n";
    }
}
?>
