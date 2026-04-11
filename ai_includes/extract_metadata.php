<?php
/**
 * Enhanced Metadata Extraction Endpoint
 * Returns: title, author, year, abstract
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once '../db_includes/db_connect.php';
require_once './DocumentMetadataExtractor.php';
require_login();
require_admin();

error_log("=== METADATA EXTRACTION REQUEST ===");

// Set error handler
set_error_handler(function($errno, $errstr) {
    error_log("Extraction Error ($errno): $errstr");
    echo json_encode(['success' => false, 'message' => 'Error: ' . $errstr]);
    exit;
});

try {
    // Validate file upload
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error: ' . ($_FILES['file']['error'] ?? 'unknown'));
    }
    
    $file = $_FILES['file'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    error_log("File info: Name={$file['name']}, Extension={$file_ext}, Size={$file['size']} bytes, TmpName={$file['tmp_name']}");
    
    // Validate file exists
    if (!file_exists($file['tmp_name'])) {
        throw new Exception('Uploaded file not found at temp location');
    }
    
    if (filesize($file['tmp_name']) === 0) {
        throw new Exception('Uploaded file is empty');
    }
    
    // Validate file type
    $allowed_types = ['pdf', 'doc', 'docx'];
    if (!in_array($file_ext, $allowed_types)) {
        throw new Exception('Invalid file type. Only PDF, DOC, and DOCX allowed. Got: ' . $file_ext);
    }
    
    error_log("File validation passed. Starting extraction...");
    
    // Extract metadata
    $metadata = DocumentMetadataExtractor::extract($file['tmp_name'], $file_ext);
    
    // Check for extraction errors
    if (isset($metadata['error'])) {
        error_log("Extraction returned error: " . $metadata['error']);
        throw new Exception($metadata['error']);
    }
    
    // Log extracted data
    error_log("✓ Extraction successful!");
    error_log("  Title: " . substr($metadata['title'] ?? '', 0, 60));
    error_log("  Authors: " . substr($metadata['authors'] ?? '', 0, 60));
    error_log("  Year: " . ($metadata['year'] ?? 'NOT FOUND'));
    error_log("  Abstract length: " . strlen($metadata['abstract'] ?? ''));
    error_log("  Page count: " . ($metadata['page_count'] ?? 'NOT FOUND'));
    
    // Validate extraction quality
    $quality_warnings = [];
    if (empty($metadata['title'])) {
        $quality_warnings[] = 'Title not extracted';
    }
    if (empty($metadata['authors'])) {
        $quality_warnings[] = 'Authors not extracted';
    }
    if (empty($metadata['year'])) {
        $quality_warnings[] = 'Year not extracted';
    }
    if (empty($metadata['abstract'])) {
        $quality_warnings[] = 'Abstract not extracted';
    }
    
    if (!empty($quality_warnings)) {
        error_log("Quality warnings: " . implode(', ', $quality_warnings));
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => [
            'title' => $metadata['title'] ?? '',
            'author' => $metadata['authors'] ?? '',
            'year' => $metadata['year'] ?? '',
            'abstract' => $metadata['abstract'] ?? '',
            'page_count' => $metadata['page_count'] ?? null
        ],
        'debug' => [
            'warnings' => $quality_warnings
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    error_log("Stack: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>

