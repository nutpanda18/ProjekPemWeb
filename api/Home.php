<?php
session_start();
if (!isset($_SESSION['isLoggedIn'])) { header("Location: index.php"); exit(); }

if ($_SESSION['role'] === 'admin') {
    header("Location: dashboard_admin.php");
} else {
    header("Location: dashboard_user.php");
}
exit();
?>