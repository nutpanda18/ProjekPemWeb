<?php
/* // TEMPORARY FIX FOR VERCEL
// We are "muting" this so the website doesn't crash.
// Note: Login/Register will not work until we connect a real Cloud Database.

$host = "localhost";
$user = "root";
$pass = "";
$db   = "laporankeluhanwisata"; 

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Gagal terhubung ke database: " . mysqli_connect_error());
}

mysqli_set_charset($koneksi, "utf8mb4");
*/

// Creating a dummy variable so Login.php doesn't give an "Undefined Variable" error
$koneksi = false; 
?>