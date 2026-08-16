<?php
/**
 * User Email Verification Script
 * CITAS Smart Archive System
 */
require_once 'db_includes/db_connect.php';

$message = "";
$success = false;

if (isset($_GET['code']) && !empty($_GET['code'])) {
    $code = sanitize_input($_GET['code']);
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE verification_code = ? AND is_verified = 0");
    if ($stmt) {
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Update user to be verified and clear the code
            $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_code = NULL WHERE id = ?");
            $update->bind_param("i", $user['id']);
            if ($update->execute()) {
                $success = true;
                $message = "Your email has been verified successfully! You can now log in to your account.";
            } else {
                $message = "Failed to update verification status. Please try again later.";
            }
            $update->close();
        } else {
            $message = "Invalid or expired verification link. Your account might already be verified.";
        }
        $stmt->close();
    } else {
        $message = "Database error. Please try again later.";
    }
} else {
    $message = "No verification code provided in the URL.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - CITAS Smart Archive</title>
    <link rel="icon" type="image/png" href="img/CITAS_logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #FF3300;
            --bg-card: #ffffff;
            --primary: #FF3300;
            --secondary:  #DC3545;
            --text-muted: #555555;
        }
        body {
            margin: 0;
            padding: 0;
            background-color: var(--bg-dark);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #333;
        }
        .verify-card {
            background: var(--bg-card);
            border: none;
            border-radius: 15px;
            padding: 2.5rem 2rem;
            text-align: center;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 15px 50px rgba(0,0,0,0.2);
            position: relative;
            animation: slideUp 0.5s ease-out;
        }
        .logo-container {
            position: absolute;
            top: -45px;
            left: 50%;
            transform: translateX(-50%);
            background: #ffffff;
            padding: 8px;
            border-radius: 50%;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .logo-container img {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            display: block;
        }
        h2 {
            margin-top: 3rem;
            margin-bottom: 1rem;
            font-weight: 700;
            font-size: 1.75rem;
            color: #111;
        }
        p {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .btn-continue {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
            box-sizing: border-box;
            border: none;
            cursor: pointer;
        }
        .btn-continue:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,51,0,0.3);
            color: #fff;
        }
        .icon-success {
            color: #48bb78;
            font-size: 3.5rem;
            margin-bottom: 0.5rem;
            display: <?php echo $success ? 'inline-block' : 'none'; ?>;
            background: #ffffff;
            border-radius: 50%;
            line-height: 1;
        }
        .icon-error {
            color: #FF3300;
            font-size: 3.5rem;
            margin-bottom: 0.5rem;
            display: <?php echo !$success ? 'inline-block' : 'none'; ?>;
            background: #ffffff;
            border-radius: 50%;
            line-height: 1;
        }
        @keyframes slideUp {
            from { transform: translateY(40px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>

    <div class="verify-card">
        <div class="logo-container">
            <img src="img/CITAS_logo.png" alt="CITAS Logo">
        </div>
        
        <i class="fas fa-check-circle icon-success"></i>
        <i class="fas fa-exclamation-circle icon-error"></i>
        
        <h2><?php echo $success ? 'Verification Successful!' : 'Verification Failed'; ?></h2>
        <p><?php echo htmlspecialchars($message); ?></p>
        
        <a href="index.php" class="btn-continue">Go to Login</a>
    </div>

</body>
</html>
