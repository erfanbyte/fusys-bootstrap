<?php
require_once __DIR__ . '/../config/session.php';
// Always set cache-control headers to prevent caching after logout/session timeout
header("Cache-Control: no-cache, no-store, must-revalidate");   
header("Pragma: no-cache");
header("Expires: 0");
// Get user details if logged in
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    $stmt = $pdo->prepare("SELECT name, role, profile_pic FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $_SESSION['user_name'] = $user['name'] ?? '';
    $_SESSION['user_role'] = $user['role'] ?? '';
    $_SESSION['user_pic'] = $user['profile_pic'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="300;url=/fusys/auth/check_session.php">
    <title>FUSYS - Fuel Usage System</title>
    <link rel="stylesheet" href="/fusys/assets/css/style.css">
    <style>
        /* Temporary styles until CSS loads */
        body { font-family: "Times New Roman", Times, serif; margin: 0; padding: 0; }
        header { background-color: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .logo { display: flex; align-items: center; }
        .logo img { height: 40px; margin-right: 10px; }
        .nav-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        nav ul { display: flex; list-style: none; margin: 0; padding: 0; align-items:center;}
        nav ul li { margin-right: 5px; }
        nav ul li a { color: white; text-decoration: none; }
        nav ul li a:hover { text-decoration: underline; }
        .profile-pic-header img {
            height: 40px ; 
            width: 40px ;
            border-radius: 50%;  
            object-fit: cover;
        }
        .profile-pic-header {
            display: flex; 
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="/fusys/assets/images/logo.jpg" alt="FUSYS Logo">
            <h2>FUSYS</h2>
        </div>
        <nav>
            <ul>
                <?php if (!empty($_SESSION['user_pic'] ?? '')): ?>
                    <li class="profile-pic-header">
                        <img src="../uploads/profile_pics/<?php echo htmlspecialchars($_SESSION['user_pic'] ?? ''); ?>" alt="Profile Picture">
                    </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (!empty($_SESSION['user_name'])): ?>
                        <li><?php echo htmlspecialchars($_SESSION['user_name']); ?></li>
                    <?php endif; ?>
                    
                    <li><a href="/fusys/auth/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="/fusys/index.php">Home</a></li>
                    <li><a href="/fusys/auth/login.php">Login</a></li>
                    <li><a href="/fusys/auth/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>