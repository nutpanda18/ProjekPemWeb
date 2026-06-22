<?php
/**
 * dashboard_admin.php
 * Updated: SPA Layout with Dynamic Tab Filtering (All, Fasilitas, Kebersihan, Pelayanan, Keamanan)
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
$category_counts = [];
$cat_q = mysqli_query($koneksi, "SELECT kategori, COUNT(*) as jumlah FROM laporan WHERE kategori IS NOT NULL AND kategori != '' GROUP BY kategori ORDER BY jumlah DESC");
if ($cat_q) {
    while ($row_cat = mysqli_fetch_assoc($cat_q)) {
        $category_counts[$row_cat['kategori']] = $row_cat['jumlah'];
    }
}
$top_category = !empty($category_counts) ? array_key_first($category_counts) : 'Belum Ada';

// Fetch reports list
$all_reports = mysqli_query($koneksi, "SELECT id_laporan, nama_pelapor, lokasi_wisata, kategori, gps_koordinat, isi_laporan, status, tanggal_laporan, tanggapan_admin, foto FROM laporan ORDER BY tanggal_laporan DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Laporan Wisata Madiun</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .dashboard-view { transition: opacity 0.2s ease-in-out; }
    </style>
</head>
<body class="bg-[#fffaf5] text-stone-800 min-h-screen flex flex-row overflow-x-hidden">

    <aside class="w-72 bg-[#4a2c1d] text-white flex flex-col justify-between p-6 shrink-0 shadow-xl min-h-screen sticky top-0">
        <div class="space-y-8">
            <div class="border-b border-white/10 pb-4">
                <h1 class="font-black text-xl flex items-center gap-2 tracking-wide text-amber-100">🍂 AdminPanel</h1>
                <p class="text-[10px] text-amber-200/60 font-semibold uppercase tracking-wider mt-1">Laporan Wisata Madiun</p>
            </div>

            <nav class="space-y-2" id="sidebar-nav">
                <p class="text-[10px] uppercase tracking-wider text-amber-200/40 font-bold px-3 mb-2">Main Menu</p>
                
                <button onclick="switchView('ringkasan', this)" id="btn-ringkasan" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm text-amber-300 bg-white/10 border-l-4 border-amber-400 text-left transition-all shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z"/></svg> Ringkasan Finansial & Tren
                </button>
                
                <button onclick="switchView('kelola', this)" id="btn-kelola" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm text-white/80 hover:text-white hover:bg-white/5 border-l-4 border-transparent text-left transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M280-280h280v-80H280v80Zm0-160h400v-80H280v80Zm0-160h400v-80H280v80Zm-80 480q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm0-560v560-560Z"/></svg> Kelola Seluruh Laporan
                </button>

                <p class="text-[10px] uppercase tracking-wider text-amber-200/40 font-bold px-3 pt-4 mb-2">Aksi Eksternal</p>
                <a href="/api/Home.php" target="_blank" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs text-amber-200/70 hover:text-white transition">🌐 Landing Page</a>
                <a href="/api/Tentang.php" target="_blank" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs text-amber-200/70 hover:text-white transition">ℹ️ Tentang</a>
            </nav>
        </div>

        <div class="border-t border-white/10 pt-4 space-y-3">
            <div class="flex items-center justify-between px-2 text-xs">
                <span class="text-stone-300">Logged in as:</span>
                <strong class="text-amber-300 font-bold"><?= htmlspecialchars($current_user); ?></strong>
            </div>
            <a href="/api/Login.php?logout=true" class="w-full bg-red-600 px-4 py-3 rounded-xl font-bold text-xs text-center block hover:bg-red-700 transition shadow-md">Keluar Akun</a>
        </div>
    </aside>

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

        <div id="view-ringkasan-content" class="dashboard-view space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border-l-8 border-stone-400">
                    <p class="text-[10px] font-bold text-gray-400 uppercase">Total Laporan Masuk</p>
                    <h2 class="text-4xl font-black text-stone-800 mt-1"><?= $total_reports; ?></h2>
                </div>
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border-l-8 border-amber-500">
                    <p class="text-[10px] font-bold text-gray-400 uppercase">Dalam Proses Evaluasi</p>
                    <h2 class="text-4xl font-black text-amber-500 mt-1"><?= $pending_reports; ?></h2>
                </div>
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border-l-8 border-green-500">
                    <p class="text-[10px] font-bold text-gray-400 uppercase">Laporan Diterima (Valid)</p>
                    <h2 class="text-4xl font-black text-green-600 mt-1"><?= $accepted_reports; ?></h2>
                </div>
            </div>

            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-orange-100/70">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4 pb-4 border-b border-stone-100">
                    <div>
                        <h3 class="font-bold text-stone-900 text-sm flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m260-520 220-360 220 360H260ZM700-80q-75 0-127.5-52.5T520-260q0-75 52.5-127.5T700-440q75 0 127.5 52.5T880-260q0 75-52.5 127.5T700-80Zm-580-20v-320h320v320H120Zm580-60q42 0 71-29t29-71q0-42-29-71t-71-29q-42 0-71 29t-29 71q0 42 29 71t71 29Zm-500-20h160v-160H200v160Zm202-420h156l-78-126-78 126Zm78 0ZM360-340Zm340 80Z"/></svg> Distribusi Kategori Keluhan</h3>
                        <p class="text-[11px] text-stone-400">Total data laporan yang terkumpul di database berdasarkan label kategori.</p>
                    </div>
                    <?php if(!empty($category_counts)): ?>
                        <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-2 flex items-center gap-2 text-xs">
                            <span class="animate-pulse">⚠️</span>
                            <span class="text-stone-600">Tren Keluhan Tertinggi: <strong class="text-amber-800 uppercase tracking-wide"><?= htmlspecialchars($top_category); ?></strong></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if(empty($category_counts)): ?>
                    <p class="text-xs text-stone-400 italic text-center py-2">Belum ada data laporan kategori terkumpul.</p>
                <?php else: ?>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <?php foreach($category_counts as $cat_name => $count): ?>
                            <div class="bg-[#fffaf5] border border-orange-100/50 p-4 rounded-2xl flex flex-col justify-between">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-stone-400 block truncate" title="<?= htmlspecialchars($cat_name); ?>">
                                    <?= htmlspecialchars($cat_name); ?>
                                </span>
                                <div class="flex items-baseline justify-between mt-1">
                                    <span class="text-2xl font-black text-stone-800"><?= $count; ?></span>
                                    <span class="text-[10px] text-stone-400 font-medium">laporan</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-orange-50 max-w-xl">
                <p class="text-[10px] font-bold text-gray-400 uppercase mb-4">Efisiensi Penanganan Keluhan</p>
                <div class="flex justify-between text-xs font-bold mb-2">
                    <span>Laporan Terverifikasi Valid</span>
                    <span class="text-green-600"><?= round($efficiency); ?>%</span>
                </div>
                <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                    <div class="bg-green-500 h-full transition-all duration-500" style="width: <?= $efficiency; ?>%"></div>
                </div>
            </div>
        </div>

        <div id="view-kelola-content" class="dashboard-view hidden space-y-6">
            
            <div class="flex flex-wrap items-center gap-2 border-b border-stone-200 pb-3" id="tab-filter-bar">
                <button onclick="filterTableCategory('ALL', this)" class="px-4 py-2 bg-[#4a2c1d] text-white text-xs font-bold rounded-xl shadow-sm transition">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M240-320h320v-80H240v80Zm0-160h480v-80H240v80Zm-80 320q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h240l80 80h320q33 0 56.5 23.5T880-640v400q0 33-23.5 56.5T800-160H160Zm0-80h640v-400H447l-80-80H160v480Zm0 0v-480 480Z"/></svg> Semua Laporan
                </button>
                <button onclick="filterTableCategory('FASILITAS', this)" class="px-4 py-2 bg-white border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-semibold rounded-xl transition">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M440-80v-520H80l400-280 400 280H520v520h-80Zm40-600h146-292 146ZM120-80v-210L88-466l78-14 30 160h164v240h-80v-160h-80v160h-80Zm480 0v-240h164l30-160 78 14-32 176v210h-80v-160h-80v160h-80ZM334-680h292L480-782 334-680Z"/></svg> Fasilitas
                </button>
                <button onclick="filterTableCategory('KEBERSIHAN', this)" class="px-4 py-2 bg-white border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-semibold rounded-xl transition">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M120-40v-280q0-83 58.5-141.5T320-520h40v-320q0-33 23.5-56.5T440-920h80q33 0 56.5 23.5T600-840v320h40q83 0 141.5 58.5T840-320v280H120Zm80-80h80v-120q0-17 11.5-28.5T320-280q17 0 28.5 11.5T360-240v120h80v-120q0-17 11.5-28.5T480-280q17 0 28.5 11.5T520-240v120h80v-120q0-17 11.5-28.5T640-280q17 0 28.5 11.5T680-240v120h80v-200q0-50-35-85t-85-35H320q-50 0-85 35t-35 85v200Zm320-400v-320h-80v320h80Zm0 0h-80 80Z"/></svg> Kebersihan
                </button>
                <button onclick="filterTableCategory('PELAYANAN', this)" class="px-4 py-2 bg-white border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-semibold rounded-xl transition">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M400-80v-80h520v80H400Zm40-120q0-81 51-141.5T620-416v-25q0-17 11.5-28.5T660-481q17 0 28.5 11.5T700-441v25q77 14 128.5 74.5T880-200H440Zm105-81h228q-19-27-48.5-43.5T660-341q-36 0-66 16.5T545-281Zm114 0ZM40-440v-440h240v58l280-78 320 100v40q0 50-35 85t-85 35h-80v24q0 25-14.5 45.5T628-541L358-440H40Zm80-80h80v-280h-80v280Zm160 0h64l232-85q11-4 17.5-13.5T600-640h-71l-117 38-24-76 125-42h247q9 0 22.5-6.5T796-742l-238-74-278 76v220Z"/></svg> Pelayanan
                </button>
                <button onclick="filterTableCategory('KEAMANAN', this)" class="px-4 py-2 bg-white border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-semibold rounded-xl transition">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M420-340h120v-100h100v-120H540v-100H420v100H320v120h100v100Zm60 260q-139-35-229.5-159.5T160-516v-244l320-120 320 120v244q0 152-90.5 276.5T480-80Zm0-84q104-33 172-132t68-220v-189l-240-90-240 90v189q0 121 68 220t172 132Zm0-316Z"/></svg> Keamanan
                </button>
            </div>

            <div id="empty-table-placeholder" class="hidden bg-white p-12 text-center rounded-[2rem] border border-orange-50 shadow-sm">
                <span class="text-4xl">🍃</span>
                <h4 class="font-bold text-stone-700 mt-2 text-sm">Tidak Ada Laporan</h4>
                <p class="text-xs text-stone-400 mt-0.5">Belum ditemukan berkas pengaduan dalam kategori ini.</p>
            </div>

            <div id="table-card-wrapper" class="bg-white rounded-[2rem] shadow-sm overflow-hidden border border-orange-50">
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
                        <tbody class="divide-y divide-orange-50 text-xs" id="reports-table-body">
                            <?php while($row = mysqli_fetch_assoc($all_reports)): 
                                $rowCatClean = strtoupper(trim($row['kategori'] ?? 'UMUM'));
                            ?>
                            <tr class="hover:bg-orange-50/30 transition-colors report-data-row" data-category="<?= htmlspecialchars($rowCatClean); ?>">
                                
                                <td class="p-4 align-top">
                                    <?php if(!empty($row['foto'])): ?>
                                        <div class="relative w-16 h-16 cursor-pointer group" onclick="openPhotoModal(this)" data-photo="<?= htmlspecialchars($row['foto']); ?>">
                                            <img src="<?= (strpos($row['foto'], 'data:image') === 0 || strpos($row['foto'], 'http') === 0) ? $row['foto'] : '../uploads/'.$row['foto']; ?>" 
                                                 class="w-16 h-16 object-cover rounded-xl border border-stone-200 shadow-sm"
                                                 loading="lazy"
                                                 onerror="this.src='https://placehold.co/150x150?text=Foto'">
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
                                        <span class="bg-orange-100 text-orange-800 font-bold px-2 py-0.5 rounded text-[9px] uppercase tracking-wide category-badge"><?= htmlspecialchars($row['kategori'] ?? 'UMUM'); ?></span>
                                        <?php if(!empty($row['gps_koordinat'])): ?>
                                            <a href="https://www.openstreetmap.org/search?query=<?= urlencode($row['gps_koordinat']); ?>" target="_blank" class="bg-blue-50 text-blue-600 font-bold px-2 py-0.5 rounded text-[9px]">🗺️ Peta</a>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-stone-600 bg-stone-50/80 p-2 rounded-xl italic">"<?= htmlspecialchars($row['isi_laporan'] ?? ''); ?>"</p>
                                    
                                    <div class="mt-3 pt-2">
                                        <form action="simpan_tanggapan.php" method="POST" class="flex gap-2">
                                            <input type="hidden" name="id_laporan" value="<?= $row['id_laporan']; ?>">
                                            <input type="text" name="tanggapan_admin" placeholder="Tanggapan perbaikan..." value="<?= htmlspecialchars($row['tanggapan_admin'] ?? ''); ?>" class="w-full px-3 py-1.5 bg-stone-50 border rounded-xl text-xs outline-none focus:ring-2 focus:ring-orange-500">
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
                                        <form action="update_status.php" method="POST">
                                            <input type="hidden" name="id_laporan" value="<?= $row['id_laporan']; ?>"><input type="hidden" name="status" value="Diterima">
                                            <button type="submit" class="w-full bg-green-600 text-white py-1 rounded-lg font-black text-[9px] uppercase">Terima</button>
                                        </form>
                                        <form action="update_status.php" method="POST">
                                            <input type="hidden" name="id_laporan" value="<?= $row['id_laporan']; ?>"><input type="hidden" name="status" value="Tidak Diterima">
                                            <button type="submit" class="w-full bg-amber-600 text-white py-1 rounded-lg font-black text-[9px] uppercase">Tolak</button>
                                        </form>
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
    </main>

    <div id="photoModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-50 p-4" onclick="closePhotoModal()">
        <div class="relative max-w-3xl w-full" onclick="event.stopPropagation()">
            <img id="modalTargetImg" src="" class="max-w-full max-h-[85vh] object-contain rounded-2xl mx-auto shadow-2xl">
        </div>
    </div>

    <script>
        // 1. Sidebar Link Switching Section View Engine
        function switchView(viewName, buttonElement) {
            document.getElementById('view-ringkasan-content').classList.add('hidden');
            document.getElementById('view-kelola-content').classList.add('hidden');
            
            const navButtons = document.querySelectorAll('#sidebar-nav button');
            navButtons.forEach(btn => {
                btn.classList.remove('bg-white/10', 'text-amber-300', 'font-bold', 'border-amber-400');
                btn.classList.add('text-white/80', 'font-semibold', 'border-transparent');
            });

            buttonElement.classList.remove('text-white/80', 'font-semibold', 'border-transparent');
            buttonElement.classList.add('bg-white/10', 'text-amber-300', 'font-bold', 'border-amber-400');

            if (viewName === 'ringkasan') {
                document.getElementById('view-ringkasan-content').classList.remove('hidden');
                document.getElementById('view-title').textContent = "Ringkasan Finansial & Performa";
                document.getElementById('view-subtitle').textContent = "Berikut adalah ringkasan performa dan keluhan infrastruktur terkini.";
            } else if (viewName === 'kelola') {
                document.getElementById('view-kelola-content').classList.remove('hidden');
                document.getElementById('view-title').textContent = "Kelola Laporan Masuk";
                document.getElementById('view-subtitle').textContent = "Daftar berkas keluhan dan laporan fasilitas pariwisata daerah.";
                
                // Default fallback to "All" whenever view initializes
                const allTabButton = document.querySelector('#tab-filter-bar button');
                if(allTabButton) filterTableCategory('ALL', allTabButton);
            }
        }

        // 2. NEW REAL-TIME TABLE ROW FILTER ENGINE
        function filterTableCategory(targetCategory, tabButton) {
            // Adjust active tab design colors
            const tabButtons = document.querySelectorAll('#tab-filter-bar button');
            tabButtons.forEach(btn => {
                btn.className = "px-4 py-2 bg-white border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-semibold rounded-xl transition";
            });
            tabButton.className = "px-4 py-2 bg-[#4a2c1d] text-white text-xs font-bold rounded-xl shadow-sm transition";

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

            // Toggle empty placeholder if no matches found
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

        // Lightbox Modals 
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