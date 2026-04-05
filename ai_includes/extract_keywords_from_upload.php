<?php
/**
 * Extract Keywords from Upload - Hybrid approach (Document + AI fallback)
 * Processes uploaded thesis file and extracts keywords
 * Uses simple text analysis first, falls back to AI if fewer than 3 keywords found
 */

header('Content-Type: application/json');

// Start output buffering to catch any stray output
ob_start();

// Catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        @ob_end_clean();
        header('Content-Type: application/json', true);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Fatal Error: ' . $error['message'],
            'error_type' => 'Fatal',
            'file' => str_replace('\\', '/', $error['file']),
            'line' => $error['line']
        ]);
        exit;
    }
    @ob_end_clean();
});

// Security & error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Catch all errors and return as JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    @ob_end_clean();
    header('Content-Type: application/json', true);
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'error' => $errstr,
        'error_type' => 'PHP Error',
        'file' => str_replace(__DIR__, '', $errfile),
        'line' => $errline
    ]));
});

set_exception_handler(function($e) {
    @ob_end_clean();
    header('Content-Type: application/json', true);
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_type' => 'Exception',
        'file' => str_replace(__DIR__, '', $e->getFile()),
        'line' => $e->getLine()
    ]));
});

try {
    // Load dependencies - wrap in try to handle any failures gracefully  
    try {
        @require_once __DIR__ . '/../db_includes/db_connect.php';
        @require_once __DIR__ . '/ollama_service.php';
        @require_once __DIR__ . '/thesis_classifier.php';
    } catch (Throwable $depError) {
        // Log but continue - these are optional for AI fallback only
        error_log("Optional dependency load failed: " . $depError->getMessage());
    }
    
    require_once __DIR__ . '/document_parser.php';
    require_once __DIR__ . '/keyword_analyzer.php';
    // Verify method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Only POST requests are allowed");
    }

    // Verify file upload
    if (!isset($_FILES['file'])) {
        throw new Exception("No file provided");
    }

    $file = $_FILES['file'];
    $abstract = $_POST['abstract'] ?? '';
    $minKeywords = (int)($_POST['min_keywords'] ?? 5);

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload error: " . getUploadError($file['error']));
    }

    // Check file size (20MB max)
    if ($file['size'] > 20 * 1024 * 1024) {
        throw new Exception("File exceeds 20MB limit. File size: " . round($file['size'] / 1024 / 1024, 2) . "MB");
    }

    // Check file type
    $allowedExtensions = ['pdf', 'doc', 'docx', 'txt'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        throw new Exception("File type not supported. Allowed: PDF, DOC, DOCX, TXT");
    }

    // Create temp directory if it doesn't exist
    $tempDir = __DIR__ . '/../uploads/temp/';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    // Save uploaded file temporarily
    $tempFile = $tempDir . uniqid() . '_' . basename($file['name']);
    
    if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
        throw new Exception("Failed to save uploaded file");
    }

    // Step 1: Extract text from document
    $extractResult = DocumentParser::extractText($tempFile);
    
    if (!$extractResult['success']) {
        @unlink($tempFile);
        throw new Exception("Failed to extract text from document: " . $extractResult['error']);
    }

    $rawText = $extractResult['text'];
    $extractedText = DocumentParser::cleanText($rawText);
    
    // Log extraction details for debugging
    $extractionDebug = [
        'raw_length' => strlen($rawText),
        'cleaned_length' => strlen($extractedText),
        'raw_preview' => mb_substr($rawText, 0, 200),
        'cleaned_preview' => mb_substr($extractedText, 0, 200)
    ];

    // If cleaned text is empty but raw text has content, use raw text
    if (empty($extractedText) && !empty($rawText)) {
        error_log("Text cleaning removed all content. Using raw extraction. Debug: " . json_encode($extractionDebug));
        $extractedText = $rawText;
    }

    if (empty($extractedText)) {
        @unlink($tempFile);
        throw new Exception("No text content could be extracted from the document");
    }

    // Step 2: Analyze extracted text for keywords
    $analysisResult = KeywordAnalyzer::analyzeText($extractedText, $abstract, $minKeywords);
    
    $documentKeywords = $analysisResult['keywords'];
    $method = $analysisResult['method'];

    // Step 3: If fewer than 3 keywords found, use AI
    $aiKeywords = [];
    $usedMethod = 'document';

    if ($method === 'ai-required') {
        // Fall back to AI generation
        try {
            $ollama = new OllamaService('mistral');
            if ($ollama->isAvailable() && $ollama->isModelAvailable('mistral')) {
                $classifier = new ThesisClassifier('mistral');
                
                // Extract keywords using AI on both title/abstract + extracted text
                $fullContent = $extractedText;
                if (!empty($abstract)) {
                    $fullContent = $abstract . "\n\n" . $extractedText;
                }

                // Call AI keyword extraction
                $aiResult = $classifier->extractKeywords(
                    $file['name'],
                    $abstract,
                    $fullContent
                );

                if (isset($aiResult['keywords']) && !empty($aiResult['keywords'])) {
                    // Parse keywords response
                    $keywords = $aiResult['keywords'];
                    
                    // Handle different response formats
                    if (is_string($keywords)) {
                        // Try to parse as JSON
                        $parsed = json_decode($keywords, true);
                        if (is_array($parsed)) {
                            $aiKeywords = $parsed;
                        } else {
                            // Parse as comma-separated
                            $aiKeywords = array_map('trim', explode(',', $keywords));
                        }
                    } elseif (is_array($keywords)) {
                        $aiKeywords = $keywords;
                    }

                    $usedMethod = 'ai';
                }
            }
        } catch (Exception $e) {
            // Log but don't fail - return document keywords even if AI unavailable
            error_log("AI keyword extraction failed: " . $e->getMessage());
        }
    }

    // Combine results: prefer document keywords if enough, otherwise use AI
    $finalKeywords = !empty($documentKeywords) && count($documentKeywords) >= 3 
        ? $documentKeywords 
        : (!empty($aiKeywords) ? $aiKeywords : []);

    $usedMethod = !empty($documentKeywords) && count($documentKeywords) >= 3 
        ? 'document' 
        : 'ai';

    // Clean up temp file
    @unlink($tempFile);

    // Send successful response
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    $response = json_encode([
        'success' => true,
        'keywords' => $finalKeywords,
        'keyword_count' => count($finalKeywords),
        'source' => $usedMethod === 'document' 
            ? 'Extracted from document' 
            : 'AI-generated based on document content',
        'method' => $usedMethod,
        'document_keywords_count' => count($documentKeywords),
        'ai_keywords_count' => count($aiKeywords),
        'text_preview' => mb_substr($extractedText, 0, 500) . '...',
        'message' => $usedMethod === 'document'
            ? count($documentKeywords) . ' keywords extracted from document'
            : 'Fewer keywords found in document, using AI generation'
    ]);
    echo $response;
    exit(0);

} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    
    // Provide helpful debugging information
    $phpErrorLog = ini_get('error_log');
    if (empty($phpErrorLog)) {
        $phpErrorLog = 'Check XAMPP Apache error_log (usually in xampp/apache/logs/)';
    }
    
    $response = json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_tip' => 'For extraction details, check error log at: ' . $phpErrorLog,
        'test_url' => 'Try debugging with test_pdf_extraction.html'
    ]);
    echo $response;
    exit(1);
}

/**
 * Get human-readable error message for file upload errors
 */
function getUploadError($errorCode)
{
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
    ];
    
    return $errors[$errorCode] ?? 'Unknown upload error';
}
?>
