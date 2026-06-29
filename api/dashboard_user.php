<?php
/**
 * dashboard_user.php
 * Automated EXIF GPS Data Extraction with Base64 Cloud Bypass & Safe Client Compression
 */
include 'koneksi.php'; 
include 'api.php'; 

// Safeguard check for missing functions
if (!function_exists('getWisataData')) {
    function getWisataData() {
        return [
            [1, "Pahlawan Street Center"],
            [2, "Madiun Umbul Square"],
            [3, "Taman Sumber Umis"],
            [4, "Hutan Kota Madiun"]
        ];
    }
}

if (!isset($_COOKIE['isLoggedIn']) || $_COOKIE['isLoggedIn'] !== 'true') { 
    header("Location: /api/Login.php"); 
    exit(); 
}

$currentUser = $_COOKIE['username'];
$wisata_data = getWisataData(); 

// Fetch categories dynamically from database for the selector dropdown matching your ERD
$categories_options = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY id_kategori ASC");

$user_total_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM laporan WHERE nama_pelapor='$currentUser'");
$total_data = mysqli_fetch_assoc($user_total_q)['total'] ?? 0;

// Normalized JOIN Query to get the string name of the category
$reports_query = mysqli_query($koneksi, "
    SELECT laporan.*, kategori.nama_kategori as kategori 
    FROM laporan 
    LEFT JOIN kategori ON laporan.id_kategori = kategori.id_kategori 
    WHERE laporan.nama_pelapor='$currentUser' 
    ORDER BY laporan.tanggal_laporan DESC
");

// Fetch all tanggapan for this user's reports
$user_tanggapan = [];
$utq = mysqli_query($koneksi, "
    SELECT tanggapan.* FROM tanggapan
    INNER JOIN laporan ON tanggapan.id_laporan = laporan.id_laporan
    WHERE laporan.nama_pelapor = '$currentUser'
    ORDER BY tanggapan.tgl_tanggapan ASC
");
if ($utq) {
    while ($ut = mysqli_fetch_assoc($utq)) {
        $user_tanggapan[$ut['id_laporan']][] = $ut;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pelapor - Madiun</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/exif-js"></script>
    <link rel="stylesheet" href="style_user.css"> 
</head>
<body class="app-body min-h-screen flex flex-col bg-[#fffaf5]">

    <nav class="bg-[#4a2c1d] text-white sticky top-0 z-50 shadow-lg">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="font-black text-lg tracking-tight flex items-center gap-2">
                🍂 Laporan Wisata <span class="text-amber-300 font-medium text-xs bg-white/10 px-2.5 py-0.5 rounded-full uppercase tracking-wider">Madiun</span>
            </h1>
            <div class="flex items-center space-x-6 text-xs font-semibold">
                <a href="/api/Home.php" class="text-white/80 hover:text-amber-300 transition">Home</a>
                <a href="/api/Tentang.php" class="text-white/80 hover:text-amber-300 transition">Tentang</a>
                <span class="user-greeting border-l border-white/20 pl-6 text-white/90">
                    Halo, <strong class="text-amber-300 font-bold"><?= htmlspecialchars($currentUser); ?></strong>
                </span>
                <a href="/api/Login.php?logout=true" class="logout-btn bg-red-600 px-4 py-2 rounded-xl font-bold hover:bg-red-700 transition shadow-sm text-white">Keluar</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8 max-w-7xl flex-1">
        
        <div class="jumbotron-banner relative rounded-[2rem] overflow-hidden shadow-sm mb-8 h-48">
            <img src="https://images.unsplash.com/photo-1625244724123-1f3045cbd9a0?q=80&w=1200&auto=format&fit=crop" class="w-full h-full object-cover" alt="Madiun Hero Image">
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent flex flex-col justify-end p-8">
                <p class="text-[10px] text-amber-300 uppercase tracking-widest font-black">Portal Layanan Pengaduan Masyarakat</p>
                <h2 class="text-2xl font-black text-white tracking-tight mt-0.5">Selamat Datang di Pusat Layanan Keluhan Wisata</h2>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            
            <div class="lg:col-span-2 space-y-8">
                
                <div class="stat-premium-card bg-white p-6 rounded-[2rem] flex items-center justify-between border border-stone-200/60 shadow-sm relative overflow-hidden">
                    <div>
                        <p class="text-[10px] font-black uppercase text-stone-400 tracking-wider">Total Laporan Kontribusi Anda</p>
                        <h4 class="text-4xl font-black text-stone-900 mt-1"><?= $total_data; ?> <span class="text-xs font-bold text-stone-400">Berkas Pengaduan</span></h4>
                    </div>
                    <div class="stat-icon-wrapper bg-[#fffaf5] p-4 rounded-2xl text-2xl border border-orange-100">📂</div>
                </div>

                <div class="glass-card bg-white p-6 rounded-[2rem] shadow-sm border border-stone-200/60">
                    <h3 class="section-title text-stone-900 mb-4 flex items-center gap-2 font-black text-sm">
                        <span class="inline-block w-1 h-4 bg-amber-500 rounded-full"></span> Panduan Label Klasifikasi Keluhan
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="category-info-box p-4 rounded-2xl text-xs border border-stone-100 bg-stone-50/50">
                            <span class="text-amber-800 font-bold block mb-0.5">📍 Fasilitas Umum</span>
                            <p class="text-stone-500 leading-relaxed">Kerusakan sarana seperti Toilet, Area Parkir, Bangku Jalan, Pagar, dan Lampu Penerangan.</p>
                        </div>
                        <div class="category-info-box p-4 rounded-2xl text-xs border border-stone-100 bg-stone-50/50">
                            <span class="text-amber-800 font-bold block mb-0.5">🧹 Kebersihan Lingkungan</span>
                            <p class="text-stone-500 leading-relaxed">Tumpukan sampah yang belum diangkut, bau kurang sedap, serta pencemaran limbah sekitar.</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="section-title text-stone-900 flex items-center gap-2 font-black text-sm">
                        <span class="inline-block w-1 h-4 bg-amber-500 rounded-full"></span> Log Riwayat Pengaduan Anda
                    </h3>
                    <div class="bg-white rounded-[2rem] shadow-sm overflow-hidden border border-stone-200/60">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left table-auto">
                                <thead class="table-head bg-[#5c3d2e] text-white text-[10px] font-bold uppercase tracking-wider">
                                    <tr>
                                        <th class="p-4 w-20 text-center">Foto</th>
                                        <th class="p-4">Destinasi & Detail</th>
                                        <th class="p-4">Rincian Keluhan</th>
                                        <th class="p-4 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="text-xs divide-y divide-stone-100">
                                    <?php if(mysqli_num_rows($reports_query) == 0): ?>
                                        <tr>
                                            <td colspan="4" class="p-8 text-center text-stone-400 italic">Belum ada riwayat berkas keluhan yang Anda ajukan.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php while($r = mysqli_fetch_assoc($reports_query)): ?>
                                        <tr class="hover:bg-[#fffaf5]/40 transition-colors">
                                            <td class="p-4 align-top text-center">
                                                <?php if(!empty($r['foto'])): ?>
                                                    <?php if(strpos($r['foto'], 'data:image') === 0 || strpos($r['foto'], 'http') === 0): ?>
                                                        <img src="<?= $r['foto']; ?>" class="w-12 h-12 object-cover rounded-xl border border-stone-200 shadow-sm cursor-pointer mx-auto transition hover:scale-105" onclick="window.open(this.src)">
                                                    <?php else: ?>
                                                        <a href="uploads/<?= $r['foto']; ?>" target="_blank">
                                                            <img src="uploads/<?= $r['foto']; ?>" class="w-12 h-12 object-cover rounded-xl border border-stone-200 shadow-sm mx-auto transition hover:scale-105">
                                                        </a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <div class="w-12 h-12 bg-stone-100 rounded-xl flex items-center justify-center text-[9px] text-stone-400 font-bold mx-auto border border-stone-200">KOSONG</div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-4 align-top space-y-1">
                                                <span class="text-stone-400 text-[10px] font-medium block"><?= $r['tanggal_laporan']; ?></span>
                                                <span class="font-black text-stone-900 text-sm block"><?= htmlspecialchars($r['lokasi_wisata'] ?? ''); ?></span>
                                                <?php if(!empty($r['kategori'])): ?>
                                                    <span class="inline-block bg-amber-50 text-amber-800 font-bold px-2 py-0.5 rounded text-[9px] uppercase tracking-wide border border-amber-100"><?= htmlspecialchars($r['kategori']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-4 align-top space-y-3">
                                                <p class="text-stone-600 bg-stone-50/70 p-2.5 rounded-xl border border-stone-100 leading-relaxed italic">"<?= htmlspecialchars($r['isi_laporan'] ?? ''); ?>"</p>

                                                <?php 
                                                $replies = $user_tanggapan[$r['id_laporan']] ?? [];
                                                if (!empty($replies)): ?>
                                                    <div class="space-y-2">
                                                        <span class="text-[9px] font-black text-amber-800 uppercase tracking-wider flex items-center gap-1">💬 Tanggapan Admin</span>
                                                        <?php foreach($replies as $reply): ?>
                                                            <div class="bg-amber-50/60 border border-amber-200/60 p-2.5 rounded-xl">
                                                                <p class="text-stone-700 font-medium text-xs leading-relaxed"><?= htmlspecialchars($reply['isi_tanggapan']); ?></p>
                                                                <span class="text-[9px] text-stone-400 mt-1 block"><?= $reply['tgl_tanggapan']; ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-4 align-top text-center whitespace-nowrap">
                                                <?php 
                                                $statusVal = $r['status'] ?? 'Menunggu';
                                                $badgeClass = 'bg-stone-50 text-stone-600 border border-stone-200'; 
                                                if ($statusVal === 'Diterima') { 
                                                    $badgeClass = 'bg-emerald-50 text-emerald-700 border border-emerald-200'; 
                                                } elseif ($statusVal === 'Ditolak' || $statusVal === 'Tidak Diterima') { 
                                                    $badgeClass = 'bg-red-50 text-red-700 border border-red-200'; 
                                                }
                                                ?>
                                                <span class="px-2.5 py-1 <?= $badgeClass; ?> rounded-full text-[9px] font-black uppercase tracking-wider">
                                                    <?= htmlspecialchars(($statusVal === 'Menunggu' || $statusVal === 'Proses') ? 'Proses' : $statusVal); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6 lg:sticky lg:top-24">
                <div class="form-container-card bg-white p-6 rounded-[2rem] shadow-sm border border-stone-200/60">
                    <div class="text-center pb-4 mb-6 border-b border-stone-100">
                        <h3 class="font-black text-stone-900 text-lg">Buat Pengaduan</h3>
                        <p class="text-xs text-stone-400 mt-0.5">Ajukan keluhan kenyamanan pariwisata Anda di sini.</p>
                    </div>
                    
                    <form action="proses_simpan.php" method="POST" class="space-y-4">
                        <input type="hidden" name="foto_base64" id="foto_base64">

                        <div class="space-y-1">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-stone-400">Pelapor Terautentikasi</label>
                            <input type="text" name="nama_pelapor" value="<?= htmlspecialchars($currentUser); ?>" class="w-full px-4 py-3 rounded-xl bg-stone-100 border border-stone-200 text-stone-500 font-bold text-xs outline-none" readonly>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-stone-600">Kategori Masalah</label>
                            <select name="id_kategori" class="w-full px-4 py-3 rounded-xl border border-stone-200 bg-stone-50 text-xs font-semibold focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all" required>
                                <option value="" disabled selected>-- Pilih Kategori Masalah --</option>
                                 <?php if ($categories_options): ?>
                                     <?php while($cat = mysqli_fetch_assoc($categories_options)): ?>
                                         <option value="<?= $cat['id_kategori']; ?>">
                                             <?= htmlspecialchars($cat['nama_kategori']); ?>
                                         </option>
                                     <?php endwhile; ?>
                                 <?php else: ?>
                                     <option value="1">Fasilitas</option>
                                     <option value="2">Kebersihan</option>
                                     <option value="3">Keamanan</option>
                                     <option value="4">Pelayanan</option>
                                 <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="space-y-1">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-stone-600">Nama Destinasi Wisata</label>
                            <select name="lokasi_wisata" class="w-full px-4 py-3 rounded-xl border border-stone-200 bg-stone-50 text-xs font-semibold focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all" required>
                                <option value="" disabled selected>-- Pilih Lokasi Destinasi --</option>
                                 <?php 
                                if (!empty($wisata_data)) {
                                    foreach ($wisata_data as $row) {
                                        $nama_lokasi = $row[1] ?? null;
                                        if ($nama_lokasi && !is_numeric($nama_lokasi) && $nama_lokasi != "-") {
                                            echo "<option value='".htmlspecialchars($nama_lokasi)."'>".htmlspecialchars($nama_lokasi)."</option>";
                                        }
                                    }
                                } else {
                                    echo "<option value='Pahlawan Street Center'>Pahlawan Street Center</option>";
                                    echo "<option value='Madiun Umbul Square'>Madiun Umbul Square</option>";
                                    echo "<option value='Taman Sumber Umis'>Taman Sumber Umis</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-stone-600">Koordinat Geografis <span class="text-amber-600 font-bold">(Otomatis EXIF)</span></label>
                            <input type="text" id="gps_koordinat" name="gps_koordinat" placeholder="Menunggu Anda mengambil foto..." class="w-full px-4 py-3 rounded-xl bg-stone-100 border border-stone-200 text-stone-700 font-bold text-xs outline-none" readonly required>
                            <p id="geo_status" class="text-[10px] text-stone-400 mt-1 italic leading-tight">Pin peta otomatis berpindah setelah foto dipilih.</p>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-stone-600">Visualisasi Titik Lokasi Peta</label>
                            <div id="userMap" class="w-full h-36 rounded-2xl border border-stone-200 shadow-inner z-10"></div>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-stone-600">Isi Ringkasan Keluhan</label>
                            <textarea name="isi_laporan" rows="3" class="w-full px-4 py-3 rounded-xl border border-stone-200 bg-stone-50 text-xs font-medium focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all resize-none" placeholder="Jelaskan secara detail kendala yang dialami..." required></textarea>
                        </div>

                        <div class="camera-upload-zone p-4 rounded-xl border-2 border-dashed border-stone-200 bg-stone-50/50 text-center transition hover:bg-stone-50">
                            <label class="block text-[10px] font-bold text-stone-500 uppercase cursor-pointer mb-2">Ambil Foto Bukti Lapangan</label>
                            <input type="file" 
                                   id="cameraField" 
                                   accept="image/jpeg, image/jpg, image/png" 
                                   capture="environment" 
                                   required
                                   class="block w-full text-xs text-stone-400 file:mr-3 file:py-1.5 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-bold file:bg-[#4a2c1d] file:text-white hover:file:bg-[#321e14] file:transition file:shadow-sm cursor-pointer">
                        </div>

                        <button type="submit" id="submitBtn" class="w-full bg-amber-600 hover:bg-amber-700 text-white font-black py-4 rounded-2xl transition transform active:scale-95 shadow-md shadow-amber-600/20">
                            Kirim Berkas Laporan 
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const defaultLat = -7.6298;
        const defaultLng = 111.5240;

        const map = L.map('userMap', { zoomControl: false }).setView([defaultLat, defaultLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        L.control.zoom({ position: 'bottomright' }).addTo(map);
        let marker = L.marker([defaultLat, defaultLng]).addTo(map);

        function updateFormFields(lat, lng) {
            document.getElementById('gps_koordinat').value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }
        updateFormFields(defaultLat, defaultLng);

        function convertDMSToDD(degrees, minutes, seconds, direction) {
            let dd = degrees + (minutes / 60) + (seconds / 3600);
            if (direction === "S" || direction === "W") { dd = dd * -1; }
            return dd;
        }

        document.getElementById('cameraField').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const statusText = document.getElementById('geo_status');
            const submitBtn = document.getElementById('submitBtn');

            statusText.innerText = "⏳ Memproses & Mengompresi Foto...";
            statusText.className = "text-[10px] text-amber-600 mt-1 font-bold animate-pulse";
            submitBtn.disabled = true;
            submitBtn.innerText = "Mengompresi Gambar...";

            const reader = new FileReader();
            reader.onload = function(event) {
                const img = new Image();
                img.src = event.target.result;
                
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    
                    const MAX_WIDTH = 600;
                    const MAX_HEIGHT = 600;
                    let width = img.width;
                    let height = img.height;
                    
                    if (width > height) {
                        if (width > MAX_WIDTH) {
                            height *= MAX_WIDTH / width;
                            width = MAX_WIDTH;
                        }
                    } else {
                        if (height > MAX_HEIGHT) {
                            width *= MAX_HEIGHT / height;
                            height = MAX_HEIGHT;
                        }
                    }
                    
                    canvas.width = width;
                    canvas.height = height;
                    ctx.drawImage(img, 0, 0, width, height);
                    
                    const compressedBase64 = canvas.toDataURL('image/jpeg', 0.5);
                    document.getElementById('foto_base64').value = compressedBase64;
                    
                    submitBtn.disabled = false;
                    submitBtn.innerText = "Kirim Berkas Laporan 🚀";
                };
            };
            reader.readAsDataURL(file);

            EXIF.getData(file, function() {
                const latData = EXIF.getTag(this, "GPSLatitude");
                const latRef  = EXIF.getTag(this, "GPSLatitudeRef");
                const lngData = EXIF.getTag(this, "GPSLongitude");
                const lngRef  = EXIF.getTag(this, "GPSLongitudeRef");

                if (latData && latRef && lngData && lngRef) {
                    const latitude = convertDMSToDD(latData[0], latData[1], latData[2], latRef);
                    const longitude = convertDMSToDD(lngData[0], lngData[1], lngData[2], lngRef);

                    const imageLocation = new L.LatLng(latitude, longitude);
                    marker.setLatLng(imageLocation);
                    map.setView(imageLocation, 17);
                    updateFormFields(latitude, longitude);

                    statusText.innerText = "✅ Lokasi & Ukuran Berhasil Dikompresi Otomatis!";
                    statusText.className = "text-[10px] text-emerald-600 mt-1 font-bold";
                } else {
                    statusText.innerText = "⚠️ Tidak ada GPS di foto. Melacak GPS live perangkat...";
                    
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                const deviceLat = position.coords.latitude;
                                const deviceLng = position.coords.longitude;
                                const currentLoc = new L.LatLng(deviceLat, deviceLng);
                                
                                marker.setLatLng(currentLoc);
                                map.setView(currentLoc, 16);
                                updateFormFields(deviceLat, deviceLng);
                                statusText.innerText = "📍 Menggunakan lokasi live perangkat & Foto Terkompresi!";
                                statusText.className = "text-[10px] text-blue-600 mt-1 font-bold";
                            },
                            () => {
                                statusText.innerText = "❌ Gagal melacak lokasi otomatis. Gambar terkompresi.";
                                statusText.className = "text-[10px] text-red-500 mt-1 font-semibold";
                            }
                        );
                    }
                }
            });
        });
    </script>
</body>
</html>