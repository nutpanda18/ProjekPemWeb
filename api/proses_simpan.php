<?php
/**
 * proses_simpan.php
 * Handles image upload validation, saves files securely, 
 * and inserts auto-extracted coordinates into the database.
 */
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Collect form data text inputs
    $nama_pelapor   = mysqli_real_escape_string($koneksi, $_POST['nama_pelapor']);
    $kategori       = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $lokasi_wisata  = mysqli_real_escape_string($koneksi, $_POST['lokasi_wisata']);
    $isi_laporan    = mysqli_real_escape_string($koneksi, $_POST['isi_laporan']);
    
    // This receives the automated coordinates string (e.g., "-7.629800, 111.524000")
    $gps_koordinat  = mysqli_real_escape_string($koneksi, $_POST['gps_koordinat']);
    
    // Default status for new complaints matching your preferred terms
    $status         = 'Diproses'; 
    $tanggal        = date('Y-m-d H:i:s');

    // 2. File Upload Configuration & Security
    $targetDir      = "uploads/";
    
    // Ensure the upload directory exists
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileName       = basename($_FILES["foto"]["name"]);
    $fileType       = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Sanitize filename to prevent overwriting issues by adding a timestamp metadata prefix
    $newFileName    = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $fileName);
    $targetFilePath = $targetDir . $newFileName;

    // Validate if a file is actually uploaded
    if (!empty($_FILES["foto"]["tmp_name"])) {
        // Enforce allowed image metadata formats (JPG/JPEG contain the EXIF location metadata)
        $allowedTypes = array('jpg', 'jpeg', 'png');
        if (!in_array($fileType, $allowedTypes)) {
            echo "<script>alert('Error: Hanya file JPG, JPEG, & PNG yang diizinkan!'); window.history.back();</script>";
            exit();
        }

        // Limit file size to 5MB to optimize database performance/bandwidth limits
        if ($_FILES["foto"]["size"] > 5000000) {
            echo "<script>alert('Error: Ukuran foto maksimal adalah 5MB.'); window.history.back();</script>";
            exit();
        }

        // Move file from temporary directory to server folder
        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFilePath)) {
            
            // 3. Insert into Database Table (Make sure your table columns match these variables)
            // Adjust column names if your database layout uses specific words (e.g., 'koordinat' or 'gps')
            $query = "INSERT INTO laporan (nama_pelapor, kategori, lokasi_wisata, isi_laporan, foto, gps_koordinat, status, tanggal_laporan) 
                      VALUES ('$nama_pelapor', '$kategori', '$lokasi_wisata', '$isi_laporan', '$newFileName', '$gps_koordinat', '$status', '$tanggal')";
            
            $insert = mysqli_query($koneksi, $query);

            if ($insert) {
                echo "<script>alert('Laporan berhasil dikirim dengan koordinat otomatis!'); window.location.href='dashboard_user.php';</script>";
            } else {
                echo "<script>alert('Gagal menyimpan data ke database: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('Gagal mengunggah file foto ke server.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Wajib melampirkan foto bukti keluhan.'); window.history.back();</script>";
    }
} else {
    header("Location: dashboard_user.php");
    exit();
}
?>