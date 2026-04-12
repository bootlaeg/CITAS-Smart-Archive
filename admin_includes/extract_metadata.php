<?php
/**
 * Metadata Extraction Endpoint (OLD - DO NOT USE)
 * This file should be deleted. Use ../ai_includes/extract_metadata.php instead
 */

// Redirect to correct endpoint
header('Location: ../ai_includes/extract_metadata.php', true, 301);
exit;

// Old code below - kept for reference

header('Content-Type: application/json; charset=utf-8');

require_once '../db_includes/db_connect.php';
require_once '../ai_includes/DocumentMetadataExtractor.php';
require_login();
require_admin();

// Check if request is POST and file is provided
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['file'];
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Validate file type
$allowed_types = ['pdf', 'doc', 'docx'];
if (!in_array($file_ext, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, DOC, and DOCX are allowed']);
    exit;
}

// Create temp directory for processing
$temp_dir = sys_get_temp_dir() . '/CITAS_extraction_' . uniqid();
if (!mkdir($temp_dir, 0755, true)) {
    echo json_encode(['success' => false, 'message' => 'Failed to create temp directory']);
    exit;
}

$temp_file = $temp_dir . '/' . basename($file['name']);

try {
    // Move uploaded file to temp location
    if (!move_uploaded_file($file['tmp_name'], $temp_file)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    // Extract metadata using DocumentMetadataExtractor
    $metadata = DocumentMetadataExtractor::extract($temp_file, $file_ext);
    
    // Check for errors
    if (isset($metadata['error'])) {
        throw new Exception($metadata['error']);
    }
    
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
    echo json_encode([
        'success' => false,
        'message' => 'Extraction failed: ' . $e->getMessage()
    ]);
} finally {
    // Clean up temp file
    if (file_exists($temp_file)) {
        unlink($temp_file);
    }
    // Clean up temp directory
    if (is_dir($temp_dir)) {
        rmdir($temp_dir);
    }
}

?>
