<?php
/**
 * HuggingFace API Tester
 * Tests HF API connectivity and functionality
 */

require_once __DIR__ . '/ai_includes/huggingface_config.php';
require_once __DIR__ . '/ai_includes/huggingface_service.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>HuggingFace API Tester</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
        }
        .test-section {
            background: #f5f5f5;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        button {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px 5px 5px 0;
        }
        button:hover {
            background: #0056b3;
        }
        pre {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            overflow-x: auto;
            border-radius: 3px;
        }
        .status {
            font-weight: bold;
            padding: 10px;
            margin: 10px 0;
            border-radius: 3px;
        }
        h1 {
            color: #333;
        }
        h2 {
            color: #666;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
    <h1>🧪 HuggingFace API Tester</h1>
    
    <div class="test-section info">
        <h2>📋 Configuration Check</h2>
        <?php
            echo "<p><strong>API URL:</strong> " . HUGGING_FACE_API_URL . "</p>";
            echo "<p><strong>Model:</strong> " . HUGGING_FACE_MODEL . "</p>";
            echo "<p><strong>Timeout:</strong> " . HUGGING_FACE_TIMEOUT . "s</p>";
            echo "<p><strong>Retry Count:</strong> " . HUGGING_FACE_RETRY_COUNT . "</p>";
            
            $api_key = HUGGING_FACE_API_KEY;
            if (empty($api_key)) {
                echo '<div class="status error">❌ API Key Not Set!</div>';
            } else {
                echo '<div class="status success">✅ API Key Configured: ' . substr($api_key, 0, 10) . '...</div>';
            }
        ?>
    </div>
    
    <div class="test-section">
        <h2>🔧 Test Functions</h2>
        <form method="POST">
            <button type="submit" name="test" value="status">Test API Status</button>
            <button type="submit" name="test" value="summarize">Test Summarization</button>
            <button type="submit" name="test" value="generate">Test Text Generation</button>
            <button type="submit" name="test" value="full">Run All Tests</button>
        </form>
    </div>
    
    <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test'])) {
            $test = $_POST['test'];
            $hf = new HuggingFaceService();
            
            // Test 1: API Status
            if ($test === 'status' || $test === 'full') {
                echo '<div class="test-section">';
                echo '<h2>Test 1: API Status Check</h2>';
                echo '<p>Testing basic connection to HuggingFace API...</p>';
                
                $status = $hf->getStatus();
                
                if ($status['success']) {
                    echo '<div class="status success">✅ Success: ' . $status['message'] . '</div>';
                } else {
                    echo '<div class="status error">❌ Failed: ' . ($status['error'] ?? 'Unknown error') . '</div>';
                }
                
                echo '<pre>' . json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>';
                echo '</div>';
            }
            
            // Test 2: Summarization
            if ($test === 'summarize' || $test === 'full') {
                echo '<div class="test-section">';
                echo '<h2>Test 2: Summarization</h2>';
                
                $test_text = "Neurological disorders are a major public health challenge affecting millions worldwide. "
                    . "Early detection and accurate diagnosis are critical for improving patient outcomes. "
                    . "This study presents a novel AI-powered system that integrates multiple data sources including EEG signals, "
                    . "gait analysis, and cognitive assessments. The proposed approach uses deep learning to achieve high accuracy "
                    . "in detecting various neurological conditions. Testing was conducted on 1,240 patient records with excellent results.";
                
                echo '<p><strong>Input text:</strong></p>';
                echo '<pre>' . htmlspecialchars($test_text) . '</pre>';
                
                echo '<p>Calling summarize()...</p>';
                $result = $hf->summarize($test_text, 50);
                
                if ($result['success']) {
                    echo '<div class="status success">✅ Summarization Successful</div>';
                    echo '<p><strong>Summary:</strong></p>';
                    echo '<pre>' . htmlspecialchars($result['summary']) . '</pre>';
                    echo '<p><strong>Word Count:</strong> ' . $result['word_count'] . '</p>';
                } else {
                    echo '<div class="status error">❌ Summarization Failed</div>';
                    echo '<p><strong>Error:</strong> ' . htmlspecialchars($result['error']) . '</p>';
                }
                
                echo '<pre>Full Response: ' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>';
                echo '</div>';
            }
            
            // Test 3: Text Generation
            if ($test === 'generate' || $test === 'full') {
                echo '<div class="test-section">';
                echo '<h2>Test 3: Text Generation</h2>';
                
                $prompt = "Write a short 100-word Introduction for an AI medical imaging study. Focus on the problem and motivation.";
                
                echo '<p><strong>Prompt:</strong></p>';
                echo '<pre>' . htmlspecialchars($prompt) . '</pre>';
                
                echo '<p>Calling generate()...</p>';
                $result = $hf->generate($prompt, 100);
                
                if ($result['success']) {
                    echo '<div class="status success">✅ Generation Successful</div>';
                    echo '<p><strong>Generated Text:</strong></p>';
                    echo '<pre>' . htmlspecialchars($result['text']) . '</pre>';
                    echo '<p><strong>Word Count:</strong> ' . $result['word_count'] . '</p>';
                } else {
                    echo '<div class="status error">❌ Generation Failed</div>';
                    echo '<p><strong>Error:</strong> ' . htmlspecialchars($result['error']) . '</p>';
                }
                
                echo '<pre>Full Response: ' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>';
                echo '</div>';
            }
            
            // Test 4: Full Journal Generation
            if ($test === 'full') {
                echo '<div class="test-section">';
                echo '<h2>Test 4: Full Section Generation (Fallback)</h2>';
                
                // Load converter and test deterministic generation
                require_once __DIR__ . '/ai_includes/journal_converter.php';
                
                $metadata = [
                    'title' => 'NeuroGuard: AI-Powered Neurological Disorder Detection System',
                    'author' => 'Test Authors',
                    'abstract' => 'This study presents NeuroGuard, an AI system for early detection of neurological disorders using EEG, gait analysis, and cognitive assessments.',
                    'year' => 2026
                ];
                
                echo '<p>Testing deterministic section generation...</p>';
                
                // Create converter with sample data
                $converter = new JournalConverter('test', 'Sample document text', $metadata);
                
                // Use reflection to access private method for testing
                $method = new ReflectionMethod($converter, 'generateSectionDeterministic');
                $method->setAccessible(true);
                
                $sections = ['introduction', 'methods', 'results', 'discussion', 'conclusions'];
                
                foreach ($sections as $section_type) {
                    $generated = $method->invoke($converter, $section_type);
                    $word_count = str_word_count($generated);
                    
                    echo '<div style="margin: 10px 0; padding: 10px; background: #f9f9f9; border-left: 4px solid #007bff;">';
                    echo '<strong>' . ucfirst($section_type) . ':</strong> ';
                    echo '<span style="color: #666;">(' . $word_count . ' words)</span><br>';
                    echo '<pre>' . substr(htmlspecialchars($generated), 0, 300) . '...</pre>';
                    echo '</div>';
                }
                
                echo '<div class="status success">✅ Deterministic generation working</div>';
                echo '</div>';
            }
        }
    ?>
    
    <div class="test-section">
        <h2>📝 Debug Info</h2>
        <button onclick="showPhpInfo()">Show PHP Info</button>
        <button onclick="testCurl()">Test cURL Support</button>
        <div id="debug-output"></div>
    </div>
    
    <script>
        function showPhpInfo() {
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=phpinfo')
                .then(r => r.text())
                .then(html => {
                    document.getElementById('debug-output').innerHTML = '<pre>' + escapeHtml(html.substring(0, 1000)) + '</pre>';
                });
        }
        
        function testCurl() {
            let output = '<p>PHP Version: <?php echo phpversion(); ?></p>';
            output += '<p>cURL Support: <?php echo extension_loaded("curl") ? "✅ Yes" : "❌ No"; ?></p>';
            output += '<p>OpenSSL Support: <?php echo extension_loaded("openssl") ? "✅ Yes" : "❌ No"; ?></p>';
            document.getElementById('debug-output').innerHTML = output;
        }
        
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    </script>
    
</body>
</html>
