<?php
/**
 * Test the journal conversion locally without needing Ollama
 */

// Set up error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/ai_includes/JsonApiErrorHandler.php';
JsonApiErrorHandler::initialize();

require_once __DIR__ . '/ai_includes/journal_converter.php';
require_once __DIR__ . '/ai_includes/document_parser.php';

try {
    // Use test file if available
    $testFile = __DIR__ . '/uploads/NeuroGuard_BSIT.docx';
    
    if (!file_exists($testFile)) {
        throw new Exception("Test file not found: $testFile");
    }
    
    // Parse the document
    $parser = new DocumentParser();
    $text = $parser->extractTextFromFile($testFile);
    
    if (!$text) {
        throw new Exception("Failed to extract text from document");
    }
    
    error_log("✅ Successfully extracted text from document (" . strlen($text) . " characters)");
    
    // Try to convert using the converter
    $converter = new JournalConverter($text, 'Test Title', 'Test Author', 'Test Abstract', 2024);
    $result = $converter->convert();
    
    if (empty($result)) {
        throw new Exception("Conversion returned empty result");
    }
    
    if (!isset($result['success'])) {
        throw new Exception("Conversion result missing 'success' key");
    }
    
    if ($result['success']) {
        error_log("✅ Conversion successful");
        error_log("Result keys: " . implode(", ", array_keys($result)));
    } else {
        error_log("❌ Conversion failed: " . ($result['error'] ?? 'Unknown error'));
    }
    
    // Output JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Test completed',
        'result' => $result
    ]);
    
} catch (Throwable $e) {
    error_log("❌ Exception: " . $e->getMessage());
    error_log("Stack: " . $e->getTraceAsString());
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
