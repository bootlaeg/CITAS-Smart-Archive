<?php
/**
 * Ollama Service using cURL - Alternative to stream context
 * This avoids potential stream context issues on Windows
 */

class OllamaServiceCurl {
    private $baseUrl;
    private $model;
    
    public function __construct($model = 'mistral', $baseUrl = null) {
        if ($baseUrl === null) {
            $baseUrl = getenv('OLLAMA_BASE_URL') ?: 'https://ollama.citas-smart-archive.com';
        }
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->model = $model;
    }
    
    /**
     * Send a prompt to Ollama and get a response using cURL
     * @param string $prompt The prompt to send to the model
     * @param array $options Additional options for the model
     * @return string The model's response
     */
    public function prompt($prompt, $options = []) {
        try {
            // Clean the prompt of invalid UTF-8 characters
            $prompt = $this->cleanUtf8($prompt);
            
            $data = [
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false,
                'temperature' => isset($options['temperature']) ? $options['temperature'] : 0.7,
            ];
            
            error_log("[OllamaServiceCurl] Data array keys: " . implode(', ', array_keys($data)));
            error_log("[OllamaServiceCurl] Data model: " . $data['model']);
            error_log("[OllamaServiceCurl] Data prompt length: " . strlen($data['prompt']));
            
            $jsonData = json_encode($data);
            
            error_log("[OllamaServiceCurl] After json_encode, jsonData length: " . strlen($jsonData));
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("[OllamaServiceCurl] JSON encode error: " . json_last_error_msg());
                // Try again with more aggressive cleaning
                $data['prompt'] = mb_convert_encoding($data['prompt'], 'UTF-8', 'UTF-8');
                $jsonData = json_encode($data);
                error_log("[OllamaServiceCurl] After re-cleaning, jsonData length: " . strlen($jsonData));
            }
            
            $url = $this->baseUrl . '/api/generate';
            
            error_log("[OllamaServiceCurl] Sending request to: $url");
            error_log("[OllamaServiceCurl] Model: " . $this->model);
            error_log("[OllamaServiceCurl] Prompt length: " . strlen($prompt));
            
            // Use PHP's curl functions if available
            if (function_exists('curl_init')) {
                error_log("[OllamaServiceCurl] Using curl_init");
                return $this->promptWithCurlFunction($url, $jsonData);
            } elseif (function_exists('shell_exec')) {
                error_log("[OllamaServiceCurl] cURL not available, using shell_exec");
                return $this->promptWithShellExec($url, $jsonData);
            } else {
                throw new Exception("Neither curl nor shell_exec available for HTTP requests");
            }
            
        } catch (Exception $e) {
            error_log("[OllamaServiceCurl] Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Clean text of invalid UTF-8 characters
     * @param string $text Input text
     * @return string Cleaned text
     */
    private function cleanUtf8($text) {
        // Remove invalid UTF-8 characters
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        // Replace any remaining invalid sequences with space
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', ' ', $text);
        return $text;
    }
    
    /**
     * Send request using PHP curl functions
     */
    private function promptWithCurlFunction($url, $jsonData) {
        error_log("[OllamaServiceCurl] JSON data length: " . strlen($jsonData));
        error_log("[OllamaServiceCurl] JSON data preview: " . substr($jsonData, 0, 100) . "...");
        
        $ch = curl_init($url);
        
        if (!$ch) {
            throw new Exception("Failed to initialize cURL");
        }
        
        // Ensure $jsonData is a string
        if (is_array($jsonData)) {
            $jsonData = json_encode($jsonData);
        }
        
        // Set curl options more explicitly
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        error_log("[OllamaServiceCurl] Executing curl request...");
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        
        error_log("[OllamaServiceCurl] cURL errno: $curlErrno");
        error_log("[OllamaServiceCurl] cURL response: $response");
        
        curl_close($ch);
        
        error_log("[OllamaServiceCurl] HTTP Code: $httpCode");
        if (!empty($curlError)) {
            error_log("[OllamaServiceCurl] cURL Error: $curlError");
            throw new Exception("cURL Error: $curlError");
        }
        
        if ($response === false) {
            throw new Exception("Failed to get response from Ollama");
        }
        
        error_log("[OllamaServiceCurl] Response length: " . strlen($response));
        error_log("[OllamaServiceCurl] Response preview: " . substr($response, 0, 200));
        
        $decoded = json_decode($response, true);
        if (isset($decoded['error'])) {
            throw new Exception("Ollama API Error: " . $decoded['error']);
        }
        
        return $decoded['response'] ?? $response;
    }
    
    /**
     * Send request using shell_exec with curl command
     */
    private function promptWithShellExec($url, $jsonData) {
        // Escape JSON for command line
        $escapedJson = escapeshellarg($jsonData);
        $escapedUrl = escapeshellarg($url);
        
        // On Windows, use double quotes and escape differently
        $cmd = sprintf(
            'curl -s -X POST -H "Content-Type: application/json" --data %s %s',
            $escapedJson,
            $escapedUrl
        );
        
        error_log("[OllamaServiceCurl] Shell command: $cmd");
        
        $response = shell_exec($cmd . ' 2>&1');
        
        if (empty($response)) {
            throw new Exception("No response from shell_exec curl command");
        }
        
        error_log("[OllamaServiceCurl] Shell response length: " . strlen($response));
        error_log("[OllamaServiceCurl] Shell response preview: " . substr($response, 0, 200));
        
        $decoded = @json_decode($response, true);
        if (is_array($decoded) && isset($decoded['response'])) {
            return $decoded['response'];
        }
        
        return $response;
    }
    
    /**
     * Check if Ollama is available
     */
    public function isAvailable() {
        try {
            if (function_exists('curl_init')) {
                $ch = curl_init($this->baseUrl . '/api/tags');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                return $httpCode === 200;
            } else {
                $cmd = sprintf('curl -s -I %s | grep HTTP', escapeshellarg($this->baseUrl . '/api/tags'));
                $response = shell_exec($cmd);
                return strpos($response, '200') !== false;
            }
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Set the model
     */
    public function setModel($model) {
        $this->model = $model;
    }
    
    /**
     * Get the current model
     */
    public function getModel() {
        return $this->model;
    }
}
?>
