<?php
/**
 * Simple Login Test Page
 * Use this to test login functionality in isolation
 */

require_once 'db_includes/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Login Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            padding: 50px 0;
        }
        .test-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .console-log {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
        }
        .console-log div {
            margin: 5px 0;
        }
        .error { color: #f48771; }
        .success { color: #89d185; }
        .info { color: #4fc1ff; }
    </style>
</head>
<body>
    <div class="test-container">
        <h2 class="text-center mb-4">🧪 Simple Login Test</h2>
        
        <?php if (is_logged_in()): ?>
            <div class="alert alert-success">
                <h4>✓ You are logged in!</h4>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                <p><strong>Student ID:</strong> <?php echo htmlspecialchars($_SESSION['student_id']); ?></p>
                <p><strong>Role:</strong> <?php echo htmlspecialchars($_SESSION['user_role']); ?></p>
                <a href="#" class="btn btn-danger" onclick="handleLogout(event)">Logout</a>
                <a href="index.php" class="btn btn-primary">Go to Homepage</a>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <strong>Test Credentials:</strong><br>
                Admin: ADMIN001 / admin123<br>
                Student: 2020-00001 / student123
            </div>
            
            <div id="messageDiv" class="alert" style="display:none;"></div>
            
            <form id="testLoginForm">
                <div class="mb-3">
                    <label class="form-label">Student ID</label>
                    <input type="text" class="form-control" name="student_id" value="ADMIN001" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" value="admin123" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Test Login</button>
            </form>
            
            <div class="console-log" id="consoleLog">
                <div class="info">Console Output:</div>
            </div>
        <?php endif; ?>
    </div>

    <script>window.USE_CUSTOM_LOGIN_HANDLER = true;</script>
    <script src="script.js"></script>
    <script>
        const consoleLog = document.getElementById('consoleLog');
        
        function log(message, type = 'info') {
            const div = document.createElement('div');
            div.className = type;
            div.textContent = new Date().toLocaleTimeString() + ' - ' + message;
            consoleLog.appendChild(div);
            consoleLog.scrollTop = consoleLog.scrollHeight;
            console.log(message);
        }

        document.getElementById('testLoginForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            log('Form submitted', 'info');
            
            const formData = new FormData(this);
            const messageDiv = document.getElementById('messageDiv');
            
            log('Student ID: ' + formData.get('student_id'), 'info');
            log('Password: ***', 'info');
            log('Sending request to login.php...', 'info');
            
            fetch('client_includes/login.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                log('Response received - Status: ' + response.status, 'info');
                log('Content-Type: ' + response.headers.get('content-type'), 'info');
                return response.text();
            })
            .then(text => {
                log('Raw response: ' + text.substring(0, 200), 'info');
                
                let data;
                try {
                    data = JSON.parse(text);
                    log('JSON parsed successfully', 'success');
                } catch (e) {
                    log('JSON parse error: ' + e.message, 'error');
                    throw new Error('Invalid JSON response');
                }
                
                if (data.success) {
                    log('Login successful!', 'success');
                    log('Redirect to: ' + data.redirect, 'success');
                    
                    messageDiv.className = 'alert alert-success';
                    messageDiv.textContent = data.message;
                    messageDiv.style.display = 'block';
                    
                    setTimeout(() => {
                        log('Redirecting...', 'info');
                        window.location.reload();
                    }, 2000);
                } else {
                    log('Login failed: ' + data.message, 'error');
                    messageDiv.className = 'alert alert-danger';
                    messageDiv.textContent = data.message;
                    messageDiv.style.display = 'block';
                }
            })
            .catch(error => {
                log('Error: ' + error.message, 'error');
                messageDiv.className = 'alert alert-danger';
                messageDiv.textContent = 'Error: ' + error.message;
                messageDiv.style.display = 'block';
            });
        });
        
        log('Page loaded - Ready to test', 'success');
    </script>
</body>
</html>