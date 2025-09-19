<?php
require_once '../config/config.php';

// Log activity before logout
if (isLoggedIn()) {
    logActivity('logout', 'User logged out');
}

// Destroy session
session_destroy();

// Clear remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to login page
header('Location: ' . BASE_URL . 'auth/login.php');
exit();
?>
