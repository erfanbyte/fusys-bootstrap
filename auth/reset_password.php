<?php
require_once '../config/database.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Validate token
$stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$stmt->execute([$token]);
$reset_request = $stmt->fetch();

if (!$reset_request) {
    $error = "Invalid or expired reset link";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password
        $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")->execute([$hashed_password, $reset_request['email']]);
        
        // Delete reset token
        $pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);
        
        $success = "Password has been reset successfully. You can now <a href='login.php'>login</a> with your new password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - FUSYS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Reset Password</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <p><a href="forgot_password.php">Request another reset link</a></p>
        <?php elseif ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php else: ?>
            <form method="post">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>