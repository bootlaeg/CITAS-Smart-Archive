<?php
/**
 * Thesis Classifier - AI-powered classification system using Ollama
 * Provides: subject classification, keyword extraction, research method identification,
 * complexity determination, citation extraction, and related thesis recommendations
 */

require_once __DIR__ . '/ollama_service.php';
require_once __DIR__ . '/../db_includes/db_connect.php';

class ThesisClassifier {
    private $ollamaService;
    private $mysqli;
    
    // Classification categories
    private $subjects = [
        'AI & Machine Learning',
        'Database Systems',
        'Cybersecurity',
        'Cloud Computing',
        'Web Development',
        'Mobile Development',
        'Natural Language Processing',
        'Computer Vision',
        'Data Science',
        'Software Engineering',
        'Networks & Communication',
        'Operating Systems',
        'Human-Computer Interaction',
        'Graphics & Visualization',
        'Internet of Things',
        'Blockchain & Cryptocurrency'
    ];
    
    private $researchMethods = [
        'Empirical Study',
        'Literature Review',
        'Case Study',
        'Experimental Study',
        'Qualitative Research',
        'Quantitative Research',
        'Mixed Methods',
        'Survey/Questionnaire',
        'Simulation & Modeling',
        'Comparative Analysis'
    ];
    
    private $complexityLevels = ['beginner', 'intermediate', 'advanced'];
    
    public function __construct($model = 'phi', $ollamaUrl = 'http://localhost:11434') {
        $this->ollamaService = new OllamaService($model, $ollamaUrl);
        // Database will be loaded lazily when needed
        $this->mysqli = null;
    }
    
    /**
     * Get database connection (lazy load)
     */
    private function getDatabase() {
        if ($this->mysqli === null) {
            // Try to get from global scope
            if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli']) {
                $this->mysqli = $GLOBALS['mysqli'];
            } else {
                throw new Exception("Database connection not available");
            }
        }
        return $this->mysqli;
    }
    
    /**
     * Classify a complete thesis
     * @param int $thesisId
     * @param string $title
     * @param string $abstract
     * @param string $content Optional: full thesis content for better analysis
     * @return array Classification results
     */
    public function classifyThesis($thesisId, $title, $abstract, $content = '') {
        try {
            // Check if already classified - if so, return cached result
            $existing = $this->getClassification($thesisId);
            if ($existing) {
                return $existing;
            }
            
            // Run all classification tasks
            $classification = [
                'thesis_id' => $thesisId,
                'subject' => $this->classifySubject($title, $abstract, $content),
                'keywords' => $this->extractKeywords($title, $abstract),
                'research_method' => $this->identifyResearchMethod($title, $abstract, $content),
                'complexity' => $this->determineComplexity($title, $abstract, $content),
                'citations' => $this->extractCitations($abstract, $content),
                'related_theses' => $this->findRelatedTheses($thesisId, $classification['subject']['category'] ?? null)
            ];
            
            // Store in database
            $this->storeClassification($thesisId, $classification);
            
            return $classification;
        } catch (Exception $e) {
            error_log("Thesis Classification Error (ID: $thesisId): " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Classify thesis subject using AI (OPTIMIZED - no confidence calculation)
     * @param string $title
     * @param string $abstract
     * @param string $content
     * @return array
     */
    private function classifySubject($title, $abstract, $content = '') {
        try {
            $subjects_list = implode(', ', $this->subjects);
            
            $prompt = "Classify this thesis into ONE of these categories: $subjects_list\n\nTitle: $title\nAbstract: $abstract\n\nRespond with ONLY category name, nothing else.";
            
            $result = $this->ollamaService->prompt($prompt, ['temperature' => 0.3]);
            $category = trim($result);
            
            // Validate category
            if (!in_array($category, $this->subjects)) {
                $category = 'Other';
            }
            
            return [
                'category' => $category,
                'confidence' => 100
            ];
        } catch (Exception $e) {
            error_log("Subject Classification Error: " . $e->getMessage());
            return ['category' => 'Unknown', 'confidence' => 0];
        }
    }
    
    /**
     * Extract keywords from thesis
     * @param string $title
     * @param string $abstract
     * @return array Array of 5 keywords with confidence scores
     */
    private function extractKeywords($title, $abstract) {
        try {
            $prompt = "Extract exactly 5 important keywords from this thesis title and abstract:\n\nTitle: $title\nAbstract: $abstract\n\nRespond with ONLY a JSON array of keyword strings like this:\n[\"keyword1\", \"keyword2\", \"keyword3\", \"keyword4\", \"keyword5\"]";
            
            $response = $this->ollamaService->prompt($prompt, ['temperature' => 0.3]);
            
            // Try to extract JSON array from response
            preg_match('/\[.*\]/s', $response, $matches);
            if (!empty($matches[0])) {
                $decoded = json_decode($matches[0], true);
                if (is_array($decoded) && count($decoded) > 0) {
                    // Convert to keyword objects for display
                    $keywords = [];
                    foreach (array_slice($decoded, 0, 5) as $index => $keyword) {
                        $keywords[] = [
                            'keyword' => is_string($keyword) ? $keyword : (isset($keyword['keyword']) ? $keyword['keyword'] : 'Unknown'),
                            'relevance' => 100 - ($index * 5)
                        ];
                    }
                    return $keywords;
                }
            }
            
            return [];
        } catch (Exception $e) {
            error_log("Keyword Extraction Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Identify research method (OPTIMIZED - no confidence calculation)
     * @param string $title
     * @param string $abstract
     * @param string $content
     * @return array
     */
    private function identifyResearchMethod($title, $abstract, $content = '') {
        try {
            $methods_list = implode(', ', $this->researchMethods);
            
            $prompt = "Identify the primary research method from: $methods_list\n\nTitle: $title\nAbstract: $abstract\n\nRespond with ONLY the method name, nothing else.";
            
            $result = $this->ollamaService->prompt($prompt, ['temperature' => 0.3]);
            $method = trim($result);
            
            // Validate method
            if (!in_array($method, $this->researchMethods)) {
                $method = 'Empirical Study';
            }
            
            return [
                'method' => $method,
                'confidence' => 100
            ];
        } catch (Exception $e) {
            error_log("Research Method Identification Error: " . $e->getMessage());
            return ['method' => 'Unknown', 'confidence' => 0];
        }
    }
    
    /**
     * Determine complexity level (OPTIMIZED - no confidence calculation)
     * @param string $title
     * @param string $abstract
     * @param string $content
     * @return array
     */
    private function determineComplexity($title, $abstract, $content = '') {
        try {
            $prompt = "Assess complexity as ONLY ONE of: beginner, intermediate, advanced\n\nTitle: $title\nAbstract: $abstract\n\nRespond with ONLY the level name, nothing else.";
            
            $result = $this->ollamaService->prompt($prompt, ['temperature' => 0.3]);
            $level = trim(strtolower($result));
            
            // Validate level
            if (!in_array($level, $this->complexityLevels)) {
                $level = 'intermediate';
            }
            
            return [
                'level' => $level,
                'confidence' => 100
            ];
        } catch (Exception $e) {
            error_log("Complexity Determination Error: " . $e->getMessage());
            return ['level' => 'intermediate', 'confidence' => 0];
        }
    }
    
    /**
     * Extract citations from abstract and content
     * @param string $abstract
     * @param string $content
     * @return array Array of extracted citations
     */
    private function extractCitations($abstract, $content = '') {
        try {
            $text = substr($abstract, 0, 300) . (strlen($content) > 0 ? ' ' . substr($content, 0, 300) : '');
            
            $prompt = "Extract up to 5 academic citations or references from this text:\n\nText: $text\n\nRespond with ONLY a JSON array of citation strings or say [] if none found:\n[\"citation1\", \"citation2\", ...]";
            
            $response = $this->ollamaService->prompt($prompt, ['temperature' => 0.2]);
            
            // Try to extract JSON array from response
            preg_match('/\[.*\]/s', $response, $matches);
            if (!empty($matches[0])) {
                $decoded = json_decode($matches[0], true);
                if (is_array($decoded) && count($decoded) > 0) {
                    // Convert to citation objects if needed
                    $citations = [];
                    foreach (array_slice($decoded, 0, 5) as $citation) {
                        if (is_string($citation)) {
                            $citations[] = [
                                'citation' => $citation,
                                'type' => 'other'
                            ];
                        } else if (is_array($citation) && isset($citation['citation'])) {
                            $citations[] = $citation;
                        }
                    }
                    return $citations;
                }
            }
            
            return [];
        } catch (Exception $e) {
            error_log("Citation Extraction Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Find related theses based on classification
     * @param int $thesisId
     * @param string $category
     * @return array Array of related thesis IDs
     */
    private function findRelatedTheses($thesisId, $category = null) {
        try {
            if (!$category) {
                return [];
            }
            
            // Query for similar theses in the same subject category
            $query = "
                SELECT tc.thesis_id FROM thesis_classification tc
                WHERE tc.subject_category = ?
                AND tc.thesis_id != ?
                ORDER BY tc.subject_confidence DESC, tc.classification_timestamp DESC
                LIMIT 5
            ";
            
            $stmt = $this->getDatabase()->prepare($query);
            $stmt->bind_param('si', $category, $thesisId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $relatedIds = [];
            while ($row = $result->fetch_assoc()) {
                $relatedIds[] = (int)$row['thesis_id'];
            }
            
            $stmt->close();
            return $relatedIds;
        } catch (Exception $e) {
            error_log("Related Theses Search Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Store classification in database
     * @param int $thesisId
     * @param array $classification
     */
    private function storeClassification($thesisId, $classification) {
        try {
            $subjectCategory = $classification['subject']['category'] ?? 'Unknown';
            $subjectConfidence = $classification['subject']['confidence'] ?? 0;
            $keywords = json_encode($classification['keywords'] ?? []);
            $researchMethod = $classification['research_method']['method'] ?? 'Unknown';
            $methodConfidence = $classification['research_method']['confidence'] ?? 0;
            $complexityLevel = $classification['complexity']['level'] ?? 'intermediate';
            $complexityConfidence = $classification['complexity']['confidence'] ?? 0;
            $citations = json_encode($classification['citations'] ?? []);
            $relatedThesisIds = json_encode($classification['related_theses'] ?? []);
            
            $query = "
                INSERT INTO thesis_classification 
                (thesis_id, subject_category, subject_confidence, keywords, 
                 research_method, method_confidence, complexity_level, 
                 complexity_confidence, citations, related_thesis_ids)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                subject_category = VALUES(subject_category),
                subject_confidence = VALUES(subject_confidence),
                keywords = VALUES(keywords),
                research_method = VALUES(research_method),
                method_confidence = VALUES(method_confidence),
                complexity_level = VALUES(complexity_level),
                complexity_confidence = VALUES(complexity_confidence),
                citations = VALUES(citations),
                related_thesis_ids = VALUES(related_thesis_ids),
                last_updated = CURRENT_TIMESTAMP
            ";
            
            $stmt = $this->getDatabase()->prepare($query);
            $stmt->bind_param(
                'isdssissss',
                $thesisId,
                $subjectCategory,
                $subjectConfidence,
                $keywords,
                $researchMethod,
                $methodConfidence,
                $complexityLevel,
                $complexityConfidence,
                $citations,
                $relatedThesisIds
            );
            
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Store Classification Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Retrieve classification from database
     * @param int $thesisId
     * @return array|null
     */
    public function getClassification($thesisId) {
        try {
            $query = "
                SELECT * FROM thesis_classification 
                WHERE thesis_id = ? LIMIT 1
            ";
            
            $stmt = $this->getDatabase()->prepare($query);
            $stmt->bind_param('i', $thesisId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if (!$row) {
                return null;
            }
            
            // Parse JSON fields
            $row['keywords'] = json_decode($row['keywords'], true) ?? [];
            $row['citations'] = json_decode($row['citations'], true) ?? [];
            $row['related_thesis_ids'] = json_decode($row['related_thesis_ids'], true) ?? [];
            
            return $row;
        } catch (Exception $e) {
            error_log("Get Classification Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Search for theses by subject category
     * @param string $category
     * @param int $limit
     * @return array
     */
    public function searchBySubject($category, $limit = 10) {
        try {
            $query = "
                SELECT t.*, tc.* FROM thesis t
                JOIN thesis_classification tc ON t.id = tc.thesis_id
                WHERE tc.subject_category = ?
                ORDER BY tc.subject_confidence DESC, t.views DESC
                LIMIT ?
            ";
            
            $stmt = $this->getDatabase()->prepare($query);
            $stmt->bind_param('si', $category, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $theses = [];
            while ($row = $result->fetch_assoc()) {
                $row['keywords'] = json_decode($row['keywords'], true) ?? [];
                $theses[] = $row;
            }
            
            $stmt->close();
            return $theses;
        } catch (Exception $e) {
            error_log("Search By Subject Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search for theses by complexity level
     * @param string $level
     * @param int $limit
     * @return array
     */
    public function searchByComplexity($level, $limit = 10) {
        try {
            if (!in_array($level, $this->complexityLevels)) {
                return [];
            }
            
            $query = "
                SELECT t.*, tc.* FROM thesis t
                JOIN thesis_classification tc ON t.id = tc.thesis_id
                WHERE tc.complexity_level = ?
                ORDER BY tc.complexity_confidence DESC, t.views DESC
                LIMIT ?
            ";
            
            $stmt = $this->getDatabase()->prepare($query);
            $stmt->bind_param('si', $level, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $theses = [];
            while ($row = $result->fetch_assoc()) {
                $row['keywords'] = json_decode($row['keywords'], true) ?? [];
                $theses[] = $row;
            }
            
            $stmt->close();
            return $theses;
        } catch (Exception $e) {
            error_log("Search By Complexity Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all available subjects
     * @return array
     */
    public function getAvailableSubjects() {
        return $this->subjects;
    }
    
    /**
     * Get all available research methods
     * @return array
     */
    public function getAvailableResearchMethods() {
        return $this->researchMethods;
    }
    
    /**
     * Get statistics about classifications
     * @return array
     */
    public function getClassificationStats() {
        try {
            $stats = [
                'total_classified' => 0,
                'subjects' => [],
                'methods' => [],
                'complexity_distribution' => ['beginner' => 0, 'intermediate' => 0, 'advanced' => 0]
            ];
            
            // Total classified
            $query = "SELECT COUNT(*) as count FROM thesis_classification";
            $result = $this->getDatabase()->query($query);
            $row = $result->fetch_assoc();
            $stats['total_classified'] = (int)$row['count'];
            
            // Subject distribution
            $query = "SELECT subject_category, COUNT(*) as count FROM thesis_classification GROUP BY subject_category";
            $result = $this->getDatabase()->query($query);
            while ($row = $result->fetch_assoc()) {
                $stats['subjects'][$row['subject_category']] = (int)$row['count'];
            }
            
            // Research method distribution
            $query = "SELECT research_method, COUNT(*) as count FROM thesis_classification GROUP BY research_method";
            $result = $this->getDatabase()->query($query);
            while ($row = $result->fetch_assoc()) {
                $stats['methods'][$row['research_method']] = (int)$row['count'];
            }
            
            // Complexity distribution
            $query = "SELECT complexity_level, COUNT(*) as count FROM thesis_classification GROUP BY complexity_level";
            $result = $this->getDatabase()->query($query);
            while ($row = $result->fetch_assoc()) {
                $stats['complexity_distribution'][$row['complexity_level']] = (int)$row['count'];
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("Classification Stats Error: " . $e->getMessage());
            return [];
        }
    }
}
?>
