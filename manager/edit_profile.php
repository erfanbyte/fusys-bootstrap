<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$errors = [];
$success = '';

if ($_SESSION['user_role'] !== 'manager') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

// Get the user ID to edit
$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header("Location: profiles.php?error=No user specified");
    exit;
}

// Define default values for ALL possible fields
$defaultUser = [
    'id' => '',
    'name' => '',
    'address' => '',
    'dob' => '',
    'email' => '',
    'phone' => '',
    'role' => 'driver',
    'profile_pic' => ''
];

try {
    // Fetch user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$user_id]);
    $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Merge with defaults - this guarantees all keys exist
    $user = array_merge($defaultUser, $dbUser ?: []);
    
    // Additional validation for critical fields
    $user['name'] = $user['name'] ?? '';
    $user['phone'] = $user['phone'] ?? '';
    $user['role'] = $user['role'] ?? 'driver';

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $dob = $_POST['dob'];
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    
    // Validation (same as before)
    if (empty($name)) $errors[] = "Name is required";
    if (empty($phone) || !preg_match('/^\d{10}$/', $phone)) $errors[] = "Valid 10-digit phone number is required";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    
    if (empty($errors)) {
        // Update user in database
        $stmt = $pdo->prepare("UPDATE users SET 
            name = ?, address = ?, dob = ?, email = ?, phone = ?, role = ? 
            WHERE id = ?");
        $stmt->execute([$name, $address, $dob, $email, $phone, $role, $user_id]);
        
        // Handle profile picture upload if needed
        $profile_pic = $user['profile_pic']; 
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/profile_pics/';
        $file_ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $file_ext;
        $destination = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
            // Delete old profile pic if it exists
            if ($profile_pic && file_exists($upload_dir . $profile_pic)) {
                unlink($upload_dir . $profile_pic);
            }
            $profile_pic = $filename;
            // Update profile_pic in the database
            $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
            $stmt->execute([$profile_pic, $user_id]);
        }
    }
        
        $success = "Profile updated successfully!";
        header("Location: profiles.php?success=" . urlencode($success));
        exit;
    }
}



?>
<link rel="stylesheet" href="/fusys/assets/css/style.css">
<?php include '../includes/header.php'; ?>
<div class="dashboard">
    <?php include __DIR__ . '/../includes/manager_sidebar.php'; ?>
    
    <main class="content">
        <h1>Edit Profile: <?= htmlspecialchars($user['name'] ?? 'Unknown') ?></h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data">
            <!-- Profile Picture -->
            <div class="profile-pic-container">
                <?php if (!empty($user['profile_pic']) && file_exists("../uploads/profile_pics/" . $user['profile_pic'])): ?>
                    <img src="../uploads/profile_pics/<?= htmlspecialchars($user['profile_pic'] ?? '') ?>" 
                         alt="Profile Picture" class="profile-pic">
                <?php else: ?>
                    <div class="profile-pic-placeholder">No Image</div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="profile_pic">Change Profile Picture</label>
                    <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
                </div>
            </div>
            
            <!-- Name Field -->
            <div class="form-group">
                <label for="name">Full Name*</label>
                <input type="text" id="name" name="name" 
                       value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
            </div>
            
            <!-- Address Field -->
            <div class="form-group">
                <label for="address">Address*</label>
                <textarea id="address" name="address" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
            </div>
            
            <!-- Date of Birth -->
            <div class="form-group">
                <label for="dob">Date of Birth*</label>
                <input type="date" id="dob" name="dob" 
                       value="<?= htmlspecialchars($user['dob'] ?? '') ?>" required>
            </div>
            
            <!-- Email Field -->
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?= htmlspecialchars($user['email'] ?? '') ?>">
            </div>
            
            <!-- Phone Field -->
            <div class="form-group">
                <label for="phone">Phone Number*</label>
                <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" 
                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
            </div>
            
            <!-- Role Field -->
            <div class="form-group">
                <label for="role">Role*</label>
                <select id="role" name="role" required>
                    <option value="driver" <?= $user['role'] === 'driver' ? 'selected' : '' ?>>Driver</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="manager" <?= $user['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
                </select>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Profile</button>
                <a href="profiles.php" class="btn btn-secondary">Cancel</a>
                <a href="change_password.php?id=<?= $user_id ?>" class="btn btn-warning">Reset Password</a>
            </div>
        </form>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>