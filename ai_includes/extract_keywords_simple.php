<?php
/**
 * Keyword Extraction Endpoint
 * Simple direct extraction from uploaded document
 */
header('Content-Type: application/json; charset=utf-8');

ob_start();

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        @ob_clean();
        http_response_code(500);
        die(json_encode(['success' => false, 'error' => 'Fatal: ' . $error['message']]));
    }
});

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
        throw new Exception("POST file required");
    }

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Upload error: " . ($file['error'] ?: 'Unknown'));
    }

    // Load required libraries
    require_once __DIR__ . '/document_parser.php';
    require_once __DIR__ . '/keyword_analyzer.php';

    // Create temp file
    $tempDir = __DIR__ . '/../uploads/temp/';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    $tempFile = $tempDir . uniqid() . '_' . basename($file['name']);
    if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
        throw new Exception("Failed to move file");
    }

    // Extract text from document
    $parsed = DocumentParser::extractText($tempFile);
    if (!$parsed['success']) {
        throw new Exception("Text extraction failed: " . $parsed['error']);
    }

    $text = DocumentParser::cleanText($parsed['text']);
    if (empty($text)) {
        throw new Exception("No readable text content extracted");
    }

    // Analyze text for keywords
    $analysis = KeywordAnalyzer::analyzeText($text, '', 5);
    $keywords = $analysis['keywords'];
    
    // Extract all citations from document (no limit)
    $rawCitations = KeywordAnalyzer::extractCitationsFromText($text);
    
    // Extract author names from document
    $author = KeywordAnalyzer::extractAuthorFromText($text);
    
    // Format citations in multiple styles
    $formattedCitations = KeywordAnalyzer::formatCitationsMultipleStyles($rawCitations);

    // Clean up temp file
    @unlink($tempFile);

    ob_end_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'keywords' => $keywords,
        'keyword_count' => count($keywords),
        'citations' => [
            'raw' => $formattedCitations['raw_citations'],
            'in_text_apa' => $formattedCitations['in_text_apa'],
            'narrative' => $formattedCitations['narrative'],
            'urls' => $formattedCitations['url_references']
        ],
        'citation_count' => count($rawCitations),
        'author' => $author,
        'message' => count($keywords) . ' keywords and ' . count($rawCitations) . ' citations extracted'
    ]);
    exit(0);

} catch (Throwable $e) {
    @unlink($tempFile ?? null);
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit(1);
}
?>

