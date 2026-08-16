<?php
require_once 'db_includes/db_connect.php';
$res = $conn->query("SHOW CREATE TABLE thesis_access");
$row = $res->fetch_row();
echo $row[1];
?>
