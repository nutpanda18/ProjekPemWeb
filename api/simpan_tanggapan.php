<?php
/**
 * simpan_tanggapan.php
 * Saves admin comments/replies directly to the database
 */
include 'koneksi.php';

// 1. Security Check: Only logged-in admins can reply
if (!isset($_COOKIE['isLoggedIn']) || $_COOKIE['isLoggedIn'] !== 'true' || $_COOKIE['role'] !== 'admin') {
    header("Location: /api/Login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if the required parameters are sent
    if (isset($_POST['id_laporan']) && isset($_POST['tanggapan_admin'])) {
        
        $id              = mysqli_real_escape_string($koneksi, $_POST['id_laporan']);
        $tanggapan_admin = mysqli_real_escape_string($koneksi, $_POST['tanggapan_admin']);
        
        // 2. Update Query: Save the reply text into the database
        $query = "UPDATE laporan SET tanggapan_admin = '$tanggapan_admin' WHERE id_laporan = '$id'";
        
        if (mysqli_query($koneksi, $query)) {
            // Success: Send them back to the admin dashboard
            header("Location: /api/dashboard_admin.php?reply=success");
            exit();
        } else {
            die("Database Error saving comment: " . mysqli_error($koneksi));
        }
    } else {
        header("Location: /api/dashboard_admin.php?reply=missing_data");
        exit();
    }
} else {
    header("Location: /api/dashboard_admin.php");
    exit();
}
?>