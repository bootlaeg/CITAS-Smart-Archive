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
    private $journal_page_count = null;
    
    public function __construct($thesis_id, $document_text, $metadata, $conn = null) {
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
                'journal_page_count' => $this->journal_page_count,
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
     * Use Hugging Face API to create intelligent summaries
     */
    private function summarizeWithOllama($text, $target_words, $prompt_hint) {
        try {
            // For Hostinger-hosted system: Use Cloudflare tunnel to access local Ollama
            $ollama_url = 'https://ollama.CITAS-smart-archive.com/api/generate';
            
            // Prepare the text (limit to reasonable size for context)
            $context_text = substr($text, 0, 6000);
            
            // Create a focused prompt for summarization
            $prompt = "Summarize the following section in approximately $target_words words. Focus on: $prompt_hint\n\nText:\n$context_text\n\nSummary:";
            
            error_log("[JournalConverter] Calling Ollama via Cloudflare tunnel at: $ollama_url");
            error_log("[JournalConverter] Using model: mistral");
            error_log("[JournalConverter] Prompt length: " . strlen($prompt));
            
            $request_body = [
                'model' => 'mistral',
                'prompt' => $prompt,
                'stream' => false,
                'temperature' => 0.3,
                'num_predict' => ceil($target_words * 1.3) // Word estimate to token estimate
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $ollama_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_body));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For tunnel self-signed certs
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            error_log("[JournalConverter] Sending request to Ollama via Cloudflare tunnel...");
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            if ($curl_error) {
                error_log("[JournalConverter] Connection failed: $curl_error (Tunnel may be offline)");
                throw new Exception("Curl error: $curl_error");
            }
            
            if ($http_code !== 200) {
                error_log("[JournalConverter] HTTP Error: Got $http_code instead of 200");
                throw new Exception("HTTP $http_code response from Ollama. Response: " . substr($response, 0, 200));
            }
            
            if (!$response) {
                throw new Exception("Empty response from Ollama");
            }
            
            error_log("[JournalConverter] Ollama response received (" . strlen($response) . " bytes)");
            
            $result = json_decode($response, true);
            
            if (!$result || !isset($result['response'])) {
                error_log("[JournalConverter] Invalid Ollama response: " . substr($response, 0, 200));
                throw new Exception("Invalid response format from Ollama");
            }
            
            $summary = trim($result['response']);
            
            if (!empty($summary)) {
                error_log("[JournalConverter] Ollama summarization successful (" . str_word_count($summary) . " words)");
                return $summary;
            } else {
                error_log("[JournalConverter] Ollama returned empty summary");
            }
        } catch (Exception $e) {
            error_log("[JournalConverter] Ollama service error: " . $e->getMessage());
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
        // Skip database update if thesis_id is not set or is 'unsaved' placeholder
        if (!$this->thesis_id || $this->thesis_id === 'unsaved') {
            error_log("[JournalConverter] Skipping database update - thesis not yet saved");
            return;
        }
        
        if (!$this->conn) {
            error_log("[JournalConverter] No database connection - skipping database update");
            return;
        }
        
        $page_count = null;
        
        if ($journal_content) {
            // Estimate page count (250 words per page)
            $word_count = str_word_count(strip_tags($journal_content));
            $page_count = ceil($word_count / 250);
            
            // Store for return value
            $this->journal_page_count = $page_count;
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
        
        $stmt->bind_param("sisisi", $pdf_path, $is_converted, $status, $page_count, $imrad_json, $this->thesis_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $stmt->close();
        
        error_log("[JournalConverter] Database updated successfully");
    }
}

// ============================================
// REQUEST HANDLER (Endpoint for POST requests)
// ============================================

// Set up error handler to catch all errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("[journal_converter.php] PHP ERROR ($errno): $errstr in $errfile:$errline");
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
});

// Set up exception handler
set_exception_handler(function($e) {
    error_log("[journal_converter.php] UNCAUGHT EXCEPTION: " . $e->getMessage());
    error_log("[journal_converter.php] Trace: " . $e->getTraceAsString());
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
});

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("[journal_converter.php] Received POST request");
    
    header('Content-Type: application/json');
    
    try {
        // Get POST data
        $input = file_get_contents('php://input');
        error_log("[journal_converter.php] Raw input: " . substr($input, 0, 200));
        
        $post_data = json_decode($input, true);
        
        if (!$post_data) {
            $post_data = $_POST;
        }
        
        error_log("[journal_converter.php] Parsed data: " . json_encode(array_keys($post_data)));
        
        if (!isset($post_data['file_path']) && !isset($post_data['document_text'])) {
            throw new Exception("Missing file_path or document_text parameter");
        }
        
        // Get file content
        $document_text = null;
        if (isset($post_data['document_text'])) {
            $document_text = $post_data['document_text'];
            error_log("[journal_converter.php] Using provided document_text");
        } else {
            $file_path = $post_data['file_path'];
            
            // Resolve relative path
            if (!file_exists($file_path)) {
                $file_path = __DIR__ . '/../' . $file_path;
            }
            
            if (!file_exists($file_path)) {
                throw new Exception("File not found: " . $file_path);
            }
            
            error_log("[journal_converter.php] Reading file: $file_path");
            
            // Read file based on type
            $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
            
            if ($ext === 'pdf') {
                // Parse PDF
                require_once 'document_parser.php';
                $parse_result = DocumentParser::extractText($file_path);
                
                if (!$parse_result['success']) {
                    throw new Exception("PDF parsing failed: " . ($parse_result['error'] ?? 'Unknown error'));
                }
                
                $document_text = $parse_result['text'];
            } else {
                // Read plain text
                $document_text = file_get_contents($file_path);
            }
            
            if (!$document_text) {
                throw new Exception("Failed to extract text from file");
            }
        }
        
        // Build metadata from POST data (may not have all fields)
        $metadata = [
            'title' => $post_data['title'] ?? 'Research Paper',
            'author' => $post_data['author'] ?? '',
            'abstract' => $post_data['abstract'] ?? '',
            'year' => $post_data['year'] ?? date('Y')
        ];
        
        error_log("[journal_converter.php] Metadata: " . json_encode($metadata));
        
        // For conversion without saving to DB, use NULL thesis_id as placeholder
        $thesis_id = isset($post_data['thesis_id']) ? (int)$post_data['thesis_id'] : null;
        
        // Create database connection if we have a valid thesis_id (for DB updates after conversion)
        $conn = null;
        if ($thesis_id && is_numeric($thesis_id) && $thesis_id > 0) {
            // Load database configuration
            $db_config = [
                'host' => 'localhost',
                'user' => 'u965322812_CITAS_Smart',
                'pass' => 'ErLv@g1e*',
                'name' => 'u965322812_thesis_db'
            ];
            
            $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
            
            if ($conn->connect_error) {
                throw new Exception("Database connection failed: " . $conn->connect_error);
            }
            
            error_log("[journal_converter.php] Database connection established for thesis_id: $thesis_id");
            
            // If metadata not provided, load from database
            if (empty($post_data['title'])) {
                $metadata_sql = "SELECT id, title, author, abstract, year FROM thesis WHERE id = ?";
                $stmt = $conn->prepare($metadata_sql);
                
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("i", $thesis_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $thesis_row = $result->fetch_assoc();
                $stmt->close();
                
                if ($thesis_row) {
                    $metadata = [
                        'title' => $thesis_row['title'],
                        'author' => $thesis_row['author'],
                        'abstract' => $thesis_row['abstract'],
                        'year' => $thesis_row['year']
                    ];
                    
                    error_log("[journal_converter.php] Metadata loaded from DB for thesis: " . $thesis_row['title']);
                }
            }
        }
        
        // Create converter with DB connection if available
        error_log("[journal_converter.php] Creating JournalConverter instance");
        
        try {
            error_log("[journal_converter.php] Instantiating converter...");
            
            $converter = new JournalConverter($thesis_id ?: 'unsaved', $document_text, $metadata, $conn);
            
            error_log("[journal_converter.php] Calling convert()...");
            $result = $converter->convert();
            
            error_log("[journal_converter.php] Conversion completed, returning result");
            
            // Return JSON response
            echo json_encode($result);
        } catch (Throwable $convertError) {
            error_log("[journal_converter.php] CONVERSION ERROR: " . $convertError->getMessage());
            error_log("[journal_converter.php] Error code: " . $convertError->getCode());
            error_log("[journal_converter.php] Trace: " . $convertError->getTraceAsString());
            
            throw new Exception("Conversion failed: " . $convertError->getMessage());
        }
    } catch (Exception $e) {
        error_log("[journal_converter.php] ERROR: " . $e->getMessage());
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    
    exit;
}

