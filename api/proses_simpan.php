<?php
/**
 * proses_simpan.php
 * TiDB-Optimized Direct Base64 String Storage with Automated Email Notifications
 */
include 'koneksi.php';

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Adjust paths below to point to wherever you uploaded your PHPMailer files
require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize standard inputs
    $nama_pelapor   = mysqli_real_escape_string($koneksi, $_POST['nama_pelapor'] ?? '');
    $id_kategori    = intval($_POST['id_kategori'] ?? 0); 
    $lokasi_wisata  = mysqli_real_escape_string($koneksi, $_POST['lokasi_wisata'] ?? '');
    $isi_laporan    = $_POST['isi_laporan'] ?? '';
    $isi_laporan_esc = mysqli_real_escape_string($koneksi, $isi_laporan);
    $gps_koordinat  = mysqli_real_escape_string($koneksi, $_POST['gps_koordinat'] ?? '');
    
    $status         = 'Diproses'; 
    $tanggal        = date('Y-m-d H:i:s');
    
    // 2. Fetch the Base64 String image payload
    $rawBase64      = $_POST['foto_base64'] ?? '';

    if (!empty($rawBase64)) {
        $imageBase64 = mysqli_real_escape_string($koneksi, $rawBase64);
        
        // 3. Insert directly into database safely
        $query = "INSERT INTO laporan (nama_pelapor, id_kategori, lokasi_wisata, isi_laporan, foto, gps_koordinat, status, tanggal_laporan) 
                  VALUES ('$nama_pelapor', '$id_kategori', '$lokasi_wisata', '$isi_laporan_esc', '$imageBase64', '$gps_koordinat', '$status', '$tanggal')";
        
        $insert = mysqli_query($koneksi, $query);

        if ($insert) {
            
            // 4. AUTOMATED EMAIL NOTIFICATION PIPELINE
            // First, find the user's email matching their username from your 'register' table
            $user_email = "pelapor@example.com"; // Fallback placeholder
            $email_lookup = mysqli_query($koneksi, "SELECT email FROM register WHERE username='$nama_pelapor' LIMIT 1");
            if ($email_lookup && mysqli_num_rows($email_lookup) > 0) {
                $user_email = mysqli_fetch_assoc($email_lookup)['email'];
            }

            $mail = new PHPMailer(true);

            try {
                // SMTP Configuration Credentials
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';                     // Your SMTP provider host server
                $mail->SMTPAuth   = true;                                 // Enable SMTP authorization protection
                $mail->Username   = 'your_system_email@gmail.com';        // System administrator sender address
                $mail->Password   = 'your_app_specific_password';         // Secure App-Specific Password (NOT regular password)
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;       // Secure transport standard encryption
                $mail->Port       = 587;                                  // SMTP Port

                // Sender & Recipient Details Mapping
                $mail->setFrom('your_system_email@gmail.com', 'Layanan Pengaduan Madiun');
                $mail->addAddress($user_email, $nama_pelapor);            // Send to the logged-in reporter

                // Email Custom Styled Layout Content HTML
                $mail->isHTML(true);
                $mail->Subject = 'Terima Kasih! Laporan Pengaduan Wisata Anda Telah Diterima';
                $mail->Body    = "
                    <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e1dbd6; border-radius: 16px; background-color: #fffaf5;'>
                        <h2 style='color: #4a2c1d;'>🍂 Halo, " . htmlspecialchars($nama_pelapor) . "!</h2>
                        <p style='color: #44403c; font-size: 14px; line-height: 1.6;'>
                            Terima kasih telah berkontribusi menjaga kenyamanan destinasi pariwisata daerah dengan menggunakan platform <strong>Laporan Keluhan Wisata Madiun</strong>.
                        </p>
                        <div style='background-color: #ffffff; padding: 15px; border-radius: 12px; margin: 20px 0; border-left: 4px solid #ea580c;'>
                            <h4 style='margin: 0 0 10px 0; color: #1c1917;'>Detail Ringkasan Pengaduan:</h4>
                            <table style='font-size: 13px; color: #44403c; width: 100%; border-collapse: collapse;'>
                                <tr><td style='padding: 4px 0; font-weight: bold; width: 120px;'>Lokasi:</td><td>" . htmlspecialchars($lokasi_wisata) . "</td></tr>
                                <tr><td style='padding: 4px 0; font-weight: bold;'>Status Awal:</td><td><span style='background-color: #fef3c7; color: #d97706; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 11px;'>DIPROSES</span></td></tr>
                                <tr><td style='padding: 4px 0; font-weight: bold;'>Waktu Kirim:</td><td>" . $tanggal . "</td></tr>
                            </table>
                        </div>
                        <p style='color: #44403c; font-size: 14px; line-height: 1.6;'>
                            Tim peninjau lapangan kami akan segera melakukan verifikasi dan penanganan perbaikan berkas aduan Anda secara berkala. Anda dapat memantau perkembangan status keluhan secara langsung melalui halaman dashboard pelapor Anda.
                        </p>
                        <hr style='border: none; border-top: 1px solid #e1dbd6; margin: 20px 0;'>
                        <p style='font-size: 11px; color: #78716c; text-align: center; margin: 0;'>
                            Pemerintah Kabupaten/Kota Madiun &bull; Dinas Kebudayaan dan Pariwisata
                        </p>
                    </div>
                ";

                $mail->send();
            } catch (Exception $e) {
                // Fail silently or log error so the user still gets their database success redirect even if the email script crashes
                error_log("Notification dispatch crash: " . $mail->ErrorInfo);
            }

            echo "<script>alert('Laporan berhasil terkirim dengan kompresi otomatis & geo-tagging!'); window.location.href='dashboard_user.php';</script>";
            exit();
        } else {
            $dbError = mysqli_error($koneksi);
            echo "<script>alert('Gagal simpan ke database! Error: " . addslashes($dbError) . "'); window.history.back();</script>";
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