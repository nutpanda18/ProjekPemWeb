<?php
/**
 * proses_simpan.php
 * Fully Repaired Version: Auto-generates folders and parses automatic metadata coordinates safely.
 */
include 'koneksi.php';

// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize incoming text parameters
    $nama_pelapor   = mysqli_real_escape_string($koneksi, $_POST['nama_pelapor'] ?? '');
    $kategori       = mysqli_real_escape_string($koneksi, $_POST['kategori'] ?? '');
    $lokasi_wisata  = mysqli_real_escape_string($koneksi, $_POST['lokasi_wisata'] ?? '');
    $isi_laporan    = mysqli_real_escape_string($koneksi, $_POST['isi_laporan'] ?? '');
    $gps_koordinat  = mysqli_real_escape_string($koneksi, $_POST['gps_koordinat'] ?? '');
    
    // Set baseline statuses matching your project rules ("Diproses")
    $status         = 'Diproses'; 
    $tanggal        = date('Y-m-d H:i:s');
    
    // 2. SELF-REPAIRING FOLDER SETUP
    $targetDir      = "uploads/";
    
    // Force build directory with full write permissions if missing
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Check if file upload payload is present and without errors
    if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] == 0) {
        $fileName       = basename($_FILES["foto"]["name"]);
        $fileType       = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Clean up special characters and prefix timestamp to make filename safe
        $cleanFileName  = preg_replace("/[^a-zA-Z0-9.]/", "_", $fileName);
        $newFileName    = time() . "_" . $cleanFileName;
        $targetFilePath = $targetDir . $newFileName;

        // Metadata validation constraints
        $allowedTypes = array('jpg', 'jpeg', 'png');
        if (!in_array($fileType, $allowedTypes)) {
            echo "<script>alert('Error: Format file salah! Hanya file JPG, JPEG, & PNG yang diizinkan.'); window.history.back();</script>";
            exit();
        }

        if ($_FILES["foto"]["size"] > 10000000) { // Limit to 10MB for modern smartphone captures
            echo "<script>alert('Error: Ukuran foto terlalu besar! Maksimal 10MB.'); window.history.back();</script>";
            exit();
        }

        // Try moving the temporary file to the upload folder
        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFilePath)) {
            
            // 3. Database Insertion
            $query = "INSERT INTO laporan (nama_pelapor, kategori, lokasi_wisata, isi_laporan, foto, gps_koordinat, status, tanggal_laporan) 
                      VALUES ('$nama_pelapor', '$kategori', '$lokasi_wisata', '$isi_laporan', '$newFileName', '$gps_koordinat', '$status', '$tanggal')";
            
            $insert = mysqli_query($koneksi, $query);

            if ($insert) {
                echo "<script>alert('Laporan berhasil terkirim dengan geo-tagging otomatis!'); window.location.href='dashboard_user.php';</script>";
                exit();
            } else {
                // If SQL fails, tell us exactly why (e.g. missing columns)
                $dbError = mysqli_error($koneksi);
                echo "<script>alert('Gagal simpan ke database! Error: " . addslashes($dbError) . "'); window.history.back();</script>";
                exit();
            }
        } else {
            // Check why move_uploaded_file failed
            echo "<script>alert('Gagal mengunggah file foto ke server. Pastikan folder uploads/ memiliki izin tulis (write permissions).'); window.history.back();</script>";
            exit();
        }
    } else {
        // Capture exact code error from PHP $_FILES array global
        $errorCode = $_FILES["foto"]["error"] ?? 'File tidak terdeteksi';
        echo "<script>alert('Gagal memproses file foto. Error Code PHP: " . $errorCode . "'); window.history.back();</script>";
        exit();
    }
} else {
    header("Location: dashboard_user.php");
    exit();
}
?>