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

// Set error handler
set_error_handler(function($errno, $errstr) {
    error_log("Extraction Error: $errstr");
    echo json_encode(['success' => false, 'message' => 'Error: ' . $errstr]);
    exit;
});

try {
    error_log("=== Enhanced Metadata Extraction Started ===");
    
    // Validate file upload
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error');
    }
    
    $file = $_FILES['file'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    error_log("File: {$file['name']}, Type: {$file_ext}, Size: {$file['size']}");
    
    // Validate file type
    $allowed_types = ['pdf', 'doc', 'docx'];
    if (!in_array($file_ext, $allowed_types)) {
        throw new Exception('Invalid file type. Only PDF, DOC, and DOCX allowed');
    }
    
    // Extract metadata
    $metadata = DocumentMetadataExtractor::extract($file['tmp_name'], $file_ext);
    
    // Check for extraction errors
    if (isset($metadata['error'])) {
        error_log("Extraction error: " . $metadata['error']);
        throw new Exception($metadata['error']);
    }
    
    error_log("✓ Extraction successful");
    error_log("Title: " . substr($metadata['title'], 0, 50));
    error_log("Authors: " . $metadata['authors']);
    error_log("Year: " . $metadata['year']);
    error_log("Abstract length: " . strlen($metadata['abstract']));
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => [
            'title' => $metadata['title'] ?? '',
            'author' => $metadata['authors'] ?? '',
            'year' => $metadata['year'] ?? '',
            'abstract' => $metadata['abstract'] ?? ''
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>

