<?php
// /fusys/auth/update_activity.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

session_start();

if (isset($_SESSION['user_id'])) {
    // Update session timestamp
    $_SESSION['last_activity'] = time();
    
    // Optional: Update database timestamp if you want to track activity server-side
    $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?")
        ->execute([$_SESSION['user_id']]);
}

exit;