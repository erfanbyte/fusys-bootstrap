<?php
require_once '../config/database.php';
require_once '../config/smtp.php';
require_once '../includes/auth_check.php';
if ($_SESSION['user_role'] !== 'driver') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($current_password)) $errors[] = "Current password is required";
    if (empty($new_password)) $errors[] = "New password is required";
    if (strlen($new_password) < 8) $errors[] = "New password must be at least 8 characters";
    if ($new_password !== $confirm_password) $errors[] = "New passwords do not match";
    
    if (empty($errors)) {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password, name, email FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($current_password, $user['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed_password, $_SESSION['user_id']]);
            
            // Log action
            $pdo->prepare("INSERT INTO audit_log (user_id, action, ip_address) VALUES (?, ?, ?)")
                ->execute([$_SESSION['user_id'], "password_changed", $_SERVER['REMOTE_ADDR']]);
            
            // Send email notification
            if (!empty($user['email'])) {
                $subject = "Your FUSYS Profile Password Has Been Updated";
                $body = "Hello {$user['name']},<br><br>
                        Your FUSYS password has been updated.<br><br>
                        If you didn't make these changes, please contact your manager immediately.<br><br>
                        Thanks,<br>FUSYS Team";
                sendEmail($user['email'], $subject, $body);
            }
            
            $success = "Password changed successfully!";
        } else {
            $errors[] = "Current password is incorrect";
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
    <aside class="sidebar">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="/fusys/includes/myprofile.php">My Profile</a></li>
            <li><a href="fuel_request.php">Request Fuel</a></li>
            <li><a href="history.php">Request History</a></li>
        </ul>
    </aside>
    
    <main class="content">
        <h1>Change Password</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required minlength="8">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn">Change Password</button>
            <a href="/fusys/includes/myprofile.php" class="btn btn-cancel">Cancel</a>
        </form>
    </main>
</div>

<?php include '../includes/footer.php'; ?>