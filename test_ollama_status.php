<?php
/**
 * Test if Ollama service is accessible
 */

header('Content-Type: application/json');

try {
    // Check if Ollama is accessible via localhost (if running locally)
    $urls = [
        'http://localhost:11434/api/tags',
        'http://127.0.0.1:11434/api/tags'
    ];
    
    $responses = [];
    foreach ($urls as $url) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ]);
        
        try {
            $response = @file_get_contents($url, false, $context);
            $responses[] = [
                'url' => $url,
                'status' => 'accessible',
                'preview' => substr($response, 0, 200) ?? 'no response'
            ];
        } catch (Exception $e) {
            $responses[] = [
                'url' => $url,
                'status' => 'not accessible',
                'error' => $e->getMessage()
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'ollama_check' => $responses
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
