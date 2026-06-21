<?php
/**
 * update_status.php
 * Updated to handle POST requests for Diterima / Tidak Diterima workflow states
 */
include 'koneksi.php';

// 1. Security Check: Ensure only logged-in admins can trigger this operation
if (!isset($_COOKIE['isLoggedIn']) || $_COOKIE['isLoggedIn'] !== 'true' || $_COOKIE['role'] !== 'admin') {
    header("Location: /api/Login.php");
    exit();
}

// 2. Process incoming data via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if both the unique ID and the objective status value exist
    if (isset($_POST['id_laporan']) && isset($_POST['status']) && !empty($_POST['id_laporan'])) {
        
        $id     = mysqli_real_escape_string($koneksi, $_POST['id_laporan']);
        $status = mysqli_real_escape_string($koneksi, $_POST['status']);
        
        // 3. Update Query: Dynamically changes status based on admin validation click
        $query = "UPDATE laporan SET status = '$status' WHERE id_laporan = '$id'";
        
        if (mysqli_query($koneksi, $query)) {
            // Success: Redirect back to panel with state parameters
            header("Location: /api/dashboard_admin.php?update=success");
            exit();
        } else {
            die("Database Error updating state value: " . mysqli_error($koneksi));
        }
    } else {
        header("Location: /api/dashboard_admin.php?update=missing_parameters");
        exit();
    }
} else {
    // Redirect back to dashboard panel if anyone attempts direct URL entry hacking
    header("Location: /api/dashboard_admin.php");
    exit();
}
?>