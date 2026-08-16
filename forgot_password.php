<?php
require_once 'db_includes/db_connect.php';
require_once 'mail_includes/smtp_config.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle AJAX Request for Password Reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset') {
    header('Content-Type: application/json');
    $email = trim($_POST['email'] ?? '');
    
    if(empty($email)){
        echo json_encode(['success' => false, 'message' => 'Please enter your email address.']);
        exit;
    }

    // Check if email exists in the system
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Create a secure verifiable token containing the email and expiry
        $payload = json_encode([
            'email' => $email,
            'hash_prefix' => substr($user['password'], 0, 10),
            'expires' => time() + 3600 // 1 hour expiry
        ]);
        
        $token = base64_encode('citas_salt_' . $payload);
        $reset_link = "https://citas-smart-archive.com/reset_password.php?token=" . $token;
        
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;

            // Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Reset Your CITAS Smart Archive Password';
            
            $mailContent = "
            <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; border: 1px solid #e0e0e0;'>
                <div style='text-align: center; margin-bottom: 25px;'>
                    <img src='https://citas-smart-archive.com/img/CITAS_logo.png' alt='CITAS Logo' style='width: 60px; height: 60px; border-radius: 50%; display: block; margin: 0 auto 10px;'>
                    <h2 style='color: #E05520; margin: 0; font-size: 24px;'>CITAS Smart Archive</h2>
                </div>
                <h3 style='color: #1A0F08; font-size: 20px; margin-bottom: 15px;'>Password Reset Request</h3>
                <p style='color: #6B5B52; line-height: 1.6; font-size: 15px;'>You recently requested to reset your password for your CITAS Smart Archive account. Click the button below to create a new password.</p>
                <div style='text-align: center; margin: 35px 0;'>
                    <a href='{$reset_link}' style='background: #E05520; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 50px; font-weight: bold; font-size: 16px; display: inline-block;'>Reset My Password</a>
                </div>
                <p style='color: #6B5B52; font-size: 14px; line-height: 1.6;'>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
                <hr style='border: none; border-top: 1px solid #e0e0e0; margin: 25px 0;'>
                <p style='text-align: center; color: #9C897E; font-size: 12px;'>&copy; " . date('Y') . " CITAS Smart Archive. All rights reserved.</p>
            </div>
            ";
            
            $mail->Body = $mailContent;
            $mail->send();
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => "Email could not be sent. Please contact the administrator."]);
        }
    } else {
        // Email not found
        echo json_encode(['success' => false, 'message' => 'We could not find any account associated with this email address.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Forgot Password - CITAS Smart Archive</title>
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
        .forgot-wrapper {
            background: var(--white);
            border: 1px solid var(--gray-100);
            border-radius: var(--radius);
            width: 100%;
            max-width: 460px;
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
        .forgot-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        .forgot-subtitle {
            text-align: center;
            color: var(--gray-500);
            font-size: 0.95rem;
            margin-bottom: 2rem;
            font-weight: 500;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-500);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            margin-top: 1.5rem;
            transition: color 0.2s;
            width: 100%;
            justify-content: center;
        }
        .back-link:hover {
            color: var(--dark);
        }
        /* Loading Overlay */
        #loadingState {
            display: none;
            text-align: center;
            padding: 2rem 0;
        }
        .spinner-icon {
            font-size: 2.5rem;
            color: var(--orange);
            margin-bottom: 1rem;
            animation: spin 1s linear infinite;
        }
        /* Success State */
        #successState {
            display: none;
            text-align: center;
            padding: 1rem 0;
            animation: modalSlideUp 0.4s ease;
        }
        .success-icon {
            width: 70px;
            height: 70px;
            background: rgba(34, 197, 94, 0.1);
            color: #16A34A;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.25rem;
            animation: successPulse 0.5s ease;
        }
        .email-highlight {
            font-weight: 700;
            color: var(--dark);
        }
        .auth-form-group label {
            color: var(--dark-2);
        }
    </style>
</head>
<body>

    <div class="forgot-wrapper">
        
        <!-- Default State: Form -->
        <div id="formState">
            <div class="header-icon">
                <i class="fas fa-key"></i>
            </div>
            <h2 class="forgot-title">Forgot Password?</h2>
            <p class="forgot-subtitle">Enter your registered email address to receive password reset instructions.</p>
            
            <div id="errorMessage" class="alert-message alert-danger"></div>
            
            <form id="forgotForm" onsubmit="handleForgotSubmit(event)">
                <div class="auth-form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="e.g., student@gmail.com" required>
                </div>
                <button type="submit" class="auth-submit-btn" id="submitBtn">
                    <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                </button>
            </form>
            
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>

        <!-- Loading State -->
        <div id="loadingState">
            <i class="fas fa-circle-notch spinner-icon"></i>
            <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--dark);">Verifying Account...</h3>
            <p style="color: var(--gray-500); font-size: 0.9rem;">Please wait while we check our system.</p>
        </div>

        <!-- Success State -->
        <div id="successState">
            <div class="success-icon">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <h2 class="forgot-title">Check Your Inbox</h2>
            <p class="forgot-subtitle" style="margin-bottom: 2rem;">
                We've sent a secure password reset link to <br>
                <span id="sentEmailAddress" class="email-highlight">your email</span>.
            </p>
            
            <a href="https://mail.google.com/" target="_blank" id="openMailBtn" class="auth-submit-btn" style="text-decoration: none; display: block; text-align: center; margin-bottom: 1rem;">
                Open Gmail Inbox
            </a>
            
            <p style="margin-top: 1.5rem; font-size: 0.85rem; color: var(--gray-500);">
                Didn't receive the email? <a href="#" onclick="resetForm(event)" style="color: var(--orange); font-weight: 600; text-decoration: none;">Try again</a>
            </p>
            
            <a href="index.php" class="back-link" style="margin-top: 0.5rem;">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>

    </div>

    <script>
        async function handleForgotSubmit(e) {
            e.preventDefault();
            const emailInput = document.getElementById('email').value.trim();
            const errorMsg = document.getElementById('errorMessage');
            
            if (!emailInput) {
                errorMsg.style.display = 'block';
                errorMsg.textContent = "Please enter your email address.";
                return;
            }
            
            // Hide error and show loading
            errorMsg.style.display = 'none';
            document.getElementById('formState').style.display = 'none';
            document.getElementById('loadingState').style.display = 'block';

            try {
                const formData = new FormData();
                formData.append('action', 'reset');
                formData.append('email', emailInput);

                const response = await fetch('forgot_password.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                setTimeout(() => {
                    document.getElementById('loadingState').style.display = 'none';
                    
                    if (data.success) {
                        // Show success state
                        document.getElementById('successState').style.display = 'block';
                        document.getElementById('sentEmailAddress').textContent = emailInput;
                    } else {
                        // Return to form and show error
                        document.getElementById('formState').style.display = 'block';
                        errorMsg.style.display = 'block';
                        errorMsg.textContent = data.message;
                    }
                }, 1200);
                
            } catch (err) {
                document.getElementById('loadingState').style.display = 'none';
                document.getElementById('formState').style.display = 'block';
                errorMsg.style.display = 'block';
                errorMsg.textContent = "A network error occurred. Please try again.";
            }
        }

        function resetForm(e) {
            e.preventDefault();
            document.getElementById('successState').style.display = 'none';
            document.getElementById('formState').style.display = 'block';
            document.getElementById('email').value = '';
            document.getElementById('email').focus();
        }
    </script>
</body>
</html>
