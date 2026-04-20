<?php
/**
 * Check HuggingFace API Status and Usage
 * Monitor conversion performance and API quotas
 */

// Load environment
if (file_exists(__DIR__ . '/../.env')) {
    $env_file = __DIR__ . '/../.env';
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

header('Content-Type: application/json');

try {
    // Load HuggingFace config and service
    require_once __DIR__ . '/../ai_includes/huggingface_config.php';
    require_once __DIR__ . '/../ai_includes/huggingface_service.php';
    
    $hf_key = getenv('HUGGING_FACE_API_KEY');
    
    if (empty($hf_key)) {
        throw new Exception("HuggingFace API key not configured");
    }
    
    // Test 1: Check API connectivity
    $ch = curl_init('https://api-inference.huggingface.co/models/facebook/bart-large-cnn');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['inputs' => 'test']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $hf_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Parse response for error info
    $response_data = @json_decode($response, true);
    
    // Test 2: Check recent conversion logs
    $log_files = [
        'HF API' => __DIR__ . '/../logs/huggingface_api.log',
        'Journal Converter' => __DIR__ . '/../logs/journal_converter.log',
    ];
    
    $log_summary = [];
    foreach ($log_files as $name => $path) {
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES);
            $lines = array_reverse($lines);
            $recent = array_slice($lines, 0, 10);
            $log_summary[$name] = $recent;
        }
    }
    
    // Determine status
    $status = 'unknown';
    $message = '';
    
    if ($http_code === 401) {
        $status = 'error';
        $message = 'Invalid API token - please check your HF token';
    } elseif ($http_code === 429) {
        $status = 'limited';
        $message = 'Rate limited - quota exceeded (free tier has limits)';
    } elseif ($http_code === 404) {
        $status = 'warning';
        $message = 'Model not found or loading - may work on next request';
    } elseif ($http_code === 503) {
        $status = 'error';
        $message = 'HF service unavailable - temporary issue';
    } elseif ($http_code === 200) {
        $status = 'success';
        $message = 'API is working and responding';
    } elseif (in_array($http_code, [201, 202])) {
        $status = 'success';
        $message = 'API is working (processing)';
    } else {
        $status = 'unknown';
        $message = "HTTP $http_code response";
    }
    
    // Build response
    $result = [
        'status' => $status,
        'message' => $message,
        'http_code' => $http_code,
        'api_key' => substr($hf_key, 0, 20) . '...' . substr($hf_key, -5),
        'model' => HUGGING_FACE_MODEL,
        'error_details' => null,
        'recent_logs' => $log_summary,
        'timestamp' => date('Y-m-d H:i:s'),
        'recommendations' => []
    ];
    
    // Add error details if response contains errors
    if (is_array($response_data)) {
        if (isset($response_data['error'])) {
            $result['error_details'] = $response_data['error'];
            $result['recommendations'][] = 'Check HF website for current status: https://huggingface.co/';
        }
    }
    
    // Add recommendations based on status
    if ($status === 'limited') {
        $result['recommendations'][] = 'Free tier has API call limits - you may have exceeded them';
        $result['recommendations'][] = 'Check your quota at: https://huggingface.co/settings/tokens';
        $result['recommendations'][] = 'Upgrade to Pro for unlimited access (optional)';
    } elseif ($status === 'error') {
        $result['recommendations'][] = 'Verify your token at: https://huggingface.co/settings/tokens';
        $result['recommendations'][] = 'Create a new token if the old one expired';
    } elseif ($status === 'warning') {
        $result['recommendations'][] = 'This is normal - model may still work on actual requests';
        $result['recommendations'][] = 'Try converting a thesis to test real functionality';
    }
    
    if ($status === 'success') {
        $result['recommendations'][] = '✓ Everything looks good!';
        $result['recommendations'][] = 'Next conversion will use HuggingFace (5-15 seconds)';
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
