<?php
/**
 * Logout handler for Registrations-only access
 */
require_once __DIR__ . '/../../src/Config.php';

// Clear registrations session
$_SESSION = [];
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');

header('Location: ' . ADMIN_PATH . '/forms/login.php');
exit;