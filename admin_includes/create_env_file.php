<?php
/**
 * Create or update .env file on the server
 * Security: Only allows setting the API key
 */

header('Content-Type: application/json');

// Verify this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $api_key = $_POST['api_key'] ?? '';
    
    if (empty($api_key)) {
        throw new Exception('API key is required');
    }
    
    // Validate API key format
    if (!preg_match('/^hf_[a-zA-Z0-9_]+$/', $api_key)) {
        throw new Exception('Invalid API key format. Must start with "hf_"');
    }
    
    // Determine where to create the .env file
    $root_dir = dirname(dirname(__DIR__)); // Go up to root
    $env_file = $root_dir . '/.env';
    
    // Alternative: try document root
    if (!is_writable($root_dir)) {
        $env_file = $_SERVER['DOCUMENT_ROOT'] . '/.env';
    }
    
    // Check if directory is writable
    $target_dir = dirname($env_file);
    if (!is_dir($target_dir)) {
        throw new Exception("Directory not writable: $target_dir");
    }
    
    if (!is_writable($target_dir)) {
        throw new Exception("Directory not writable: $target_dir. Please check file permissions.");
    }
    
    // Create the .env content
    $env_content = "# Hugging Face API Configuration
# IMPORTANT: This file contains sensitive credentials
# NEVER commit this file to git or push it to GitHub
# It's listed in .gitignore for protection

HUGGING_FACE_API_KEY=$api_key
";
    
    // Write the file
    $bytes_written = file_put_contents($env_file, $env_content);
    
    if ($bytes_written === false) {
        throw new Exception("Failed to write .env file. Check directory permissions.");
    }
    
    // Set proper permissions (read-only for security)
    chmod($env_file, 0600);
    
    echo json_encode([
        'success' => true,
        'message' => '.env file created successfully',
        'path' => $env_file,
        'size' => $bytes_written
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
