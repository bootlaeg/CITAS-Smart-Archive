<?php
/**
 * Hugging Face API Service
 * 
 * Handles communication with Hugging Face Inference API
 * for document summarization and text generation
 */

require_once __DIR__ . '/huggingface_config.php';

class HuggingFaceService {
    
    private $api_key;
    private $api_url;
    private $model;
    private $timeout;
    private $retry_count;
    private $retry_delay;
    private $log_enabled;
    private $log_file;
    private $use_fallback;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = HUGGING_FACE_API_KEY;
        $this->api_url = HUGGING_FACE_API_URL;
        $this->model = HUGGING_FACE_MODEL;
        $this->timeout = HUGGING_FACE_TIMEOUT;
        $this->retry_count = HUGGING_FACE_RETRY_COUNT;
        $this->retry_delay = HUGGING_FACE_RETRY_DELAY;
        $this->log_enabled = HUGGING_FACE_LOG_ENABLED;
        $this->log_file = HUGGING_FACE_LOG_FILE;
        $this->use_fallback = HUGGING_FACE_USE_FALLBACK;
    }
    
    /**
     * Summarize text using Hugging Face API
     * 
     * @param string $text Text to summarize
     * @param int $max_words Maximum words in summary
     * @return array Success status and summary text
     */
    public function summarize($text, $max_words = 500) {
        
        if (empty($text)) {
            return [
                'success' => false,
                'error' => 'Empty text provided',
                'summary' => ''
            ];
        }
        
        // Clean and prepare text
        $text = $this->cleanText($text);
        
        // Log request
        $this->log("Summarization request: " . strlen($text) . " characters, target " . $max_words . " words");
        
        // Make API call with retry logic
        $attempt = 0;
        while ($attempt <= $this->retry_count) {
            $attempt++;
            
            $result = $this->callAPI($text, $max_words);
            
            if ($result['success']) {
                $this->log("Summarization successful on attempt {$attempt}");
                return $result;
            }
            
            if ($attempt <= $this->retry_count) {
                $this->log("Attempt {$attempt} failed, retrying in " . ($this->retry_delay / 1000) . " seconds...");
                usleep($this->retry_delay * 1000); // Convert ms to microseconds
            }
        }
        
        // All attempts failed
        $this->log("All {$attempt} attempts failed", 'error');
        
        // Return fallback if enabled
        if ($this->use_fallback) {
            $this->log("Using fallback extractive summarization");
            return $this->extractiveSummarize($text, $max_words);
        }
        
        return [
            'success' => false,
            'error' => 'API call failed after ' . $attempt . ' attempts',
            'summary' => ''
        ];
    }
    
    /**
     * Call Hugging Face API
     * 
     * @param string $text Input text
     * @param int $max_words Maximum output length
     * @return array API response
     */
    private function callAPI($text, $max_words) {
        
        try {
            // Initialize cURL
            $curl = curl_init();
            
            // Prepare headers
            $headers = [
                'Authorization: Bearer ' . $this->api_key,
                'Content-Type: application/json'
            ];
            
            // Prepare payload
            $payload = [
                'inputs' => $text,
                'parameters' => [
                    'max_length' => $max_words,
                    'min_length' => max(100, $max_words / 2),
                    'do_sample' => HUGGING_FACE_DO_SAMPLE,
                    'temperature' => HUGGING_FACE_TEMPERATURE
                ]
            ];
            
            // Configure cURL
            curl_setopt_array($curl, [
                CURLOPT_URL => $this->api_url . '/' . $this->model,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);
            
            // Execute request
            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($curl);
            curl_close($curl);
            
            // Handle cURL errors
            if ($curl_error) {
                throw new Exception("cURL error: " . $curl_error);
            }
            
            // Check HTTP status
            if ($http_code !== 200) {
                $this->log("HTTP {$http_code}: " . substr($response, 0, 200), 'warning');
                throw new Exception("HTTP error {$http_code}");
            }
            
            // Parse response
            $result = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON response: " . json_last_error_msg());
            }
            
            // Extract summary
            if (is_array($result) && isset($result[0]['summary_text'])) {
                $summary = $result[0]['summary_text'];
                return [
                    'success' => true,
                    'summary' => $summary,
                    'error' => null,
                    'word_count' => str_word_count($summary)
                ];
            }
            
            throw new Exception("Unexpected response format: " . json_encode($result));
            
        } catch (Exception $e) {
            $this->log("API Exception: " . $e->getMessage(), 'error');
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'summary' => ''
            ];
        }
    }
    
    /**
     * Extractive summarization fallback
     * Uses keyword scoring to extract important sentences
     * 
     * @param string $text Input text
     * @param int $max_words Target word count
     * @return array Summary result
     */
    private function extractiveSummarize($text, $max_words) {
        
        try {
            // Split into sentences
            $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
            
            if (empty($sentences)) {
                return [
                    'success' => false,
                    'error' => 'Could not extract sentences',
                    'summary' => ''
                ];
            }
            
            // Score sentences based on word frequency
            $words = str_word_count(strtolower($text), 1);
            $word_freq = array_count_values($words);
            arsort($word_freq);
            
            // Keep top 30% of words
            $top_words = array_slice(array_keys($word_freq), 0, max(1, count($word_freq) / 3));
            $top_words_set = array_flip($top_words);
            
            $sentence_scores = [];
            foreach ($sentences as $idx => $sentence) {
                $score = 0;
                $sentence_words = str_word_count(strtolower(trim($sentence)), 1);
                
                foreach ($sentence_words as $word) {
                    if (isset($top_words_set[$word])) {
                        $score++;
                    }
                }
                
                $sentence_scores[$idx] = [
                    'sentence' => trim($sentence),
                    'score' => $score,
                    'words' => count($sentence_words)
                ];
            }
            
            // Select top sentences to reach target word count
            usort($sentence_scores, function ($a, $b) {
                return $b['score'] - $a['score'];
            });
            
            $selected = [];
            $total_words = 0;
            
            foreach ($sentence_scores as $item) {
                if ($total_words >= $max_words) break;
                $selected[] = $item['sentence'];
                $total_words += $item['words'];
            }
            
            // Sort by original order
            $summary = implode('. ', $selected) . '.';
            
            return [
                'success' => true,
                'summary' => $summary,
                'error' => null,
                'word_count' => str_word_count($summary),
                'method' => 'extractive_fallback'
            ];
            
        } catch (Exception $e) {
            $this->log("Extractive summarization error: " . $e->getMessage(), 'error');
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'summary' => ''
            ];
        }
    }
    
    /**
     * Clean text for API processing
     * 
     * @param string $text Raw text
     * @return string Cleaned text
     */
    private function cleanText($text) {
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove special characters (keep basic punctuation)
        $text = preg_replace('/[^\w\s.!?,\-\'"]/', '', $text);
        
        // Trim
        $text = trim($text);
        
        return $text;
    }
    
    /**
     * Log API activity
     * 
     * @param string $message Log message
     * @param string $level Log level (info, warning, error)
     */
    private function log($message, $level = 'info') {
        
        if (!$this->log_enabled) {
            return;
        }
        
        try {
            $timestamp = date('Y-m-d H:i:s');
            $log_message = "[{$timestamp}] [{$level}] {$message}\n";
            
            // Ensure log directory exists
            $log_dir = dirname($this->log_file);
            if (!is_dir($log_dir)) {
                mkdir($log_dir, 0755, true);
            }
            
            // Append to log file
            file_put_contents($this->log_file, $log_message, FILE_APPEND | LOCK_EX);
            
        } catch (Exception $e) {
            // Silently fail if logging is not possible
            error_log("HuggingFace logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Get API status
     * Tests connection to Hugging Face API
     * 
     * @return array Status result
     */
    public function getStatus() {
        
        try {
            $test_text = "This is a test sentence to verify the API is working correctly.";
            $result = $this->callAPI($test_text, 20);
            
            return [
                'success' => $result['success'],
                'status' => $result['success'] ? 'connected' : 'failed',
                'error' => $result['error'] ?? null,
                'message' => $result['success'] ? 'API is working' : 'API connection failed'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'status' => 'error',
                'error' => $e->getMessage(),
                'message' => 'API test failed'
            ];
        }
    }
}

?>
