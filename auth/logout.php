<?php
// /fusys/auth/logout.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

session_start();

// Log logout action if user was logged in
if (isset($_SESSION['user_id'])) {
    $reason = isset($_GET['timeout']) ? 'timeout' : (isset($_GET['tab_close']) ? 'tab_close' : 'manual');
    
    $pdo->prepare("INSERT INTO audit_log (user_id, action, ip_address) 
                  VALUES (?, ?, ?)")
       ->execute([
           $_SESSION['user_id'],
           'logout_' . $reason,
           $_SERVER['REMOTE_ADDR']
       ]);
}

// Completely destroy session
session_unset();
session_destroy();

// Clear session cookie
setcookie(session_name(), '', time() - 3600, '/fusys');

// Redirect to login page with appropriate message
$message = isset($_GET['timeout']) ? 'timeout=1' : (isset($_GET['tab_close']) ? 'tab_close=1' : '');
header("Location: /fusys/auth/login.php?$message");
exit;