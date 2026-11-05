<?php
require_once '../includes/auth_check.php';
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

$user_id = $_GET['id'] ?? $_SESSION['user_id'];
$errors = [];
$success = '';

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "<script>
            alert('User not found');
            window.location.href = '../includes/myprofile.php';
          </script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($new_password)) $errors[] = "New password is required";
    if (strlen($new_password) < 8) $errors[] = "New password must be at least 8 characters";
    if ($new_password !== $confirm_password) $errors[] = "New passwords do not match";
    
    if (empty($errors)) {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed_password, $user_id]);
        
        // Log action
        $pdo->prepare("INSERT INTO audit_log (user_id, action, ip_address, target_user_id) VALUES (?, ?, ?, ?)")
            ->execute([$_SESSION['user_id'], "password_changed_by_admin", $_SERVER['REMOTE_ADDR'], $user_id]);
        $subject = "Your FUSYS Profile Password Has Been Updated";
        $body = "Hello $user{['name']},<br><br>Your FUSYS password has been updated.<br><br>
        <br>If you didn't make these changes, please contact your manager immediately.<br><br>Thanks,<br>FUSYS Team</br>";
        sendEmail($email, $subject, $body);
        $success = "Password changed successfully!";
        
        // If admin changed their own password, redirect to profile
        if ($user_id == $_SESSION['user_id']) {
            echo "<script>
            alert('".addslashes($success)."');
            window.location.href = '../includes/myprofile.php';
            </script>";
            exit;
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard">
    <aside class="sidebar">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="profiles.php">View All Profiles</a></li>
            <li><a href="cars.php">View All Cars</a></li>
            <li><a href="fuel_requests.php">Fuel Requests</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li class="active"><a href="profile.php">My Profile</a></li>
        </ul>
    </aside>
    
    <main class="content">
        <h1>Change Password</h1>
        <h2><?php echo htmlspecialchars($user['name']); ?></h2>
        
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
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required minlength="8">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn">Change Password</button>
            <a href="<?php echo $user_id == $_SESSION['user_id'] ? '../includes/myprofile.php' : '../includes/myprofiles.php'; ?>" class="btn btn-cancel">Cancel</a>
        </form>
    </main>
</div>

<?php include '../includes/footer.php'; ?>