<?php
/**
 * CITAS Smart Archive - AI Chat Interface
 * Connects to Ollama AI on localhost:11434
 */

session_start();

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ollama configuration
define('OLLAMA_HOST', 'http://localhost:11434');
define('OLLAMA_MODEL', 'mistral:latest');

/**
 * Send message to Ollama and get response
 */
function chatWithOllama($message) {
    $url = OLLAMA_HOST . '/api/generate';
    
    $payload = [
        'model' => OLLAMA_MODEL,
        'prompt' => $message,
        'stream' => false,
        'temperature' => 0.7,
        'top_p' => 0.9
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 2 minutes timeout
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => "Connection error: $error"
        ];
    }
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error' => "Ollama error (HTTP $httpCode): $response"
        ];
    }
    
    $data = json_decode($response, true);
    
    return [
        'success' => true,
        'response' => $data['response'] ?? 'No response',
        'model' => $data['model'] ?? OLLAMA_MODEL
    ];
}

/**
 * Check if Ollama is running
 */
function isOllamaRunning() {
    $ch = curl_init(OLLAMA_HOST . '/api/tags');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

// Handle requests
$action = $_GET['action'] ?? $_POST['action'] ?? null;

if ($action === 'status') {
    echo json_encode([
        'running' => isOllamaRunning(),
        'host' => OLLAMA_HOST,
        'model' => OLLAMA_MODEL
    ]);
    exit;
}

if ($action === 'chat') {
    $message = $_POST['message'] ?? trim($_GET['message'] ?? '');
    
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Message required']);
        exit;
    }
    
    if (strlen($message) > 5000) {
        http_response_code(400);
        echo json_encode(['error' => 'Message too long (max 5000 chars)']);
        exit;
    }
    
    $result = chatWithOllama($message);
    echo json_encode($result);
    exit;
}

// Default: Show chat interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITAS Smart Archive - AI Chat</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .chat-container {
            width: 100%;
            max-width: 800px;
            height: 90vh;
            max-height: 700px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .chat-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }
        
        .status-indicator.online {
            background-color: #4ade80;
        }
        
        .status-indicator.offline {
            background-color: #ef4444;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
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
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message.user {
            justify-content: flex-end;
        }
        
        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 12px;
            word-wrap: break-word;
            white-space: pre-wrap;
            line-height: 1.5;
        }
        
        .message.user .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message.ai .message-content {
            background: #f0f0f0;
            color: #333;
            border-bottom-left-radius: 4px;
        }
        
        .message.error .message-content {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
        
        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 12px 16px;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #999;
            animation: typing 1.4s infinite;
        }
        
        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.7;
            }
            30% {
                transform: translateY(-10px);
                opacity: 1;
            }
        }
        
        .chat-input-area {
            padding: 20px;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        
        .input-form {
            display: flex;
            gap: 10px;
        }
        
        #messageInput {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            resize: none;
            max-height: 120px;
            transition: border-color 0.3s;
        }
        
        #messageInput:focus {
            outline: none;
            border-color: #667eea;
        }
        
        #sendBtn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        #sendBtn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        #sendBtn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .error-banner {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: none;
        }
        
        .model-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h1>💬 AI Chat - CITAS Archive</h1>
            <div>
                <span class="status-indicator online" id="statusIndicator"></span>
                <span id="statusText">Connecting...</span>
            </div>
            <div class="model-info" id="modelInfo">Loading model info...</div>
        </div>
        
        <div class="error-banner" id="errorBanner"></div>
        
        <div class="messages" id="messages"></div>
        
        <div class="chat-input-area">
            <form class="input-form" id="chatForm">
                <textarea 
                    id="messageInput" 
                    placeholder="Type your message here..." 
                    rows="2"
                    autocomplete="off"
                ></textarea>
                <button type="submit" id="sendBtn">Send</button>
            </form>
        </div>
    </div>
    
    <script>
        const messagesContainer = document.getElementById('messages');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const statusIndicator = document.getElementById('statusIndicator');
        const statusText = document.getElementById('statusText');
        const modelInfo = document.getElementById('modelInfo');
        const errorBanner = document.getElementById('errorBanner');
        const chatForm = document.getElementById('chatForm');
        
        let isOllamaOnline = false;
        
        // Adjust textarea height
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
        
        // Check Ollama status on load
        async function checkStatus() {
            try {
                const response = await fetch('?action=status');
                const data = await response.json();
                
                isOllamaOnline = data.running;
                statusIndicator.className = data.running ? 'status-indicator online' : 'status-indicator offline';
                statusText.textContent = data.running ? `✓ Online - ${data.model}` : '✗ Offline';
                modelInfo.textContent = `Host: ${data.host}`;
                
                if (!data.running) {
                    showError('⚠️ Ollama is not running. Start it first!');
                    sendBtn.disabled = true;
                }
            } catch (error) {
                isOllamaOnline = false;
                statusIndicator.className = 'status-indicator offline';
                statusText.textContent = '✗ Connection Error';
                showError('Cannot connect to Ollama. Make sure it is running on localhost:11434');
                sendBtn.disabled = true;
            }
        }
        
        function showError(message) {
            errorBanner.textContent = message;
            errorBanner.style.display = 'block';
            setTimeout(() => {
                errorBanner.style.display = 'none';
            }, 5000);
        }
        
        function addMessage(content, isUser = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isUser ? 'user' : 'ai'}`;
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';
            contentDiv.textContent = content;
            
            messageDiv.appendChild(contentDiv);
            messagesContainer.appendChild(messageDiv);
            
            // Auto scroll to bottom
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        function addTypingIndicator() {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message ai typing-message';
            messageDiv.id = 'typingIndicator';
            
            messageDiv.innerHTML = '<div class="typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>';
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        function removeTypingIndicator() {
            const typingMsg = document.getElementById('typingIndicator');
            if (typingMsg) {
                typingMsg.remove();
            }
        }
        
        async function sendMessage(e) {
            e.preventDefault();
            
            const message = messageInput.value.trim();
            if (!message) return;
            
            if (!isOllamaOnline) {
                showError('⚠️ Ollama is not online. Cannot send message.');
                return;
            }
            
            // Add user message
            addMessage(message, true);
            messageInput.value = '';
            messageInput.style.height = 'auto';
            sendBtn.disabled = true;
            
            // Show typing indicator
            addTypingIndicator();
            
            try {
                const response = await fetch('?action=chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'message=' + encodeURIComponent(message)
                });
                
                const data = await response.json();
                removeTypingIndicator();
                
                if (data.success) {
                    addMessage(data.response, false);
                } else {
                    showError('Error: ' + (data.error || 'Unknown error'));
                    addMessage('Sorry, I encountered an error: ' + (data.error || 'Unknown error'), false);
                }
            } catch (error) {
                removeTypingIndicator();
                console.error('Error:', error);
                showError('Network error: ' + error.message);
                addMessage('Sorry, I could not connect to the AI. Please try again.', false);
            } finally {
                sendBtn.disabled = false;
                messageInput.focus();
            }
        }
        
        // Event listeners
        chatForm.addEventListener('submit', sendMessage);
        
        // Allow Enter to send (Shift+Enter for new line)
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage(e);
            }
        });
        
        // Initialize
        window.addEventListener('load', checkStatus);
    </script>
</body>
</html>
