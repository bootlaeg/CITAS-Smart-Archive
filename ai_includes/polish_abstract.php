<?php
/**
 * Polish Text (Title and/or Abstract)
 * Fixes typos, adds proper spacing, improves readability
 * Preserves grammar and original meaning
 */

// Set error handling and JSON response
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error ($errno): $errstr in $errfile:$errline");
    returnError("PHP Error: $errstr");
});

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');

try {
    error_log("=== Text Polish Request Started ===");
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('No input provided in request');
    }

    $title = $input['title'] ?? '';
    $abstract = $input['abstract'] ?? '';
    
    if (empty($title) && empty($abstract)) {
        throw new Exception('No title or abstract provided in request');
    }

    error_log("Title length: " . strlen($title) . " characters");
    error_log("Abstract length: " . strlen($abstract) . " characters");

    // Load KeywordAnalyzer class
    $keywordAnalyzerPath = __DIR__ . '/keyword_analyzer.php';
    if (!file_exists($keywordAnalyzerPath)) {
        throw new Exception("keyword_analyzer.php not found at: $keywordAnalyzerPath");
    }
    
    require_once $keywordAnalyzerPath;
    
    if (!class_exists('KeywordAnalyzer')) {
        throw new Exception('KeywordAnalyzer class not found after requiring file');
    }

    $response = [
        'success' => true,
        'title' => $title,
        'abstract' => $abstract,
        'errors' => []
    ];

    // Polish title if provided
    if (!empty($title)) {
        error_log("Polishing title...");
        $titleResult = KeywordAnalyzer::polishTitleText($title);
        
        if ($titleResult['success']) {
            $response['title'] = $titleResult['polished_text'];
            error_log("✅ Title polished successfully");
        } else {
            error_log("⚠️  Title polishing warning: " . $titleResult['error']);
            $response['errors'][] = "Title polish: " . $titleResult['error'];
        }
    }

    // Polish abstract if provided
    if (!empty($abstract)) {
        error_log("Polishing abstract...");
        $abstractResult = KeywordAnalyzer::polishAbstractText($abstract);
        
        if ($abstractResult['success']) {
            $response['abstract'] = $abstractResult['polished_text'];
            error_log("✅ Abstract polished successfully");
        } else {
            error_log("⚠️  Abstract polishing warning: " . $abstractResult['error']);
            $response['errors'][] = "Abstract polish: " . $abstractResult['error'];
        }
    }

    error_log("Text polishing completed");
    
    // Return success response
    echo json_encode($response);
    exit;
    
} catch (Exception $e) {
    error_log("Exception caught: " . $e->getMessage());
    returnError($e->getMessage());
}

function returnError($message) {
    error_log("Returning error: $message");
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit;
}
?>
