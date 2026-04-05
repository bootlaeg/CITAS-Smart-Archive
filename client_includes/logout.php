<?php
/**
 * User Logout Handler
 */

require_once __DIR__ . '/../db_includes/db_connect.php';

// Destroy session
session_destroy();

// Redirect to homepage
header("Location: ../index.php");
exit();
?>
