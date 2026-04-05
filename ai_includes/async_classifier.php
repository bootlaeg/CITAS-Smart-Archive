<?php
/**
 * Async Classifier Worker
 * Runs as separate process to classify theses in the background
 * Called by auto_classification_service.php
 */

require_once __DIR__ . '/thesis_classifier.php';
require_once __DIR__ . '/../db_includes/db_connect.php';

// Suppress output
ob_start();

try {
    $thesisId = (int)($argv[1] ?? 0);
    
    if ($thesisId <= 0) {
        throw new Exception("Invalid thesis ID");
    }
    
    // Get thesis from database
    $query = "SELECT id, title, abstract FROM thesis WHERE id = ? LIMIT 1";
    $stmt = $GLOBALS['mysqli']->prepare($query);
    $stmt->bind_param('i', $thesisId);
    $stmt->execute();
    $result = $stmt->get_result();
    $thesis = $result->fetch_assoc();
    $stmt->close();
    
    if (!$thesis) {
        throw new Exception("Thesis not found: $thesisId");
    }
    
    // Classify
    $classifier = new ThesisClassifier('phi');
    $classification = $classifier->classifyThesis(
        $thesis['id'],
        $thesis['title'],
        $thesis['abstract'] ?? ''
    );
    
    // Log success
    error_log("Async Classification Completed for Thesis ID: $thesisId");
    
} catch (Exception $e) {
    error_log("Async Classifier Error: " . $e->getMessage());
}

ob_end_clean();
?>
