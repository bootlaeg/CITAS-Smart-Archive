<?php
/**
 * Save Classification for Thesis
 * Saves classification data (auto-generated or manually edited)
 */

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../db_includes/db_connect.php';

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
    
    $thesis_id = (int)($_POST['thesis_id'] ?? 0);
    $subject_category = $_POST['subject_category'] ?? '';
    $subject_confidence = (float)($_POST['subject_confidence'] ?? 0);
    $keywords = $_POST['keywords'] ?? '[]'; // JSON string
    $research_method = $_POST['research_method'] ?? '';
    $method_confidence = (float)($_POST['method_confidence'] ?? 0);
    $complexity_level = $_POST['complexity_level'] ?? 'intermediate';
    $complexity_confidence = (float)($_POST['complexity_confidence'] ?? 0);
    $citations = $_POST['citations'] ?? '[]'; // JSON string
    $related_thesis_ids = $_POST['related_thesis_ids'] ?? '[]'; // JSON string
    
    if ($thesis_id <= 0) {
        throw new Exception("Missing or invalid thesis_id");
    }
    
    if (empty($subject_category)) {
        throw new Exception("Missing subject_category");
    }
    
    // Validate JSON fields
    $keywords_decoded = json_decode($keywords, true);
    if ($keywords === 'null' || $keywords === '' || !is_array($keywords_decoded)) {
        $keywords = '[]';
    }
    
    $citations_decoded = json_decode($citations, true);
    if ($citations === 'null' || $citations === '' || !is_array($citations_decoded)) {
        $citations = '[]';
    }
    
    $related_decoded = json_decode($related_thesis_ids, true);
    if ($related_thesis_ids === 'null' || $related_thesis_ids === '' || !is_array($related_decoded)) {
        $related_thesis_ids = '[]';
    }
    
    // Validate complexity level
    $valid_levels = ['beginner', 'intermediate', 'advanced'];
    if (!in_array($complexity_level, $valid_levels)) {
        $complexity_level = 'intermediate';
    }
    
    // Check if thesis exists
    $stmt = $GLOBALS['mysqli']->prepare("SELECT id FROM thesis WHERE id = ?");
    $stmt->bind_param('i', $thesis_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Thesis not found with ID: $thesis_id");
    }
    $stmt->close();
    
    // Check if classification already exists
    $stmt = $GLOBALS['mysqli']->prepare("SELECT id FROM thesis_classification WHERE thesis_id = ?");
    $stmt->bind_param('i', $thesis_id);
    $stmt->execute();
    $existing = $stmt->get_result();
    $stmt->close();
    
    if ($existing->num_rows > 0) {
        // Update existing classification
        $stmt = $GLOBALS['mysqli']->prepare("
            UPDATE thesis_classification 
            SET subject_category = ?,
                subject_confidence = ?,
                keywords = ?,
                research_method = ?,
                method_confidence = ?,
                complexity_level = ?,
                complexity_confidence = ?,
                citations = ?,
                related_thesis_ids = ?,
                last_updated = NOW()
            WHERE thesis_id = ?
        ");
        
        $stmt->bind_param(
            'sdsssdssi',
            $subject_category,
            $subject_confidence,
            $keywords,
            $research_method,
            $method_confidence,
            $complexity_level,
            $complexity_confidence,
            $citations,
            $related_thesis_ids,
            $thesis_id
        );
    } else {
        // Insert new classification
        $stmt = $GLOBALS['mysqli']->prepare("
            INSERT INTO thesis_classification
            (thesis_id, subject_category, subject_confidence, keywords, research_method, 
             method_confidence, complexity_level, complexity_confidence, citations, 
             related_thesis_ids, classification_timestamp, last_updated)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->bind_param(
            'isdsssdsss',
            $thesis_id,
            $subject_category,
            $subject_confidence,
            $keywords,
            $research_method,
            $method_confidence,
            $complexity_level,
            $complexity_confidence,
            $citations,
            $related_thesis_ids
        );
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to save classification: " . $stmt->error);
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Classification saved successfully',
        'thesis_id' => $thesis_id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
