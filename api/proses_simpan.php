<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header("Location: Login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_pelapor = mysqli_real_escape_string($koneksi, $_POST['nama_pelapor']);
    $lokasi_wisata = mysqli_real_escape_string($koneksi, $_POST['lokasi_wisata']);
    $isi_laporan = mysqli_real_escape_string($koneksi, $_POST['isi_laporan']);
    $tanggal = date("Y-m-d H:i:s");
    $status = "Menunggu";

    // --- PHOTO UPLOAD LOGIC ---
    $foto_name = "";
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "uploads/"; // Fixed the name here
        
        // Create unique name: 20240424_filename.jpg
        $foto_name = time() . "_" . basename($_FILES["foto"]["name"]);
        $target_file = $target_dir . $foto_name;

        // Move the file to the 'uploads' folder
        move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file);
    }

    // Check if your query looks like this:
$query = "INSERT INTO laporan (nama_pelapor, lokasi_wisata, isi_laporan, tanggal_laporan, status, foto) 
          VALUES ('$nama_pelapor', '$lokasi_wisata', '$isi_laporan', '$tanggal', '$status', '$foto_name')";
          
    if (mysqli_query($koneksi, $query)) {
        header("Location: dashboard_user.php?pesan=sukses");
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
} else {
    header("Location: dashboard_user.php");
}
?>