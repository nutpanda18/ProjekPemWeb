<?php
/**
 * proses_simpan.php
 * Foolproof Absolute-Path Windows Version
 */
include 'koneksi.php';

// Force error reporting to find bugs immediately
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize Form Text Inputs
    $nama_pelapor   = mysqli_real_escape_string($koneksi, $_POST['nama_pelapor'] ?? '');
    $kategori       = mysqli_real_escape_string($koneksi, $_POST['kategori'] ?? '');
    $lokasi_wisata  = mysqli_real_escape_string($koneksi, $_POST['lokasi_wisata'] ?? '');
    $isi_laporan    = mysqli_real_escape_string($koneksi, $_POST['isi_laporan'] ?? '');
    $gps_koordinat  = mysqli_real_escape_string($koneksi, $_POST['gps_koordinat'] ?? '');
    
    $status         = 'Diproses'; 
    $tanggal        = date('Y-m-d H:i:s');
    
    // 2. USE ABSOLUTE SYSTEM DIRECTORY PATHS (Fixes Windows XAMPP glitches)
    $currentFolder = dirname(__FILE__); 
    $targetDir     = $currentFolder . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    
    // Force build directory with complete write/read permissions if missing
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Check file array payload structure
    if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] == 0) {
        $fileName       = basename($_FILES["foto"]["name"]);
        $fileType       = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Clean up text characters to prevent upload path breaks
        $cleanFileName  = preg_replace("/[^a-zA-Z0-9.]/", "_", $fileName);
        $newFileName    = time() . "_" . $cleanFileName;
        $targetFilePath = $targetDir . $newFileName;

        // Image Validation
        $allowedTypes = array('jpg', 'jpeg', 'png');
        if (!in_array($fileType, $allowedTypes)) {
            echo "<script>alert('Error: Format file salah! Hanya JPG, JPEG, & PNG.'); window.history.back();</script>";
            exit();
        }

        if ($_FILES["foto"]["size"] > 12000000) { // Modern smartphone friendly (12MB)
            echo "<script>alert('Error: Ukuran foto terlalu besar! Maksimal 12MB.'); window.history.back();</script>";
            exit();
        }

        // Execute file transaction using the safe absolute system path
        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFilePath)) {
            
            // 3. Database Insertion Setup
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
            // Diagnostic message printout if Windows is still blocking the folder
            echo "<script>alert('XAMPP masih memblokir folder upload. Solusi: Buat folder bernama \'uploads\' secara manual di dalam folder proyek Anda.'); window.history.back();</script>";
            exit();
        }
    } else {
        $errorCode = $_FILES["foto"]["error"] ?? 'Data Kosong';
        echo "<script>alert('Gagal memproses file foto. Error Code PHP: " . $errorCode . ". Silakan coba ganti file gambar lain.'); window.history.back();</script>";
        exit();
    }
} else {
    header("Location: dashboard_user.php");
    exit();
}
?>