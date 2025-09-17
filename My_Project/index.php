<?php
require_once 'config/config.php';

// Redirect to dashboard if logged in, otherwise to login
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard/');
} else {
    header('Location: ' . BASE_URL . 'auth/login.php');
}
exit();
?>
