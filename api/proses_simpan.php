<?php
/**
 * proses_simpan.php
 * Updated for Cookie-based Auth and Vercel Pathing
 */
include 'koneksi.php';

// 1. Security Check: Use Cookies instead of Sessions
if (!isset($_COOKIE['isLoggedIn']) || $_COOKIE['isLoggedIn'] !== 'true') {
    header("Location: /api/Login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. Sanitize Inputs
    $nama_pelapor = mysqli_real_escape_string($koneksi, $_POST['nama_pelapor']);
    $lokasi_wisata = mysqli_real_escape_string($koneksi, $_POST['lokasi_wisata']);
    $isi_laporan = mysqli_real_escape_string($koneksi, $_POST['isi_laporan']);
    
    // Set timezone for Madiun (WIB)
    date_default_timezone_set('Asia/Jakarta');
    $tanggal = date("Y-m-d H:i:s");
    $status = "Menunggu";

    // --- PHOTO UPLOAD LOGIC ---
    $foto_name = "";
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "uploads/"; 
        
        // Ensure folder exists (though Vercel is read-only, this helps in local dev)
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $foto_name = time() . "_" . basename($_FILES["foto"]["name"]);
        $target_file = $target_dir . $foto_name;

        // Note: On Vercel, this file will disappear after a few minutes 
        // because the filesystem is ephemeral (temporary).
        move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file);
    }

    // 3. Insert into TiDB
    $query = "INSERT INTO laporan (nama_pelapor, lokasi_wisata, isi_laporan, tanggal_laporan, status, foto) 
              VALUES ('$nama_pelapor', '$lokasi_wisata', '$isi_laporan', '$tanggal', '$status', '$foto_name')";
          
    if (mysqli_query($koneksi, $query)) {
        // Redirect back to user dashboard with absolute path
        header("Location: /api/dashboard_user.php?pesan=sukses");
        exit();
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
} else {
    header("Location: /api/dashboard_user.php");
    exit();
}
?>