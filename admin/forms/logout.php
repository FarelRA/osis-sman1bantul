<?php
/**
 * Logout handler for Registrations-only access
 */
session_start();

// Clear registrations session
unset($_SESSION['forms_logged_in']);
unset($_SESSION['forms_username']);

session_destroy();

header('Location: /admin/forms/login.php');
exit;
