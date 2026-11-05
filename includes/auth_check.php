<?php
// Start session with strict settings
require_once __DIR__ . '/../config/session.php';

require_once '../config/database.php';

// Session timeout configuration (60 seconds)
$inactive = 60; 

// 1. Check session expiration
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $inactive) {
    // Log timeout if user was logged in
    if (isset($_SESSION['user_id'])) {
        $pdo->prepare("INSERT INTO audit_log (user_id, action, ip_address) 
                      VALUES (?, ?, ?)")
           ->execute([
               $_SESSION['user_id'],
               'session_timeout', 
               $_SERVER['REMOTE_ADDR']
           ]);
    }
    
    session_unset();
    session_destroy();
    header("Location: ../auth/login.php?timeout=1");
    exit;
}

// 2. Update last activity - moved to ping.php
$_SESSION['last_activity'] = time();

// 3. Authentication checks
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// 4. Verify user exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    $pdo->prepare("INSERT INTO audit_log (action, ip_address) 
                  VALUES (?, ?)")
       ->execute(['invalid_session', $_SERVER['REMOTE_ADDR']]);
    
    session_destroy();
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Location: ../auth/login.php");
    exit;
}

// 5. Update session data
$_SESSION['user_role'] = $user['role'];