<?php
/**
 * Journal Converter
 * Converts raw documents to journal format (10-20 pages, IMRaD structure)
 */

class JournalConverter {
    
    private $thesis_id;
    private $original_file_path;
    private $document_text;
    private $metadata;
    private $imrad_sections;
    private $conn;
    
    public function __construct($thesis_id, $document_text, $metadata, &$conn) {
        $this->thesis_id = $thesis_id;
        $this->document_text = $document_text;
        $this->metadata = $metadata;
        $this->conn = $conn;
    }
    
    /**
     * Main conversion process
     */
    public function convert() {
        error_log("[JournalConverter] Starting conversion for thesis $this->thesis_id");
        
        try {
            // Step 1: Analyze IMRaD structure
            $this->analyzeStructure();
            
            // Step 2: Extract and condense sections
            $condensed = $this->condenseToJournal();
            
            // Step 3: Reconstruct in IMRaD format
            $journal_content = $this->reconstructAsIMRaD($condensed);
            
            // Step 4: Generate PDF
            $pdf_path = $this->generateJournalPDF($journal_content);
            
            // Step 5: Update database
            $this->updateDatabase($pdf_path, $journal_content);
            
            error_log("[JournalConverter] Conversion completed successfully for thesis $this->thesis_id");
            
            return [
                'success' => true,
                'thesis_id' => $this->thesis_id,
                'journal_file_path' => $pdf_path,
                'conversion_status' => 'completed',
                'message' => 'Document successfully converted to journal format'
            ];
            
        } catch (Exception $e) {
            error_log("[JournalConverter] ERROR: " . $e->getMessage());
            $this->updateDatabase(null, null, 'failed');
            
            return [
                'success' => false,
                'thesis_id' => $this->thesis_id,
                'error' => $e->getMessage(),
                'conversion_status' => 'failed'
            ];
        }
    }
    
    /**
     * Analyze document structure
     */
    private function analyzeStructure() {
        require_once 'imrad_analyzer.php';
        
        $analyzer = new IMRaDAnalyzer($this->document_text);
        $analysis = $analyzer->analyze();
        
        $this->imrad_sections = $analysis['sections'];
        
        error_log("[JournalConverter] Document structure: " . json_encode([
            'section_count' => $analysis['section_count'],
            'confidence' => $analysis['confidence']
        ]));
    }
    
    /**
     * Condense document to journal format (target: 10-20 pages)
     * Assumes ~250 words per page, so 2500-5000 words target
     */
    private function condenseToJournal() {
        $target_words = 3500; // ~14 pages at 250 words/page
        
        $condensed = [
            'introduction' => $this->extractSummary('introduction', 500, 'Introduction: Summarize motivation, problem, and objectives'),
            'methods' => $this->extractSummary('methods', 800, 'Methods: Describe key methodology and approach'),
            'results' => $this->extractSummary('results', 1000, 'Results: Present main findings and key outcomes'),
            'discussion' => $this->extractSummary('discussion', 1000, 'Discussion: Interpret results and implications'),
            'conclusions' => $this->extractSummary('conclusions', 200, 'Conclusions: Summarize and suggest future work')
        ];
        
        error_log("[JournalConverter] Condensed sections to target ~" . $target_words . " words");
        
        return $condensed;
    }
    
    /**
     * Extract and summarize a section
     */
    private function extractSummary($section_type, $target_words, $prompt_hint) {
        // Find the section
        $section_content = '';
        foreach ($this->imrad_sections as $section) {
            if ($section['type'] === $section_type) {
                $section_content = $section['content'];
                break;
            }
        }
        
        if (empty($section_content)) {
            // Section not found, create placeholder
            return $prompt_hint . "\n\n[Content to be extracted from original document]";
        }
        
        // Use Ollama to summarize if available
        $summary = $this->summarizeWithOllama($section_content, $target_words, $prompt_hint);
        
        if ($summary) {
            return $summary;
        }
        
        // Fallback: Simple truncation and keyword extraction
        return $this->simpleSummarize($section_content, $target_words);
    }
    
    /**
     * Use Ollama to create intelligent summaries
     */
    private function summarizeWithOllama($text, $target_words, $prompt_hint) {
        try {
            require_once 'ollama_service.php';
            
            $ollama = new OllamaService();
            
            $prompt = "Summarize the following text in approximately {$target_words} words for a journal article section. " .
                      $prompt_hint . "\n\nText:\n" . substr($text, 0, 3000);
            
            $summary = $ollama->generateText($prompt, [
                'temperature' => 0.3, // Low temperature for factual summarization
                'num_predict' => $target_words + 100
            ]);
            
            if ($summary) {
                error_log("[JournalConverter] Ollama summarization successful");
                return trim($summary);
            }
        } catch (Exception $e) {
            error_log("[JournalConverter] Ollama unavailable: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Simple summarization fallback
     */
    private function simpleSummarize($text, $target_words) {
        // Extract key sentences using scoring
        $sentences = preg_split('/[.!?]+/', $text);
        $scored_sentences = [];
        
        foreach ($sentences as $i => $sentence) {
            $sentence = trim($sentence);
            if (strlen($sentence) < 20) continue; // Skip short sentences
            
            // Score based on:
            // 1. Contains numbers (results)
            // 2. Contains methodology keywords
            // 3. Position in text (early sentences often important)
            
            $score = 1;
            if (preg_match('/\d+[\.\d]*\s*(%|pages?|results?|findings?)/i', $sentence)) {
                $score += 3;
            }
            if (preg_match('/(method|approach|algorithm|technique|design)/i', $sentence)) {
                $score += 2;
            }
            if ($i < 5) {
                $score += 1; // Boost early sentences
            }
            
            $scored_sentences[] = [
                'sentence' => $sentence,
                'score' => $score,
                'index' => $i
            ];
        }
        
        // Sort by score, then by original order
        usort($scored_sentences, function($a, $b) {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }
            return $a['index'] <=> $b['index'];
        });
        
        // Select top sentences
        $selected = array_slice($scored_sentences, 0, 10);
        usort($selected, function($a, $b) {
            return $a['index'] <=> $b['index'];
        });
        
        return implode('. ', array_column($selected, 'sentence')) . '.';
    }
    
    /**
     * Reconstruct document as proper IMRaD journal article
     */
    private function reconstructAsIMRaD($sections) {
        $journal = "# " . htmlspecialchars($this->metadata['title'] ?? 'Research Article') . "\n\n";
        
        // Add authors
        if (!empty($this->metadata['author'])) {
            $journal .= "**Authors:** " . htmlspecialchars($this->metadata['author']) . "\n\n";
        }
        
        // Add abstract (keep original)
        if (!empty($this->metadata['abstract'])) {
            $journal .= "## Abstract\n\n";
            $journal .= substr($this->metadata['abstract'], 0, 500) . "\n\n";
        }
        
        // Add IMRaD sections
        $journal .= "## 1. Introduction\n\n" . $sections['introduction'] . "\n\n";
        $journal .= "## 2. Methods\n\n" . $sections['methods'] . "\n\n";
        $journal .= "## 3. Results\n\n" . $sections['results'] . "\n\n";
        $journal .= "## 4. Discussion\n\n" . $sections['discussion'] . "\n\n";
        $journal .= "## 5. Conclusions\n\n" . $sections['conclusions'] . "\n\n";
        
        return $journal;
    }
    
    /**
     * Generate PDF from journal content
     */
    private function generateJournalPDF($journal_content) {
        // For now, save as text file with .pdf naming convention
        // In production, use TCPDF or similar library
        
        $filename = 'thesis_' . $this->thesis_id . '_journal_' . uniqid() . '.pdf';
        $file_path = 'uploads/thesis_files/' . $filename;
        
        // Create directory if needed
        if (!is_dir('uploads/thesis_files')) {
            mkdir('uploads/thesis_files', 0755, true);
        }
        
        // Save content (ideally as proper PDF with TCPDF)
        file_put_contents(
            __DIR__ . '/../' . $file_path,
            $journal_content
        );
        
        error_log("[JournalConverter] Journal PDF saved to: " . $file_path);
        
        return $file_path;
    }
    
    /**
     * Update database with conversion results
     */
    private function updateDatabase($pdf_path, $journal_content, $status = 'completed') {
        $page_count = null;
        
        if ($journal_content) {
            // Estimate page count (250 words per page)
            $word_count = str_word_count(strip_tags($journal_content));
            $page_count = ceil($word_count / 250);
        }
        
        $update_sql = "UPDATE thesis SET 
            journal_file_path = ?,
            is_journal_converted = ?,
            journal_conversion_status = ?,
            journal_page_count = ?,
            journal_converted_at = NOW(),
            journal_imrad_sections = ?
            WHERE id = ?";
        
        $stmt = $this->conn->prepare($update_sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        
        $is_converted = ($status === 'completed') ? 1 : 0;
        $imrad_json = json_encode($this->imrad_sections);
        
        $stmt->bind_param("siisii", $pdf_path, $is_converted, $status, $page_count, $imrad_json, $this->thesis_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $stmt->close();
        
        error_log("[JournalConverter] Database updated successfully");
    }
}

?>
