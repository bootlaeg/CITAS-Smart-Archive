<?php
/**
 * Extract Metadata from Thesis Files
 * Extracts: Title, Author, Year, Abstract
 * Returns: JSON response
 */

// Set error handling to catch all errors and return them as JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error ($errno): $errstr in $errfile:$errline");
    returnError("PHP Error: $errstr");
});

// Set JSON header and error reporting EARLY
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Enable exceptions for fatal errors
set_exception_handler(function($e) {
    error_log("Uncaught Exception: " . $e->getMessage());
    returnError("Exception: " . $e->getMessage());
});

try {
    error_log("=== Extract Metadata Request Started ===");
    
    // Get file from request
    if (!isset($_FILES['file'])) {
        throw new Exception('No file uploaded');
    }

    $file = $_FILES['file'];
    error_log("File received: " . $file['name'] . " (size: " . $file['size'] . " bytes)");
    
    $filePath = $file['tmp_name'];
    error_log("Temp file path: " . $filePath);

    if (!file_exists($filePath)) {
        throw new Exception('Uploaded file not found at: ' . $filePath);
    }
    
    if ($file['size'] === 0) {
        throw new Exception('Uploaded file is empty');
    }

    error_log("File exists and has size: " . filesize($filePath));

    // Classes for document parsing
    $docParserPath = __DIR__ . '/document_parser.php';
    $keywordAnalyzerPath = __DIR__ . '/keyword_analyzer.php';
    
    error_log("Checking files: $docParserPath and $keywordAnalyzerPath");
    
    if (!file_exists($docParserPath)) {
        throw new Exception("document_parser.php not found at: $docParserPath");
    }
    if (!file_exists($keywordAnalyzerPath)) {
        throw new Exception("keyword_analyzer.php not found at: $keywordAnalyzerPath");
    }
    
    require_once $docParserPath;
    require_once $keywordAnalyzerPath;

    // Parse document
    if (!class_exists('DocumentParser')) {
        throw new Exception('DocumentParser class not found after requiring file');
    }
    
    error_log("Creating DocumentParser instance");
    $parser = new DocumentParser();
    
    error_log("Extracting text from file");
    // Pass original filename to help with format detection
    $extractionResult = $parser->extractText($filePath, $file['name']);
    
    // DocumentParser::extractText returns an array with ['success', 'text', 'error']
    if (!is_array($extractionResult)) {
        throw new Exception('DocumentParser::extractText returned non-array response');
    }
    
    if (!$extractionResult['success']) {
        throw new Exception('Text extraction failed: ' . ($extractionResult['error'] ?? 'Unknown error'));
    }
    
    $text = $extractionResult['text'];
    error_log("Text extracted, length: " . strlen($text) . " characters");
    
    if (empty($text)) {
        throw new Exception('Failed to extract text from file - empty result');
    }
    
    // Extract metadata
    if (!class_exists('KeywordAnalyzer')) {
        throw new Exception('KeywordAnalyzer class not found after requiring file');
    }
    
    error_log("Extracting metadata from text");
    $metadata = KeywordAnalyzer::extractMetadata($text);
    
    error_log("Metadata extracted: title=" . strlen($metadata['title']) . " chars, abstract=" . strlen($metadata['abstract']) . " chars");
    
    // Return success response
    $response = [
        'success' => true,
        'data' => [
            'title' => $metadata['title'] ?? '',
            'author' => $metadata['author'] ?? '',
            'year' => $metadata['year'] ?? '',
            'abstract' => $metadata['abstract'] ?? ''
        ]
    ];
    
    $json = json_encode($response);
    error_log("JSON encoded successfully, length: " . strlen($json));
    
    echo $json;
    exit;
    
} catch (Exception $e) {
    error_log("Exception caught: " . $e->getMessage());
    returnError('Error: ' . $e->getMessage());
}

function returnError($message) {
    error_log("Returning error: $message");
    $response = [
        'success' => false,
        'error' => $message
    ];
    echo json_encode($response);
    exit;
}

