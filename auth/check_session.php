<?php

require_once __DIR__ . '/../config/session.php';


echo isset($_SESSION['user_id']) ? 'true' : 'false';
header("Location: login.php?timeout=1");
exit;