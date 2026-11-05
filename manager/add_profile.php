<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SESSION['user_role'] !== 'manager') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $dob = $_POST['dob'];
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Validation
    if (empty($name)) $errors[] = "Name is required";
    if (empty($phone) || !preg_match('/^\d{10}$/', $phone)) $errors[] = "Valid 10-digit phone number is required";
    if (empty($password) || strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    
    // Check if phone exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) $errors[] = "Phone number already registered";
    
    // Check if email exists if provided
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = "Email already registered";
    }
    
    // After successful profile addition (in the empty($errors) block)
if (empty($errors)) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $verification_token = !empty($email) ? bin2hex(random_bytes(32)) : null;
    $is_verified = empty($email) ? 1 : 0;
    
    $stmt = $pdo->prepare("INSERT INTO users (name, address, dob, email, phone, password, role, verification_token, is_verified) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $address, $dob, $email, $phone, $hashed_password, $role, $verification_token, $is_verified]);
    
    $new_user_id = $pdo->lastInsertId();
    
    // Log action
    $pdo->prepare("INSERT INTO audit_log (user_id, action, ip_address) VALUES (?, ?, ?)")
        ->execute([$_SESSION['user_id'], "profile_added:$new_user_id", $_SERVER['REMOTE_ADDR']]);
    
    // Handle email verification if email exists
    if (!empty($email)) {
        $verification_link = "https://yourdomain.com/fusys/auth/verify_email.php?token=$verification_token";
        $subject = "Verify Your FUSYS Account";
        $body = "Hello $name,<br><br>
                A FUSYS account has been created for you by an administrator.<br><br>
                Please click the following link to verify your email address:<br>
                <a href='$verification_link'>Verify Email</a><br><br>
                Your temporary password is: $password<br>
                Please change it after logging in.<br><br>
                Thanks,<br>FUSYS Team";
        
        sendEmail($email, $subject, $body);
        
        $success = "Profile added successfully! Verification email sent to $email";
    } else {
        $success = "Profile added successfully!";
    }
    
    header("Location: profiles.php?success=" . urlencode($success));
    exit;
}
}

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard">
    <?php include __DIR__ . '/../includes/manager_sidebar.php'; ?>
    
    <main class="content">
        <h1>Add New Profile</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="name">Full Name*</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="address">Address*</label>
                <textarea id="address" name="address" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="dob">Date of Birth*</label>
                <input type="date" id="dob" name="dob" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number*</label>
                <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password*</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>
            
            <div class="form-group">
                <label for="role">Role*</label>
                <select id="role" name="role" required>
                    <option value="driver">Driver</option>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Add Profile</button>
            <a href="profiles.php" class="btn btn-secondary">Cancel</a>
        </form>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>