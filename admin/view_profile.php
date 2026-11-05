<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/unauthorized.php");
    exit;
}

// Get the user ID from URL parameter
$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header("Location: profiles.php?error=No user specified");
    exit;
}

// Fetch user data with all fields
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: profiles.php?error=User not found");
    exit;
}

// Format date of birth for display
$formatted_dob = $user['dob'] ? date('d-m-Y', strtotime($user['dob'])) : 'Not specified';

// Calculate age if DOB is available
$age = '';
if ($user['dob']) {
    $dob = new DateTime($user['dob']);
    $now = new DateTime();
    $age = $dob->diff($now)->y;
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
            <li><a href="/fusys/includes/myprofile.php">My Profile</a></li>
        </ul>
    </aside>
    
    <main class="content">
        <div class="profile-header">
            <h1>User Profile: <?= htmlspecialchars($user['name']) ?></h1>
            <div class="profile-actions">
                <a href="profiles.php" class="btn btn-back">Back to List</a>
            </div>
        </div>
        
        <div class="profile-card">
            <div class="profile-section">
                <div class="profile-pic-container">
                    <?php if (!empty($user['profile_pic']) && file_exists("../uploads/profile_pics/" . $user['profile_pic'])): ?>
                        <img src="../uploads/profile_pics/<?= htmlspecialchars($user['profile_pic']) ?>" 
                             alt="Profile Picture" class="profile-pic-large">
                    <?php else: ?>
                        <div class="profile-pic-placeholder-large">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-details">
                    <h2>Basic Information</h2>
                    <div class="detail-row">
                        <span class="detail-label">Full Name:</span>
                        <span class="detail-value"><?= htmlspecialchars($user['name']??'') ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Role:</span>
                        <span class="detail-value badge badge-<?= $user['role'] ?>">
                            <?= ucfirst($user['role']) ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date of Birth:</span>
                        <span class="detail-value"><?= $formatted_dob ?></span>
                    </div>
                    <?php if ($age): ?>
                    <div class="detail-row">
                        <span class="detail-label">Age:</span>
                        <span class="detail-value"><?= $age ?> years</span>
                    </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value">
                            <?= !empty($user['email']) ? htmlspecialchars($user['email']??'') : 'Not provided' ?>
                            <?php if (!empty($user['email_verified'] ?? false)): ?>
                                <span class="verified-badge" title="Email Verified">
                                    <i class="fas fa-check-circle"></i>
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value"><?= htmlspecialchars($user['phone']??'') ?></span>
                    </div>
                </div>
            </div>
            
            <div class="profile-section">
                <h2>Address Information</h2>
                <div class="address-box">
                    <?= !empty($user['address']) ? nl2br(htmlspecialchars($user['address']??'')) : 'Address not provided' ?>
                </div>
            </div>
            
            <div class="profile-section">
                <h2>Account Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Account Created:</span>
                    <span class="detail-value">
                        <?= date('d-m-Y H:i', strtotime($user['created_at']??'')) ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Last Updated:</span>
                    <span class="detail-value">
                        <?= date('d-m-Y H:i', strtotime($user['updated_at']??'')) ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <?php if ($user['deleted_at']??''): ?>
                            <span class="badge badge-danger">Deleted</span>
                        <?php else: ?>
                            <span class="badge badge-success">Active</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>