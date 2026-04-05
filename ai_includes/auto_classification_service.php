<?php
/**
 * Auto-Classification Service
 * Automatically classifies new theses asynchronously
 * Can be called as a background process or inline
 */

require_once __DIR__ . '/thesis_classifier.php';
require_once __DIR__ . '/../db_includes/db_connect.php';

class AutoClassificationService {
    private $classifier;
    
    public function __construct() {
        try {
            $this->classifier = new ThesisClassifier('phi');
        } catch (Exception $e) {
            error_log("AutoClassificationService Init Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Classify a thesis asynchronously using a background process
     * @param int $thesisId
     * @return bool
     */
    public function classifyAsync($thesisId) {
        try {
            // For Windows
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $cmd = sprintf(
                    'start /B php.exe "%s" %d > NUL 2>&1',
                    __DIR__ . '/async_classifier.php',
                    $thesisId
                );
                exec($cmd);
            } else {
                // For Linux/Mac
                $cmd = sprintf(
                    'php %s %d > /dev/null 2>&1 &',
                    __DIR__ . '/async_classifier.php',
                    $thesisId
                );
                exec($cmd);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Async Classification Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Classify a thesis synchronously (blocking)
     * @param int $thesisId
     * @return array|false
     */
    public function classifySync($thesisId) {
        try {
            // Get thesis from database
            $query = "SELECT id, title, abstract FROM thesis WHERE id = ? LIMIT 1";
            $stmt = $GLOBALS['mysqli']->prepare($query);
            $stmt->bind_param('i', $thesisId);
            $stmt->execute();
            $result = $stmt->get_result();
            $thesis = $result->fetch_assoc();
            $stmt->close();
            
            if (!$thesis) {
                error_log("Thesis not found for classification: $thesisId");
                return false;
            }
            
            // Classify
            $classification = $this->classifier->classifyThesis(
                $thesis['id'],
                $thesis['title'],
                $thesis['abstract'] ?? ''
            );
            
            return $classification;
        } catch (Exception $e) {
            error_log("Sync Classification Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get classification status
     * @param int $thesisId
     * @return array
     */
    public function getStatus($thesisId) {
        try {
            $classification = $this->classifier->getClassification($thesisId);
            
            return [
                'is_classified' => $classification !== null,
                'classification' => $classification
            ];
        } catch (Exception $e) {
            error_log("Get Status Error: " . $e->getMessage());
            return ['is_classified' => false, 'error' => $e->getMessage()];
        }
    }
}

/**
 * Global helper function to auto-classify a thesis
 * @param int $thesisId
 * @param bool $async
 * @return bool
 */
function autoClassifyThesis($thesisId, $async = true) {
    try {
        $service = new AutoClassificationService();
        
        if ($async) {
            return $service->classifyAsync($thesisId);
        } else {
            $result = $service->classifySync($thesisId);
            return $result !== false;
        }
    } catch (Exception $e) {
        error_log("autoClassifyThesis Error: " . $e->getMessage());
        return false;
    }
}
?>
