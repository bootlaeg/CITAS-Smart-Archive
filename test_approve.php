<?php
$_SERVER["REQUEST_METHOD"] = "POST";
$_POST = ["request_id" => 1, "user_id" => 1, "thesis_id" => 1];
session_start();
$_SESSION["user_id"] = 1;
$_SESSION["user_role"] = "admin";
include("admin_includes/admin_approve_access.php");
?>
