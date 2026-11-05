<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/fusys',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Initialize critical session variables
$_SESSION['user_id'] = $_SESSION['user_id'] ?? null;
$_SESSION['user_role'] = $_SESSION['user_role'] ?? null;
$_SESSION['created'] = $_SESSION['created'] ?? time(); // Default to now
$_SESSION['last_activity'] = $_SESSION['last_activity'] ?? time(); // Default to now