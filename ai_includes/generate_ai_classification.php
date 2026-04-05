<?php
/**
 * Endpoint: Generate Full AI Classification - Uses OllamaServiceCurl
 * STEP 2: When admin clicks "Enhance with AI" button
 * 
 * Generates: Subject Category, Research Method, Complexity Level, Keywords
 * Using uploaded document or provided text
 * 
 * Uses cURL-based Ollama connection for better Windows compatibility
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
    error_log("=== AI Classification Request (cURL) ===");
    error_log("Method: " . $_SERVER['REQUEST_METHOD']);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("POST request required");
    }

    // Get input data
    $input = file_get_contents('php://input');
    error_log("Raw input: " . substr($input, 0, 200) . "...");
    
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception("Invalid JSON input");
    }

    error_log("Parsed data keys: " . implode(', ', array_keys($data)));

    // Require document text (either from file or provided text)
    $documentText = $data['document_text'] ?? '';
    $abstract = $data['abstract'] ?? '';
    $title = $data['title'] ?? '';
    $filePath = $data['file_path'] ?? '';
    $model = $data['model'] ?? 'mistral';
    
    error_log("Document text length: " . strlen($documentText));
    error_log("Abstract: " . substr($abstract, 0, 100));
    error_log("Title: " . $title);
    error_log("Model: " . $model);
    
    require_once __DIR__ . '/document_parser.php';
    require_once __DIR__ . '/keyword_analyzer.php';
    require_once __DIR__ . '/ollama_service_curl.php';

    // If file path provided, extract text from it
    if (!empty($filePath) && file_exists($filePath)) {
        error_log("Extracting text from file: $filePath");
        $parseResult = DocumentParser::extractText($filePath);
        if ($parseResult['success']) {
            $documentText = DocumentParser::cleanText($parseResult['text']);
            error_log("File text extracted: " . strlen($documentText) . " bytes");
        }
    }

    // Validate we have document text
    if (empty($documentText)) {
        throw new Exception("No document text provided or could not extract from file");
    }

    error_log("Calling KeywordAnalyzer::generateAIClassification() with cURL");
    
    // Generate AI classification
    $classification = KeywordAnalyzer::generateAIClassification(
        $documentText,
        $abstract,
        $title,
        $model
    );

    error_log("Classification result: " . json_encode($classification));

    // Check for errors
    if ($classification['error'] !== null) {
        error_log("AI Error occurred: " . $classification['error']);
        throw new Exception("AI Generation Error: " . $classification['error']);
    }

    error_log("✅ AI Classification successful");
    error_log("   Subject: " . $classification['subject_category']);
    error_log("   Method: " . $classification['research_method']);
    error_log("   Complexity: " . $classification['complexity_level']);
    error_log("   Keywords: " . implode(', ', $classification['keywords']));
    error_log("   Citations: " . implode(', ', $classification['citations'] ?? []));
    error_log("   Author: " . ($classification['author'] ?? 'Not extracted'));

    ob_end_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'subject_category' => $classification['subject_category'],
        'research_method' => $classification['research_method'],
        'complexity_level' => $classification['complexity_level'],
        'keywords' => $classification['keywords'],
        'citations' => $classification['citations'],
        'author' => $classification['author'] ?? '',
        'message' => 'AI classification generated successfully'
    ]);
    exit(0);

} catch (Throwable $e) {
    error_log("❌ Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit(1);
}
?>

