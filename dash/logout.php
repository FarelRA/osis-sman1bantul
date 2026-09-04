<?php
require_once __DIR__ . '/../src/Config.php';
$_SESSION = [];
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
header('Location: ' . ADMIN_PATH . '/login.php');