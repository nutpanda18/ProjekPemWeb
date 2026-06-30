<?php
/**
 * proses_simpan.php
 * TiDB-Optimized Direct Base64 String Storage with Verbose Error Debugging for PHPMailer
 */
include 'koneksi.php';

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PASTIKAN FOLDER 'phpmailer' SUDAH DIUPLOAD DI DALAM FOLDER /api DI GITHUB
require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_pelapor   = mysqli_real_escape_string($koneksi, $_POST['nama_pelapor'] ?? '');
    $id_kategori    = intval($_POST['id_kategori'] ?? 0); 
    $lokasi_wisata  = mysqli_real_escape_string($koneksi, $_POST['lokasi_wisata'] ?? '');
    $isi_laporan    = $_POST['isi_laporan'] ?? '';
    $isi_laporan_esc = mysqli_real_escape_string($koneksi, $isi_laporan);
    $gps_koordinat  = mysqli_real_escape_string($koneksi, $_POST['gps_koordinat'] ?? '');
    
    $status         = 'Diproses'; 
    $tanggal        = date('Y-m-d H:i:s');
    
    $rawBase64      = $_POST['foto_base64'] ?? '';

    if (!empty($rawBase64)) {
        $imageBase64 = mysqli_real_escape_string($koneksi, $rawBase64);
        
        $query = "INSERT INTO laporan (nama_pelapor, id_kategori, lokasi_wisata, isi_laporan, foto, gps_koordinat, status, tanggal_laporan) 
                  VALUES ('$nama_pelapor', '$id_kategori', '$lokasi_wisata', '$isi_laporan_esc', '$imageBase64', '$gps_koordinat', '$status', '$tanggal')";
        
        $insert = mysqli_query($koneksi, $query);

        if ($insert) {
            
            // Ambil email user pelapor dari tabel register
            $user_email = "";
            $email_lookup = mysqli_query($koneksi, "SELECT email FROM register WHERE username='$nama_pelapor' LIMIT 1");
            if ($email_lookup && mysqli_num_rows($email_lookup) > 0) {
                $user_email = mysqli_fetch_assoc($email_lookup)['email'];
            }

            // Jika email tidak ditemukan di DB, gunakan email sementara untuk testing
            if (empty($user_email)) {
                $user_email = "lupi@example.com"; 
            }

            $mail = new PHPMailer(true);

            try {
                // Aktifkan output debug yang sangat detail
                $mail->SMTPDebug = 0;                                     // 2 = Client & Server messages
                $mail->Debugoutput = 'html';

                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';                     
                $mail->SMTPAuth   = true;                                 
                $mail->Username   = 'nutpanda18@gmail.com';               // GANTI dengan Gmail kamu
                $mail->Password   = 'bkwt rptr awpc rcsa';              // GANTI dengan 16 digit App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;       
                $mail->Port       = 587;                                  

                $mail->setFrom('nutpanda18@gmail.com', 'Layanan Pengaduan Madiun');
                $mail->addAddress($user_email, $nama_pelapor);            

                $mail->isHTML(true);
                $mail->Subject = 'Terima Kasih! Laporan Pengaduan Wisata Anda Telah Diterima';
                $mail->Body    = "
                    <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e1dbd6; background-color: #fffaf5; border-radius: 16px;'>
                        <h2 style='color: #4a2c1d;'>🍂 Halo, " . htmlspecialchars($nama_pelapor) . "!</h2>
                        <p>Terima kasih telah mengirimkan laporan keluhan di platform kami.</p>
                        <p><strong>Lokasi Wisata:</strong> " . htmlspecialchars($lokasi_wisata) . "</p>
                        <p>Laporan Anda sedang berada dalam tahap <strong>Diproses</strong> oleh admin aduan.</p>
                    </div>
                ";

                $mail->send();
                
                echo "<script>alert('Laporan & Email Berhasil Dikirim!'); window.location.href='dashboard_user.php';</script>";
                exit();

            } catch (Exception $e) {
                // JIKA EMAIL GAGAL, BERHENTI DI SINI DAN TAMPILKAN ERORNYA
                die("<div style='padding: 20px; background: #fee2e2; color: #991b1b; font-family: sans-serif; border-radius: 12px; margin: 20px;'>".
                    "<h3>❌ Pengiriman Email Gagal</h3>".
                    "<strong>Pesan Error:</strong> " . $mail->ErrorInfo . "</div>");
            }
            
        } else {
            die("Gagal simpan ke database: " . mysqli_error($koneksi));
        }
    } else {
        die("Wajib melampirkan foto bukti keluhan.");
    }
} else {
    header("Location: dashboard_user.php");
    exit();
}