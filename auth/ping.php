<?php
require_once __DIR__ . '/../config/session.php';

$_SESSION['last_activity'] = time();

exit; // No output needed
?>