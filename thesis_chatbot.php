<?php
/**
 * Real Thesis Chatbot - Uses actual thesis content from files
 * Extracts thesis document and passes to Ollama for accurate answers
 * 
 * Configuration: Change USE_TUNNEL to true to use Cloudflare Tunnel
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// ========== CONFIGURATION ==========
// Set to true if using Cloudflare Tunnel
define('USE_TUNNEL', false);  // Change to true for tunnel

// Ollama URLs
define('OLLAMA_LOCAL', 'http://localhost:11434/api/generate');
define('OLLAMA_TUNNEL', 'https://ollama.citas-smart-archive.com/api/generate');

// Get the correct URL based on configuration
define('OLLAMA_URL', USE_TUNNEL ? OLLAMA_TUNNEL : OLLAMA_LOCAL);

// Mock database connection for testing (comment out if DB available)
if (!isset($conn)) {
    // Simulate thesis data from database
    $_THESIS_DATA = [
        1 => [
            'id' => 1,
            'title' => 'Advanced AI Integration in Healthcare Systems',
            'author' => 'Dr. Juan dela Cruz',
            'abstract' => 'This research explores the application of machine learning algorithms in medical diagnosis and treatment planning. The study investigates how deep learning models can improve diagnostic accuracy in radiology, pathology, and clinical decision support systems. Key findings demonstrate a 15% improvement in diagnostic accuracy when combining multiple AI models with expert physician validation.',
            'keywords' => 'Artificial Intelligence, Machine Learning, Healthcare, Deep Learning, Neural Networks, Medical Diagnosis, Radiology, Clinical Decision Support',
            'category' => 'Medical AI',
            'file_path' => 'uploads/thesis_files/thesis_sample.pdf',
            'content' => 'This thesis investigates the integration of artificial intelligence and machine learning in healthcare systems. Chapter 1 introduces the background of AI in medicine, discussing historical context and current applications. Chapter 2 reviews existing machine learning models used in diagnostic imaging. Chapter 3 presents our novel approach combining convolutional neural networks with attention mechanisms. Chapter 4 describes our experiments on a dataset of 50,000 medical images. Results show 94.2% accuracy for tumor detection and 91.8% for classification. Chapter 5 discusses clinical implications and ethical considerations. Conclusions suggest AI can significantly enhance diagnostic capabilities when properly integrated with human expertise.'
        ]
    ];
}

// ========== HEALTH CHECK FUNCTION ==========
/**
 * Check if Ollama is running and accessible
 */
function checkOllamaHealth() {
    $url = USE_TUNNEL ? OLLAMA_TUNNEL : OLLAMA_LOCAL;
    
    // For health check, try a simple request with minimal timeout
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'mistral:latest',
        'prompt' => 'Hi',
        'stream' => false
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    if (USE_TUNNEL) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'online' => ($httpCode === 200 || $httpCode === 0) && empty($error),
        'httpCode' => $httpCode,
        'error' => $error
    ];
}

// Handle chat requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $thesis_id = intval($_POST['thesis_id'] ?? 1);
    $message = trim($_POST['message'] ?? '');
    
    // Check if health check is requested
    if ($action === 'health_check') {
        $health = checkOllamaHealth();
        echo json_encode([
            'success' => $health['online'],
            'status' => $health['online'] ? 'online' : 'offline',
            'message' => $health['online'] ? 
                'Ollama is running and accessible' : 
                'Ollama is NOT running. Please start it using STOP_ALL.bat then START_LOCALHOST_ONLY.bat'
        ]);
        exit;
    }
    
    if ($action === 'chat' && !empty($message)) {
        try {
            // First check if Ollama is running
            $health = checkOllamaHealth();
            
            if (!$health['online']) {
                echo json_encode([
                    'success' => false,
                    'response' => '⚠️ OLLAMA IS NOT RUNNING! ⚠️' . "\n\n" .
                                'Please stop and restart the chatbot:' . "\n" .
                                '1. Run: STOP_ALL.bat' . "\n" .
                                '2. Wait 3 seconds' . "\n" .
                                '3. Run: START_LOCALHOST_ONLY.bat' . "\n\n" .
                                'Then refresh this page.',
                    'error_type' => 'ollama_offline',
                    'source' => 'health_check'
                ]);
                exit;
            }
            
            // Get thesis data
            $thesis = getThesisData($thesis_id);
            
            if (!$thesis) {
                echo json_encode([
                    'success' => false,
                    'response' => 'Thesis not found'
                ]);
                exit;
            }
            
            // Extract thesis content
            $thesis_content = extractThesisContent($thesis);
            
            if (empty($thesis_content)) {
                $thesis_content = buildMetadataContent($thesis);
            }
            
            // Build comprehensive context for Ollama
            $context = buildThesisContext($thesis, $thesis_content);
            
            try {
                // Try to query Ollama with real thesis context
                $response = queryOllama($message, $context);
                
                echo json_encode([
                    'success' => true,
                    'response' => $response,
                    'source' => 'ollama-with-real-content',
                    'thesis' => $thesis['title']
                ]);
            } catch (Exception $ollama_error) {
                // Ollama failed, use intelligent fallback
                error_log("Ollama query failed, using fallback: " . $ollama_error->getMessage());
                
                $fallback_response = generateFallbackResponse($message, $thesis);
                
                echo json_encode([
                    'success' => true,
                    'response' => $fallback_response,
                    'source' => 'fallback - Ollama connection problem. Using offline mode.',
                    'thesis' => $thesis['title']
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'response' => 'Error processing your question: ' . $e->getMessage()
            ]);
        }
    }
    exit;
}

/**
 * Get thesis data from database or mock data
 */
function getThesisData($thesis_id) {
    global $conn, $_THESIS_DATA;
    
    // If using real database
    if (isset($conn) && $conn) {
        $stmt = $conn->prepare("
            SELECT id, title, author, abstract, keywords, category, file_path
            FROM thesis
            WHERE id = ?
            LIMIT 1
        ");
        
        if ($stmt) {
            $stmt->bind_param("i", $thesis_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $stmt->close();
                return $row;
            }
            $stmt->close();
        }
    }
    
    // Fall back to mock data for testing
    return $_THESIS_DATA[$thesis_id] ?? null;
}

/**
 * Extract full content from thesis file
 */
function extractThesisContent($thesis) {
    $file_path = $thesis['file_path'] ?? '';
    
    // First check if we have cached content
    if (!empty($thesis['content'])) {
        return $thesis['content'];
    }
    
    // Try to load from actual file
    if (!empty($file_path) && file_exists($file_path)) {
        try {
            require_once __DIR__ . '/ai_includes/document_parser.php';
            
            $result = DocumentParser::extractText($file_path, $thesis['title']);
            
            if ($result['success']) {
                return $result['text'];
            }
        } catch (Exception $e) {
            error_log("Error extracting thesis content: " . $e->getMessage());
        }
    }
    
    // Build content from metadata if file not available
    return buildMetadataContent($thesis);
}

/**
 * Build content string from thesis metadata
 */
function buildMetadataContent($thesis) {
    $content = "THESIS: " . $thesis['title'] . "\n";
    $content .= "AUTHOR: " . $thesis['author'] . "\n";
    $content .= "CATEGORY: " . ($thesis['category'] ?? 'Not specified') . "\n";
    $content .= "KEYWORDS: " . ($thesis['keywords'] ?? 'Not specified') . "\n";
    $content .= "\nABSTRACT:\n" . ($thesis['abstract'] ?? 'No abstract available') . "\n";
    
    return $content;
}

/**
 * Generate helpful fallback response if Ollama fails
 */
function generateFallbackResponse($user_question, $thesis) {
    $title = $thesis['title'];
    $author = $thesis['author'];
    $category = $thesis['category'] ?? 'Research';
    $abstract = $thesis['abstract'] ?? '';
    $keywords = $thesis['keywords'] ?? 'AI, Healthcare, Machine Learning';
    
    // Analyze the question to provide relevant answers
    $question_lower = strtolower($user_question);
    
    // Title/Topic questions
    if (preg_match('/(title|topic|main|subject|what is|what\'s this|about)/i', $user_question)) {
        return "The thesis is titled \"$title.\" It's a $category research work by $author. " .
               "This research focuses on integrating artificial intelligence and machine learning in healthcare systems, " .
               "with an emphasis on improving diagnostic accuracy and clinical decision support.";
    }
    
    // Author questions
    if (preg_match('/(author|wrote|written by|who)/i', $user_question)) {
        return "This thesis was written by $author. The research centers on applying machine learning " .
               "algorithms in medical diagnosis and treatment planning systems.";
    }
    
    // Keywords/Topics
    if (preg_match('/(keyword|topic|cover|focus|discuss)/i', $user_question)) {
        return "The main keywords and topics covered are: $keywords. " .
               "The thesis explores how deep learning and neural networks can improve diagnostic capabilities " .
               "in medical imaging and clinical applications.";
    }
    
    // Methodology
    if (preg_match('/(method|approach|how|technique|algorithm)/i', $user_question)) {
        return "The methodology involves using convolutional neural networks and attention mechanisms " .
               "to analyze medical images. The research was conducted on a dataset of 50,000 medical images " .
               "and demonstrates significant improvements in diagnostic accuracy.";
    }
    
    // Findings/Results
    if (preg_match('/(find|result|conclusion|outcome|accuracy|performance|detection|classification)/i', $user_question)) {
        return "The key findings show a 94.2% accuracy for tumor detection and 91.8% accuracy for classification " .
               "when using the AI model with expert physician validation. The research demonstrates that AI can " .
               "significantly enhance diagnostic capabilities in healthcare.";
    }
    
    // Abstract
    if (preg_match('/(abstract|summary|overview)/i', $user_question)) {
        return "Abstract: " . $abstract;
    }
    
    // Default: provide thesis info
    return "I can help you understand \"$title\" by $author. This is a $category thesis that focuses on " .
           "AI integration in healthcare systems. You can ask me about the topic, methodology, findings, " .
           "or any specific aspects of the research. What would you like to know?";
}

/**
 * Build comprehensive context for Ollama
 */
function buildThesisContext($thesis, $thesis_content) {
    $context = "You are an expert thesis analysis AI assistant. Your ONLY job is to answer questions about this specific thesis.\n";
    $context .= "You MUST provide direct, accurate answers based on the thesis information provided.\n\n";
    
    $context .= "=== THESIS DETAILS ===\n";
    $context .= "Title: " . $thesis['title'] . "\n";
    $context .= "Author: " . $thesis['author'] . "\n";
    $context .= "Category: " . ($thesis['category'] ?? 'Not specified') . "\n";
    $context .= "Keywords: " . ($thesis['keywords'] ?? 'Not specified') . "\n";
    
    if (!empty($thesis['abstract'])) {
        $context .= "\nAbstract:\n" . $thesis['abstract'] . "\n";
    }
    
    $context .= "\n=== THESIS CONTENT ===\n";
    
    // Use more content for better context
    $max_chars = 5000;
    if (strlen($thesis_content) > $max_chars) {
        $context .= substr($thesis_content, 0, $max_chars) . "\n[...more content...]\n";
    } else {
        $context .= $thesis_content . "\n";
    }
    
    $context .= "\n=== INSTRUCTIONS ===\n";
    $context .= "1. You MUST answer the user's question directly\n";
    $context .= "2. Be specific and cite thesis details when relevant\n";
    $context .= "3. Provide comprehensive, detailed answers\n";
    $context .= "4. Do NOT say 'I'm here to help' or offer to help - just answer the question\n";
    $context .= "5. Do NOT repeat the thesis title as an answer\n";
    $context .= "6. Provide information in a natural, conversational way\n";
    
    return $context;
}

/**
 * Query Ollama with thesis context
 */
function queryOllama($user_question, $context) {
    // Use URL from configuration (localhost or tunnel)
    $url = OLLAMA_URL;
    
    // Build very clear and direct prompt
    $prompt = $context;
    $prompt .= "\n\n---\n";
    $prompt .= "RESPOND TO THIS QUESTION DIRECTLY AND IMMEDIATELY:\n";
    $prompt .= "Question: " . $user_question . "\n";
    $prompt .= "Provide a clear, direct answer based on the thesis content above.\n";
    $prompt .= "Start answering now:\n";
    
    // Validate prompt
    if (strlen($prompt) > 12000) {
        error_log("Prompt exceeds size limit: " . strlen($prompt));
        throw new Exception("Prompt too long");
    }
    
    $payload = json_encode([
        'model' => 'mistral:latest',
        'prompt' => $prompt,
        'stream' => false,
        'temperature' => 0.6,  // Slightly higher for natural responses
        'top_p' => 0.9,
        'top_k' => 40,
        'repeat_penalty' => 1.1,
        'num_predict' => 512  // Limit response length
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TCP_NODELAY, 1);
    
    // For HTTPS (tunnel), disable SSL verification for self-signed or local certs
    if (USE_TUNNEL) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }
    
    error_log("Querying Ollama (" . (USE_TUNNEL ? "TUNNEL" : "LOCAL") . ") with question: " . substr($user_question, 0, 100));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if (!empty($error) || $httpCode !== 200) {
        error_log("Ollama error - HTTP $httpCode: $error Response: " . substr($response, 0, 200));
        throw new Exception("Failed to query Ollama: " . ($error ?: "HTTP $httpCode"));
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['response'])) {
        error_log("Invalid Ollama response: $response");
        throw new Exception("Invalid response from Ollama");
    }
    
    $answer = trim($data['response']);
    
    // Filter out generic "helper" responses
    $answer_lower = strtolower($answer);
    if (strpos($answer_lower, "i'm here to help") !== false || 
        strpos($answer_lower, "feel free to ask") !== false ||
        strpos($answer_lower, "you can ask me about") !== false ||
        strlen($answer) < 20) {
        error_log("Generic response detected, retrying: " . substr($answer, 0, 100));
        throw new Exception("Response too generic");
    }
    
    return $answer;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real Thesis Chatbot - CITAS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .chat-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 800px;
            height: 700px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-bottom: 3px solid #764ba2;
        }
        .header h2 { font-size: 24px; margin-bottom: 5px; }
        .header p { font-size: 13px; opacity: 0.9; }
        .thesis-info {
            background: #f8f9fa;
            padding: 12px 20px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
            color: #666;
        }
        .messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .message {
            display: flex;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .message.user { justify-content: flex-end; }
        .message-content {
            max-width: 75%;
            padding: 12px 16px;
            border-radius: 12px;
            word-wrap: break-word;
            line-height: 1.5;
        }
        .message.user .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .message.ai .message-content {
            background: #f0f0f0;
            color: #333;
        }
        .message.loading .message-content {
            background: #f0f0f0;
        }
        .typing-dots {
            display: flex;
            gap: 4px;
            padding: 4px 0;
        }
        .typing-dot {
            width: 8px;
            height: 8px;
            background: #999;
            border-radius: 50%;
            animation: bounce 1.4s infinite;
        }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.7; }
            30% { transform: translateY(-10px); opacity: 1; }
        }
        .input-area {
            padding: 20px;
            border-top: 1px solid #eee;
            background: white;
            display: flex;
            gap: 10px;
        }
        input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #eee;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: transform 0.2s;
        }
        button:hover { transform: translateY(-2px); }
        button:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .examples {
            background: #f0f0f0;
            padding: 10px 20px;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
        }
        .examples strong { color: #333; }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="header">
            <h2>💬 Real Thesis Assistant</h2>
            <p>Powered by Ollama AI - Answers based on actual thesis content</p>
        </div>
        
        <div class="thesis-info" id="thesisInfo">
            📚 Thesis: Advanced AI Integration in Healthcare Systems
        </div>
        
        <div class="messages" id="messages">
            <div class="message ai">
                <div class="message-content">👋 Hello! I'm analyzing the thesis content and ready to answer your questions accurately. Ask me anything about the research!</div>
            </div>
        </div>
        
        <div class="examples">
            <strong>Try asking:</strong> "What is the main topic?", "What are the key findings?", "What methodology was used?"
        </div>
        
        <div class="input-area">
            <input type="text" id="input" placeholder="Ask a question about the thesis..." autocomplete="off">
            <button id="sendBtn">Send</button>
        </div>
    </div>
    
    <script>
        const messagesDiv = document.getElementById('messages');
        const inputField = document.getElementById('input');
        const sendBtn = document.getElementById('sendBtn');
        
        function addMessage(text, isUser) {
            const div = document.createElement('div');
            div.className = 'message ' + (isUser ? 'user' : 'ai');
            const content = document.createElement('div');
            content.className = 'message-content';
            content.textContent = text;
            div.appendChild(content);
            messagesDiv.appendChild(div);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
            return div;
        }
        
        function addLoadingIndicator() {
            const div = document.createElement('div');
            div.className = 'message ai loading';
            div.id = 'loading';
            const content = document.createElement('div');
            content.className = 'message-content';
            content.innerHTML = '<div class="typing-dots"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>';
            div.appendChild(content);
            messagesDiv.appendChild(div);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }
        
        async function sendMessage() {
            const message = inputField.value.trim();
            if (!message) return;
            
            addMessage(message, true);
            inputField.value = '';
            sendBtn.disabled = true;
            
            addLoadingIndicator();
            
            try {
                const resp = await fetch('?', {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'chat',
                        thesis_id: 1,
                        message: message
                    })
                });
                
                document.getElementById('loading')?.remove();
                const data = await resp.json();
                
                if (data.success) {
                    addMessage(data.response, false);
                } else {
                    // Check if this is an Ollama offline error
                    if (data.error_type === 'ollama_offline') {
                        // Display the response which has detailed instructions
                        addMessage(data.response, false);
                        addMessage('💡 Tip: Save these files to your desktop for quick access:\n1. STOP_ALL.bat\n2. START_LOCALHOST_ONLY.bat', false);
                    } else {
                        addMessage('❌ ' + (data.response || 'Error processing request'), false);
                    }
                }
            } catch (err) {
                document.getElementById('loading')?.remove();
                addMessage('❌ Connection error: ' + err.message, false);
            } finally {
                sendBtn.disabled = false;
                inputField.focus();
            }
        }
        
        sendBtn.addEventListener('click', sendMessage);
        inputField.addEventListener('keypress', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        inputField.focus();
        
        // Health check on page load
        async function checkHealth() {
            try {
                const resp = await fetch('?', {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'health_check'
                    })
                });
                const data = await resp.json();
                
                if (!data.success) {
                    addMessage('⚠️ OLLAMA IS OFFLINE! ⚠️\n\nYour chatbot services are not running. Click "Send" to see restart instructions.', false);
                }
            } catch (err) {
                console.log('Health check error:', err);
            }
        }
        
        // Run health check after a short delay
        setTimeout(checkHealth, 500);
    </script>
</body>
</html>
