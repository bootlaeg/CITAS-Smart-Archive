<?php
/**
 * Simple Chatbot Test - No Database Required
 */

session_start();
$_SESSION['user_id'] = 1;  // Mock user

header('Content-Type: application/json');

// Handle chatbot API request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $thesis_id = $_POST['thesis_id'] ?? 1;
    $message = $_POST['message'] ?? '';
    
    if ($action === 'chat' && !empty($message)) {
        // Simulate thesis data
        $thesis = [
            'id' => 1,
            'title' => 'Advanced AI Integration in Healthcare Systems',
            'author' => 'Dr. Juan dela Cruz',
            'abstract' => 'This research explores the application of machine learning algorithms in medical diagnosis and treatment planning...',
            'category' => 'Artificial Intelligence',
            'keywords' => 'AI, Healthcare, Machine Learning, Neural Networks, Medical Diagnosis'
        ];
        
        // Build prompt for Ollama
        $thesis_context = sprintf(
            "Thesis Information:\nTitle: %s\nAuthor: %s\nAbstract: %s\nCategory: %s\nKeywords: %s",
            $thesis['title'],
            $thesis['author'],
            substr($thesis['abstract'], 0, 500),
            $thesis['category'],
            $thesis['keywords']
        );
        
        $prompt = "You are a helpful thesis analysis assistant. Based on the following thesis context, answer the user's question concisely and professionally in 2-3 sentences max.\n\n" . 
                  $thesis_context . "\n\n" .
                  "User Question: " . $message . "\n\nAnswer:";
        
        // Try to connect to Ollama
        $url = 'http://localhost:11434/api/generate';
        
        $payload = json_encode([
            'model' => 'mistral:latest',
            'prompt' => $prompt,
            'stream' => false,
            'temperature' => 0.5
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if (!empty($error) || $httpCode !== 200) {
            // Fallback response
            echo json_encode([
                'success' => true,
                'response' => "Based on the thesis about AI in healthcare, I can help you understand the key concepts discussed. Could you ask a more specific question?",
                'source' => 'template (Ollama offline)'
            ]);
        } else {
            $data = json_decode($response, true);
            echo json_encode([
                'success' => true,
                'response' => $data['response'] ?? 'No response',
                'source' => 'ollama'
            ]);
        }
    }
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Test - CITAS</title>
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
            max-width: 700px;
            height: 600px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
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
        }
        .message.user {
            justify-content: flex-end;
        }
        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 12px;
            word-wrap: break-word;
        }
        .message.user .message-content {
            background: #667eea;
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
            display: flex;
            gap: 10px;
        }
        input {
            flex: 1;
            padding: 12px;
            border: 2px solid #eee;
            border-radius: 8px;
            font-size: 14px;
        }
        button {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        button:hover { opacity: 0.9; }
        button:disabled { opacity: 0.5; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="header">
            <h2>💬 Thesis Assistant Test</h2>
            <p style="font-size: 12px; margin-top: 5px;">Testing Ollama AI Integration</p>
        </div>
        
        <div class="messages" id="messages">
            <div class="message ai">
                <div class="message-content">Hello! I'm the Thesis Assistant powered by Ollama AI. You can ask me questions about the thesis. Try asking: "What is neurology?" or "Tell me about the research"</div>
            </div>
        </div>
        
        <div class="input-area">
            <input type="text" id="input" placeholder="Ask me anything..." autocomplete="off">
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
            div.innerHTML = `<div class="message-content">${escapeHtml(text)}</div>`;
            messagesDiv.appendChild(div);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }
        
        function addLoadingIndicator() {
            const div = document.createElement('div');
            div.className = 'message ai loading';
            div.id = 'loading';
            div.innerHTML = '<div class="message-content"><div class="typing-dots"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div></div>';
            messagesDiv.appendChild(div);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        async function sendMessage() {
            const message = inputField.value.trim();
            if (!message) return;
            
            addMessage(message, true);
            inputField.value = '';
            sendBtn.disabled = true;
            
            addLoadingIndicator();
            
            try {
                const resp = await fetch('?action=chat', {
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
                    addMessage('Error: ' + (data.error || 'Unknown error'), false);
                }
            } catch (err) {
                document.getElementById('loading')?.remove();
                addMessage('Connection error: ' + err.message, false);
            } finally {
                sendBtn.disabled = false;
                inputField.focus();
            }
        }
        
        sendBtn.addEventListener('click', sendMessage);
        inputField.addEventListener('keypress', e => {
            if (e.key === 'Enter') sendMessage();
        });
        
        inputField.focus();
    </script>
</body>
</html>
