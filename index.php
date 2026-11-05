<link rel="stylesheet" href="/fusys/assets/css/style_index.css">
<?php 
require_once 'config/session.php'; 
include 'includes/header.php'; 
?>

<div class="container_index">
    <h1 class="subtitle_index">Fuel Usage System</h1>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="welcome-message">
            <p>You are logged in as <strong><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'user'); ?></strong>.</p>
            <a href="/fusys/<?php echo $_SESSION['user_role'] ?>/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
        </div>
    <?php else: ?>
        <div class="auth-options">
            <a href="/fusys/auth/login.php" class="btn btn-primary">Login</a>
            <a href="/fusys/auth/register.php" class="btn btn-secondary">Register</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>