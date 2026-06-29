<?php
/**
 * proses_simpan.php
 * TiDB-Optimized Direct Base64 String Storage with Secure Escaping
 */
include 'koneksi.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize standard inputs
    $nama_pelapor   = mysqli_real_escape_string($koneksi, $_POST['nama_pelapor'] ?? '');
    $id_kategori    = intval($_POST['id_kategori'] ?? 0); // Step 5: capture FK integer
    $lokasi_wisata  = mysqli_real_escape_string($koneksi, $_POST['lokasi_wisata'] ?? '');
    $isi_laporan    = $_POST['isi_laporan'] ?? '';
    $isi_laporan_esc = mysqli_real_escape_string($koneksi, $isi_laporan);
    $gps_koordinat  = mysqli_real_escape_string($koneksi, $_POST['gps_koordinat'] ?? '');
    
    $status         = 'Diproses'; 
    $tanggal        = date('Y-m-d H:i:s');
    
    // 2. Fetch the Base64 String image payload from our hidden form field
    $rawBase64      = $_POST['foto_base64'] ?? '';

    if (!empty($rawBase64)) {
        // CRITICAL FIX: Escape the base64 string because it contains slashes, pluses, and data URIs
        $imageBase64 = mysqli_real_escape_string($koneksi, $rawBase64);
        
        // 3. Insert directly into TiDB cloud rows safely!
        $query = "INSERT INTO laporan (nama_pelapor, id_kategori, lokasi_wisata, isi_laporan, foto, gps_koordinat, status, tanggal_laporan) 
                  VALUES ('$nama_pelapor', '$id_kategori', '$lokasi_wisata', '$isi_laporan_esc', '$imageBase64', '$gps_koordinat', '$status', '$tanggal')";
        
        $insert = mysqli_query($koneksi, $query);

        if ($insert) {
            echo "<script>alert('Laporan berhasil terkirim dengan kompresi otomatis & geo-tagging!'); window.location.href='dashboard_user.php';</script>";
            exit();
        } else {
            $dbError = mysqli_error($koneksi);
            echo "<script>alert('Gagal simpan ke TiDB! Error: " . addslashes($dbError) . "'); window.history.back();</script>";
            exit();
        }
    } else {
        echo "<script>alert('Wajib melampirkan foto bukti keluhan.'); window.history.back();</script>";
        exit();
    }
} else {
    header("Location: dashboard_user.php");
    exit();
}
?>