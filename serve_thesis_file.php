<?php
/**
 * Secure File Server for Thesis Files
 * Serves uploaded thesis files with proper headers and access control
 */

require_once 'db_includes/db_connect.php';

// IMPORTANT: Check if user is logged in
if (!is_logged_in()) {
    http_response_code(403);
    die('Access Denied: You must be logged in');
}

// Add CORS headers for Google Docs Viewer
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Range');
header('Access-Control-Expose-Headers: Content-Length, Content-Range');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if file path is provided
if (!isset($_GET['file']) || empty($_GET['file'])) {
    http_response_code(400);
    die('Bad Request: No file specified');
}

$file_path = $_GET['file'];
$thesis_id = isset($_GET['thesis_id']) ? intval($_GET['thesis_id']) : 0;

// Sanitize the file path to prevent directory traversal
$file_path = str_replace(['..', '\\'], ['', '/'], $file_path);

// Ensure the file is in the uploads directory
if (strpos($file_path, 'uploads/thesis_files/') !== 0) {
    http_response_code(403);
    die('Forbidden: Invalid file path');
}

$full_path = __DIR__ . '/' . $file_path;

// Check if file exists
if (!file_exists($full_path)) {
    http_response_code(404);
    die('File Not Found');
}

// Check if it's a file (not a directory)
if (!is_file($full_path)) {
    http_response_code(403);
    die('Forbidden: Not a file');
}

// Check file permissions
if (!is_readable($full_path)) {
    http_response_code(403);
    die('Forbidden: File is not readable');
}

// If thesis_id is provided, verify user has access
if ($thesis_id > 0) {
    // Admins can always access
    if (!is_admin()) {
        $access_check = $conn->prepare("
            SELECT ta.id FROM thesis_access 
            WHERE user_id = ? AND thesis_id = ? AND status = 'approved'
        ");
        
        if ($access_check) {
            $access_check->bind_param("ii", $_SESSION['user_id'], $thesis_id);
            $access_check->execute();
            $access_result = $access_check->get_result();
            
            if ($access_result->num_rows === 0) {
                http_response_code(403);
                die('Access Denied: You do not have permission to access this file');
            }
            $access_check->close();
        }ied: You do not have permission to access this file');
            }
            $access_check->close();
        }
    }
}

// Get file extension
$file_ext = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));

// Define MIME types
$mime_types = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

// Set appropriate MIME type
$mime_type = isset($mime_types[$file_ext]) ? $mime_types[$file_ext] : 'application/octet-stream';

// Get file size
$file_size = filesize($full_path);

// Set headers for streaming (INLINE - don't download)
header('Content-Type: ' . $mime_type);
header('Content-Disposition: inline; filename="' . basename($full_path) . '"');
header('Content-Length: ' . $file_size);
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=3600');

// Last-Modified and ETag for caching
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($full_path)) . ' GMT');
header('ETag: "' . md5_file($full_path) . '"');

// Handle Range requests
if (isset($_SERVER['HTTP_RANGE'])) {
    list($size_unit, $range_spec) = explode('=', $_SERVER['HTTP_RANGE'], 2);
    
    if ($size_unit === 'bytes') {
        $ranges = explode(',', $range_spec);
        $range = explode('-', $ranges[0]);
        
        $seek_start = intval($range[0]);
        $seek_end = (intval($range[1]) > 0) ? intval($range[1]) : $file_size - 1;
        
        $seek_length = $seek_end - $seek_start + 1;
        
        if ($seek_start >= 0 && $seek_end < $file_size && $seek_start <= $seek_end) {
            http_response_code(206);
            header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . $file_size);
            header('Content-Length: ' . $seek_length);
            
            $file = fopen($full_path, 'rb');
            fseek($file, $seek_start);
            echo fread($file, $seek_length);
            fclose($file);
            exit();
        }
    }
}

// Read and output full file
readfile($full_path);
exit();
?>
