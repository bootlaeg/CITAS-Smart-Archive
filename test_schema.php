<?php
require 'db_includes/db_connect.php';
$res = $conn->query('SHOW CREATE TABLE thesis_access');
print_r($res->fetch_assoc());
?>
