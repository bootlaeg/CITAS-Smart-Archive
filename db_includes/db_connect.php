<?php
/**
 * Database Connection File
 * Citas Smart Archive System
 */

// Database configuration
define('DB_HOST', 'srv2092.hstgr.io');
define('DB_USER', 'u965322812_CITAS_Smart');
define('DB_PASS', 'ErLv@g1e*');
define('DB_NAME', 'u965322812_thesis_db');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for proper character support
$conn->set_charset("utf8mb4");

// Make connection available globally for AI classifier
$GLOBALS['mysqli'] = $conn;

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sanitize input data
 * @param string $data
 * @return string
 */
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

/**
 * Check if user is logged in
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * @return bool
 */
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Redirect to login if not authenticated
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: index.php");
        exit();
    }
}

/**
 * Redirect to index if not admin
 */
function require_admin() {
    if (!is_admin()) {
        header("Location: index.php");
        exit();
    }
}
?>
