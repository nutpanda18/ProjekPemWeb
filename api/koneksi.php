<?php
// TiDB Cloud Configuration
$host = "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com"; // Get this from TiDB 'Connect' panel
$user = "4NvsVAGhBM8TkY1.root";               // Your TiDB username usually ends in .root
$pass = "W0BmG6TkTygW56z9";                    // The password you set in TiDB
$db   = "laporankeluhanwisata";             // Your database name
$port = 4000;                               // TiDB default port

// 1. Initialize MySQLi
$koneksi = mysqli_init();

// 2. Set SSL (Required for TiDB Cloud)
// We pass NULL because the server uses a valid certificate by default
mysqli_ssl_set($koneksi, NULL, NULL, NULL, NULL, NULL);

// 3. Connect using the port and SSL settings
$status = mysqli_real_connect(
    $koneksi, 
    $host, 
    $user, 
    $pass, 
    $db, 
    $port,
    NULL,
    MYSQL_CLIENT_SSL
);

// Check connection
if (!$status) {
    die("Gagal terhubung ke database cloud: " . mysqli_connect_error());
}

// Set charset to utf8mb4
mysqli_set_charset($koneksi, "utf8mb4");
?>