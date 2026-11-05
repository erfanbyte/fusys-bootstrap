<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? AND deleted_at IS NULL");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        $_SESSION['created'] = time();
        
        // Log login action
        $stmt = $pdo->prepare("INSERT INTO audit_log (user_id, action, ip_address) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], 'login', $_SERVER['REMOTE_ADDR']]);
        
        // Redirect based on role
        switch ($user['role']) {
            case 'admin':
                header("Location: /fusys/admin/dashboard.php");
                break;
            case 'manager':
                header("Location: /fusys/manager/dashboard.php");
                break;
            case 'driver':
                header("Location: /fusys/driver/dashboard.php");
                break;
        }
        exit;
    } else {
        $error = "Invalid phone number or password";
    }
}
?>
<?php if (isset($_GET['timeout'])): ?>
<div class="alert alert-warning">
    Your session expired due to inactivity. Please login again.
</div>
<?php endif; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - FUSYS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Login</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="login.php">
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <p>Don't have an account? <a href="register.php">Register here</a></p>
        <p><a href="forgot_password.php">Forgot password?</a></p>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
</body>
</html>