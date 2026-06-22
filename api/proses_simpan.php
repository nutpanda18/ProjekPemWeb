<?php
/**
 * proses_simpan.php
 * Robust Version: Auto-generates folder and tracks deep upload errors.
 */
include 'koneksi.php';

// Force error outputs to assist debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_pelapor   = mysqli_real_escape_string($koneksi, $_POST['nama_pelapor'] ?? '');
    $kategori       = mysqli_real_escape_string($koneksi, $_POST['kategori'] ?? '');
    $lokasi_wisata  = mysqli_real_escape_string($koneksi, $_POST['lokasi_wisata'] ?? '');
    $isi_laporan    = mysqli_real_escape_string($koneksi, $_POST['isi_laporan'] ?? '');
    $gps_koordinat  = mysqli_real_escape_string($koneksi, $_POST['gps_koordinat'] ?? '');
    
    $status         = 'Diproses'; 
    $tanggal        = date('Y-m-d H:i:s');
    
    // Auto-create folder if it's missing entirely
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0777, true)) {
            echo "<script>alert('Sistem gagal membuat folder uploads/ secara otomatis. Buatlah folder secara manual di direktori proyek Anda.'); window.history.back();</script>";
            exit();
        }
    }

    // Check if file upload array exists without error codes
    if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] == 0) {
        $fileName       = basename($_FILES["foto"]["name"]);
        $fileType       = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Sanitize naming anomalies using clean timestamps
        $cleanFileName  = preg_replace("/[^a-zA-Z0-9.]/", "_", $fileName);
        $newFileName    = time() . "_" . $cleanFileName;
        $targetFilePath = $targetDir . $newFileName;

        $allowedTypes = array('jpg', 'jpeg', 'png');
        if (!in_array($fileType, $allowedTypes)) {
            echo "<script>alert('Error: Format file salah! Hanya JPG, JPEG, & PNG yang diizinkan.'); window.history.back();</script>";
            exit();
        }

        if ($_FILES["foto"]["size"] > 10000000) {
            echo "<script>alert('Error: Ukuran foto terlalu besar! Maksimal 10MB.'); window.history.back();</script>";
            exit();
        }

        // Execute file move transaction
        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFilePath)) {
            
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
            // Detailed environment tracking fallback diagnostic print out
            $lastError = error_get_last();
            $systemErrorMessage = isset($lastError['message']) ? addslashes($lastError['message']) : 'Masalah izin folder lokal';
            echo "<script>alert('Gagal mengunggah file foto! Detail System Log: " . $systemErrorMessage . "'); window.history.back();</script>";
            exit();
        }
    } else {
        $errorCode = $_FILES["foto"]["error"] ?? 'Data file kosong';
        echo "<script>alert('Gagal memproses file foto. Error Code: " . $errorCode . ". Coba ganti file foto lain.'); window.history.back();</script>";
        exit();
    }
} else {
    header("Location: dashboard_user.php");
    exit();
}
?>