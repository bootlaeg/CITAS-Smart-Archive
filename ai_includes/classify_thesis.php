<?php
/**
 * Thesis Classification API Endpoint
 * Handles classification requests asynchronously
 * Usage: POST /ai_includes/classify_thesis.php with thesis_id or full thesis data
 */

header('Content-Type: application/json');

// Increase execution time for long-running AI tasks
set_time_limit(300);
ini_set('default_socket_timeout', 300);

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile:$errline");
    throw new Exception("PHP Error: $errstr");
});
set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
});

// Load dependencies
require_once __DIR__ . '/../db_includes/db_connect.php';
require_once __DIR__ . '/ollama_service.php';
require_once __DIR__ . '/thesis_classifier.php';

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? 'classify';
    
    // For health check, only test Ollama availability (no DB or classifier needed)
    if ($action === 'health') {
        $ollama = new OllamaService('mistral');
        $available = $ollama->isAvailable();
        $modelAvailable = $ollama->isModelAvailable('mistral');
        
        echo json_encode([
            'success' => true,
            'ollama_available' => $available,
            'phi_model_available' => $modelAvailable,
            'status' => 'OK'
        ]);
        exit;
    }
    
    // For all other actions, check if Ollama is available
    $ollama = new OllamaService('mistral');
    if (!$ollama->isAvailable()) {
        throw new Exception("Ollama service is not available. Please ensure Ollama is running on http://localhost:11434");
    }
    
    if (!$ollama->isModelAvailable('mistral')) {
        throw new Exception("mistral model is not available in Ollama. Please pull the model first with: ollama pull mistral");
    }
    
    // Initialize classifier for actions that need it
    $classifier = new ThesisClassifier('mistral');
    
    switch ($action) {
        case 'classify':
            // Classify a single thesis
            if (!isset($_POST['thesis_id']) && !isset($_GET['thesis_id'])) {
                throw new Exception("Missing thesis_id parameter");
            }
            
            $thesisId = (int)($_POST['thesis_id'] ?? $_GET['thesis_id']);
            
            // Get thesis from database
            $query = "SELECT id, title, abstract FROM thesis WHERE id = ? LIMIT 1";
            $stmt = $GLOBALS['mysqli']->prepare($query);
            $stmt->bind_param('i', $thesisId);
            $stmt->execute();
            $result = $stmt->get_result();
            $thesis = $result->fetch_assoc();
            $stmt->close();
            
            if (!$thesis) {
                throw new Exception("Thesis not found with ID: $thesisId");
            }
            
            // Classify
            $classification = $classifier->classifyThesis(
                $thesis['id'],
                $thesis['title'],
                $thesis['abstract'] ?? '',
                '' // No full content for now
            );
            
            echo json_encode([
                'success' => true,
                'thesis_id' => $thesisId,
                'classification' => $classification,
                'message' => 'Thesis classified successfully'
            ]);
            break;
            
        case 'classify_batch':
            // Classify multiple theses
            $status = $_POST['status'] ?? 'approved';
            $limit = (int)($_POST['limit'] ?? 20);
            
            // Get unclassified theses
            $query = "
                SELECT t.id, t.title, t.abstract FROM thesis t
                LEFT JOIN thesis_classification tc ON t.id = tc.thesis_id
                WHERE t.status = ? AND tc.thesis_id IS NULL
                LIMIT ?
            ";
            
            $stmt = $GLOBALS['mysqli']->prepare($query);
            $stmt->bind_param('si', $status, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $theses = [];
            while ($row = $result->fetch_assoc()) {
                $theses[] = $row;
            }
            $stmt->close();
            
            $classified = [];
            $errors = [];
            
            foreach ($theses as $thesis) {
                try {
                    $classification = $classifier->classifyThesis(
                        $thesis['id'],
                        $thesis['title'],
                        $thesis['abstract'] ?? ''
                    );
                    $classified[] = $thesis['id'];
                } catch (Exception $e) {
                    $errors[] = ['thesis_id' => $thesis['id'], 'error' => $e->getMessage()];
                }
            }
            
            echo json_encode([
                'success' => true,
                'classified_count' => count($classified),
                'error_count' => count($errors),
                'classified_ids' => $classified,
                'errors' => $errors,
                'message' => 'Batch classification completed'
            ]);
            break;
            
        case 'get':
            // Get classification for a thesis
            if (!isset($_GET['thesis_id'])) {
                throw new Exception("Missing thesis_id parameter");
            }
            
            $thesisId = (int)$_GET['thesis_id'];
            $classification = $classifier->getClassification($thesisId);
            
            if (!$classification) {
                throw new Exception("No classification found for thesis ID: $thesisId");
            }
            
            echo json_encode([
                'success' => true,
                'thesis_id' => $thesisId,
                'classification' => $classification
            ]);
            break;
            
        case 'search_by_subject':
            // Search theses by subject
            if (!isset($_GET['category']) && !isset($_POST['category'])) {
                throw new Exception("Missing category parameter");
            }
            
            $category = $_POST['category'] ?? $_GET['category'];
            $limit = (int)($_GET['limit'] ?? $_POST['limit'] ?? 10);
            
            $results = $classifier->searchBySubject($category, $limit);
            
            echo json_encode([
                'success' => true,
                'category' => $category,
                'count' => count($results),
                'results' => $results
            ]);
            break;
            
        case 'stats':
            // Get classification statistics
            $stats = $classifier->getClassificationStats();
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        case 'subjects':
            // Get available subjects
            $subjects = $classifier->getAvailableSubjects();
            
            echo json_encode([
                'success' => true,
                'subjects' => $subjects
            ]);
            break;
            
        case 'methods':
            // Get available research methods
            $methods = $classifier->getAvailableResearchMethods();
            
            echo json_encode([
                'success' => true,
                'methods' => $methods
            ]);
            break;
            
        case 'classify_test':
            // Test classification with arbitrary title and abstract
            if (!isset($_POST['title']) && !isset($_GET['title'])) {
                throw new Exception("Missing title parameter");
            }
            if (!isset($_POST['abstract']) && !isset($_GET['abstract'])) {
                throw new Exception("Missing abstract parameter");
            }
            
            $title = $_POST['title'] ?? $_GET['title'];
            $abstract = $_POST['abstract'] ?? $_GET['abstract'];
            
            // Use a dummy thesis ID (0) since this is just for testing
            $classification = $classifier->classifyThesis(
                0,
                $title,
                $abstract,
                '' // No full content for test
            );
            
            echo json_encode([
                'success' => true,
                'thesis_id' => 0,
                'is_test' => true,
                'summary' => [
                    'title' => $title,
                    'abstract' => substr($abstract, 0, 100) . (strlen($abstract) > 100 ? '...' : '')
                ],
                'classification' => $classification,
                'message' => 'Test classification completed successfully'
            ]);
            break;
            
        default:
            throw new Exception("Unknown action: $action");
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
