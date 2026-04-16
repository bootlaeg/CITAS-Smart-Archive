<?php
/**
 * Journal Conversion API Endpoint
 * Called from admin_add_thesis.php after upload
 * Converts raw documents to journal format
 */

require_once 'db_includes/db_connect.php';
require_once 'ai_includes/document_parser.php';
require_once 'ai_includes/journal_converter.php';

// Check if admin
if (!is_admin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Only admins can trigger journal conversion'
    ]);
    exit;
}

// Get thesis ID and file path
if (!isset($_POST['thesis_id']) || !isset($_POST['file_path'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing thesis_id or file_path'
    ]);
    exit;
}

$thesis_id = intval($_POST['thesis_id']);
$file_path = sanitize_input($_POST['file_path']);

error_log("[Journal Conversion API] Starting conversion for thesis $thesis_id");
error_log("[Journal Conversion API] File: " . $file_path);

try {
    // Step 1: Get thesis details from database
    $stmt = $conn->prepare("SELECT title, author, abstract, year FROM thesis WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $thesis_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Thesis not found");
    }
    
    $thesis_data = $result->fetch_assoc();
    $stmt->close();
    
    // Step 2: Parse document and extract text
    $parser = new DocumentParser();
    $full_path = __DIR__ . '/' . $file_path;
    
    if (!file_exists($full_path)) {
        throw new Exception("File not found: " . $full_path);
    }
    
    error_log("[Journal Conversion API] Parsing document...");
    $document_text = $parser->parseDocument($full_path);
    
    if (empty($document_text)) {
        throw new Exception("Could not extract text from document");
    }
    
    error_log("[Journal Conversion API] Extracted " . strlen($document_text) . " characters");
    
    // Step 3: Prepare metadata
    $metadata = [
        'title' => $thesis_data['title'],
        'author' => $thesis_data['author'],
        'abstract' => $thesis_data['abstract'],
        'year' => $thesis_data['year']
    ];
    
    // Step 4: Run journal conversion
    $converter = new JournalConverter($thesis_id, $document_text, $metadata, $conn);
    $conversion_result = $converter->convert();
    
    // Return result
    if ($conversion_result['success']) {
        error_log("[Journal Conversion API] SUCCESS: Conversion completed");
        http_response_code(200);
        echo json_encode($conversion_result);
    } else {
        error_log("[Journal Conversion API] FAILED: " . $conversion_result['error']);
        http_response_code(500);
        echo json_encode($conversion_result);
    }
    
} catch (Exception $e) {
    error_log("[Journal Conversion API] EXCEPTION: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();

?>
