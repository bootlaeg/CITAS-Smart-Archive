<?php
/**
 * Upload Thesis File
 * Handles thesis file uploads and returns the file path
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');

try {
    error_log("📁 Upload request received. POST data: " . json_encode($_POST));
    error_log("📁 FILES: " . json_encode(array_keys($_FILES)));
    
    // Check if file was uploaded
    if (!isset($_FILES['file'])) {
        throw new Exception("No file in FILES array. Available keys: " . json_encode(array_keys($_FILES)));
    }
    
    error_log("📁 File array: " . json_encode($_FILES['file'], JSON_PRETTY_PRINT));
    
    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp directory',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'PHP extension blocked upload'
        ];
        $error_code = $_FILES['file']['error'];
        $error_msg = $error_messages[$error_code] ?? 'Unknown error (' . $error_code . ')';
        throw new Exception("File upload error: " . $error_msg);
    }

    $file = $_FILES['file'];
    
    // Validate file
    $fileName = $file['name'];
    $fileType = $file['type'];
    $fileTmpPath = $file['tmp_name'];
    $fileSize = $file['size'];
    
    error_log("📁 Uploading file: name=$fileName, size=$fileSize, tmp=$fileTmpPath");
    
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
        throw new Exception("File size exceeds maximum of 50MB. Got: " . number_format($fileSize) . " bytes");
    }
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = __DIR__ . '/../uploads/thesis_files';
    if (!is_dir($uploadsDir)) {
        error_log("📁 Creating uploads directory: $uploadsDir");
        if (!mkdir($uploadsDir, 0755, true)) {
            throw new Exception("Failed to create uploads directory");
        }
    }
    
    // Verify directory is writable
    if (!is_writable($uploadsDir)) {
        throw new Exception("Uploads directory is not writable: $uploadsDir");
    }
    
    // Generate unique filename
    $uniqueId = uniqid() . '_' . time();
    $newFileName = "thesis_" . $uniqueId . "." . $fileExt;
    $newFilePath = $uploadsDir . "/" . $newFileName;
    $relativePath = "uploads/thesis_files/" . $newFileName;
    
    error_log("📁 Moving file from $fileTmpPath to $newFilePath");
    
    // Move uploaded file
    if (!move_uploaded_file($fileTmpPath, $newFilePath)) {
        throw new Exception("Failed to move uploaded file. Check permissions on: " . $uploadsDir);
    }
    
    // Verify file was actually created
    if (!file_exists($newFilePath)) {
        throw new Exception("File move succeeded but file not found after move: $newFilePath");
    }
    
    $actualSize = filesize($newFilePath);
    error_log("✅ File uploaded successfully: $relativePath (size: $actualSize bytes)");
    
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
