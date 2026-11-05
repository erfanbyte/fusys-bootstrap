<?php
require_once 'auth_check.php';

if ($_SESSION['user_role'] !== 'driver'&& $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/unauthorized.php");
    exit;
}
if ($_SESSION['user_role'] === 'driver'|| $_SESSION['user_role'] === 'admin'){

// Check if user already has a pending deletion request
$stmt = $pdo->prepare("SELECT id FROM deletion_requests WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$_SESSION['user_id']]);
$existing_request = $stmt->fetch();

if ($existing_request) {
        echo "<script>
                alert('You already have a pending deletion request');
                window.location.href = 'myprofile.php';
              </script>";
        exit;
}

// Create new deletion request
$pdo->prepare("INSERT INTO deletion_requests (user_id) VALUES (?)")->execute([$_SESSION['user_id']]);

// Log action
$pdo->prepare("INSERT INTO audit_log (user_id, action, ip_address) VALUES (?, ?, ?)")
    ->execute([$_SESSION['user_id'], "deletion_requested", $_SERVER['REMOTE_ADDR']]);

    echo "<script>
            alert('Deletion request submitted successfully');
            window.location.href = 'myprofile.php';
          </script>";
    exit;
exit;
}
?>