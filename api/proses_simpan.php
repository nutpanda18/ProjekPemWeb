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
    $foto_name = "";
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        // We generate the name so it can be stored in the database
        $foto_name = time() . "_" . basename($_FILES["foto"]["name"]);
        
        /* NOTE: move_uploaded_file() is removed because Vercel 
           is a Read-Only file system. To truly save images on Vercel, 
           you would need a service like Cloudinary or Imgur.
        */
    }

    // 3. Insert into TiDB 
    // This relies on 'id_laporan' being AUTO_INCREMENT in your database
    $query = "INSERT INTO laporan (nama_pelapor, lokasi_wisata, isi_laporan, tanggal_laporan, status, foto) 
              VALUES ('$nama_pelapor', '$lokasi_wisata', '$isi_laporan', '$tanggal', '$status', '$foto_name')";
          
    if (mysqli_query($koneksi, $query)) {
        // Redirect back to user dashboard using absolute path
        header("Location: /api/dashboard_user.php?pesan=sukses");
        exit();
    } else {
        // This will help catch if the AUTO_INCREMENT fix hasn't been applied yet
        echo "Database Error: " . mysqli_error($koneksi);
    }
} else {
    header("Location: /api/dashboard_user.php");
    exit();
}
?>