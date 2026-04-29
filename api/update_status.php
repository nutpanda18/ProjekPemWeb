<?php
include 'koneksi.php';

// Security Check: Ensure only logged-in admins can trigger this
if (!isset($_COOKIE['isLoggedIn']) || $_COOKIE['role'] !== 'admin') {
    header("Location: /api/Login.php");
    exit();
}

// Check if the ID exists in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    // Update Query: Change status to 'Selesai'
    $query = "UPDATE laporan SET status = 'Selesai' WHERE id_laporan = '$id'";
    
    if (mysqli_query($koneksi, $query)) {
        // Success: Redirect back with absolute path
        header("Location: /api/dashboard_admin.php?update=success");
        exit();
    } else {
        die("Error updating status: " . mysqli_error($koneksi));
    }
} else {
    header("Location: /api/dashboard_admin.php");
    exit();
}
?>