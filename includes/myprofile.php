<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

$errors = [];
$success = '';

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user not found, redirect or show error
if (!$user) {
    die("User not found.");
}

// Store the original user data
$original_user = $user;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $errors = [];

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (!empty($email) && $email === $user['email']) {
        $errors[] = "Same email provided";
    }

    if (!empty($email) && $email !== $user['email']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $errors[] = "Email already in use";
        }
    }

    if (empty($errors)) {
        $email_changed = $email !== $user['email'];

        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$email, $_SESSION['user_id']]);

        if ($email_changed && !empty($email)) {
            require_once '../config/smtp.php';
            $verification_token = bin2hex(random_bytes(32));
    $pdo->prepare("UPDATE users SET email_verified = 0, verification_token = ? WHERE id = ?")
       ->execute([$verification_token, $_SESSION['user_id']]);
    
    $verification_link = "https://localhost/fusys/auth/verify_email.php?token=$verification_token";
            $subject = "Your FUSYS email has been updated";
            $body = "Hello {$user['name']},<br><br>This is to confirm that your email address associated with FUSYS has been updated to $email.<br>
            <br>Please verify your new email address:<br>
             <a href='$verification_link'>Verify Email</a><br>
            <br>If you didn't make this change, please contact your manager immediately.<br><br>Thanks,<br>FUSYS Team";
            sendEmail($email, $subject, $body);
        }

        $pdo->prepare("INSERT INTO audit_log (user_id, action, ip_address) VALUES (?, ?, ?)")
            ->execute([$_SESSION['user_id'], "email_updated", $_SERVER['REMOTE_ADDR']]);

        $success = "Email updated successfully!";
        header("Location: myprofile.php");
        exit;
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="dashboard">
    <aside class="sidebar">
        <ul>
            <li><a href="/fusys/<?php echo $_SESSION['user_role']?>/dashboard.php">Dashboard</a></li>
            <?php if ($_SESSION['user_role'] === 'manager'): ?>
                <li><a href="/fusys/<?php echo $_SESSION['user_role']?>/profiles.php">Manage Profiles</a></li>
                <li><a href="/fusys/<?php echo $_SESSION['user_role']?>/cars.php">Manage Cars</a></li>
                <li><a href="/fusys/<?php echo $_SESSION['user_role']?>/requests.php">Deletion Requests</a></li>
                <li class="active"><a href="#">My Profile</a></li>
                <li><a href="/fusys/<?php echo $_SESSION['user_role']?>/user_management.php">User Management</a></li>
            <?php endif; ?>
            
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <li><a href="/fusys/<?php echo $_SESSION['user_role']?>/profiles.php">View All Profiles</a></li>
                <li><a href="/fusys/<?php echo $_SESSION['user_role']?>/cars.php">View All Cars</a></li>
                <li><a href="/fusys/<?php echo $_SESSION['user_role']?>/fuel_requests.php">Fuel Requests</a></li>
                <li><a href="/fusys/<?php echo $_SESSION['user_role']?>/reports.php">Reports</a></li>
                <li class="active"><a href="#">My Profile</a></li>
            <?php endif; ?>
            
            <?php if ($_SESSION['user_role'] === 'driver'): ?>
                <li class="active"><a href="#">My Profile</a></li>
                <li><a href="/fusys/<?php echo $_SESSION['user_role']?>/fuel_request.php">Request Fuel</a></li>
                <li><a href="/fusys/<?php echo $_SESSION['user_role']?>/history.php">Request History</a></li>
            <?php endif; ?>
        </ul>
    </aside>
    
    <main class="content">
        <h1>My Profile</h1>
        
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
        
        <div class="profile-display" style="display: block; width: 100%;">
            <div class="profile-pic-container" style="margin-bottom: 20px;">
                <?php if (!empty($user['profile_pic'] ?? '')): ?>
                    <img src="../uploads/profile_pics/<?php echo htmlspecialchars($user['profile_pic']??''); ?>" alt="Profile Picture" class="profile-pic">
                <?php else: ?>
                    <div class="profile-pic-placeholder">No Image</div>
                <?php endif; ?>
            </div>
            
            <div class="profile-details" style="width: 100%; display: block;">
                <div class="form-group">
                    <label><strong>Full Name</strong></label>
                    <div class="readonly-value" style="padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; color: #000; display: block !important;">
                        <?php echo htmlspecialchars($original_user['name'] ?? ''); ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><strong>Address</strong></label>
                    <div class="readonly-value" style="padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; color: #000; display: block !important;">
                        <?php echo htmlspecialchars($original_user['address'] ?? ''); ?>
                    </div>
                </div>
              
                <div class="form-group">
                    <label><strong>Date of Birth</strong></label>
                    <div class="readonly-value" style="padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; color: #000; display: block !important;">
                        <?php echo htmlspecialchars($original_user['dob'] ?? ''); ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><strong>Phone Number</strong></label>
                    <div class="readonly-value" style="padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; color: #000; display: block !important;">
                        <?php echo htmlspecialchars($original_user['phone'] ?? ''); ?>
                    </div>
                </div>
                
                <form method="post" style="margin-top: 20px;">
                    <div class="form-group">
                        <label for="email"><strong>Email</strong></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($original_user['email'] ?? ''); ?>" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    
                    <button type="submit" class="btn" style="margin-top: 10px;">Update Email</button>
                </form>
            </div>
        </div>
        
        <div class="profile-actions">
            <a href="/fusys/<?php echo $_SESSION['user_role']?>/change_password.php?id=<?php echo $_SESSION['user_id'] ?>" class="btn">Change Password</a>
            <?php if ($_SESSION['user_role'] === 'driver' || $_SESSION['user_role'] === 'admin'): ?>
                <form method="post" action="request_deletion.php" style="display: inline;">
                    <button type="submit" class="btn btn-delete" onclick="return confirm('Are you sure you want to request account deletion?')">Request Account Deletion</button>
                </form>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>