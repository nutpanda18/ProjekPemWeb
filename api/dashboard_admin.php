<?php
/**
 * dashboard_admin.php
 * Updated: SPA Layout with Dynamic Tab Filtering & Advanced Chart.js Statistics Layout
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

// Fetch total counts grouped by category via JOIN
$category_counts = [];
$cat_q = mysqli_query($koneksi, "SELECT kategori.nama_kategori as kategori, COUNT(*) as jumlah FROM laporan LEFT JOIN kategori ON laporan.id_kategori = kategori.id_kategori WHERE kategori.nama_kategori IS NOT NULL GROUP BY kategori.nama_kategori ORDER BY jumlah DESC");
if ($cat_q) {
    while ($row_cat = mysqli_fetch_assoc($cat_q)) {
        $category_counts[$row_cat['kategori']] = $row_cat['jumlah'];
    }
}
$top_category = !empty($category_counts) ? array_key_first($category_counts) : 'Belum Ada';

// Fetch reports list with kategori name via JOIN
$all_reports = mysqli_query($koneksi, "SELECT laporan.id_laporan, laporan.nama_pelapor, laporan.lokasi_wisata, laporan.gps_koordinat, laporan.isi_laporan, laporan.status, laporan.tanggal_laporan, laporan.foto, kategori.nama_kategori as kategori FROM laporan LEFT JOIN kategori ON laporan.id_kategori = kategori.id_kategori ORDER BY laporan.tanggal_laporan DESC");

// Pre-fetch all tanggapan grouped by id_laporan for efficient lookup
$all_tanggapan = [];
$tq = mysqli_query($koneksi, "SELECT * FROM tanggapan ORDER BY tgl_tanggapan ASC");
if ($tq) {
    while ($t = mysqli_fetch_assoc($tq)) {
        $all_tanggapan[$t['id_laporan']][] = $t;
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z"/></svg> Ringkasan Data Laporan
                </button>
                
                <button onclick="switchView('kelola', this)" id="btn-kelola" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm text-white/80 hover:text-white hover:bg-white/5 border-l-4 border-transparent text-left transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="M280-280h280v-80H280v80Zm0-160h400v-80H280v80Zm0-160h400v-80H280v80Zm-80 480q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm0-560v560-560Z"/></svg> Kelola Laporan
                </button>
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
                <div class="bg-white p-6 rounded-[1.5rem] shadow-sm flex justify-between items-center border border-stone-100">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Laporan</p>
                        <h2 class="text-4xl font-black text-stone-900 mt-1"><?= $total_reports; ?></h2>
                    </div>
                    <div class="bg-stone-50 p-3 rounded-2xl text-2xl">📁</div>
                </div>
                <div class="bg-white p-6 rounded-[1.5rem] shadow-sm flex justify-between items-center border border-stone-100">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Dalam Proses</p>
                        <h2 class="text-4xl font-black text-amber-500 mt-1"><?= $pending_reports; ?></h2>
                    </div>
                    <div class="bg-amber-50 p-3 rounded-2xl text-2xl">⏳</div>
                </div>
                <div class="bg-white p-6 rounded-[1.5rem] shadow-sm flex justify-between items-center border border-stone-100">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Laporan Selesai</p>
                        <h2 class="text-4xl font-black text-emerald-600 mt-1"><?= $accepted_reports; ?></h2>
                    </div>
                    <div class="bg-emerald-50 p-3 rounded-2xl text-2xl">✅</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2 bg-white p-8 rounded-[2rem] shadow-sm border border-stone-100 flex flex-col">
                    <div class="mb-4">
                        <h3 class="font-bold text-stone-900 text-base">Proporsi Kategori Keluhan</h3>
                        <p class="text-xs text-stone-400">Visualisasi persentase pengaduan masuk.</p>
                    </div>
                    
                    <div class="flex-1 flex items-center justify-center min-h-[280px] max-h-[320px] relative">
                        <?php if(empty($category_counts)): ?>
                            <p class="text-xs text-stone-400 italic">Belum ada data visualisasi.</p>
                        <?php else: ?>
                            <canvas id="categoryDonutChart"></canvas>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="space-y-6">
                    
                    <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-stone-100">
                        <h3 class="font-bold text-stone-900 text-sm flex items-center gap-2">☑️ Penyelesaian Kasus</h3>
                        <p class="text-[11px] text-stone-400 mb-4">Rasio efisiensi penanganan pengaduan valid.</p>
                        
                        <div class="bg-[#fffaf5] border border-orange-100/70 p-4 rounded-2xl">
                            <div class="flex justify-between items-center text-xs font-bold mb-2">
                                <span class="text-stone-700">Laporan Terverifikasi</span>
                                <span class="text-emerald-600 text-sm"><?= round($efficiency); ?>%</span>
                            </div>
                            <div class="w-full bg-stone-200/60 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-emerald-500 h-full transition-all duration-500" style="width: <?= $efficiency; ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-stone-100">
                        <p class="text-[10px] uppercase font-bold tracking-wider text-stone-400 mb-3">Tren Terbanyak</p>
                        <div class="bg-amber-50/60 border border-amber-200/70 p-4 rounded-2xl flex items-start gap-3">
                            <span class="text-xl mt-0.5">🔥</span>
                            <div>
                                <h4 class="text-xs font-bold text-stone-500 uppercase">Kategori</h4>
                                <h3 class="text-sm font-black text-amber-900 uppercase tracking-wide mt-0.5"><?= htmlspecialchars($top_category); ?></h3>
                                <p class="text-[11px] text-stone-500 mt-1">Mendominasi total laporan masuk saat ini.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <div id="view-kelola-content" class="dashboard-view hidden space-y-6">
            <div class="flex flex-wrap items-center gap-2 border-b border-stone-200 pb-3" id="tab-filter-bar">
                <button onclick="filterTableCategory('ALL', this)" class="px-4 py-2 bg-[#4a2c1d] text-white text-xs font-bold rounded-xl shadow-sm transition">Semua Laporan</button>
                <button onclick="filterTableCategory('FASILITAS', this)" class="px-4 py-2 bg-white border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-semibold rounded-xl transition">Fasilitas</button>
                <button onclick="filterTableCategory('KEBERSIHAN', this)" class="px-4 py-2 bg-white border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-semibold rounded-xl transition">Kebersihan</button>
                <button onclick="filterTableCategory('PELAYANAN', this)" class="px-4 py-2 bg-white border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-semibold rounded-xl transition">Pelayanan</button>
                <button onclick="filterTableCategory('KEAMANAN', this)" class="px-4 py-2 bg-white border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-semibold rounded-xl transition">Keamanan</button>
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
                                            <img src="<?= (strpos($row['foto'], 'data:image') === 0 || strpos($row['foto'], 'http') === 0) ? $row['foto'] : '../uploads/'.$row['foto']; ?>" class="w-16 h-16 object-cover rounded-xl border border-stone-200 shadow-sm" loading="lazy" onerror="this.src='https://placehold.co/150x150?text=Foto'">
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
                                        <span class="bg-orange-100 text-orange-800 font-bold px-2 py-0.5 rounded text-[9px] uppercase tracking-wide"><?= htmlspecialchars($row['kategori'] ?? 'UMUM'); ?></span>
                                        <?php if(!empty($row['gps_koordinat'])): ?>
                                            <a href="https://www.openstreetmap.org/search?query=<?= urlencode($row['gps_koordinat']); ?>" target="_blank" class="bg-blue-50 text-blue-600 font-bold px-2 py-0.5 rounded text-[9px]">🗺️ Peta</a>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-stone-600 bg-stone-50/80 p-2 rounded-xl italic">"<?= htmlspecialchars($row['isi_laporan'] ?? ''); ?>"</p>

                                    <?php 
                                    $replies = $all_tanggapan[$row['id_laporan']] ?? [];
                                    if (!empty($replies)): ?>
                                        <div class="space-y-1.5 mt-2">
                                            <span class="text-[9px] font-black text-amber-800 uppercase tracking-wider">💬 Tanggapan Admin</span>
                                            <?php foreach($replies as $reply): ?>
                                                <div class="bg-amber-50/60 border border-amber-200/60 p-2.5 rounded-xl">
                                                    <p class="text-stone-700 text-xs leading-relaxed"><?= htmlspecialchars($reply['isi_tanggapan']); ?></p>
                                                    <span class="text-[9px] text-stone-400 mt-1 block"><?= $reply['tgl_tanggapan']; ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <form action="/api/simpan_tanggapan.php" method="POST" class="mt-2 space-y-1.5">
                                        <input type="hidden" name="id_laporan" value="<?= $row['id_laporan']; ?>">
                                        <textarea name="isi_tanggapan" rows="2" placeholder="Tulis tanggapan baru..." class="w-full px-2.5 py-2 text-xs border border-stone-200 rounded-xl bg-stone-50 outline-none focus:ring-2 focus:ring-amber-400/30 resize-none" required></textarea>
                                        <button type="submit" class="w-full bg-amber-100 text-amber-900 py-1 rounded-lg font-black text-[9px] uppercase hover:bg-amber-200 transition">Kirim Tanggapan</button>
                                    </form>
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
                                        <button type="submit" class="w-full bg-[#d1fae5] text-[#065f46] py-1 rounded-lg font-black text-[9px] uppercase">Terima</button>
                                    </form>
                                    <form action="update_status.php" method="POST">
                                        <input type="hidden" name="id_laporan" value="<?= $row['id_laporan']; ?>"><input type="hidden" name="status" value="Tidak Diterima">
                                        <button type="submit" class="w-full bg-[#fef3c7] text-[#d97706] py-1 rounded-lg font-black text-[9px] uppercase">Tolak</button>
                                    </form>
                                    <a href="hapus_laporan.php?id=<?= $row['id_laporan']; ?>" onclick="return confirm('Hapus permanen data ini?')" class="w-full bg-[#fee2e2] text-[#991b1b] text-center block py-1 rounded-lg font-black text-[9px] uppercase">Hapus</a>
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

    <script>
        function switchView(viewName, buttonElement) {
            document.getElementById('view-ringkasan-content').classList.add('hidden');
            document.getElementById('view-kelola-content').classList.add('hidden');
            
            const navButtons = document.querySelectorAll('#sidebar-nav button');
            navButtons.forEach(btn => {
                btn.className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm text-white/80 hover:text-white hover:bg-white/5 border-l-4 border-transparent text-left transition-all";
            });

            buttonElement.className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm text-amber-300 bg-white/10 border-l-4 border-amber-400 text-left transition-all shadow-inner";

            if (viewName === 'ringkasan') {
                document.getElementById('view-ringkasan-content').classList.remove('hidden');
                document.getElementById('view-title').textContent = "Ringkasan Data Laporan";
            } else if (viewName === 'kelola') {
                document.getElementById('view-kelola-content').classList.remove('hidden');
                document.getElementById('view-title').textContent = "Kelola Laporan Masuk";
                const allTabButton = document.querySelector('#tab-filter-bar button');
                if(allTabButton) filterTableCategory('ALL', allTabButton);
            }
        }

        function filterTableCategory(targetCategory, tabButton) {
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

            document.getElementById('table-card-wrapper').classList.toggle('hidden', visibleRowsCount === 0);
            document.getElementById('empty-table-placeholder').classList.toggle('hidden', visibleRowsCount > 0);
        }

        // --- Render Dynamic Donut Chart via Chart.js ---
        <?php if(!empty($category_counts)): ?>
        document.addEventListener("DOMContentLoaded", function() {
            const chartData = <?php echo json_encode($category_counts); ?>;
            const labels = Object.keys(chartData);
            const dataValues = Object.values(chartData);

            const backgroundColors = [
                '#78716c', // Kebersihan
                '#ea580c', // Keamanan
                '#eab308', // Pelayanan
                '#d97706'  // Fasilitass
            ];

            const ctx = document.getElementById('categoryDonutChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: dataValues,
                        backgroundColor: backgroundColors.slice(0, labels.length),
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#44403c',
                                font: { size: 11, weight: 'bold', family: 'sans-serif' },
                                padding: 20,
                                boxWidth: 12
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>