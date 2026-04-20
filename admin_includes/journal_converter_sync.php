<?php
/**
 * Synchronous Journal Converter
 * Converts thesis to IMRaD format synchronously (no background processing)
 * Used in the admin workflow to validate conversion before saving to database
 * 
 * INPUT (JSON POST):
 *   - file_path: path to uploaded thesis file
 *   - title: thesis title
 *   - author: thesis author
 *   - abstract: thesis abstract
 *   - year: publication year
 * 
 * OUTPUT (JSON):
 *   - success: true/false
 *   - temp_path: path to generated temp journal file (if success)
 *   - page_count: estimated pages in converted journal
 *   - error: error message (if failed)
 */

// Allow 3 minutes for conversion (Ollama can be slow)
set_time_limit(180);
ini_set('default_socket_timeout', 180);

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Log that this endpoint was called
error_log("[journal_converter_sync.php] ENDPOINT CALLED");
error_log("[journal_converter_sync.php] Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("[journal_converter_sync.php] Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));

try {
    // Parse JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('No input data provided');
    }
    
    // Validate required fields
    $required = ['file_path', 'title', 'author', 'abstract', 'year'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    error_log("[journal_converter_sync] Starting synchronous conversion");
    error_log("[journal_converter_sync] File: " . $input['file_path']);
    error_log("[journal_converter_sync] Title: " . $input['title']);
    
    // Extract file content
    $file_path = $input['file_path'];
    
    // Validate file exists
    $full_path = __DIR__ . '/../' . $file_path;
    error_log("[journal_converter_sync] Full file path: $full_path");
    if (!file_exists($full_path)) {
        throw new Exception("File not found: $full_path");
    }
    error_log("[journal_converter_sync] ✓ File exists");
    
    // Resolve relative path
    if (!file_exists($file_path)) {
        $file_path = __DIR__ . '/../' . $file_path;
    }
    
    if (!file_exists($file_path)) {
        throw new Exception("File not found: " . $file_path);
    }
    
    error_log("[journal_converter_sync] File resolved to: $file_path");
    
    // Extract text from file
    $document_text = null;
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    if ($ext === 'pdf') {
        require_once __DIR__ . '/../ai_includes/document_parser.php';
        $parse_result = DocumentParser::extractText($file_path);
        
        if (!$parse_result['success']) {
            throw new Exception("PDF parsing failed: " . ($parse_result['error'] ?? 'Unknown error'));
        }
        
        $document_text = $parse_result['text'];
    } else {
        $document_text = file_get_contents($file_path);
    }
    
    if (!$document_text) {
        throw new Exception("Could not extract text from file");
    }
    
    error_log("[journal_converter_sync] Text extracted: " . strlen($document_text) . " characters");
    
    // Prepare metadata
    $metadata = [
        'title' => $input['title'],
        'author' => $input['author'],
        'abstract' => $input['abstract'],
        'year' => intval($input['year'])
    ];
    
    // Load converter
    require_once __DIR__ . '/../ai_includes/imrad_analyzer.php';
    require_once __DIR__ . '/../ai_includes/journal_converter.php';
    
    // Create converter WITHOUT database connection (we're just generating the file)
    // Pass null as $thesis_id since we don't have a thesis ID yet
    error_log("[journal_converter_sync] Instantiating JournalConverter");
    // Pass 'unsaved' as thesis_id so updateDatabase() will skip (database write done in save_thesis_classification.php)
    $converter = new JournalConverter('unsaved', $document_text, $metadata, null);
    
    error_log("[journal_converter_sync] Starting conversion process");
    
    // Perform conversion
    // This will generate the journal file and return result
    // It won't update the database (no DB connection provided)
    $result = $converter->convert();
    
    if (!$result['success']) {
        throw new Exception("Conversion failed: " . ($result['error'] ?? 'Unknown error'));
    }
    
    error_log("[journal_converter_sync] ✅ Conversion successful");
    error_log("[journal_converter_sync] Journal file: " . $result['journal_file_path']);
    error_log("[journal_converter_sync] Page count: " . $result['journal_page_count']);
    
    // Move the journal file to temp location (not permanent yet)
    $journal_file = $result['journal_file_path'];
    
    // Generate temp file path
    $temp_dir = __DIR__ . '/../uploads/temp';
    if (!is_dir($temp_dir)) {
        mkdir($temp_dir, 0755, true);
        error_log("[journal_converter_sync] Created temp directory: $temp_dir");
    }
    
    // Generate unique temp filename
    $temp_filename = 'journal_' . uniqid() . '_' . bin2hex(random_bytes(4)) . '.html';
    $temp_path = 'uploads/temp/' . $temp_filename;
    $temp_full_path = __DIR__ . '/../' . $temp_path;
    
    // Move file from conversion output to temp location
    if (file_exists(__DIR__ . '/../' . $journal_file)) {
        if (!rename(__DIR__ . '/../' . $journal_file, $temp_full_path)) {
            throw new Exception("Failed to move journal file to temp location");
        }
        error_log("[journal_converter_sync] Journal file moved to temp: $temp_path");
    } else {
        throw new Exception("Journal file not found at expected location: " . $journal_file);
    }
    
    // Prepare response data
    $page_count = isset($result['journal_page_count']) ? intval($result['journal_page_count']) : 0;
    error_log("[journal_converter_sync] About to return response with:");
    error_log("[journal_converter_sync]   - success: true");
    error_log("[journal_converter_sync]   - temp_path: $temp_path");
    error_log("[journal_converter_sync]   - page_count: $page_count");
    
    // Return success with temp path
    echo json_encode([
        'success' => true,
        'temp_path' => $temp_path,
        'page_count' => $page_count,
        'message' => 'Conversion successful! Ready to save.'
    ]);
    error_log("[journal_converter_sync] Response sent successfully");
    
} catch (Exception $e) {
    error_log("[journal_converter_sync] ❌ ERROR: " . $e->getMessage());
    error_log("[journal_converter_sync] Trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

exit;
?>
