//coba
<?php
session_start();
include 'koneksi.php';

// 1. Security Check: Ensure only logged-in admins can trigger this script
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['role'] !== 'admin') {
    header("Location: Login.php");
    exit();
}

// 2. ID Validation: Check if the ID exists in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    // 3. Update Query: Change status to 'Selesai'
    // Using a prepared statement is safer, but here is the cleaned standard query
    $query = "UPDATE laporan SET status = 'Selesai' WHERE id_laporan = '$id'";
    
    if (mysqli_query($koneksi, $query)) {
        // 4. Success: Redirect back with a success flag
        header("Location: dashboard_admin.php?update=success");
        exit();
    } else {
        // 5. Error handling: Friendly error for the user
        die("Error updating status: " . mysqli_error($koneksi));
    }
} else {
    // No ID provided? Send them back to the dashboard
    header("Location: dashboard_admin.php");
    exit();
}
?>