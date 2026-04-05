<?php
/**
 * Generate Classification for Thesis
 * Manual classification endpoint for admin use
 * POST: thesis_id (required)
 */

header('Content-Type: application/json');

// Enable error logging, suppress display
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../db_includes/db_connect.php';
require_once __DIR__ . '/../ai_includes/ollama_service.php';
require_once __DIR__ . '/../ai_includes/thesis_classifier.php';

// Check admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin access required.']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method. POST required.");
    }
    
    $thesis_id = (int)($_POST['thesis_id'] ?? $_GET['thesis_id'] ?? 0);
    
    if ($thesis_id <= 0) {
        throw new Exception("Missing or invalid thesis_id parameter");
    }
    
    // Get thesis from database
    $query = "SELECT id, title, abstract FROM thesis WHERE id = ? LIMIT 1";
    $stmt = $GLOBALS['mysqli']->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Database error: " . $GLOBALS['mysqli']->error);
    }
    
    $stmt->bind_param('i', $thesis_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $thesis = $result->fetch_assoc();
    $stmt->close();
    
    if (!$thesis) {
        throw new Exception("Thesis not found with ID: $thesis_id");
    }
    
    // Check if Ollama is available
    $ollama = new OllamaService('phi');
    if (!$ollama->isAvailable()) {
        throw new Exception("Ollama service is not available. Please ensure Ollama is running on http://localhost:11434");
    }
    
    if (!$ollama->isModelAvailable('phi')) {
        throw new Exception("phi model is not available in Ollama");
    }
    
    // Generate classification
    $classifier = new ThesisClassifier('phi');
    
    $classification = $classifier->classifyThesis(
        $thesis['id'],
        $thesis['title'],
        $thesis['abstract'] ?? '',
        '' // No full content for now
    );
    
    // Return the classification for preview/editing
    echo json_encode([
        'success' => true,
        'thesis_id' => $thesis_id,
        'thesis' => [
            'title' => $thesis['title'],
            'abstract' => substr($thesis['abstract'] ?? '', 0, 200) . (strlen($thesis['abstract'] ?? '') > 200 ? '...' : '')
        ],
        'classification' => $classification
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
