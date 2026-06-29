<?php
/**
 * simpan_tanggapan.php
 * Inserts a new reply into the tanggapan table (supports multiple replies per laporan)
 */
include 'koneksi.php';

if (!isset($_COOKIE['isLoggedIn']) || $_COOKIE['isLoggedIn'] !== 'true' || $_COOKIE['role'] !== 'admin') {
    header("Location: /api/Login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['id_laporan']) && isset($_POST['isi_tanggapan']) && !empty(trim($_POST['isi_tanggapan']))) {

        $id_laporan    = mysqli_real_escape_string($koneksi, $_POST['id_laporan']);
        $isi_tanggapan = mysqli_real_escape_string($koneksi, $_POST['isi_tanggapan']);
        $tgl_tanggapan = date('Y-m-d H:i:s');

        $query = "INSERT INTO tanggapan (id_laporan, isi_tanggapan, tgl_tanggapan)
                  VALUES ('$id_laporan', '$isi_tanggapan', '$tgl_tanggapan')";

        if (mysqli_query($koneksi, $query)) {
            header("Location: /api/dashboard_admin.php?reply=success");
            exit();
        } else {
            die("Database Error saving tanggapan: " . mysqli_error($koneksi));
        }
    } else {
        header("Location: /api/dashboard_admin.php?reply=missing_data");
        exit();
    }
} else {
    header("Location: /api/dashboard_admin.php");
    exit();
}
?>