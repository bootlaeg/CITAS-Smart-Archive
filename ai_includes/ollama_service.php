<?php
/**
 * Ollama Service - Handles communication with local Ollama API
 * Requires: Ollama running locally on http://localhost:11434
 * Model: phi (or any other model)
 */

class OllamaService {
    private $baseUrl;
    private $model;
    private $timeout;
    
    public function __construct($model = 'phi', $baseUrl = 'http://localhost:11434') {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->model = $model;
        $this->timeout = 300; // 5 minutes timeout for Ollama responses (increased for slower models like neural-chat)
    }
    
    /**
     * Send a prompt to Ollama and get a response
     * @param string $prompt The prompt to send to the model
     * @param array $options Additional options for the model
     * @return string The model's response
     */
    public function prompt($prompt, $options = []) {
        try {
            $data = [
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false,
                'temperature' => isset($options['temperature']) ? $options['temperature'] : 0.7,
            ];
            
            $response = $this->makeRequest('/api/generate', $data);
            return $response['response'] ?? '';
        } catch (Exception $e) {
            error_log("Ollama Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Send a structured prompt with JSON parsing
     * @param string $prompt The prompt to send
     * @param array $options Additional options
     * @return array Parsed JSON response
     */
    public function promptJson($prompt, $options = []) {
        try {
            $response = $this->prompt($prompt, $options);
            
            // Try to extract JSON from the response - matches both objects and arrays
            // First try to find an array pattern
            $arrayPattern = '/\[[^\[\]]*(?:\{[^{}]*\}[^\[\]]*)*\]/s';
            if (preg_match($arrayPattern, $response, $matches)) {
                $decoded = json_decode($matches[0], true);
                if ($decoded !== null && is_array($decoded)) {
                    return $decoded;
                }
            }
            
            // Then try to find an object pattern
            $objectPattern = '/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s';
            if (preg_match($objectPattern, $response, $matches)) {
                $decoded = json_decode($matches[0], true);
                if ($decoded !== null) {
                    return $decoded;
                }
            }
            
            // If no JSON found, try parsing the entire response
            $decoded = @json_decode($response, true);
            if ($decoded !== null && is_array($decoded)) {
                return $decoded;
            }
            
            // Fallback: return response as string within array
            return ['response' => $response];
        } catch (Exception $e) {
            error_log("Ollama JSON Parsing Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check if Ollama is available and model is loaded (with quick timeout)
     * @return bool
     */
    public function isAvailable() {
        try {
            // Quick health check with short timeout
            $response = $this->makeRequestWithTimeout('/api/tags', [], 'GET', 5);
            return !empty($response['models']);
        } catch (Exception $e) {
            error_log("Ollama Availability Check Failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if specific model is available
     * @param string $modelName
     * @return bool
     */
    public function isModelAvailable($modelName = null) {
        $model = $modelName ?? $this->model;
        try {
            // Quick health check with short timeout
            $response = $this->makeRequestWithTimeout('/api/tags', [], 'GET', 5);
            if (empty($response['models'])) {
                return false;
            }
            
            foreach ($response['models'] as $m) {
                if ($m['name'] === $model || strpos($m['name'], $model) === 0) {
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Pull/download a model from Ollama registry
     * @param string $modelName
     * @return bool
     */
    public function pullModel($modelName) {
        try {
            $data = ['name' => $modelName];
            $this->makeRequest('/api/pull', $data);
            return true;
        } catch (Exception $e) {
            error_log("Model Pull Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Make HTTP request to Ollama API
     * @param string $endpoint
     * @param array $data
     * @param string $method
     * @return array
     * @throws Exception
     */
    private function makeRequest($endpoint, $data = [], $method = 'POST') {
        $url = $this->baseUrl . $endpoint;
        
        $options = [
            'http' => [
                'method' => $method,
                'timeout' => $this->timeout,
                'header' => "Content-Type: application/json\r\n",
            ]
        ];
        
        if ($method === 'POST' && !empty($data)) {
            $options['http']['content'] = json_encode($data);
        }
        
        $context = stream_context_create($options);
        
        try {
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                throw new Exception("Failed to connect to Ollama at {$this->baseUrl}");
            }
            
            // Parse JSON Lines format (multiple JSON objects separated by newlines)
            $lines = array_filter(explode("\n", trim($response)));
            $result = [];
            
            foreach ($lines as $line) {
                $decoded = json_decode($line, true);
                if ($decoded !== null) {
                    $result = array_merge($result, $decoded);
                }
            }
            
            return $result;
        } catch (Exception $e) {
            throw new Exception("Ollama API Error: " . $e->getMessage());
        }
    }

    /**
     * Make HTTP request to Ollama API with custom timeout
     * @param string $endpoint
     * @param array $data
     * @param string $method
     * @param int $timeout Custom timeout in seconds
     * @return array
     * @throws Exception
     */
    private function makeRequestWithTimeout($endpoint, $data = [], $method = 'GET', $timeout = 5) {
        $url = $this->baseUrl . $endpoint;
        
        $options = [
            'http' => [
                'method' => $method,
                'timeout' => $timeout,
                'header' => "Content-Type: application/json\r\n",
            ]
        ];
        
        if ($method === 'POST' && !empty($data)) {
            $options['http']['content'] = json_encode($data);
        }
        
        $context = stream_context_create($options);
        
        try {
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                throw new Exception("Failed to connect to Ollama at {$this->baseUrl} (timeout after {$timeout}s)");
            }
            
            // Parse JSON Lines format (multiple JSON objects separated by newlines)
            $lines = array_filter(explode("\n", trim($response)));
            $result = [];
            
            foreach ($lines as $line) {
                $decoded = json_decode($line, true);
                if ($decoded !== null) {
                    $result = array_merge($result, $decoded);
                }
            }
            
            return $result;
        } catch (Exception $e) {
            throw new Exception("Ollama API Error: " . $e->getMessage());
        }
    }
    
    /**
     * Set the model to use
     * @param string $model
     */
    public function setModel($model) {
        $this->model = $model;
    }
    
    /**
     * Get current model
     * @return string
     */
    public function getModel() {
        return $this->model;
    }
}
?>
