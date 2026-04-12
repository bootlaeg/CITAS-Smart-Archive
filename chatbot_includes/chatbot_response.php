<?php
/**
 * Chatbot Response Handler
 * Processes user messages and returns AI-generated responses
 */

header('Content-Type: application/json; charset=utf-8');

// Force error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../debug_chatbot_error.log');
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/../db_includes/db_connect.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit(1);
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$thesis_id = isset($_POST['thesis_id']) ? intval($_POST['thesis_id']) : 0;
$user_message = isset($_POST['message']) ? trim($_POST['message']) : '';
$source = isset($_POST['source']) ? $_POST['source'] : 'ollama';  // 'ollama' or 'template'

if ($thesis_id <= 0 || empty($user_message)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

// Verify database connection
if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

// Check if user has approved thesis access (which gives chatbot access too)
$user_id = $_SESSION['user_id'];
error_log("Checking access - user_id=$user_id, thesis_id=$thesis_id");

$access_check = $conn->prepare("
    SELECT id, status FROM thesis_access 
    WHERE user_id = ? AND thesis_id = ?
");

if (!$access_check) {
    error_log("Prepare failed in chatbot_response: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

$access_check->bind_param("ii", $user_id, $thesis_id);

if (!$access_check->execute()) {
    error_log("Execute failed in chatbot_response: " . $access_check->error);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    $access_check->close();
    exit();
}

$access_result = $access_check->get_result();
error_log("Access check returned " . $access_result->num_rows . " rows");

if ($access_result->num_rows === 0) {
    error_log("No access record found - user_id=$user_id, thesis_id=$thesis_id");
    echo json_encode(['success' => false, 'message' => 'No access request found. Please request access in Browse Thesis.']);
    $access_check->close();
    exit();
}

$access_row = $access_result->fetch_assoc();
error_log("Access status: " . $access_row['status']);

if ($access_row['status'] !== 'approved') {
    error_log("Access not approved - status: " . $access_row['status']);
    echo json_encode(['success' => false, 'message' => 'Your access request is still pending. Please wait for admin approval.']);
    $access_check->close();
    exit();
}

error_log("Access check passed");
$access_check->close();

// Get thesis details
$thesis_stmt = $conn->prepare("
    SELECT t.id, t.title, t.author, t.abstract, t.category, t.keywords
    FROM thesis t
    WHERE t.id = ?
");

if (!$thesis_stmt) {
    error_log("Prepare failed for thesis query: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

$thesis_stmt->bind_param("i", $thesis_id);

if (!$thesis_stmt->execute()) {
    error_log("Execute failed for thesis query: " . $thesis_stmt->error);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    $thesis_stmt->close();
    exit();
}

$result = $thesis_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Thesis not found']);
    $thesis_stmt->close();
    exit();
}

$thesis = $result->fetch_assoc();
$thesis_stmt->close();

// Build context for the chatbot
$thesis_context = sprintf(
    "Thesis Information:\nTitle: %s\nAuthor: %s\nAbstract: %s\nCategory: %s\nKeywords: %s",
    $thesis['title'],
    $thesis['author'],
    substr($thesis['abstract'] ?? 'N/A', 0, 500),
    $thesis['category'] ?? 'Not classified',
    $thesis['keywords'] ?? 'N/A'
);

// Generate response - Route based on selected source
try {
    // Check which source the user selected
    if ($source === 'template') {
        error_log("Using TEMPLATE response source");
        $response = generateFallbackResponse($user_message, $thesis);
        
        echo json_encode([
            'success' => true,
            'response' => $response,
            'source' => 'template'
        ]);
    } else {
        // Use Ollama
        error_log("Using OLLAMA response source");
        
        $prompt = "You are a helpful thesis analysis assistant. Based on the following thesis context, answer the user's question concisely and professionally in 2-3 sentences max.\n\n" . 
                  $thesis_context . "\n\n" .
                  "User Question: " . $user_message . "\n\nAnswer:";
        
        error_log("Sending prompt to Ollama: " . substr($prompt, 0, 100) . "...");
        
        // Build request using same approach as OllamaServiceCurl but with LONGER timeouts for tunnel
        // Clean UTF-8 characters (same as ollama_service_curl.php does)
        $prompt = mb_convert_encoding($prompt, 'UTF-8', 'UTF-8');
        $prompt = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', ' ', $prompt);
        
        $data = [
            'model' => 'phi',
            'prompt' => $prompt,
            'stream' => false,
            'temperature' => 0.5,
        ];
        
        $jsonData = json_encode($data);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON encode error: " . json_last_error_msg());
            throw new Exception("Failed to encode prompt to JSON");
        }
        
        // Direct cURL request - use cloudflared tunnel URL (like admin page does)
        // Check for environment variable or use default cloudflared URL
        $ollamaUrl = getenv('OLLAMA_BASE_URL') ?: 'https://ollama.CITAS-smart-archive.com';
        $url = $ollamaUrl . '/api/generate';
        $ch = curl_init($url);
        
        if (!$ch) {
            throw new Exception("Failed to initialize cURL");
        }
        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);  // 5 minutes total timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);  // 30 seconds connection timeout (increased from 10)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        error_log("Executing cURL request to $url with 30-second connection timeout");
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        
        error_log("cURL errno: $curlErrno, HTTP Code: $httpCode");
        
        if (!empty($curlError)) {
            error_log("cURL Error: $curlError");
            throw new Exception("cURL Error: $curlError");
        }
        
        curl_close($ch);
        
        if ($response === false) {
            throw new Exception("Failed to get response from Ollama");
        }
        
        error_log("Ollama response received: " . substr($response, 0, 100) . "...");
        
        // Parse response
        $decoded = json_decode($response, true);
        
        if (isset($decoded['error'])) {
            throw new Exception("Ollama API Error: " . $decoded['error']);
        }
        
        $ollamaResponse = trim($decoded['response'] ?? $response);
        
        echo json_encode([
            'success' => true,
            'response' => $ollamaResponse,
            'source' => 'ollama'
        ]);
    }
    
} catch (Exception $e) {
    // Log detailed error information
    error_log("=== OLLAMA ERROR ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("Error Code: " . $e->getCode());
    error_log("Error File: " . $e->getFile());
    error_log("Error Line: " . $e->getLine());
    error_log("=== END ERROR ===");
    
    echo json_encode([
        'success' => false,
        'message' => 'Ollama error: ' . $e->getMessage(),
        'source' => 'ollama'
    ]);
}

$conn->close();

/**
 * Generate fallback response based on predefined patterns
 */
function generateFallbackResponse($user_message, $thesis) {
    $message_lower = strtolower($user_message);
    
    // Pattern matching for common questions
    if (preg_match('/summary|abstract|overview/', $message_lower)) {
        return "Here's a summary of this thesis: " . substr($thesis['abstract'] ?? 'No abstract available', 0, 300) . "...";
    }
    
    if (preg_match('/author|who wrote|research|researcher/', $message_lower)) {
        return "This thesis was authored by " . $thesis['author'] . ". You can find more details in the thesis information section.";
    }
    
    if (preg_match('/keywords|topic|subject|field/', $message_lower)) {
        $keywords = $thesis['keywords'] ?? 'Classification information not available';
        return "The main topics and keywords for this thesis are: " . $keywords;
    }
    
    if (preg_match('/download|access|view|full|document/', $message_lower)) {
        return "You can access and download the full thesis document from the overview tab. The download button is located in the document section.";
    }
    
    if (preg_match('/help|question|how|what|where/', $message_lower)) {
        return "I'm here to help! You can ask me about:\n- The thesis summary and abstract\n- Author and research information\n- Keywords and topics covered\n- How to access the full document\n\nFeel free to ask any specific questions about this thesis.";
    }
    
    // Default response
    return "Thank you for your question. This thesis is titled \"" . 
           $thesis['title'] . "\" and was authored by " . $thesis['author'] . 
           ". Feel free to ask me more specific questions about the abstract, keywords, or how to access the full document.";
}
?>
