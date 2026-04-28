<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['isLoggedIn']) || $_SESSION['role'] !== 'admin') { 
    header("Location: Login.php"); 
    exit(); 
}

// 1. Fetch Stats
$total_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM laporan");
$total_reports = mysqli_fetch_assoc($total_q)['total'] ?? 0;

$pending_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM laporan WHERE status='Menunggu'");
$pending_reports = mysqli_fetch_assoc($pending_q)['total'] ?? 0;

$resolved_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM laporan WHERE status='Selesai'");
$resolved_reports = mysqli_fetch_assoc($resolved_q)['total'] ?? 0;

// 2. Calculate Efficiency Percentage
$efficiency = ($total_reports > 0) ? ($resolved_reports / $total_reports) * 100 : 0;

$all_reports = mysqli_query($koneksi, "SELECT * FROM laporan ORDER BY tanggal_laporan DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Laporan Wisata</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style_admin.css">
</head>
<body class="bg-[#fffaf5] text-stone-800">

    <nav class="bg-[#4a2c1d] text-white shadow-lg mb-10">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="font-bold text-xl flex items-center gap-2">🍂 Laporan Keluhan Wisata</h1>
            <div class="flex items-center space-x-6 text-sm">
                <a href="Home.php" class="hover:text-amber-400">Home</a>
                <a href="Tentang.php" class="hover:text-amber-400">Tentang</a>
                <span class="text-amber-300 font-bold">Hi, <?= htmlspecialchars($_SESSION['username']); ?></span>
                <a href="Login.php?logout=true" class="bg-red-600 px-4 py-2 rounded-lg font-bold">Logout</a>
            </div>
        </div>
    </nav>   

    <div class="container mx-auto px-4 max-w-6xl">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border-l-8 border-stone-400">
                <p class="text-[10px] font-bold text-gray-400 uppercase">Total Laporan</p>
                <h2 class="text-4xl font-black text-stone-800"><?= $total_reports; ?></h2>
            </div>
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border-l-8 border-orange-400">
                <p class="text-[10px] font-bold text-gray-400 uppercase">Menunggu</p>
                <h2 class="text-4xl font-black text-orange-500"><?= $pending_reports; ?></h2>
            </div>
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border-l-8 border-green-500">
                <p class="text-[10px] font-bold text-gray-400 uppercase">Selesai</p>
                <h2 class="text-4xl font-black text-green-600"><?= $resolved_reports; ?></h2>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <div class="lg:col-span-2">
                <h3 class="font-bold text-orange-900 mb-6 flex items-center gap-2">
                    <span class="text-orange-600 text-2xl">|</span> Daftar Laporan Masuk
                </h3>
                <div class="bg-white rounded-[2rem] shadow-sm overflow-hidden border border-orange-50">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#5c3d2e] text-white font-bold uppercase">
                            <tr>
                                <th class="p-4">Bukti</th> 
                                <th class="p-4">Info Laporan</th>
                                <th class="p-4 text-center">Status</th>
                                <th class="p-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-orange-50">
                            <?php while($row = mysqli_fetch_assoc($all_reports)): ?>
                            <tr class="hover:bg-orange-50/30 transition-colors">
                                <td class="p-4 w-20">
                                    <?php 
                                    // Make sure folder name is exactly 'uploads' (no underscore) idk
                                    $imagePath = "uploads/" . $row['foto'];
                                    
                                    if(!empty($row['foto']) && file_exists($imagePath)): ?>
                                        <a href="<?= $imagePath; ?>" target="_blank">
                                            <img src="<?= $imagePath; ?>" class="w-12 h-12 object-cover rounded-lg border border-orange-100 shadow-sm hover:scale-110 transition">
                                        </a>
                                    <?php else: ?>
                                        <div title="Path checked: <?= realpath($imagePath); ?>" class="w-12 h-12 bg-stone-100 rounded-lg flex flex-col items-center justify-center text-[7px] text-stone-400 leading-tight text-center px-1 cursor-help">
                                            <span>⚠️</span>
                                            <span><?= empty($row['foto']) ? "No Data" : "File Missing"; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[10px] text-gray-400 font-medium"><?= $row['tanggal_laporan']; ?></span>
                                        <span class="font-bold text-orange-900 text-sm"><?= htmlspecialchars($row['lokasi_wisata']); ?></span>
                                        <span class="text-stone-500 italic">Oleh: <?= htmlspecialchars($row['nama_pelapor']); ?></span>
                                        <p class="mt-1 text-stone-400 line-clamp-1"><?= htmlspecialchars($row['isi_laporan']); ?></p>
                                    </div>
                                </td>

                                <td class="p-4 text-center">
                                    <?php 
                                        $statusColor = ($row['status'] == 'Selesai') ? 'bg-green-50 text-green-700 border-green-100' : 'bg-amber-50 text-orange-700 border-orange-100';
                                    ?>
                                    <span class="px-2 py-1 <?= $statusColor; ?> rounded-md text-[9px] font-bold border uppercase"><?= $row['status']; ?></span>
                                </td>

                                <td class="p-4">
                                    <div class="flex justify-center gap-2">
                                        <?php if($row['status'] !== 'Selesai'): ?>
                                            <a href="update_status.php?id=<?= $row['id_laporan']; ?>" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-lg font-black text-[9px] uppercase transition-all shadow-sm">Selesaikan</a>
                                        <?php endif; ?>
                                        <a href="hapus_laporan.php?id=<?= $row['id_laporan']; ?>" onclick="return confirm('Hapus laporan ini?')" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg font-black text-[9px] uppercase transition-all shadow-sm">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-[2rem] shadow-md border border-orange-50 sticky top-10">
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-4">Efektivitas Penyelesaian</p>
                    <div class="flex justify-between text-xs font-bold mb-2">
                        <span>Laporan Selesai</span>
                        <span class="text-green-600"><?= round($efficiency); ?>%</span>
                    </div>
                    <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                        <div class="bg-green-500 h-full transition-all duration-500" style="width: <?= $efficiency; ?>%"></div>
                    </div>
                    <p class="text-[9px] text-gray-400 mt-3 italic">*Dihitung dari total laporan masuk dibandingkan laporan selesai.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>