<?php
// reset_password.php
require_once 'db_includes/db_connect.php';

$token = $_GET['token'] ?? '';
$email = '';
$error_title = '';
$error_message = '';
$error_link_url = '';
$error_link_text = '';
$fatal_error = false;

if (empty($token)) {
    $error_title = 'Invalid Link';
    $error_message = 'The password reset link is invalid or missing.';
    $error_link_url = 'index.php';
    $error_link_text = 'Go back to login';
    $fatal_error = true;
} else {
    // Decode the token
    $decoded = base64_decode($token);
    if (strpos($decoded, 'citas_salt_') !== 0) {
        $error_title = 'Invalid Link';
        $error_message = 'The password reset link is invalid or has been tampered with.';
        $error_link_url = 'index.php';
        $error_link_text = 'Go back to login';
        $fatal_error = true;
    } else {
        $payload_json = substr($decoded, 11); // Remove 'citas_salt_'
        $payload = json_decode($payload_json, true);
        
        if (!$payload || !isset($payload['email']) || !isset($payload['expires'])) {
            $error_title = 'Invalid Format';
            $error_message = 'The password reset link format is incorrect.';
            $error_link_url = 'index.php';
            $error_link_text = 'Go back to login';
            $fatal_error = true;
        } else if (time() > $payload['expires']) {
            $error_title = 'Link Expired';
            $error_message = 'This password reset link has expired. For your security, reset links are only valid for 1 hour.';
            $error_link_url = 'forgot_password.php';
            $error_link_text = 'Request a new link';
            $fatal_error = true;
        } else {
            $email = $payload['email'];

            $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $error_title = 'Account Not Found';
                $error_message = 'We could not find an account associated with this reset link.';
                $error_link_url = 'index.php';
                $error_link_text = 'Go back to login';
                $fatal_error = true;
            } else {
                $user = $result->fetch_assoc();
                $current_password_hash = $user['password'];

                if (isset($payload['hash_prefix']) && substr($current_password_hash, 0, 10) !== $payload['hash_prefix']) {
                    $error_title = 'Link Already Used';
                    $error_message = 'This password reset link has already been used and is no longer valid. If you still need to reset your password, please request a new link.';
                    $error_link_url = 'forgot_password.php';
                    $error_link_text = 'Request a new link';
                    $fatal_error = true;
                }
            }
        }
    }
}

if ($fatal_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit;
}

if (!$fatal_error) {
    // Handle AJAX Request for actual password update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_password') {
        header('Content-Type: application/json');
        $new_password = $_POST['new_password'] ?? '';
        
        if (strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit;
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Create New Password - CITAS Smart Archive</title>
    <link rel="icon" type="image/png" href="img/CITAS_logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index_redesign.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #FF3300;
            padding: 1rem;
        }
        .reset-wrapper {
            background: var(--white);
            border: 1px solid var(--gray-100);
            border-radius: var(--radius);
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }
        .header-icon {
            width: 56px;
            height: 56px;
            background: var(--orange-50);
            color: var(--orange);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1.25rem;
            box-shadow: 0 4px 10px rgba(240, 104, 32, 0.1);
        }
        .reset-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        .reset-subtitle {
            text-align: center;
            color: var(--gray-500);
            font-size: 0.95rem;
            margin-bottom: 2rem;
            font-weight: 500;
        }
        /* Loading & Success States */
        #loadingState, #successState { display: none; text-align: center; padding: 2rem 0; }
        .spinner-icon { font-size: 2.5rem; color: var(--orange); margin-bottom: 1rem; animation: spin 1s linear infinite; }
        .success-icon { width: 70px; height: 70px; background: rgba(34, 197, 94, 0.1); color: #16A34A; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.25rem; animation: successPulse 0.5s ease; }
        
        /* Error State */
        .error-icon { width: 70px; height: 70px; background: rgba(220, 53, 69, 0.1); color: #DC3545; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.25rem; animation: errorShake 0.5s ease; }
        @keyframes errorShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
        }

        .auth-form-group label { color: var(--dark-2); }
        
        /* Password Strength Indicator */
        .password-strength { height: 4px; background: var(--gray-100); border-radius: 2px; margin-top: 8px; overflow: hidden; display: none; }
        .strength-bar { height: 100%; width: 0%; transition: all 0.3s ease; }
    </style>
</head>
<body>

    <div class="reset-wrapper">
        
        <?php if ($fatal_error): ?>
        <!-- Fatal Error State -->
        <div id="fatalErrorState" style="text-align: center; padding: 1rem 0;">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 class="reset-title"><?php echo htmlspecialchars($error_title); ?></h2>
            <p class="reset-subtitle" style="margin-bottom: 2.5rem; color: var(--gray-600); line-height: 1.6;">
                <?php echo htmlspecialchars($error_message); ?>
            </p>
            <a href="<?php echo htmlspecialchars($error_link_url); ?>" class="auth-submit-btn" style="text-decoration: none; display: block; text-align: center;">
                <i class="fas <?php echo strpos($error_link_url, 'forgot') !== false ? 'fa-redo' : 'fa-arrow-left'; ?> me-2"></i><?php echo htmlspecialchars($error_link_text); ?>
            </a>
        </div>
        <?php else: ?>
        
        <!-- Form State -->
        <div id="formState">
            <div class="header-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h2 class="reset-title">Create New Password</h2>
            <p class="reset-subtitle">Your new password must be different from previous used passwords.</p>
            
            <div id="errorMessage" class="alert-message alert-danger"></div>
            
            <form id="resetForm" onsubmit="handleResetSubmit(event)">
                <div class="auth-form-group" style="position: relative;">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Min. 8 characters" required oninput="checkStrength()">
                    <div class="password-strength" id="strengthContainer"><div class="strength-bar" id="strengthBar"></div></div>
                </div>
                
                <div class="auth-form-group mt-3">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat new password" required>
                </div>
                
                <button type="submit" class="auth-submit-btn" style="margin-top: 1.75rem;">
                    <i class="fas fa-save me-2"></i>Reset Password
                </button>
            </form>
        </div>

        <!-- Loading State -->
        <div id="loadingState">
            <i class="fas fa-circle-notch spinner-icon"></i>
            <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--dark);">Updating Security...</h3>
            <p style="color: var(--gray-500); font-size: 0.9rem;">Saving your new password securely.</p>
        </div>

        <!-- Success State -->
        <div id="successState">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="reset-title">Password Reset!</h2>
            <p class="reset-subtitle" style="margin-bottom: 2rem;">
                Your password has been successfully updated. You can now use it to log in.
            </p>
            <a href="index.php" class="auth-submit-btn" style="text-decoration: none; display: block; text-align: center;">
                <i class="fas fa-sign-in-alt me-2"></i>Continue to Login
            </a>
        </div>

        <?php endif; ?>

    </div>

    <script>
        function checkStrength() {
            const pass = document.getElementById('new_password').value;
            const container = document.getElementById('strengthContainer');
            const bar = document.getElementById('strengthBar');
            
            if (pass.length === 0) {
                container.style.display = 'none';
                return;
            }
            container.style.display = 'block';
            
            let strength = 0;
            if (pass.length > 5) strength += 33;
            if (pass.length > 7 && pass.match(/[A-Z]/) && pass.match(/[0-9]/)) strength += 33;
            if (pass.length > 8 && pass.match(/[^A-Za-z0-9]/)) strength += 34;
            
            bar.style.width = strength + '%';
            if (strength <= 33) bar.style.background = 'var(--red)';
            else if (strength <= 66) bar.style.background = 'var(--orange)';
            else bar.style.background = 'var(--green)';
        }

        async function handleResetSubmit(e) {
            e.preventDefault();
            const pass1 = document.getElementById('new_password').value;
            const pass2 = document.getElementById('confirm_password').value;
            const errorMsg = document.getElementById('errorMessage');
            
            if (pass1.length < 6) {
                errorMsg.style.display = 'block';
                errorMsg.textContent = "Password must be at least 6 characters long.";
                return;
            }
            if (pass1 !== pass2) {
                errorMsg.style.display = 'block';
                errorMsg.textContent = "Passwords do not match.";
                return;
            }
            
            // Hide error and show loading
            errorMsg.style.display = 'none';
            document.getElementById('formState').style.display = 'none';
            document.getElementById('loadingState').style.display = 'block';

            try {
                const formData = new FormData();
                formData.append('action', 'update_password');
                formData.append('new_password', pass1);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                setTimeout(() => {
                    document.getElementById('loadingState').style.display = 'none';
                    if (data.success) {
                        document.getElementById('successState').style.display = 'block';
                    } else {
                        document.getElementById('formState').style.display = 'block';
                        errorMsg.style.display = 'block';
                        errorMsg.textContent = data.message || "Failed to update password.";
                    }
                }, 1000);
            } catch (err) {
                document.getElementById('loadingState').style.display = 'none';
                document.getElementById('formState').style.display = 'block';
                errorMsg.style.display = 'block';
                errorMsg.textContent = "Network error. Please try again.";
            }
        }
    </script>
</body>
</html>
