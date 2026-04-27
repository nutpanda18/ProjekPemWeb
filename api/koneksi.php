<?php
// Database configuration
$host = "localhost";
$user = "root";
$pass = "";
$db   = "laporankeluhanwisata"; 

// Create connection using the variables above
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Check connection
if (!$koneksi) {
    // In a real production site, you'd hide the error details, 
    // but for development, this is perfect.
    die("Gagal terhubung ke database: " . mysqli_connect_error());
}

// Set charset to utf8mb4 (optional, but recommended for special characters/emojis)
mysqli_set_charset($koneksi, "utf8mb4");
?>