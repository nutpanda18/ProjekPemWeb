<?php
/**
 * dashboard_user.php
 * Automated EXIF GPS Data Extraction with Base64 Cloud Bypass
 */
include 'koneksi.php'; 
include 'api.php'; 

if (!isset($_COOKIE['isLoggedIn']) || $_COOKIE['isLoggedIn'] !== 'true') { 
    header("Location: /api/Login.php"); 
    exit(); 
}

$currentUser = $_COOKIE['username'];
$wisata_data = getWisataData(); 

$user_total_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM laporan WHERE nama_pelapor='$currentUser'");
$total_data = mysqli_fetch_assoc($user_total_q)['total'] ?? 0;

$reports_query = mysqli_query($koneksi, "SELECT * FROM laporan WHERE nama_pelapor='$currentUser' ORDER BY tanggal_laporan DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User - Madiun</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/exif-js"></script>
    <link rel="stylesheet" href="style_user.css"> 
</head>
<body class="bg-stone-50">

    <nav class="bg-[#4a2c1d] text-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="font-bold text-xl tracking-tight">🍂 Laporan Keluhan Wisata Madiun</h1>
            <div class="flex items-center space-x-6 text-sm">
                <a href="/api/Home.php" class="hover:text-amber-400">Home</a>
                <a href="/api/Tentang.php" class="hover:text-amber-400">Tentang</a>
                <span class="text-amber-300 font-bold">Hi, <?= htmlspecialchars($currentUser); ?></span>
                <a href="/api/Login.php?logout=true" class="bg-red-600 px-4 py-2 rounded-lg text-xs font-bold hover:bg-red-700 transition">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-10 max-w-6xl">
        
        <div class="relative rounded-[2.5rem] overflow-hidden shadow-xl mb-8 h-56">
            <img src="https://static.promediateknologi.id/crop/0x0:0x0/0x0/webp/photo/p2/220/2024/04/04/CaptureJPG-1596998515.jpg" class="w-full h-full object-cover" alt="Madiun Hero">
            <div class="absolute inset-0 bg-black/30 flex items-end p-10">
                <h2 class="text-3xl font-bold text-white">Wisata Kota Madiun</h2>
            </div>
        </div>

        <div class="stat-orange mb-10 relative bg-white p-8 rounded-3xl shadow-md border-l-8 border-orange-500">
            <p class="text-[10px] font-bold uppercase opacity-80 text-orange-800">Laporan Saya</p>
            <h4 class="text-4xl font-black text-orange-950"><?= $total_data; ?></h4>
            <span class="absolute right-8 top-1/2 -translate-y-1/2 text-5xl opacity-10">📂</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <div class="lg:col-span-2 space-y-10">
                
                <div class="glass-card bg-white p-6 rounded-3xl shadow-sm border border-orange-100">
                    <h3 class="font-bold text-orange-900 mb-6 flex items-center gap-2">
                        <span class="text-orange-600 text-2xl">|</span> Info Kategori Keluhan
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-orange-50/50 p-4 rounded-2xl border border-orange-100 text-xs">
                            <span class="text-orange-700 font-bold block">📍 Fasilitas</span>
                            <p class="text-stone-500">Toilet, Parkir, Bangku Jalan, Lampu, dll.</p>
                        </div>
                        <div class="bg-orange-50/50 p-4 rounded-2xl border border-orange-100 text-xs">
                            <span class="text-orange-700 font-bold block">🧹 Kebersihan</span>
                            <p class="text-stone-500">Sampah menumpuk, bau tidak sedap, limbah.</p>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-bold text-orange-900 mb-6 flex items-center gap-2">
                        <span class="text-orange-600 text-2xl">|</span> Daftar Laporan Anda
                    </h3>
                    <div class="bg-white rounded-3xl shadow-sm overflow-hidden border border-stone-200">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left table-auto">
                                <thead class="bg-orange-50 text-orange-900">
                                    <tr>
                                        <th class="p-4 text-xs font-black uppercase">Foto</th>
                                        <th class="p-4 text-xs font-black uppercase">Detail Informasi</th>
                                        <th class="p-4 text-xs font-black uppercase">Isi Keluhan</th>
                                        <th class="p-4 text-center text-xs font-black uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="text-xs divide-y divide-orange-50">
                                    <?php while($r = mysqli_fetch_assoc($reports_query)): ?>
                                    <tr class="hover:bg-orange-50/30 transition">
                                        <td class="p-4 align-top">
                                            <?php if(!empty($r['foto'])): ?>
                                                <?php if(strpos($r['foto'], 'data:image') === 0 || strpos($r['foto'], 'http') === 0): ?>
                                                    <img src="<?= $r['foto']; ?>" class="w-16 h-16 object-cover rounded-xl border border-orange-200 shadow-sm cursor-pointer" onclick="window.open(this.src)">
                                                <?php else: ?>
                                                    <a href="uploads/<?= $r['foto']; ?>" target="_blank">
                                                        <img src="uploads/<?= $r['foto']; ?>" class="w-16 h-16 object-cover rounded-xl border border-orange-200 shadow-sm">
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="w-16 h-16 bg-stone-100 rounded-xl flex items-center justify-center text-[8px] text-stone-400">N/A</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4 align-top space-y-1">
                                            <div class="text-stone-400 text-[10px]"><?= $r['tanggal_laporan']; ?></div>
                                            <div class="font-black text-stone-900"><?= htmlspecialchars($r['lokasi_wisata']); ?></div>
                                            <?php if(!empty($r['kategori'])): ?>
                                                <span class="inline-block bg-stone-100 text-stone-700 font-bold px-2 py-0.5 rounded text-[9px]"><?= htmlspecialchars($r['kategori']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4 align-top space-y-3">
                                            <p class="text-stone-600 italic">"<?= htmlspecialchars($r['isi_laporan']); ?>"</p>
                                            
                                            <?php if(!empty($r['tanggapan_admin'])): ?>
                                                <div class="bg-stone-50 border border-stone-200 p-3 rounded-xl space-y-1">
                                                    <span class="text-[9px] font-black text-orange-700 uppercase tracking-wider block">💬 Tanggapan Admin:</span>
                                                    <p class="text-stone-700 font-medium text-xs"><?= htmlspecialchars($r['tanggapan_admin']); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4 align-top text-center whitespace-nowrap">
                                            <?php 
                                            $badgeClass = 'bg-stone-100 text-stone-600 border border-stone-300'; 
                                            if ($r['status'] === 'Diterima') { 
                                                $badgeClass = 'bg-green-100 text-green-700 border border-green-300'; 
                                            } elseif ($r['status'] === 'Ditolak') { 
                                                $badgeClass = 'bg-red-100 text-red-700 border border-red-300'; 
                                            }
                                            ?>
                                            <span class="px-3 py-1 <?= $badgeClass; ?> rounded-full text-[9px] font-black uppercase tracking-wider">
                                                <?= htmlspecialchars($r['status'] ?? 'Diproses'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card bg-white p-6 rounded-3xl shadow-xl border-t-8 border-orange-600 h-fit sticky top-24">
                <h3 class="font-bold text-orange-900 mb-6 text-center text-xl">Buat Pengaduan</h3>
                
                <form action="proses_simpan.php" method="POST" class="space-y-4">
                    <input type="hidden" name="foto_base64" id="foto_base64">

                    <div>
                        <label class="text-[9px] font-bold text-gray-400 uppercase">Pelapor</label>
                        <input type="text" name="nama_pelapor" value="<?= htmlspecialchars($currentUser); ?>" class="w-full px-4 py-3 rounded-xl bg-stone-100 border border-stone-200 text-stone-500 font-bold outline-none" readonly>
                    </div>

                    <div>
                        <label class="text-[9px] font-bold text-stone-600 uppercase">Kategori Masalah</label>
                        <select name="kategori" class="w-full px-4 py-3 rounded-xl border border-stone-200 focus:ring-2 focus:ring-orange-500 outline-none" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Fasilitas">Fasilitas Rusak (Bangku, Toilet, Lampu)</option>
                            <option value="Kebersihan">Masalah Kebersihan / Sampah</option>
                            <option value="Keamanan">Keamanan & Parkir Liar</option>
                            <option value="Pelayanan">Pelayanan Petugas Wisata</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="text-[9px] font-bold text-stone-600 uppercase">Nama Destinasi Wisata</label>
                        <select name="lokasi_wisata" class="w-full px-4 py-3 rounded-xl border border-stone-200 focus:ring-2 focus:ring-orange-500 outline-none" required>
                            <option value="">-- Pilih Lokasi --</option>
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

                    <div>
                        <label class="text-[9px] font-bold text-stone-600 uppercase">Koordinat Geografis (Otomatis via Foto)</label>
                        <input type="text" id="gps_koordinat" name="gps_koordinat" placeholder="Menunggu Anda mengambil foto..." class="w-full px-4 py-3 rounded-xl bg-stone-50 border border-stone-200 text-stone-700 font-medium text-xs outline-none" readonly required>
                        <p id="geo_status" class="text-[10px] text-stone-400 mt-1 italic">Pin peta otomatis berpindah setelah foto dipilih.</p>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[9px] font-bold text-stone-600 uppercase block">Titik Lokasi Peta</label>
                        <div id="userMap" class="w-full h-44 rounded-2xl border border-stone-200 shadow-sm z-10"></div>
                    </div>

                    <div>
                        <label class="text-[9px] font-bold text-gray-400 uppercase">Isi Keluhan</label>
                        <textarea name="isi_laporan" rows="3" class="w-full px-4 py-3 rounded-xl border border-stone-200 focus:ring-2 focus:ring-orange-500 outline-none" placeholder="Jelaskan detail masalah..." required></textarea>
                    </div>

                    <div class="bg-orange-50 p-4 rounded-xl border border-dashed border-orange-200">
                        <label class="text-[9px] font-bold text-orange-700 uppercase block mb-2">Ambil Foto Bukti (Kamera HP)</label>
                        <input type="file" 
                               id="cameraField" 
                               accept="image/jpeg, image/jpg, image/png" 
                               capture="environment" 
                               required
                               class="block w-full text-xs text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-orange-600 file:text-white hover:file:bg-orange-700 cursor-pointer">
                    </div>

                    <button type="submit" class="w-full bg-orange-600 text-white font-black py-4 rounded-2xl shadow-lg shadow-orange-200 hover:bg-orange-700 transition transform hover:scale-[1.02]">
                        Kirim Laporan 🚀
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const defaultLat = -7.6298;
        const defaultLng = 111.5240;

        const map = L.map('userMap').setView([defaultLat, defaultLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
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

            document.getElementById('geo_status').innerText = "Memproses metadata file foto...";
            document.getElementById('geo_status').className = "text-[10px] text-amber-600 mt-1 font-bold animate-pulse";

            // PIPELINE 1: Convert the photo into a Base64 string instantly for TiDB Submission
            const reader = new FileReader();
            reader.onloadend = function() {
                document.getElementById('foto_base64').value = reader.result;
            }
            reader.readAsDataURL(file);

            // PIPELINE 2: Parse EXIF Geo-Coordinates
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

                    document.getElementById('geo_status').innerText = "✅ Lokasi foto berhasil diekstrak otomatis ke peta!";
                    document.getElementById('geo_status').className = "text-[10px] text-green-600 mt-1 font-bold";
                } else {
                    document.getElementById('geo_status').innerText = "⚠️ Tidak ada data GPS di foto. Mencoba GPS perangkat...";
                    
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                const deviceLat = position.coords.latitude;
                                const deviceLng = position.coords.longitude;
                                const currentLoc = new L.LatLng(deviceLat, deviceLng);
                                
                                marker.setLatLng(currentLoc);
                                map.setView(currentLoc, 16);
                                updateFormFields(deviceLat, deviceLng);
                                document.getElementById('geo_status').innerText = "📍 Menggunakan lokasi live perangkat Anda.";
                                document.getElementById('geo_status').className = "text-[10px] text-blue-600 mt-1 font-bold";
                            },
                            () => {
                                document.getElementById('geo_status').innerText = "❌ Gagal mendeteksi lokasi otomatis.";
                                document.getElementById('geo_status').className = "text-[10px] text-red-500 mt-1";
                            }
                        );
                    }
                }
            });
        });
    </script>
</body>
</html>