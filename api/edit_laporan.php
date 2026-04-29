<?php
/**
 * edit_laporan.php
 * Updated to use Cookie-based authentication for Vercel stability
 */
include 'koneksi.php';

// 1. Security Check: Ensure only logged-in admins can access this page
if (!isset($_COOKIE['isLoggedIn']) || $_COOKIE['role'] !== 'admin') {
    header("Location: /api/Login.php");
    exit();
}

// 2. Fetch Data Logic
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    // NOTE: Make sure your column name is 'id' or 'id_laporan' as per your database
    $query = mysqli_query($koneksi, "SELECT * FROM laporan WHERE id_laporan = '$id'");
    $data = mysqli_fetch_assoc($query);

    if (!$data) {
        die("Data tidak ditemukan!");
    }
}

// 3. Update Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_laporan = mysqli_real_escape_string($koneksi, $_POST['id']);
    $lokasi = mysqli_real_escape_string($koneksi, $_POST['lokasi_wisata']);
    $isi = mysqli_real_escape_string($koneksi, $_POST['isi_laporan']);
    $status = mysqli_real_escape_string($koneksi, $_POST['status']);

    $update_query = "UPDATE laporan SET 
                     lokasi_wisata = '$lokasi', 
                     isi_laporan = '$isi', 
                     status = '$status' 
                     WHERE id_laporan = '$id_laporan'";

    if (mysqli_query($koneksi, $update_query)) {
        header("Location: /api/dashboard_admin.php?pesan=update_berhasil");
        exit();
    } else {
        $error = "Gagal mengupdate data: " . mysqli_error($koneksi);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Edit Laporan - Admin</title>
</head>
<body class="bg-orange-50 text-stone-800 min-h-screen flex items-center justify-center p-6">

    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-lg border-t-8 border-amber-500">
        <h2 class="text-2xl font-bold mb-6 text-orange-900">Edit Laporan #<?php echo $data['id_laporan']; ?></h2>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="edit_laporan.php" method="POST" class="space-y-4">
            <input type="hidden" name="id" value="<?php echo $data['id_laporan']; ?>">

            <div>
                <label class="text-xs font-bold text-stone-500 uppercase">Nama Pelapor (Read Only)</label>
                <input type="text" class="w-full mt-1 p-2 bg-stone-100 border rounded outline-none" value="<?php echo htmlspecialchars($data['nama_pelapor']); ?>" readonly>
            </div>

            <div>
                <label class="text-xs font-bold text-stone-500 uppercase">Lokasi Wisata</label>
                <input type="text" name="lokasi_wisata" value="<?php echo htmlspecialchars($data['lokasi_wisata']); ?>" class="w-full mt-1 p-2 bg-orange-50 border border-orange-200 rounded outline-none focus:ring-2 focus:ring-amber-500" required>
            </div>

            <div>
                <label class="text-xs font-bold text-stone-500 uppercase">Isi Laporan</label>
                <textarea name="isi_laporan" rows="4" class="w-full mt-1 p-2 bg-orange-50 border border-orange-200 rounded outline-none focus:ring-2 focus:ring-amber-500" required><?php echo htmlspecialchars($data['isi_laporan']); ?></textarea>
            </div>

            <div>
                <label class="text-xs font-bold text-stone-500 uppercase">Status Laporan</label>
                <select name="status" class="w-full mt-1 p-2 bg-orange-50 border border-orange-200 rounded outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="Menunggu" <?php echo ($data['status'] == 'Menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                    <option value="Proses" <?php echo ($data['status'] == 'Proses') ? 'selected' : ''; ?>>Sedang Diproses</option>
                    <option value="Selesai" <?php echo ($data['status'] == 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                </select>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-orange-600 text-white py-3 rounded-xl font-bold hover:bg-orange-700 transition">Simpan Perubahan</button>
                <a href="/api/dashboard_admin.php" class="flex-1 bg-stone-200 text-stone-700 py-3 rounded-xl font-bold text-center hover:bg-stone-300 transition">Batal</a>
            </div>
        </form>
    </div>

</body>
</html>