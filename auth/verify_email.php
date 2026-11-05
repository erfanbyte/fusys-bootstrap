<?php
require_once '../config/database.php';
require_once '../config/smtp.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Mark as verified
        $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?")
           ->execute([$user['id']]);
        
        // Send welcome email
        $subject = "Welcome to FUSYS!";
        $body = "Hello {$user['name']},<br><br>
                Your email has been successfully verified and your FUSYS account is now active!<br><br>
                You can now login using your credentials.<br><br>
                Thanks,<br>FUSYS Team";
        
        sendEmail($user['email'], $subject, $body);
        
        $_SESSION['verified_message'] = "Email verified successfully! You can now login.";
        header("Location: ../auth/login.php");
        exit;
    }
}

// Invalid token
$_SESSION['error_message'] = "Invalid verification link.";
header("Location: ../auth/login.php");
exit;
?>