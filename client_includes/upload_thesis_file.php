<?php
/**
 * Upload Thesis File
 * Handles thesis file uploads and returns the file path
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');

try {
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error = $_FILES['file']['error'] ?? 'Unknown error';
        throw new Exception("File upload error: " . $error);
    }

    $file = $_FILES['file'];
    
    // Validate file
    $fileName = $file['name'];
    $fileType = $file['type'];
    $fileTmpPath = $file['tmp_name'];
    $fileSize = $file['size'];
    
    // Get file extension
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Allowed file types
    $allowedExts = ['pdf', 'doc', 'docx', 'txt'];
    if (!in_array($fileExt, $allowedExts)) {
        throw new Exception("File type '$fileExt' not allowed. Allowed: PDF, DOC, DOCX, TXT");
    }
    
    // Validate file size (max 50MB)
    $maxSize = 50 * 1024 * 1024;
    if ($fileSize > $maxSize) {
        throw new Exception("File size exceeds maximum of 50MB");
    }
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = __DIR__ . '/../uploads/thesis_files';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    
    // Generate unique filename
    $uniqueId = uniqid() . '_' . time();
    $newFileName = "thesis_" . $uniqueId . "." . $fileExt;
    $newFilePath = $uploadsDir . "/" . $newFileName;
    $relativePath = "uploads/thesis_files/" . $newFileName;
    
    // Move uploaded file
    if (!move_uploaded_file($fileTmpPath, $newFilePath)) {
        throw new Exception("Failed to move uploaded file to destination");
    }
    
    error_log("📁 File uploaded successfully: " . $relativePath);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'file_path' => $relativePath,
        'file_type' => $fileExt,
        'file_size' => $fileSize,
        'file_name' => $fileName,
        'message' => 'File uploaded successfully'
    ]);
    
} catch (Exception $e) {
    error_log("❌ Upload error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
