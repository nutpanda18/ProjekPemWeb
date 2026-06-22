<?php
/**
 * dashboard_admin.php
 * Updated: Left Sidebar SPA Layout with Modern Chart.js Ringkasan Integration
 */
include 'koneksi.php';

// 1. Security Check
if (!isset($_COOKIE['isLoggedIn']) || $_COOKIE['isLoggedIn'] !== 'true' || $_COOKIE['role'] !== 'admin') { 
    header("Location: /api/Login.php"); 
    exit(); 
}

$current_user = $_COOKIE['username'] ?? 'Admin';

// 2. Fetch Metrics
$total_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM laporan");
if (!$total_q) {
    die("<div style='color:red; font-family:sans-serif; padding:20px; background:#ffebee; border-radius:10px; margin:20px;'>".
        "<h3>❌ Connection Error</h3><strong>Error:</strong> " . mysqli_error($koneksi) . "</div>");
}
$total_reports = mysqli_fetch_assoc($total_q)['total'] ?? 0;

$accepted_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM laporan WHERE status='Diterima'");
$accepted_reports = (!$accepted_q) ? 0 : (mysqli_fetch_assoc($accepted_q)['total'] ?? 0);

$pending_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM laporan WHERE status='Proses' OR status='Menunggu' OR status='Diproses'");
$pending_reports = (!$pending_q) ? 0 : (mysqli_fetch_assoc($pending_q)['total'] ?? 0);

$efficiency = ($total_reports > 0) ? ($accepted_reports / $total_reports) * 100 : 0;

// Fetch total counts grouped by category
$category_labels = [];
$category_values = [];
$category_counts = [];

$cat_q = mysqli_query($koneksi, "SELECT kategori, COUNT(*) as jumlah FROM laporan WHERE kategori IS NOT NULL AND kategori != '' GROUP BY kategori ORDER BY jumlah DESC");
if ($cat_q) {
    while ($row_cat = mysqli_fetch_assoc($cat_q)) {
        $cat_name = strtoupper(trim($row_cat['kategori']));
        $category_counts[$cat_name] = $row_cat['jumlah'];
        $category_labels[] = $cat_name;
        $category_values[] = (int)$row_cat['jumlah'];
    }
}
$top_category = !empty($category_counts) ? array_key_first($category_counts) : 'Belum Ada';

// Fetch reports list
$all_reports = mysqli_query($koneksi, "SELECT id_laporan, nama_pelapor, lokasi_wisata, kategori, gps_koordinat, isi_laporan, status, tanggal_laporan, tanggapan_admin, foto FROM laporan ORDER BY tanggal_laporan DESC");

// Fetch accounts for team management section
$team_users = [];
$user_table_check = mysqli_query($koneksi, "SHOW TABLES LIKE 'user'");
if (mysqli_num_rows($user_table_check) > 0) {
    $users_q = mysqli_query($koneksi, "SELECT id, username, role FROM user ORDER BY role ASC, username ASC");
    if ($users_q) {
        while ($u_row = mysqli_fetch_assoc($users_q)) {
            $team_users[] = $u_row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Laporan Wisata Madiun</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js for beautiful analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-view { transition: opacity 0.2s ease-in-out; }
    </style>
</head>
<body class="bg-[#fcf8f5] text-stone-800 min-h-screen flex flex-row overflow-x-hidden">

    <!-- FIXED LEFT SIDEBAR PANEL -->
    <aside class="w-72 bg-[#3e2316] text-white flex flex-col justify-between p-6 shrink-0 shadow-xl min-h-screen sticky top-0">
        <div class="space-y-8">
            <div class="border-b border-white/10 pb-4">
                <h1 class="font-black text-xl flex items-center gap-2 tracking-wide text-amber-100">🍂 AdminPanel</h1>
                <p class="text-[10px] text-amber-200/60 font-semibold uppercase tracking-wider mt-1">Laporan Wisata Madiun</p>
            </div>

            <nav class="space-y-2" id="sidebar-nav">
                <p class="text-[10px] uppercase tracking-wider text-amber-200/40 font-bold px-3 mb-2">Main Menu</p>
                
                <button onclick="switchView('ringkasan', this)" id="btn-ringkasan" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm text-amber-300 bg-white/10 border-l-4 border-amber-400 text-left transition-all shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor"><path d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Z"/></svg> Ringkasan Data Laporan
                </button>
                
                <button onclick="switchView('kelola', this)" id="btn-kelola" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm text-white/80 hover:text-white hover:bg-white/5 border-l-4 border-transparent text-left transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor"><path d="M280-280h280v-80H280v80Zm0-160h400v-80H280v80Zm0-160h400v-80H280v80Zm-80 480q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Z"/></svg> Kelola Laporan
                </button>

                <button onclick="switchView('karyawan', this)" id="btn-karyawan" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm text-white/80 hover:text-white hover:bg-white/5 border-l-4 border-transparent text-left transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor"><path d="M400-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM80-160v-112q0-33 17-62t47-44q51-26 115-44t141-18q77 0 141 18t115 44q30 15 47 44t17 62v112H80Z"/></svg> Kelola Pengguna Tim
                </button>

                <p class="text-[10px] uppercase tracking-wider text-amber-200/40 font-bold px-3 pt-4 mb-2">Aksi Eksternal</p>
                <a href="/api/Home.php" target="_blank" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs text-amber-200/70 hover:text-white transition">🌐 Landing Page</a>
            </nav>
        </div>

        <div class="border-t border-white/10 pt-4 space-y-3">
            <div class="flex items-center justify-between px-2 text-xs">
                <span class="text-stone-300">Account:</span>
                <strong class="text-amber-300 font-bold"><?= htmlspecialchars($current_user); ?></strong>
            </div>
            <a href="/api/Login.php?logout=true" class="w-full bg-red-600 px-4 py-3 rounded-xl font-bold text-xs text-center block hover:bg-red-700 transition shadow-md">Keluar Akun</a>
        </div>
    </aside>

    <!-- RIGHT MAIN CONTENT CONTENT BODY -->
    <main class="flex-1 p-8 lg:p-12 max-w-7xl overflow-y-auto">
        
        <header class="flex items-center justify-between border-b border-stone-200 pb-6 mb-10">
            <div>
                <h2 id="view-title" class="text-2xl font-black text-stone-900">Ringkasan Data Laporan</h2>
                <p id="view-subtitle" class="text-xs text-stone-400 mt-0.5">Berikut adalah ringkasan data laporan.</p>
            </div>
            <div class="bg-white px-4 py-2 rounded-2xl border border-stone-200/60 shadow-sm text-xs font-semibold text-stone-600">
                📅 Hari Ini: <span class="text-stone-900"><?= date('d M Y'); ?></span>
            </div>
        </header>

        <!-- ================= VIEW 1: RINGKASAN & CHART ANALYTICS ================= -->
        <div id="view-ringkasan-content" class="dashboard-view space-y-8">
            
            <!-- Quick Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-stone-100 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Laporan</p>
                        <h2 class="text-4xl font-black text-stone-800 mt-1"><?= $total_reports; ?></h2>
                    </div>
                    <div class="p-3 bg-stone-100 rounded-2xl text-xl">📁</div>
                </div>
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-stone-100 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Dalam Proses</p>
                        <h2 class="text-4xl font-black text-amber-600 mt-1"><?= $pending_reports; ?></h2>
                    </div>
                    <div class="p-3 bg-amber-50 rounded-2xl text-xl">⏳</div>
                </div>
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-stone-100 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Laporan Selesai</p>
                        <h2 class="text-4xl font-black text-emerald-600 mt-1"><?= $accepted_reports; ?></h2>
                    </div>
                    <div class="p-3 bg-emerald-50 rounded-2xl text-xl">✅</div>
                </div>
            </div>

            <!-- Chart & breakdown split layout -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
                
                <!-- Chart Panel -->
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-stone-100 lg:col-span-3 flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-stone-900 text-sm">📊 Proporsi Kategori Keluhan</h3>
                        <p class="text-[11px] text-stone-400 mb-6">Visualisasi persentase pengaduan masuk.</p>
                    </div>
                    <div class="relative max-w-[280px] mx-auto w-full pb-4">
                        <?php if(empty($category_counts)): ?>
                            <div class="text-center py-12 text-xs text-stone-400 italic">Data Chart Belum Tersedia</div>
                        <?php else: ?>
                            <canvas id="categoryDoughnutChart"></canvas>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Progress Metric Panel -->
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-stone-100 lg:col-span-2 flex flex-col justify-between space-y-6">
                    <div>
                        <h3 class="font-bold text-stone-900 text-sm">📈 Penyelesaian Kasus</h3>
                        <p class="text-[11px] text-stone-400 mb-4">Rasio efisiensi penanganan pengaduan valid.</p>
                        
                        <div class="bg-[#fffaf5] border border-orange-100/50 p-4 rounded-2xl mt-2">
                            <div class="flex justify-between text-xs font-bold mb-2">
                                <span class="text-stone-600">Laporan Terverifikasi</span>
                                <span class="text-emerald-600 font-extrabold"><?= round($efficiency); ?>%</span>
                            </div>
                            <div class="w-full bg-stone-200/60 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-emerald-500 h-full transition-all duration-500" style="width: <?= $efficiency; ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-[10px] uppercase tracking-wider text-stone-400 font-bold mb-2">Tren Terbanyak</h4>
                        <?php if(!empty($category_counts)): ?>
                            <div class="bg-amber-50/60 border border-amber-200/60 rounded-xl p-3 flex items-center gap-2.5 text-xs">
                                <span class="text-lg">🔥</span>
                                <span class="text-stone-700">Kategori <strong class="text-amber-900 uppercase"><?= htmlspecialchars($top_category); ?></strong> mendominasi laporan masuk.</span>
                            </div>
                        <?php else: ?>
                            <p class="text-xs text-stone-400 italic">Belum ada statistik laporan.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Clean Table Breakdown underneath -->
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-stone-100">
                <h3 class="font-bold text-stone-900 text-sm mb-4">📋 Rincian Data Angka Kategori</h3>
                <?php if(empty($category_counts)): ?>
                    <p class="text-xs text-stone-400 italic text-center py-2">Belum ada data terkumpul.</p>
                <?php else: ?>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <?php foreach($category_counts as $cat_name => $count): ?>
                            <div class="bg-[#fcfcfc] border border-stone-200/50 p-4 rounded-xl">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-stone-400 block truncate"><?= htmlspecialchars($cat_name); ?></span>
                                <div class="flex items-baseline justify-between mt-1">
                                    <span class="text-xl font-black text-stone-800"><?= $count; ?></span>
                                    <span class="text-[10px] text-stone-400 font-medium">Laporan</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ================= VIEW 2: KELOLA LAPORAN TABLE ================= -->
        <div id="view-kelola-content" class="dashboard-view hidden space-y-6">
            <div class="flex flex-wrap items-center gap-2 border-b border-stone-200 pb-3" id="tab-filter-bar">
                <button onclick="filterTableCategory('ALL', this)" class="px-4 py-2 bg-[#3e2316] text-white text-xs font-bold rounded-xl shadow-sm transition">Semua Laporan</button>
                <button onclick="filterTableCategory('FASILITAS', this)" class="px-4 py-2 bg-white border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-semibold rounded-xl transition">🧱 Fasilitas</button>
                <button onclick="filterTableCategory('KEBERSIHAN', this)" class="px-4 py-2 bg-white border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-semibold rounded-xl transition">✨ Kebersihan</button>
                <button onclick="filterTableCategory('PELAYANAN', this)" class="px-4 py-2 bg-white border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-semibold rounded-xl transition">🛎️ Pelayanan</button>
                <button onclick="filterTableCategory('KEAMANAN', this)" class="px-4 py-2 bg-white border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-semibold rounded-xl transition">🛡️ Keamanan</button>
            </div>

            <div id="empty-table-placeholder" class="hidden bg-white p-12 text-center rounded-[2rem] border border-stone-100 shadow-sm">
                <span class="text-4xl">🍃</span>
                <h4 class="font-bold text-stone-700 mt-2 text-sm">Tidak Ada Laporan</h4>
                <p class="text-xs text-stone-400 mt-0.5">Belum ditemukan berkas pengaduan dalam kategori ini.</p>
            </div>

            <div id="table-card-wrapper" class="bg-white rounded-[2rem] shadow-sm overflow-hidden border border-stone-100">
                <div class="overflow-x-auto">
                    <table class="w-full text-left table-auto">
                        <thead class="bg-[#523324] text-white font-bold uppercase text-xs">
                            <tr>
                                <th class="p-4 w-24">Bukti Foto</th> 
                                <th class="p-4">Detail Informasi Keluhan</th>
                                <th class="p-4 text-center">Status</th>
                                <th class="p-4 text-center">Aksi Moderasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 text-xs" id="reports-table-body">
                            <?php while($row = mysqli_fetch_assoc($all_reports)): 
                                $rowCatClean = strtoupper(trim($row['kategori'] ?? 'UMUM'));
                            ?>
                            <tr class="hover:bg-stone-50/40 transition-colors report-data-row" data-category="<?= htmlspecialchars($rowCatClean); ?>">
                                <td class="p-4 align-top">
                                    <?php if(!empty($row['foto'])): ?>
                                        <div class="relative w-16 h-16 cursor-pointer group" onclick="openPhotoModal(this)" data-photo="<?= htmlspecialchars($row['foto']); ?>">
                                            <img src="<?= (strpos($row['foto'], 'data:image') === 0 || strpos($row['foto'], 'http') === 0) ? $row['foto'] : '../uploads/'.$row['foto']; ?>" class="w-16 h-16 object-cover rounded-xl border border-stone-200 shadow-sm" loading="lazy">
                                        </div>
                                    <?php else: ?>
                                        <div class="w-16 h-16 bg-stone-100 rounded-xl flex items-center justify-center text-[8px] text-stone-400">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 align-top space-y-2">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[10px] text-gray-400 font-medium"><?= $row['tanggal_laporan']; ?></span>
                                        <span class="font-black text-stone-900 text-sm"><?= htmlspecialchars($row['lokasi_wisata'] ?? ''); ?></span>
                                        <span class="text-stone-500 font-semibold">Pelapor: <?= htmlspecialchars($row['nama_pelapor'] ?? ''); ?></span>
                                    </div>
                                    <div class="flex flex-wrap gap-2 items-center">
                                        <span class="bg-stone-100 text-stone-800 font-bold px-2 py-0.5 rounded text-[9px] uppercase tracking-wide"><?= htmlspecialchars($row['kategori'] ?? 'UMUM'); ?></span>
                                        <?php if(!empty($row['gps_koordinat'])): ?>
                                            <a href="https://www.openstreetmap.org/search?query=<?= urlencode($row['gps_koordinat']); ?>" target="_blank" class="bg-blue-50 text-blue-600 font-bold px-2 py-0.5 rounded text-[9px]">🗺️ Peta</a>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-stone-600 bg-stone-50/80 p-2 rounded-xl italic">"<?= htmlspecialchars($row['isi_laporan'] ?? ''); ?>"</p>
                                    <div class="mt-3 pt-2">
                                        <form action="simpan_tanggapan.php" method="POST" class="flex gap-2">
                                            <input type="hidden" name="id_laporan" value="<?= $row['id_laporan']; ?>">
                                            <input type="text" name="tanggapan_admin" placeholder="Tanggapan perbaikan..." value="<?= htmlspecialchars($row['tanggapan_admin'] ?? ''); ?>" class="w-full px-3 py-1.5 bg-stone-50 border rounded-xl text-xs outline-none focus:ring-2 focus:ring-amber-500">
                                            <button type="submit" class="bg-stone-900 text-white font-bold px-3 py-1.5 rounded-xl text-[10px]">Balas</button>
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
                                    <span class="px-2 py-1 <?= $badgeStyle; ?> rounded-full text-[9px] font-black border uppercase">
                                        <?= htmlspecialchars(($statusVal === 'Menunggu' || $statusVal === 'Proses') ? 'Proses' : $statusVal); ?>
                                    </span>
                                </td>
                                <td class="p-4 align-top">
                                    <div class="flex flex-col gap-1.5">
                                        <form action="update_status.php" method="POST"><input type="hidden" name="id_laporan" value="<?= $row['id_laporan']; ?>"><input type="hidden" name="status" value="Diterima"><button type="submit" class="w-full bg-green-600 text-white py-1 rounded-lg font-black text-[9px] uppercase">Terima</button></form>
                                        <form action="update_status.php" method="POST"><input type="hidden" name="id_laporan" value="<?= $row['id_laporan']; ?>"><input type="hidden" name="status" value="Tidak Diterima"><button type="submit" class="w-full bg-amber-600 text-white py-1 rounded-lg font-black text-[9px] uppercase">Tolak</button></form>
                                        <a href="hapus_laporan.php?id=<?= $row['id_laporan']; ?>" onclick="return confirm('Hapus permanen data ini?')" class="w-full bg-red-600 text-white text-center block py-1 rounded-lg font-black text-[9px] uppercase">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================= VIEW 3: USER/TEAM MANAGEMENT PANEL ================= -->
        <div id="view-karyawan-content" class="dashboard-view hidden space-y-6">
            <div class="bg-white rounded-[2rem] shadow-sm overflow-hidden border border-stone-100">
                <div class="p-6 border-b border-stone-100">
                    <h3 class="font-bold text-stone-900 text-sm">Daftar Akun Pengguna Platform</h3>
                    <p class="text-[11px] text-stone-400">Seluruh akun yang terdaftar pada sistem pengaduan pariwisata daerah Madiun.</p>
                </div>
                
                <?php if(empty($team_users)): ?>
                    <div class="p-8 text-center text-xs text-stone-400 italic">Data tabel `user` kosong atau tidak ditemukan.</div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left table-auto">
                            <thead class="bg-[#523324] text-white font-bold uppercase text-xs">
                                <tr>
                                    <th class="p-4">ID Pengguna</th>
                                    <th class="p-4">Username</th>
                                    <th class="p-4 text-center">Hak Akses / Role</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 text-xs">
                                <?php foreach($team_users as $user_row): ?>
                                <tr class="hover:bg-stone-50/40 transition-colors">
                                    <td class="p-4 font-mono font-bold text-stone-400">#<?= $user_row['id']; ?></td>
                                    <td class="p-4 font-black text-stone-800"><?= htmlspecialchars($user_row['username']); ?></td>
                                    <td class="p-4 text-center">
                                        <?php if(strtolower($user_row['role']) === 'admin'): ?>
                                            <span class="px-3 py-1 bg-red-50 border border-red-200 text-red-700 rounded-full font-black uppercase tracking-wider text-[9px]">👑 Admin Sistem</span>
                                        <?php else: ?>
                                            <span class="px-3 py-1 bg-blue-50 border border-blue-200 text-blue-700 rounded-full font-bold uppercase tracking-wider text-[9px]">👤 Pelapor / User</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <!-- FLOATING LIGHTBOX CONTAINER -->
    <div id="photoModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-50 p-4" onclick="closePhotoModal()">
        <div class="relative max-w-3xl w-full" onclick="event.stopPropagation()">
            <img id="modalTargetImg" src="" class="max-w-full max-h-[85vh] object-contain rounded-2xl mx-auto shadow-2xl">
        </div>
    </div>

    <!-- MAIN JAVASCRIPT LOGIC OPERATIONS -->
    <script>
        // Inject Dynamic Database data safely into ChartJS
        const chartLabels = <?= json_encode($category_labels); ?>;
        const chartDataValues = <?= json_encode($category_values); ?>;

        document.addEventListener("DOMContentLoaded", function () {
            if (chartLabels.length > 0) {
                const ctx = document.getElementById('categoryDoughnutChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            data: chartDataValues,
                            backgroundColor: [
                                '#a8a29e', // FASILITAS
                                '#d97706', // KEBERSIHAN
                                '#f59e0b', // PELAYANAN
                                '#78716c', // KEAMANAN
                                '#e7e5e4'  // LAINNYA
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    font: { size: 10, family: 'sans-serif', weight: 'bold' },
                                    color: '#444444'
                                }
                            }
                        }
                    }
                });
            }
        });

        function switchView(viewName, buttonElement) {
            document.getElementById('view-ringkasan-content').classList.add('hidden');
            document.getElementById('view-kelola-content').classList.add('hidden');
            document.getElementById('view-karyawan-content').classList.add('hidden');
            
            const navButtons = document.querySelectorAll('#sidebar-nav button');
            navButtons.forEach(btn => {
                btn.classList.remove('bg-white/10', 'text-amber-300', 'font-bold', 'border-amber-400');
                btn.classList.add('text-white/80', 'font-semibold', 'border-transparent');
            });

            buttonElement.classList.remove('text-white/80', 'font-semibold', 'border-transparent');
            buttonElement.classList.add('bg-white/10', 'text-amber-300', 'font-bold', 'border-amber-400');

            if (viewName === 'ringkasan') {
                document.getElementById('view-ringkasan-content').classList.remove('hidden');
                document.getElementById('view-title').textContent = "Ringkasan Data Laporan";
                document.getElementById('view-subtitle').textContent = "Berikut adalah ringkasan data laporan.";
            } else if (viewName === 'kelola') {
                document.getElementById('view-kelola-content').classList.remove('hidden');
                document.getElementById('view-title').textContent = "Kelola Laporan Masuk";
                document.getElementById('view-subtitle').textContent = "Daftar berkas keluhan dan laporan fasilitas pariwisata daerah.";
                
                const allTabButton = document.querySelector('#tab-filter-bar button');
                if(allTabButton) filterTableCategory('ALL', allTabButton);
            } else if (viewName === 'karyawan') {
                document.getElementById('view-karyawan-content').classList.remove('hidden');
                document.getElementById('view-title').textContent = "Kelola Pengguna Tim";
                document.getElementById('view-subtitle').textContent = "Daftar hak akses administrator dan pelapor publik terdaftar.";
            }
        }

        function filterTableCategory(targetCategory, tabButton) {
            const tabButtons = document.querySelectorAll('#tab-filter-bar button');
            tabButtons.forEach(btn => {
                btn.className = "px-4 py-2 bg-white border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-semibold rounded-xl transition";
            });
            tabButton.className = "px-4 py-2 bg-[#3e2316] text-white text-xs font-bold rounded-xl shadow-sm transition";

            const rows = document.querySelectorAll('.report-data-row');
            let visibleRowsCount = 0;

            rows.forEach(row => {
                const rowCategory = row.getAttribute('data-category');
                if (targetCategory === 'ALL' || rowCategory === targetCategory) {
                    row.classList.remove('hidden');
                    visibleRowsCount++;
                } else {
                    row.classList.add('hidden');
                }
            });

            const tableCard = document.getElementById('table-card-wrapper');
            const placeholder = document.getElementById('empty-table-placeholder');
            
            if (visibleRowsCount === 0) {
                tableCard.classList.add('hidden');
                placeholder.classList.remove('hidden');
            } else {
                tableCard.classList.remove('hidden');
                placeholder.classList.add('hidden');
            }
        }

        function openPhotoModal(element) {
            const rawData = element.getAttribute('data-photo');
            if(!rawData) return;
            const targetImg = document.getElementById('modalTargetImg');
            targetImg.src = (rawData.indexOf('data:image') !== 0 && rawData.indexOf('http') !== 0) ? '../uploads/' + rawData : rawData;
            document.getElementById('photoModal').classList.remove('hidden');
            document.getElementById('photoModal').classList.add('flex');
        }
        function closePhotoModal() {
            document.getElementById('photoModal').classList.remove('flex');
            document.getElementById('photoModal').classList.add('hidden');
        }
    </script>
</body>
</html>