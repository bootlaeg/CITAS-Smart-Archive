<?php
/**
 * Document Proxy Server
 * Converts and serves documents for viewing
 */

require_once __DIR__ . '/../db_includes/db_connect.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$file_path = isset($_POST['file']) ? $_POST['file'] : '';
$file_type = isset($_POST['type']) ? $_POST['type'] : '';

if (empty($file_path) || empty($file_type)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit();
}

// Sanitize path
$file_path = str_replace(['..', '\\'], ['', '/'], $file_path);

if (strpos($file_path, 'uploads/thesis_files/') !== 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid file path']);
    exit();
}

$full_path = __DIR__ . '/' . $file_path;

if (!file_exists($full_path) || !is_file($full_path)) {
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit();
}

// For DOCX/DOC, generate a Google Docs Viewer URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$server_name = $_SERVER['SERVER_NAME'];
$file_url = $protocol . $server_name . '/' . $file_path;

$google_viewer_url = 'https://docs.google.com/viewer?url=' . urlencode($file_url) . '&embedded=true';

echo json_encode([
    'success' => true,
    'viewer_url' => $google_viewer_url,
    'file_url' => $file_url,
    'type' => $file_type
]);
?>
