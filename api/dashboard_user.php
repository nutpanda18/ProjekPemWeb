<?php
session_start();
include 'koneksi.php'; 
include 'api.php'; 

if (!isset($_SESSION['isLoggedIn'])) { 
    header("Location: Login.php"); 
    exit(); 
}

$currentUser = $_SESSION['username'];
$wisata_data = getWisataData(); 

// Data Counts
$user_total_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM laporan WHERE nama_pelapor='$currentUser'");
$total_data = mysqli_fetch_assoc($user_total_q)['total'] ?? 0;

$reports_query = mysqli_query($koneksi, "SELECT * FROM laporan WHERE nama_pelapor='$currentUser' ORDER BY tanggal_laporan DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard User - Madiun</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style_user.css"> 
</head>
<body class="bg-stone-50">

    <nav class="bg-[#4a2c1d] text-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="font-bold text-xl tracking-tight">🍂 Laporan Keluhan Wisata Madiun</h1>
            <div class="flex items-center space-x-6 text-sm">
                <a href="Home.php" class="hover:text-amber-400">Home</a>
                <a href="Tentang.php" class="hover:text-amber-400">Tentang</a>
                <span class="text-amber-300 font-bold">Hi, <?= htmlspecialchars($currentUser); ?></span>
                <a href="Login.php?logout=true" class="bg-red-600 px-4 py-2 rounded-lg text-xs font-bold hover:bg-red-700 transition">Logout</a>
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
                            <p class="text-stone-500">Toilet, Parkir, Lampu Jalan, dll.</p>
                        </div>
                        <div class="bg-orange-50/50 p-4 rounded-2xl border border-orange-100 text-xs">
                            <span class="text-orange-700 font-bold block">🧹 Kebersihan</span>
                            <p class="text-stone-500">Sampah menumpuk, bau tidak sedap.</p>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-bold text-orange-900 mb-6 flex items-center gap-2">
                        <span class="text-orange-600 text-2xl">|</span> Daftar Laporan Anda
                    </h3>
                    <div class="report-table-container bg-white rounded-3xl shadow-sm overflow-hidden border border-stone-200">
                        <table class="w-full text-left">
                            <thead class="bg-orange-50 text-orange-900">
                                <tr>
                                    <th class="p-4 text-xs font-black uppercase">Foto</th>
                                    <th class="p-4 text-xs font-black uppercase">Tanggal & Lokasi</th>
                                    <th class="p-4 text-xs font-black uppercase">Keluhan</th>
                                    <th class="p-4 text-center text-xs font-black uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="text-xs divide-y divide-orange-50">
                                <?php while($r = mysqli_fetch_assoc($reports_query)): ?>
                                <tr class="hover:bg-orange-50/30 transition">
                                    <td class="p-4">
                                        <?php if(!empty($r['foto'])): ?>
                                            <a href="uploads/<?= $r['foto']; ?>" target="_blank">
                                                <img src="uploads/<?= $r['foto']; ?>" class="w-12 h-12 object-cover rounded-lg border border-orange-200 shadow-sm">
                                            </a>
                                        <?php else: ?>
                                            <div class="w-12 h-12 bg-stone-100 rounded-lg flex items-center justify-center text-[8px] text-stone-400">N/A</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4">
                                        <div class="text-gray-400"><?= $r['tanggal_laporan']; ?></div>
                                        <div class="font-bold text-orange-900"><?= htmlspecialchars($r['lokasi_wisata']); ?></div>
                                    </td>
                                    <td class="p-4 text-gray-500 italic"><?= htmlspecialchars($r['isi_laporan']); ?></td>
                                    <td class="p-4 text-center">
                                        <span class="px-3 py-1 <?= ($r['status'] == 'Selesai') ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-orange-800'; ?> rounded-md text-[9px] font-black uppercase">
                                            <?= $r['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="glass-card bg-white p-8 rounded-3xl shadow-xl border-t-8 border-orange-600 h-fit sticky top-24">
                <h3 class="font-bold text-orange-900 mb-6 text-center text-xl">Buat Pengaduan</h3>
                
                <form action="proses_simpan.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="text-[9px] font-bold text-gray-400 uppercase">Pelapor</label>
                        <input type="text" name="nama_pelapor" value="<?= $currentUser; ?>" class="w-full px-4 py-3 rounded-xl bg-stone-100 border border-stone-200 text-stone-500 font-bold" readonly>
                    </div>
                    
                    <div>
                        <label class="text-[9px] font-bold text-gray-400 uppercase">Lokasi Wisata</label>
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
                                // Manual backup options
                                echo "<option value='Pahlawan Street Center'>Pahlawan Street Center</option>";
                                echo "<option value='Madiun Umbul Square'>Madiun Umbul Square</option>";
                                echo "<option value='Taman Sumber Umis'>Taman Sumber Umis</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label class="text-[9px] font-bold text-gray-400 uppercase">Isi Keluhan</label>
                        <textarea name="isi_laporan" rows="4" class="w-full px-4 py-3 rounded-xl border border-stone-200 focus:ring-2 focus:ring-orange-500 outline-none" placeholder="Jelaskan masalah..." required></textarea>
                    </div>

                    <div class="bg-orange-50 p-4 rounded-xl border border-dashed border-orange-200">
                        <label class="text-[9px] font-bold text-orange-700 uppercase block mb-2">Foto Bukti</label>
                        <input type="file" name="foto" accept="image/*" required
                               class="block w-full text-xs text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-orange-600 file:text-white hover:file:bg-orange-700 cursor-pointer">
                    </div>

                    <button type="submit" class="w-full bg-orange-600 text-white font-black py-4 rounded-2xl shadow-lg shadow-orange-200 hover:bg-orange-700 transition transform hover:scale-[1.02]">
                        Kirim Laporan 🚀
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>