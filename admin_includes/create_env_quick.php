<?php
/**
 * Quick .env Creator for Hostinger
 * Securely creates .env file by accepting user input
 * Never stores secrets in code
 */

header('Content-Type: text/html; charset=utf-8');

// Determine the root directory
$root_dir = dirname(dirname(__DIR__)); // Go up from admin_includes

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>.env Creator - Secure Setup</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .help-text {
            font-size: 12px;
            color: #999;
            margin-top: 6px;
        }
        .help-text a {
            color: #667eea;
            text-decoration: none;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
            margin-top: 10px;
        }
        button:hover {
            background: #764ba2;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 10px;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .divider {
            border: none;
            border-top: 2px solid #e0e0e0;
            margin: 30px 0;
        }
        .manual-setup {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .manual-setup h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .manual-setup ol {
            margin-left: 20px;
            color: #666;
            line-height: 1.8;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 .env Creator</h1>
        <p class="subtitle">Securely create your HuggingFace API configuration</p>

        <?php
        // Check if .env already exists
        $env_file = $root_dir . '/.env';
        $env_exists = file_exists($env_file);

        if ($env_exists) {
            echo '<div class="alert alert-success">';
            echo '<strong>✓ Success!</strong> Your .env file is already configured.';
            echo '<br><a href="hf_monitor.html" style="color: inherit; text-decoration: underline;">→ Check HF Status</a>';
            echo '</div>';
        }

        // Process form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $api_key = trim($_POST['api_key'] ?? '');

            if (empty($api_key)) {
                echo '<div class="alert alert-error"><strong>✗ Error:</strong> API key is required</div>';
            } elseif (!preg_match('/^hf_[a-zA-Z0-9_]{1,}$/', $api_key)) {
                echo '<div class="alert alert-error"><strong>✗ Error:</strong> Invalid API key format. Must start with "hf_"</div>';
            } else {
                // Validate it's writable
                if (!is_writable($root_dir)) {
                    echo '<div class="alert alert-error"><strong>✗ Error:</strong> Directory is not writable: ' . htmlspecialchars($root_dir) . '</div>';
                } else {
                    // Create the .env file
                    $env_content = "# Hugging Face API Configuration\n"
                        . "# IMPORTANT: This file contains sensitive credentials\n"
                        . "# NEVER commit this file to git or push it to GitHub\n"
                        . "# It's listed in .gitignore for protection\n\n"
                        . "HUGGING_FACE_API_KEY=$api_key\n";

                    $result = file_put_contents($env_file, $env_content);

                    if ($result !== false) {
                        @chmod($env_file, 0600);
                        echo '<div class="alert alert-success">';
                        echo '<strong>✓ Success!</strong> Your .env file has been created!<br>';
                        echo 'Location: <code>' . htmlspecialchars($env_file) . '</code><br>';
                        echo 'API Key: ' . substr($api_key, 0, 20) . '...<br><br>';
                        echo '<a href="hf_monitor.html" style="display: inline-block; background: #667eea; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; margin-top: 10px;">→ Check HF Status</a>';
                        echo '</div>';
                    } else {
                        echo '<div class="alert alert-error"><strong>✗ Error:</strong> Failed to write .env file. Check directory permissions.</div>';
                    }
                }
            }
        }
        ?>

        <?php if (!$env_exists || $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <form method="POST">
            <div class="form-group">
                <label for="api-key">HuggingFace API Key:</label>
                <input
                    type="password"
                    id="api-key"
                    name="api_key"
                    placeholder="hf_..."
                    required
                    autocomplete="off"
                >
                <div class="help-text">
                    Don't have a token?
                    <a href="https://huggingface.co/settings/tokens" target="_blank">Create one here</a>
                </div>
            </div>
            <button type="submit">Create .env File</button>
        </form>
        <?php endif; ?>

        <hr class="divider">

        <div class="manual-setup">
            <h3>📋 Manual Setup (if needed)</h3>
            <p style="font-size: 14px; color: #666; margin-bottom: 15px;">
                If automatic creation doesn't work, create the file manually via Hostinger File Manager:
            </p>
            <ol>
                <li>Log in to Hostinger Control Panel</li>
                <li>Open <strong>File Manager</strong></li>
                <li>Navigate to: <code><?php echo htmlspecialchars($root_dir); ?></code></li>
                <li>Create a new file named: <code>.env</code></li>
                <li>Paste this content (replace YOUR_TOKEN):</li>
            </ol>
            <div style="background: #f0f0f0; padding: 10px; margin-top: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; overflow-x: auto;">
# Hugging Face API Configuration<br>
# IMPORTANT: This file contains sensitive credentials<br>
# NEVER commit this file to git or push it to GitHub<br>
<br>
HUGGING_FACE_API_KEY=YOUR_TOKEN_HERE
            </div>
            <p style="font-size: 12px; color: #999; margin-top: 10px;">
                Replace <code>YOUR_TOKEN_HERE</code> with your actual HuggingFace token.
            </p>
        </div>
    </div>
</body>
</html>
<?php
/**
 * Quick .env Creator for Hostinger
 * Navigate to this file in your browser to create .env automatically
 */

header('Content-Type: text/html; charset=utf-8');

// Determine the root directory
$root_dir = dirname(dirname(__DIR__)); // Go up from admin_includes

echo "<!DOCTYPE html>";
echo "<html><head><title>.env Creator</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }";
echo ".success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }";
echo ".error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }";
echo ".info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; }";
echo ".warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }";
echo "code { background: #f5f5f5; padding: 10px; border-left: 3px solid #667eea; display: block; margin: 10px 0; overflow-x: auto; }";
echo "input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; }";
echo "button { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; }";
echo "button:hover { background: #764ba2; }";
echo "</style></head><body>";

echo "<h1>🔧 Quick .env Creator</h1>";

// Check if form was submitted
$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$api_key = $submitted ? ($_POST['api_key'] ?? '') : '';

// Check if .env already exists
$env_file = $root_dir . '/.env';

if (file_exists($env_file) && !$submitted) {
    echo "<div class='success'>";
    echo "<strong>✓ SUCCESS!</strong><br>";
    echo ".env file already exists at: <code>$env_file</code><br>";
    echo "Size: " . filesize($env_file) . " bytes<br>";
    echo "<a href='hf_monitor.html'>→ Go to HF Monitor</a>";
    echo "</div>";
} elseif ($submitted) {
    // Validate API key
    if (empty($api_key)) {
        echo "<div class='error'>";
        echo "<strong>✗ ERROR:</strong> API key is required";
        echo "</div>";
    } elseif (!preg_match('/^hf_[a-zA-Z0-9_]+$/', $api_key)) {
        echo "<div class='error'>";
        echo "<strong>✗ ERROR:</strong> Invalid API key format. Must start with 'hf_'";
        echo "</div>";
    } else {
        // Create content
        $env_content = "# Hugging Face API Configuration
# IMPORTANT: This file contains sensitive credentials
# NEVER commit this file to git or push it to GitHub
# It's listed in .gitignore for protection

HUGGING_FACE_API_KEY=$api_key
";
        
        // Try to write the file
        if (is_writable($root_dir)) {
            $result = file_put_contents($env_file, $env_content);
            
            if ($result !== false) {
                // Set permissions
                @chmod($env_file, 0600);
                
                echo "<div class='success'>";
                echo "<strong>✓ SUCCESS!</strong><br>";
                echo ".env file created successfully!<br>";
                echo "Location: <code>$env_file</code><br>";
                echo "Size: $result bytes<br>";
                echo "API Key: " . substr($api_key, 0, 20) . "...<br><br>";
                echo "<a href='hf_monitor.html' style='background: #667eea; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block;'>→ Check HF Status</a>";
                echo "</div>";
                
                echo "<div class='info'>";
                echo "<strong>Next Step:</strong> Your journal conversions can now use HuggingFace!<br>";
                echo "Try converting a thesis in the admin panel.";
                echo "</div>";
            } else {
                echo "<div class='error'>";
                echo "<strong>✗ ERROR:</strong> Failed to write .env file<br>";
                echo "Directory: $root_dir<br>";
                echo "Error: Check directory permissions";
                echo "</div>";
            }
        } else {
            echo "<div class='error'>";
            echo "<strong>✗ ERROR:</strong> Directory is not writable<br>";
            echo "Directory: $root_dir<br>";
            echo "Please use Hostinger File Manager to create .env manually<br><br>";
            
            echo "<strong>Manual Steps:</strong>";
            echo "<ol>";
            echo "<li>Log in to Hostinger Control Panel</li>";
            echo "<li>Open File Manager</li>";
            echo "<li>Navigate to: <code>" . htmlspecialchars($root_dir) . "</code></li>";
            echo "<li>Create new file: <code>.env</code></li>";
            echo "<li>Paste this content (replace YOUR_TOKEN):</li>";
            echo "</ol>";
            
            echo "<code># Hugging Face API Configuration
# IMPORTANT: This file contains sensitive credentials
# NEVER commit this file to git or push it to GitHub
# It's listed in .gitignore for protection

HUGGING_FACE_API_KEY=YOUR_TOKEN_HERE</code>";
            
            echo "<p>Then replace YOUR_TOKEN_HERE with your actual HuggingFace API key.</p>";
            echo "</div>";
        }
    }
    
    // Show form again
    echo "<hr style='margin: 30px 0;'>";
    echo "<h3>Or Create Another .env File:</h3>";
} 

// Show form
if (!file_exists($env_file) || $submitted) {
    echo "<form method='POST'>";
    echo "<label><strong>Enter Your HuggingFace API Key:</strong></label><br>";
    echo "<input type='password' name='api_key' placeholder='hf_...' required>";
    echo "<p style='font-size: 0.9em; color: #666;'>";
    echo "Don't have a token? <a href='https://huggingface.co/settings/tokens' target='_blank'>Create one here</a>";
    echo "</p>";
    echo "<button type='submit'>Create .env File</button>";
    echo "</form>";
}

echo "</body></html>";
?>

