<?php
require_once '../config/database.php';
require_once '../config/smtp.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $dob = $_POST['dob'];
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = 'driver'; // Default role
    
    // Validate inputs
    $errors = [];
    
    if (empty($name)) $errors[] = "Name is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($dob)) $errors[] = "Date of birth is required";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($phone) || !preg_match('/^\d{10}$/', $phone)) $errors[] = "Valid 10-digit phone number is required";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    
    // Check if phone already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) $errors[] = "Phone number already registered";
    
    // Check if email exists if provided
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = "Email already registered";
    }
    
    // Handle profile picture upload
    $profile_pic = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/profile_pics/';
        $file_ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $file_ext;
        $destination = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
            $profile_pic = $filename;
        }
    }
    
    // After successful registration (in the empty($errors) block)
    if (empty($errors)) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $verification_token = bin2hex(random_bytes(32));
    $email_verified = empty($email) ? 1 : 0; // Auto-verify if no email
    
    $stmt = $pdo->prepare("INSERT INTO users (name, address, dob, profile_pic, email, phone, password, verification_token, email_verified, role) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $address, $dob, $profile_pic, $email, $phone, $hashed_password, $verification_token, $email_verified, $role]);
    
    // Send appropriate email
    if (!empty($email)) {
        // Verification email
        $verification_link = "https://localhost/fusys/auth/verify_email.php?token=$verification_token";
        $subject = "Verify Your Email for FUSYS";
        $body = "Hello $name,<br><br>
                Thank you for registering with FUSYS!<br><br>
                Please click the following link to verify your email address:<br>
                <a href='$verification_link'>Verify Email</a><br><br>
                If you didn't create this account, please ignore this email.<br><br>
                Thanks,<br>FUSYS Team";
        
        sendEmail($email, $subject, $body);
        
        $_SESSION['register_message'] = "Registration successful! Please check your email to verify your account.";
        if ($user_id == $_SESSION['user_id']) {
            echo "<script>
            alert('".addslashes($_SESSION['register_message'])."');
            window.location.href = 'login.php';
            </script>";
            exit;
        }
    } else {
        // No email - send welcome SMS (pseudo-code)
        $welcome_message = "Hello $name, your FUSYS account has been created. Your phone number is your login.";
        // sendSMS($phone, $welcome_message); // Implement your SMS gateway
        
        $_SESSION['register_message'] = "Registration successful! Your phone number is your login.";
        if ($user_id == $_SESSION['user_id']) {
            echo "<script>
            alert('".addslashes($welcome_message)."');
            alert('".addslashes($_SESSION['register_message'])."');
            window.location.href = 'login.php';
            </script>";
            exit;
        }
    }
    
    header("Location: login.php");
    exit;
}
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - FUSYS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Register</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" required>
            </div>
            
            <div class="form-group">
                <label for="profile_pic">Profile Picture</label>
                <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
            </div>
            
            <div class="form-group">
                <label for="email">Email (optional)</label>
                <input type="email" id="email" name="email">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn">Register</button>
        </form>
        
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>