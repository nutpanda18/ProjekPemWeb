<?php
/**
 * proses_simpan.php
 * Final Update for Vercel (Read-Only) and TiDB Compatibility
 */
include 'koneksi.php';

// 1. Security Check: Use Cookies instead of Sessions for Vercel stability
if (!isset($_COOKIE['isLoggedIn']) || $_COOKIE['isLoggedIn'] !== 'true') {
    header("Location: /api/Login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. Sanitize Inputs to prevent SQL Injection
    $nama_pelapor = mysqli_real_escape_string($koneksi, $_POST['nama_pelapor']);
    $lokasi_wisata = mysqli_real_escape_string($koneksi, $_POST['lokasi_wisata']);
    $isi_laporan = mysqli_real_escape_string($koneksi, $_POST['isi_laporan']);
    
    // Set timezone for Madiun (WIB)
    date_default_timezone_set('Asia/Jakarta');
    $tanggal = date("Y-m-d H:i:s");
    $status = "Menunggu";

    // --- PHOTO LOGIC (Vercel Friendly) ---
    $foto_name = "no_image.jpg"; // Default placeholder
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        // We generate a unique name for the database record
        $foto_name = time() . "_" . basename($_FILES["foto"]["name"]);
        
        /* Note on Vercel: 
           We cannot use move_uploaded_file() here because the filesystem is read-only.
           The 'foto' column in TiDB will store the name, but the file won't exist 
           on the server. To fix this in the future, you would upload to Cloudinary/S3 here.
        */
    }

    // 3. Insert into TiDB 
    // This works now because 'id_laporan' is AUTO_INCREMENT in your database
    $query = "INSERT INTO laporan (nama_pelapor, lokasi_wisata, isi_laporan, tanggal_laporan, status, foto) 
              VALUES ('$nama_pelapor', '$lokasi_wisata', '$isi_laporan', '$tanggal', '$status', '$foto_name')";
          
    if (mysqli_query($koneksi, $query)) {
        // SUCCESS: Redirect back to user dashboard
        header("Location: /api/dashboard_user.php?pesan=sukses");
        exit();
    } else {
        // FAILURE: Show the specific database error
        echo "Database Error: " . mysqli_error($koneksi);
    }
} else {
    // If accessed directly without POST, send them back
    header("Location: /api/dashboard_user.php");
    exit();
}
?>