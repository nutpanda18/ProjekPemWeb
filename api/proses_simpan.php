<?php
/**
 * proses_simpan.php
 * Fully Updated: Imgur API Hosting, Structured Categories, GPS Coordinates, and TiDB Integration
 */
include 'koneksi.php';

// 1. Security Check: Use Cookies instead of Sessions for Vercel stability
if (!isset($_COOKIE['isLoggedIn']) || $_COOKIE['isLoggedIn'] !== 'true') {
    header("Location: /api/Login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. Sanitize and Extract Inputs (Including the newly added fields)
    $nama_pelapor   = mysqli_real_escape_string($koneksi, $_POST['nama_pelapor']);
    $kategori       = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $lokasi_wisata  = mysqli_real_escape_string($koneksi, $_POST['lokasi_wisata']);
    $gps_koordinat  = mysqli_real_escape_string($koneksi, $_POST['gps_koordinat']);
    $isi_laporan    = mysqli_real_escape_string($koneksi, $_POST['isi_laporan']);
    
    // Set default baseline status and timezone
    date_default_timezone_set('Asia/Jakarta');
    $tanggal = date("Y-m-d H:i:s");
    $status  = "Proses"; // Default baseline state before admin moderation

    // --- PHOTO LOGIC (Imgur Cloud Bypass Strategy for Vercel) ---
    $foto_url = "https://placehold.co/600x400?text=No+Image"; // Safe placeholder fallback

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $file_path = $_FILES['foto']['tmp_name'];
        
        // Paste your Imgur Client ID inside the quotes below
        $client_id = "PASTE_YOUR_IMGUR_CLIENT_ID_HERE"; 

        // Execute background network teleportation stream directly to Imgur
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.imgur.com/3/image');
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Client-ID ' . $client_id));
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('image' => base64_encode(file_get_contents($file_path))));

        $response = curl_exec($ch);
        $result = json_decode($response, true);
        curl_close($ch);

        // If upload is valid, grab the permanent clean web resource endpoint
        if (isset($result['data']['link'])) {
            $foto_url = $result['data']['link']; // Example: https://i.imgur.com/xyz.jpg
        }
    }

    // 3. Insert Structured Entry into TiDB
    $query = "INSERT INTO laporan (nama_pelapor, lokasi_wisata, kategori, gps_koordinat, isi_laporan, tanggal_laporan, status, foto) 
              VALUES ('$nama_pelapor', '$lokasi_wisata', '$kategori', '$gps_koordinat', '$isi_laporan', '$tanggal', '$status', '$foto_url')";
          
    if (mysqli_query($koneksi, $query)) {
        // SUCCESS: Redirect back to dashboard safely
        header("Location: /api/dashboard_user.php?pesan=sukses");
        exit();
    } else {
        // FAILURE: Show diagnostic feedback error statement
        echo "Database Routing Error: " . mysqli_error($koneksi);
    }
} else {
    header("Location: /api/dashboard_user.php");
    exit();
}
?>