<?php
session_start();

// Save role before destroying session
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Clear cookies if they exist
if (isset($_COOKIE['remember_user'])) {
    setcookie("remember_user", "", time() - 3600, "/");
}
if (isset($_COOKIE['remember_role'])) {
    setcookie("remember_role", "", time() - 3600, "/");
}

// Redirect based on role
if ($role === 'admin') {
    header("Location: admin_login.php");
} else {
    header("Location: user_login.php");
}
exit();
?>
