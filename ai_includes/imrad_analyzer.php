<?php
/**
 * IMRaD Structure Analyzer
 * Analyzes document content and identifies IMRaD sections
 * IMRaD = Introduction, Methods, Results, And Discussion
 */

class IMRaDAnalyzer {
    
    private $document_text;
    private $sections = [];
    
    /**
     * Initialize analyzer with document text
     */
    public function __construct($document_text) {
        $this->document_text = $document_text;
        $this->sections = [
            'introduction' => [],
            'methods' => [],
            'results' => [],
            'discussion' => [],
            'conclusions' => []
        ];
    }
    
    /**
     * Analyze document and extract IMRaD sections
     */
    public function analyze() {
        error_log("[IMRaD Analyzer] Starting analysis...");
        
        $text = $this->document_text;
        
        // Step 1: Normalize text
        $text = $this->normalizeText($text);
        
        // Step 2: Split by common section headers
        $sections_raw = $this->splitBySectionHeaders($text);
        
        // Step 3: Classify sections
        $classified = $this->classifySections($sections_raw);
        
        error_log("[IMRaD Analyzer] Found " . count($classified) . " sections");
        
        return [
            'success' => true,
            'sections' => $classified,
            'section_count' => count($classified),
            'confidence' => $this->calculateConfidence($classified)
        ];
    }
    
    /**
     * Normalize text: lowercase, remove extra whitespace
     */
    private function normalizeText($text) {
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        // Preserve paragraph breaks somewhat
        $text = trim($text);
        return $text;
    }
    
    /**
     * Split document by common section headers
     */
    private function splitBySectionHeaders($text) {
        // Common IMRaD section patterns
        $patterns = [
            'introduction' => ['introduction', 'background', 'problem statement'],
            'methods' => ['methodology', 'methods', 'research design', 'approach', 'experimental design'],
            'results' => ['results', 'findings', 'outcomes', 'experimental results'],
            'discussion' => ['discussion', 'analysis', 'interpretation'],
            'conclusions' => ['conclusion', 'conclusions', 'recommendations', 'future work']
        ];
        
        $sections_found = [];
        
        // Search for section headers (case-insensitive)
        foreach ($patterns as $section_type => $keywords) {
            foreach ($keywords as $keyword) {
                $pattern = '/^' . preg_quote($keyword, '/') . '/im';
                if (preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                    $position = $matches[0][1];
                    $sections_found[$position] = [
                        'type' => $section_type,
                        'keyword' => $keyword,
                        'position' => $position
                    ];
                }
            }
        }
        
        // Sort by position
        ksort($sections_found);
        
        error_log("[IMRaD] Found " . count($sections_found) . " section headers");
        
        return $sections_found;
    }
    
    /**
     * Classify and extract section content
     */
    private function classifySections($sections_raw) {
        $sections = [];
        
        if (empty($sections_raw)) {
            // No clear structure found - use heuristics
            return $this->analyzeWithoutHeaders();
        }
        
        $positions = array_keys($sections_raw);
        
        foreach ($positions as $index => $position) {
            $section_info = $sections_raw[$position];
            $section_type = $section_info['type'];
            
            // Determine end position
            $end_position = isset($positions[$index + 1]) ? $positions[$index + 1] : strlen($this->document_text);
            
            // Extract content
            $content = substr($this->document_text, $position, $end_position - $position);
            
            // Calculate word count
            $word_count = str_word_count(strip_tags($content));
            
            if ($word_count > 50) { // Only keep sections with meaningful content
                $sections[] = [
                    'type' => $section_type,
                    'header' => $section_info['keyword'],
                    'content' => substr($content, 0, 5000), // Limit to 5000 chars for preview
                    'word_count' => $word_count,
                    'confidence' => $this->calculateSectionConfidence($section_type, $content)
                ];
            }
        }
        
        return $sections;
    }
    
    /**
     * Analyze document without clear headers
     */
    private function analyzeWithoutHeaders() {
        error_log("[IMRaD] No clear headers found, using heuristic analysis...");
        
        // Split document into thirds or quarters
        $total_words = str_word_count($this->document_text);
        $third = floor($total_words / 3);
        
        $sections = [];
        
        // First part = Introduction
        $sections[] = [
            'type' => 'introduction',
            'header' => 'Introduction (Detected)',
            'content' => $this->document_text,
            'word_count' => $total_words,
            'confidence' => 40 // Low confidence
        ];
        
        return $sections;
    }
    
    /**
     * Calculate confidence for a section
     */
    private function calculateSectionConfidence($section_type, $content) {
        $confidence = 50; // Base confidence
        
        // Keyword matching to increase confidence
        $keywords = [
            'introduction' => ['background', 'motivation', 'problem', 'objective'],
            'methods' => ['methodology', 'approach', 'design', 'implement', 'algorithm'],
            'results' => ['result', 'finding', 'outcome', 'performance', 'evaluation'],
            'discussion' => ['discuss', 'analysis', 'interpret', 'implication', 'limitation'],
            'conclusions' => ['conclude', 'future', 'recommendation', 'summary']
        ];
        
        if (isset($keywords[$section_type])) {
            foreach ($keywords[$section_type] as $keyword) {
                if (stripos($content, $keyword) !== false) {
                    $confidence += 10;
                }
            }
        }
        
        return min($confidence, 100); // Cap at 100%
    }
    
    /**
     * Calculate overall confidence in structure
     */
    private function calculateConfidence($sections) {
        if (empty($sections)) {
            return 30; // Very low confidence if no sections
        }
        
        // Average confidence of all sections
        $total = 0;
        foreach ($sections as $section) {
            $total += $section['confidence'];
        }
        
        $avg = $total / count($sections);
        
        // Boost if we have all 3 main sections (Intro, Methods, Results/Discussion)
        $types = array_column($sections, 'type');
        if (in_array('introduction', $types) && 
            (in_array('methods', $types) || in_array('results', $types))) {
            $avg = min($avg + 20, 100);
        }
        
        return round($avg);
    }
}

// Test/Usage Example
/*
require_once 'DocumentMetadataExtractor.php';

$extractor = new DocumentMetadataExtractor();
$text = $extractor->extractTextFromPdf('/path/to/document.pdf');

$analyzer = new IMRaDAnalyzer($text);
$structure = $analyzer->analyze();

echo json_encode($structure, JSON_PRETTY_PRINT);
*/

?>
