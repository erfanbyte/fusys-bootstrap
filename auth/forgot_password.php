<?php
require_once '../config/database.php';
require_once '../config/smtp.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ? AND deleted_at IS NULL");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Delete any existing tokens for this email
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
        
        // Insert new token
        $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)")->execute([$email, $token]);
        
        // Send email
        $reset_link = "http://localhost/fusys/auth/reset_password.php?token=$token";
        $subject = "Password Reset Request";
        $body = "Hello {$user['name']},<br><br>You requested to reset your password. Please click the following link to reset your password:<br><br><a href='$reset_link'>$reset_link</a><br><br>This link will expire in 1 hour.<br><br>If you didn't request this, please ignore this email.<br><br>Thanks,<br>FUSYS Team";
        
        if (sendEmail($email, $subject, $body)) {
            $success = "Password reset link has been sent to your email";
        } else {
            $error = "Failed to send reset email. Please try again later.";
        }
    } else {
        $error = "No account found with that email address";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - FUSYS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Forgot Password</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php else: ?>
            <form method="post">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <button type="submit" class="btn">Send Reset Link</button>
            </form>
        <?php endif; ?>
        
        <p>Remember your password? <a href="login.php">Login here</a></p>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>