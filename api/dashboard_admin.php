<?php
/**
 * dashboard_admin.php
 * Optimized: Crash Protection, Lazy Payload Execution, and Interactive Modals
 */
include 'koneksi.php';

// 1. Security Check: Must be logged in AND must be an admin
if (!isset($_COOKIE['isLoggedIn']) || $_COOKIE['isLoggedIn'] !== 'true' || $_COOKIE['role'] !== 'admin') { 
    header("Location: /api/Login.php"); 
    exit(); 
}

// Get display name from cookie
$current_user = $_COOKIE['username'] ?? 'Admin';

// 2. Fetch Dynamic Dashboard Metrics
$total_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM laporan");
if (!$total_q) {
    die("<div style='color:red; font-family:sans-serif; padding:20px; background:#ffebee; border-radius:10px; margin:20px;'>".
        "<h3>❌ Admin Connection Failed!</h3><strong>Error:</strong> " . mysqli_error($koneksi) . "</div>");
}
$total_reports = mysqli_fetch_assoc($total_q)['total'] ?? 0;

$accepted_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM laporan WHERE status='Diterima'");
$accepted_reports = (!$accepted_q) ? 0 : (mysqli_fetch_assoc($accepted_q)['total'] ?? 0);

$pending_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM laporan WHERE status='Proses' OR status='Menunggu' OR status='Diproses'");
$pending_reports = (!$pending_q) ? 0 : (mysqli_fetch_assoc($pending_q)['total'] ?? 0);

$efficiency = ($total_reports > 0) ? ($accepted_reports / $total_reports) * 100 : 0;

// CRITICAL FIX: Fetch metadata first, keep payload handled efficiently
$all_reports = mysqli_query($koneksi, "SELECT id_laporan, nama_pelapor, lokasi_wisata, kategori, gps_koordinat, isi_laporan, status, tanggal_laporan, tanggapan_admin, foto FROM laporan ORDER BY tanggal_laporan DESC");
if (!$all_reports) {
    die("<div style='color:red; font-family:sans-serif; padding:20px; background:#ffebee; border-radius:10px; margin:20px;'>".
        "<h3>❌ Failed to fetch Laporan List</h3><strong>Error:</strong> " . mysqli_error($koneksi) . "</div>");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Laporan Wisata Madiun</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#fffaf5] text-stone-800">

    <nav class="bg-[#4a2c1d] text-white shadow-lg mb-10 sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="font-bold text-xl flex items-center gap-2">🍂 Admin Panel</h1>
            <div class="flex items-center space-x-6 text-sm">
                <a href="/api/Home.php" class="hover:text-amber-400">Home</a>
                <a href="/api/Tentang.php" class="hover:text-amber-400">Tentang</a>
                <span class="text-amber-300 font-bold">Hi, <?= htmlspecialchars($current_user); ?></span>
                <a href="/api/Login.php?logout=true" class="bg-red-600 px-4 py-2 rounded-lg font-bold hover:bg-red-700 transition">Logout</a>
            </div>
        </div>
    </nav>   

    <div class="container mx-auto px-4 max-w-6xl">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border-l-8 border-stone-400">
                <p class="text-[10px] font-bold text-gray-400 uppercase">Total Laporan Masuk</p>
                <h2 class="text-4xl font-black text-stone-800"><?= $total_reports; ?></h2>
            </div>
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border-l-8 border-amber-500">
                <p class="text-[10px] font-bold text-gray-400 uppercase">Dalam Proses Evaluasi</p>
                <h2 class="text-4xl font-black text-amber-500"><?= $pending_reports; ?></h2>
            </div>
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border-l-8 border-green-500">
                <p class="text-[10px] font-bold text-gray-400 uppercase">Laporan Diterima (Valid)</p>
                <h2 class="text-4xl font-black text-green-600"><?= $accepted_reports; ?></h2>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <div class="lg:col-span-2">
                <h3 class="font-bold text-orange-900 mb-6 flex items-center gap-2">
                    <span class="text-orange-600 text-2xl">|</span> Data Administrasi Laporan Wisata
                </h3>
                <div class="bg-white rounded-[2rem] shadow-sm overflow-hidden border border-orange-50">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left table-auto">
                            <thead class="bg-[#5c3d2e] text-white font-bold uppercase text-xs">
                                <tr>
                                    <th class="p-4 w-24">Bukti Foto</th> 
                                    <th class="p-4">Detail Informasi Keluhan</th>
                                    <th class="p-4 text-center">Status</th>
                                    <th class="p-4 text-center">Aksi Moderasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-orange-50 text-xs">
                                <?php while($row = mysqli_fetch_assoc($all_reports)): ?>
                                <tr class="hover:bg-orange-50/30 transition-colors">
                                    
                                    <td class="p-4 align-top">
                                        <?php if(!empty($row['foto'])): ?>
                                            <div class="relative w-16 h-16 cursor-pointer group" onclick="openPhotoModal(this)" data-photo="<?= htmlspecialchars($row['foto']); ?>">
                                                <img src="<?= (strpos($row['foto'], 'data:image') === 0 || strpos($row['foto'], 'http') === 0) ? $row['foto'] : '../uploads/'.$row['foto']; ?>" 
                                                     class="w-16 h-16 object-cover rounded-xl border border-stone-200 shadow-sm group-hover:opacity-80 transition"
                                                     loading="lazy"
                                                     onerror="this.src='https://placehold.co/150x150?text=Bukti+Foto'">
                                                <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 rounded-xl flex items-center justify-center text-white text-[9px] font-bold transition">Buka</div>
                                            </div>
                                        <?php else: ?>
                                            <div class="w-16 h-16 bg-stone-100 rounded-xl flex items-center justify-center text-[8px] text-stone-400">No Image</div>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-4 align-top space-y-2">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-[10px] text-gray-400 font-medium"><?= $row['tanggal_laporan']; ?></span>
                                            <span class="font-black text-orange-900 text-sm"><?= htmlspecialchars($row['lokasi_wisata'] ?? ''); ?></span>
                                            <span class="text-stone-500 font-semibold">Pelapor: <?= htmlspecialchars($row['nama_pelapor'] ?? ''); ?></span>
                                        </div>

                                        <div class="flex flex-wrap gap-2 items-center">
                                            <?php if(!empty($row['kategori'])): ?>
                                                <span class="bg-orange-100 text-orange-800 font-bold px-2 py-0.5 rounded text-[9px] uppercase tracking-wider"><?= htmlspecialchars($row['kategori']); ?></span>
                                            <?php endif; ?>
                                            
                                            <?php if(!empty($row['gps_koordinat'])): ?>
                                                <a href="https://www.openstreetmap.org/search?query=<?= urlencode($row['gps_koordinat']); ?>" target="_blank" 
                                                   class="bg-blue-50 text-blue-600 hover:underline font-bold px-2 py-0.5 rounded text-[9px] flex items-center gap-1">
                                                   🗺️ Lihat di Peta
                                                </a>
                                            <?php endif; ?>
                                        </div>

                                        <p class="text-stone-600 bg-stone-50/80 p-2 rounded-xl italic border border-stone-100">"<?= htmlspecialchars($row['isi_laporan'] ?? ''); ?>"</p>

                                        <div class="mt-3 pt-2 border-t border-stone-100">
                                            <form action="simpan_tanggapan.php" method="POST" class="flex gap-2">
                                                <input type="hidden" name="id_laporan" value="<?= $row['id_laporan']; ?>">
                                                <input type="text" name="tanggapan_admin" 
                                                       placeholder="Berikan tanggapan instruksi perbaikan..." 
                                                       value="<?= htmlspecialchars($row['tanggapan_admin'] ?? ''); ?>"
                                                       class="w-full px-3 py-1.5 bg-stone-50 border border-stone-200 rounded-xl text-xs outline-none focus:ring-2 focus:ring-orange-500 transition-all shadow-inner">
                                                <button type="submit" class="bg-stone-900 text-white font-bold px-3 py-1.5 rounded-xl hover:bg-black transition text-[10px]">
                                                    Balas
                                                </button>
                                            </form>
                                        </div>
                                    </td>

                                    <td class="p-4 align-top text-center whitespace-nowrap">
                                        <?php 
                                        $statusVal = $row['status'] ?? 'Menunggu';
                                        $badgeStyle = "bg-stone-100 text-stone-600 border-stone-200"; 
                                        if ($statusVal === 'Diterima') { $badgeStyle = "bg-green-50 text-green-700 border-green-200"; }
                                        elseif ($statusVal === 'Tidak Diterima' || $statusVal === 'Ditolak') { $badgeStyle = "bg-red-50 text-red-700 border-red-200"; }
                                        ?>
                                        <span class="px-2 py-1 <?= $badgeStyle; ?> rounded-full text-[9px] font-black border uppercase tracking-wider">
                                            <?= htmlspecialchars(($statusVal === 'Menunggu' || $statusVal === 'Proses' || $statusVal === 'Diproses') ? 'Proses' : $statusVal); ?>
                                        </span>
                                    </td>

                                    <td class="p-4 align-top">
                                        <div class="flex flex-col gap-1.5 justify-center h-full">
                                            <form action="update_status.php" method="POST" class="w-full">
                                                <input type="hidden" name="id_laporan" value="<?= $row['id_laporan']; ?>">
                                                <input type="hidden" name="status" value="Diterima">
                                                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-1 px-2 rounded-lg font-black text-[9px] uppercase transition shadow-sm">
                                                    Terima
                                                </button>
                                            </form>

                                            <form action="update_status.php" method="POST" class="w-full">
                                                <input type="hidden" name="id_laporan" value="<?= $row['id_laporan']; ?>">
                                                <input type="hidden" name="status" value="Tidak Diterima">
                                                <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white py-1 px-2 rounded-lg font-black text-[9px] uppercase transition shadow-sm">
                                                    Tolak
                                                </button>
                                            </form>

                                            <a href="hapus_laporan.php?id=<?= $row['id_laporan']; ?>" 
                                               onclick="return confirm('Hapus permanen berkas data laporan ini?')" 
                                               class="w-full bg-red-600 hover:bg-red-700 text-white text-center py-1 px-2 rounded-lg font-black text-[9px] uppercase transition shadow-sm">
                                                Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-[2rem] shadow-md border border-orange-50 sticky top-24">
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-4">Efisiensi Penanganan Keluhan</p>
                    <div class="flex justify-between text-xs font-bold mb-2">
                        <span>Laporan Terverifikasi</span>
                        <span class="text-green-600"><?= round($efficiency); ?>%</span>
                    </div>
                    <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                        <div class="bg-green-500 h-full transition-all duration-500" style="width: <?= $efficiency; ?>%"></div>
                    </div>
                    <p class="text-[10px] text-stone-400 mt-2 leading-relaxed italic">Persentase dihitung berdasarkan total keluhan publik yang dinilai valid ("Diterima") dibanding total keseluruhan data masuk.</p>
                </div>
            </div>
        </div>
    </div>

    <div id="photoModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-50 p-4 transition-all opacity-0 duration-300" onclick="closePhotoModal()">
        <div class="relative max-w-3xl w-full max-h-[85vh] flex items-center justify-center" onclick="event.stopPropagation()">
            <button class="absolute -top-10 right-0 text-white font-bold text-sm bg-white/10 hover:bg-white/20 px-3 py-1 rounded-full transition" onclick="closePhotoModal()">✕ Tutup</button>
            <img id="modalTargetImg" src="" class="max-w-full max-h-[85vh] object-contain rounded-2xl shadow-2xl border border-white/10">
        </div>
    </div>

    <script>
        function openPhotoModal(element) {
            const rawData = element.getAttribute('data-photo');
            if(!rawData) return;
            
            const targetImg = document.getElementById('modalTargetImg');
            const modal = document.getElementById('photoModal');
            
            // If the raw image isn't a base64 or external url link, route to fallback path cleanly
            if(rawData.indexOf('data:image') !== 0 && rawData.indexOf('http') !== 0) {
                targetImg.src = '../uploads/' + rawData;
            } else {
                targetImg.src = rawData;
            }
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => { modal.classList.add('opacity-100'); }, 10);
        }

        function closePhotoModal() {
            const modal = document.getElementById('photoModal');
            modal.classList.remove('opacity-100');
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
                document.getElementById('modalTargetImg').src = '';
            }, 300);
        }
    </script>
</body>
</html>