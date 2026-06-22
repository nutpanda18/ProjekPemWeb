<?php
/**
 * proses_simpan.php
 * Absolute Bulletproof Path Fix for Windows XAMPP
 */
include 'koneksi.php';

// Force error reporting to catch database layout issues later
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize Form Inputs
    $nama_pelapor   = mysqli_real_escape_string($koneksi, $_POST['nama_pelapor'] ?? '');
    $kategori       = mysqli_real_escape_string($koneksi, $_POST['kategori'] ?? '');
    $lokasi_wisata  = mysqli_real_escape_string($koneksi, $_POST['lokasi_wisata'] ?? '');
    $isi_laporan    = mysqli_real_escape_string($koneksi, $_POST['isi_laporan'] ?? '');
    $gps_koordinat  = mysqli_real_escape_string($koneksi, $_POST['gps_koordinat'] ?? '');
    
    $status         = 'Diproses'; 
    $tanggal        = date('Y-m-d H:i:s');
    
    // 2. FORCE ABSOLUTE PATH USING PHP'S BUILT-IN CONSTANT
    // This tells XAMPP exactly: "Look into the uploads folder right next to this file!"
    $targetDir = __DIR__ . '/uploads/';
    
    // Auto-create with full permissions if Windows dropped it
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] == 0) {
        $fileName       = basename($_FILES["foto"]["name"]);
        $fileType       = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Clean name format to prevent Windows path breaks
        $cleanFileName  = preg_replace("/[^a-zA-Z0-9.]/", "_", $fileName);
        $newFileName    = time() . "_" . $cleanFileName;
        
        // Target file full absolute destination path
        $targetFilePath = $targetDir . $newFileName;

        // Image file extension validation
        $allowedTypes = array('jpg', 'jpeg', 'png');
        if (!in_array($fileType, $allowedTypes)) {
            echo "<script>alert('Error: Format file salah! Hanya JPG, JPEG, & PNG.'); window.history.back();</script>";
            exit();
        }

        if ($_FILES["foto"]["size"] > 12000000) { // 12MB Max
            echo "<script>alert('Error: Ukuran foto terlalu besar! Maksimal 12MB.'); window.history.back();</script>";
            exit();
        }

        // Move the temp file to our absolute uploads path
        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFilePath)) {
            
            // 3. Database Sync Query
            $query = "INSERT INTO laporan (nama_pelapor, kategori, lokasi_wisata, isi_laporan, foto, gps_koordinat, status, tanggal_laporan) 
                      VALUES ('$nama_pelapor', '$kategori', '$lokasi_wisata', '$isi_laporan', '$newFileName', '$gps_koordinat', '$status', '$tanggal')";
            
            $insert = mysqli_query($koneksi, $query);

            if ($insert) {
                echo "<script>alert('Laporan berhasil terkirim dengan geo-tagging otomatis!'); window.location.href='dashboard_user.php';</script>";
                exit();
            } else {
                $dbError = mysqli_error($koneksi);
                echo "<script>alert('Gagal simpan ke database! Error MySQL: " . addslashes($dbError) . "'); window.history.back();</script>";
                exit();
            }
        } else {
            // Debug text showing you exactly where it attempted to save the file
            echo "<script>alert('XAMPP gagal memindahkan file ke: " . addslashes($targetDir) . ". Pastikan nama folder Anda huruf kecil semua (uploads).'); window.history.back();</script>";
            exit();
        }
    } else {
        $errorCode = $_FILES["foto"]["error"] ?? 'Data Kosong';
        echo "<script>alert('Gagal memproses file foto. Error Code PHP: " . $errorCode . "'); window.history.back();</script>";
        exit();
    }
} else {
    header("Location: dashboard_user.php");
    exit();
}
?>