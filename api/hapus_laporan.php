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
?>