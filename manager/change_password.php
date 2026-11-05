<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SESSION['user_role'] !== 'manager') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header("Location: profiles.php?error=No user specified");
    exit;
}

// Verify user exists
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: profiles.php?error=User not found");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_password) || strlen($new_password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
            ->execute([$hashed_password, $user_id]);
            
        // Log action
        $pdo->prepare("INSERT INTO audit_log (user_id, action, ip_address) VALUES (?, ?, ?)")
            ->execute([$_SESSION['user_id'], "password_reset", $_SERVER['REMOTE_ADDR']]);

        $subject = "Your FUSYS Profile Password Has Been Updated";
        $body = "Hello $user{['name']},<br><br>Your FUSYS password has been updated.<br><br>
        <br>If you didn't make these changes, please contact your manager immediately.<br><br>Thanks,<br>FUSYS Team</br>";
        sendEmail($email, $subject, $body);
        $success = "Password has been reset for " . htmlspecialchars($user['name']);
        header("Location: profiles.php?success=" . urlencode($success));

        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard">
    <?php include __DIR__ . '/../includes/manager_sidebar.php'; ?>
    
    <main class="content">
        <h1>Reset Password for <?= htmlspecialchars($user['name']) ?></h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="new_password">New Password*</label>
                <input type="password" id="new_password" name="new_password" required minlength="8">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password*</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Reset Password</button>
            <a href="profiles.php" class="btn btn-secondary">Cancel</a>
        </form>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>