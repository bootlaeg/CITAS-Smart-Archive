<?php
// Disable execution timeout for long Ollama processing
set_time_limit(0);
ini_set('default_socket_timeout', 300);
ini_set('max_execution_time', 300);

// Start output buffering to prevent accidental output
ob_start();

// Function to close HTTP connection and continue processing in background
function closeConnectionAndContinue($response) {
    // Prepare JSON response
    $json_response = json_encode($response);
    
    // Clear any buffered output
    ob_end_clean();
    
    // Send proper HTTP headers
    http_response_code(200);
    header('Content-Type: application/json');
    header('Content-Length: ' . strlen($json_response));
    header('Connection: close');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    
    // Send the response
    echo $json_response;
    
    // Flush output immediately
    flush();
    ob_flush();
    
    // Close session if active
    if (function_exists('session_write_close')) {
        session_write_close();
    }
    
    // Try to close connection using available methods
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    
    // Give browser time to process response
    sleep(1);
    
    // Log that we're continuing
    error_log("[journal_converter] Connection closed, continuing with background processing");
    
    // Start new output buffer to suppress any further output
    ob_start();
}

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
            error_log("[JournalConverter] STEP 1: Analyzing document structure...");
            $this->analyzeStructure();
            error_log("[JournalConverter] STEP 1: ✅ Structure analysis complete");
            
            // Step 2: Extract and condense sections
            error_log("[JournalConverter] STEP 2: Condensing to journal format...");
            $condensed = $this->condenseToJournal();
            error_log("[JournalConverter] STEP 2: ✅ Condensing complete");
            
            // Step 3: Reconstruct in IMRaD format
            error_log("[JournalConverter] STEP 3: Reconstructing as IMRaD...");
            $journal_content = $this->reconstructAsIMRaD($condensed);
            error_log("[JournalConverter] STEP 3: ✅ Reconstruction complete");
            
            // Step 4: Generate PDF
            error_log("[JournalConverter] STEP 4: Generating journal PDF...");
            $pdf_path = $this->generateJournalPDF($journal_content);
            error_log("[JournalConverter] STEP 4: ✅ PDF generation complete");
            
            // Step 5: Update database
            error_log("[JournalConverter] STEP 5: Updating database with fresh connection...");
            $this->updateDatabase($pdf_path, $journal_content);
            error_log("[JournalConverter] STEP 5: ✅ Database update complete");
            
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
            error_log("[JournalConverter] ❌ CONVERSION ERROR at step: " . $e->getMessage());
            error_log("[JournalConverter] Error code: " . $e->getCode());
            error_log("[JournalConverter] File: " . $e->getFile());
            error_log("[JournalConverter] Line: " . $e->getLine());
            error_log("[JournalConverter] Trace: " . $e->getTraceAsString());
            
            try {
                $this->updateDatabase(null, null, 'failed');
            } catch (Exception $updateError) {
                error_log("[JournalConverter] Failed to log conversion failure to database: " . $updateError->getMessage());
            }
            
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
        // First try: Find the section in analyzed IMRAD sections
        $section_content = '';
        foreach ($this->imrad_sections as $section) {
            if ($section['type'] === $section_type) {
                $section_content = $section['content'];
                break;
            }
        }
        
        // Second try: If not found in analysis, search document directly for section keywords
        if (empty($section_content)) {
            error_log("[JournalConverter] Section '$section_type' not found in analysis, searching document...");
            $section_content = $this->extractSectionFromDocument($section_type);
        }
        
        if (empty($section_content)) {
            // Last resort: return placeholder if truly not found
            error_log("[JournalConverter] Section '$section_type' not found anywhere in document");
            return $prompt_hint . "\n\n[Section not found in original document]";
        }
        
        error_log("[JournalConverter] Extracted section '$section_type' with " . strlen($section_content) . " characters");
        
        // Use Ollama to summarize
        $summary = $this->summarizeWithOllama($section_content, $target_words, $prompt_hint);
        
        if ($summary) {
            return $summary;
        }
        
        // Fallback: Simple extraction and truncation
        return $this->simpleSummarize($section_content, $target_words);
    }
    
    /**
     * Extract section content directly from document text
     * Searches for common section headers and extracts content
     */
    private function extractSectionFromDocument($section_type) {
        $patterns = [];
        
        switch($section_type) {
            case 'introduction':
                $patterns = [
                    '/1\.\s*introductions?\b(.+?)(?=\n\d+\.|$)/ims',
                    '/introductions?\b(.+?)(?=\n(?:Methods|Methods|2\.|$))/ims',
                    '/^(?:introduction|introduction section)(.+?)(?=methods|2\.|$)/ims'
                ];
                break;
            case 'methods':
                $patterns = [
                    '/2\.\s*methods?\b(.+?)(?=\n3\.|\nresults|$)/ims',
                    '/methods?\b(.+?)(?=\n(?:Results|3\.|$))/ims',
                    '/^(?:methodology|methods)(.+?)(?=results|3\.|$)/ims'
                ];
                break;
            case 'results':
                $patterns = [
                    '/3\.\s*results?\b(.+?)(?=\n4\.|\ndiscussion|$)/ims',
                    '/results?\b(.+?)(?=\n(?:Discussion|4\.|$))/ims',
                    '/^(?:findings|results)(.+?)(?=discussion|4\.|$)/ims'
                ];
                break;
            case 'discussion':
                $patterns = [
                    '/4\.\s*discussions?\b(.+?)(?=\n5\.|\nconclusions?|$)/ims',
                    '/discussions?\b(.+?)(?=\n(?:Conclusion|5\.|$))/ims',
                    '/^(?:discussion)(.+?)(?=conclusion|5\.|$)/ims'
                ];
                break;
            case 'conclusions':
            case 'conclusion':
                $patterns = [
                    '/5\.\s*conclusions?\b(.+?)(?=\n|$)/ims',
                    '/conclusions?\b(.+?)(?=\n(?:References|$))/ims',
                    '/^(?:conclusions|conclusion)(.+?)(?=references|$)/ims'
                ];
                break;
        }
        
        // Try each pattern
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $this->document_text, $matches)) {
                $content = trim($matches[1]);
                if (strlen($content) > 100) { // Only accept if substantial content found
                    return $content;
                }
            }
        }
        
        return '';
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 300); // Allow up to 5 minutes for Ollama processing + tunnel latency
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
        // Generate a proper HTML file for the journal (better than fake PDF)
        // This creates a professional-looking HTML document that displays correctly
        
        $filename = 'thesis_' . $this->thesis_id . '_journal_' . uniqid() . '.html';
        $file_path = 'uploads/thesis_files/' . $filename;
        
        // Create directory if needed
        if (!is_dir('uploads/thesis_files')) {
            mkdir('uploads/thesis_files', 0755, true);
        }
        
        // Convert markdown content to HTML
        $html_content = $this->markdownToHTML($journal_content);
        
        // Save as HTML file
        file_put_contents(
            __DIR__ . '/../' . $file_path,
            $html_content
        );
        
        error_log("[JournalConverter] Journal HTML saved to: " . $file_path);
        
        return $file_path;
    }
    
    /**
     * Convert markdown content to HTML
     */
    private function markdownToHTML($markdown) {
        // Basic markdown to HTML conversion
        $html = htmlspecialchars($markdown);
        
        // Headers
        $html = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $html);
        
        // Bold
        $html = preg_replace('/\*\*(.*?)\*\*/m', '<strong>$1</strong>', $html);
        
        // Line breaks and paragraphs
        $html = nl2br($html);
        
        // Wrap in HTML document
        $full_html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal Format Document</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.8;
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            background-color: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #E67E22;
            border-bottom: 3px solid #E67E22;
            padding-bottom: 10px;
            text-align: center;
        }
        h2 {
            color: #D35400;
            margin-top: 30px;
            margin-bottom: 15px;
            border-left: 4px solid #E67E22;
            padding-left: 15px;
        }
        p {
            margin: 10px 0;
            text-align: justify;
        }
        strong {
            color: #D35400;
        }
        .metadata {
            background-color: #FFF8F0;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
            font-size: 0.95em;
            border-left: 4px solid #E67E22;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        $html
        <div class="footer">
            <p>Generated by CITAS Smart Archive - Journal Format Conversion</p>
            <p>Converted on: <em>April 17, 2026</em></p>
        </div>
    </div>
</body>
</html>
HTML;
        
        return $full_html;
    }
    
    /**
     * Update database with conversion results
     */
    private function updateDatabase($pdf_path, $journal_content, $status = 'completed') {
        // ALWAYS calculate page count from journal content
        // This is needed even for synchronous conversion without database update
        $page_count = null;
        if ($journal_content) {
            // Estimate page count (250 words per page)
            $word_count = str_word_count(strip_tags($journal_content));
            $page_count = ceil($word_count / 250);
            $this->journal_page_count = $page_count;
            error_log("[JournalConverter] Page count calculated: $page_count pages");
        }
        
        // Skip database update if thesis_id is not set or is 'unsaved' placeholder
        if (!$this->thesis_id || $this->thesis_id === 'unsaved') {
            error_log("[JournalConverter] Skipping database update - thesis not yet saved");
            error_log("[JournalConverter] Page count available for return: $page_count");
            return;
        }
        
        // CRITICAL FIX: Create a FRESH database connection instead of using the stale one from constructor
        // The original connection has been idle for 60-90 seconds while Ollama was processing
        // Hostinger closes idle connections, so we need a new one
        error_log("[JournalConverter] Creating fresh database connection for update...");
        
        $db_config = [
            'host' => 'localhost',
            'user' => 'u965322812_CITAS_Smart',
            'pass' => 'ErLv@g1e*',
            'name' => 'u965322812_thesis_db'
        ];
        
        // Retry connection up to 3 times with small delays
        $conn = null;
        $max_retries = 3;
        for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
            error_log("[JournalConverter] Connection attempt $attempt/$max_retries...");
            
            $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
            
            if (!$conn->connect_error) {
                error_log("[JournalConverter] ✅ Fresh database connection established on attempt $attempt");
                break;
            }
            
            error_log("[JournalConverter] Connection attempt $attempt failed: " . $conn->connect_error);
            
            if ($attempt < $max_retries) {
                error_log("[JournalConverter] Waiting 2 seconds before retry...");
                sleep(2);
            }
        }
        
        if ($conn->connect_error) {
            throw new Exception("Database connection failed after $max_retries attempts: " . $conn->connect_error);
        }
        
        $update_sql = "UPDATE thesis SET 
            journal_file_path = ?,
            is_journal_converted = ?,
            journal_conversion_status = ?,
            journal_page_count = ?,
            journal_converted_at = NOW()
            WHERE id = ?";
        
        $is_converted = ($status === 'completed') ? 1 : 0;
        
        // Prepare the statement with the FRESH connection
        error_log("[JournalConverter] Preparing UPDATE statement...");
        $stmt = $conn->prepare($update_sql);
        
        if (!$stmt) {
            $conn->close();
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        error_log("[JournalConverter] Binding parameters for thesis_id=$this->thesis_id, status=$status...");
        
        $stmt->bind_param("sisii", $pdf_path, $is_converted, $status, $page_count, $this->thesis_id);
        
        error_log("[JournalConverter] Executing UPDATE query...");
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            throw new Exception("Execute failed: $error");
        }
        
        error_log("[JournalConverter] UPDATE successful! Affected rows: " . $stmt->affected_rows);
        
        $stmt->close();
        $conn->close();
        
        error_log("[JournalConverter] Database updated successfully with fresh connection");
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
    
    try {
        // Get POST data IMMEDIATELY
        $input = file_get_contents('php://input');
        error_log("[journal_converter.php] Raw input: " . substr($input, 0, 200));
        
        $post_data = json_decode($input, true);
        
        if (!$post_data) {
            $post_data = $_POST;
        }
        
        error_log("[journal_converter.php] Parsed data: " . json_encode(array_keys($post_data)));
        
        // Extract thesis_id FIRST, before validation
        $thesis_id = isset($post_data['thesis_id']) ? (int)$post_data['thesis_id'] : null;
        error_log("[journal_converter.php] Thesis ID: $thesis_id");
        
        // Send IMMEDIATE async response (before any file operations)
        $immediate_response = [
            'success' => true,
            'status' => 'processing',
            'thesis_id' => $thesis_id,
            'message' => 'Conversion started in background. Processing will continue...'
        ];
        
        error_log("[journal_converter.php] Sending immediate response to close connection");
        closeConnectionAndContinue($immediate_response);
        
        // ============================================================
        // EVERYTHING BELOW THIS RUNS IN BACKGROUND (connection closed)
        // ============================================================
        
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
            
            // Send immediate response to close HTTP connection
            $immediate_response = [
                'success' => true,
                'status' => 'processing',
                'thesis_id' => $thesis_id,
                'message' => 'Conversion started in background. Please wait...'
            ];
            
            error_log("[journal_converter.php] Sending immediate response and closing connection");
            closeConnectionAndContinue($immediate_response);
            
            // Continue conversion in background (connection already closed)
            // Any errors here will be logged but not sent to client
            error_log("[journal_converter.php] Starting background conversion process");
            try {
                $result = $converter->convert();
                error_log("[journal_converter.php] Conversion completed: " . json_encode($result));
            } catch (Throwable $bgError) {
                error_log("[journal_converter.php] Background conversion error: " . $bgError->getMessage());
                error_log("[journal_converter.php] Trace: " . $bgError->getTraceAsString());
            }
            
            // Exit without sending further output
            exit;
            
        } catch (Throwable $convertError) {
            error_log("[journal_converter.php] ERROR during setup: " . $convertError->getMessage());
            
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $convertError->getMessage()
            ]);
            exit;
        }
    } catch (Exception $e) {
        // Clear any buffered output
        ob_end_clean();
        
        error_log("[journal_converter.php] ERROR: " . $e->getMessage());
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    
    exit;
}

