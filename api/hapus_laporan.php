<?php
session_start();
include 'koneksi.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "DELETE FROM laporan WHERE id_laporan = '$id'";
    $result = mysqli_query($koneksi, $query);

    if ($result) {
        // Redirect back to dashboard after deleting
        header("Location: dashboard_admin.php?status=deleted");
        exit();
    }
}
header("Location: dashboard_admin.php");
exit();
?><?php
include 'koneksi.php';

// Security Check: Only admins can delete
if (!isset($_COOKIE['isLoggedIn']) || $_COOKIE['role'] !== 'admin') {
    header("Location: /api/Login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    $query = "DELETE FROM laporan WHERE id_laporan = '$id'";
    $result = mysqli_query($koneksi, $query);

    if ($result) {
        // Redirect back with absolute path
        header("Location: /api/dashboard_admin.php?status=deleted");
        exit();
    }
}
header("Location: /api/dashboard_admin.php");
exit();
?>