//coba
<?php
session_start();

$isLoggedIn = isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true;
$currentUser = $isLoggedIn ? $_SESSION['username'] : 'Tamu';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - Laporan Keluhan Wisata</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-orange-50 text-stone-800">

    <nav class="bg-orange-950 text-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="font-bold text-xl tracking-tight">🍂 Laporan Keluhan Wisata</h1>
            <div class="flex items-center space-x-6 text-sm">
                <a href="Home.php" class="hover:text-amber-400">Home</a>
                <a href="Tentang.php" class="hover:text-amber-400 underline underline-offset-8">Tentang</a>
                <?php if ($isLoggedIn): ?>
                    <span class="text-amber-300 font-semibold">Hi, <?php echo htmlspecialchars($currentUser); ?></span>
                    <a href="Login.php?logout=true" class="bg-red-600 px-4 py-2 rounded-lg hover:bg-red-700 transition text-xs">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="bg-orange-900 text-white py-16 px-4">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-4xl font-bold mb-4">Membangun Pariwisata Madiun Lebih Baik</h2>
            <p class="text-orange-200 text-lg">Platform aspirasi demi menjaga keindahan dan kenyamanan aset kota Madiun.</p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-12 max-w-5xl">
        
        <div class="grid md:grid-cols-3 gap-8 mb-16">
            <div class="bg-white p-8 rounded-2xl shadow-sm border-b-4 border-orange-500 text-center">
                <div class="text-3xl mb-4">⚡</div>
                <h3 class="font-bold text-xl mb-2">Respon Cepat</h3>
                <p class="text-gray-600 text-sm">Menghubungkan laporan Anda langsung ke pengelola wisata terkait.</p>
            </div>
            <div class="bg-white p-8 rounded-2xl shadow-sm border-b-4 border-orange-500 text-center">
                <div class="text-3xl mb-4">🔍</div>
                <h3 class="font-bold text-xl mb-2">Transparansi</h3>
                <p class="text-gray-600 text-sm">Status laporan dapat dipantau secara terbuka oleh masyarakat.</p>
            </div>
            <div class="bg-white p-8 rounded-2xl shadow-sm border-b-4 border-orange-500 text-center">
                <div class="text-3xl mb-4">🤝</div>
                <h3 class="font-bold text-xl mb-2">Partisipasi</h3>
                <p class="text-gray-600 text-sm">Masyarakat ikut aktif menjaga fasilitas publik tetap nyaman.</p>
            </div>
        </div>

        <div class="bg-white rounded-3xl p-10 shadow-xl mb-16 border border-orange-100">
            <h3 class="text-2xl font-bold text-orange-900 mb-8 text-center">Cara Melaporkan Keluhan</h3>
            <div class="grid md:grid-cols-4 gap-6 relative">
                <div class="text-center">
                    <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center mx-auto mb-4 font-bold text-xl border-2 border-orange-200">1</div>
                    <p class="font-bold text-sm">Login Akun</p>
                    <p class="text-xs text-gray-500 mt-1">Masuk ke sistem menggunakan akun Anda.</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center mx-auto mb-4 font-bold text-xl border-2 border-orange-200">2</div>
                    <p class="font-bold text-sm">Isi Form</p>
                    <p class="text-xs text-gray-500 mt-1">Tulis lokasi dan detail keluhan fasilitas.</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center mx-auto mb-4 font-bold text-xl border-2 border-orange-200">3</div>
                    <p class="font-bold text-sm">Verifikasi</p>
                    <p class="text-xs text-gray-500 mt-1">Admin akan memeriksa laporan Anda.</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center mx-auto mb-4 font-bold text-xl border-2 border-orange-200">4</div>
                    <p class="font-bold text-sm">Selesai</p>
                    <p class="text-xs text-gray-500 mt-1">Keluhan akan ditindaklanjuti petugas.</p>
                </div>
            </div>
        </div>

        <div class="text-center border-t border-orange-200 pt-10">
            <h3 class="font-bold text-lg text-orange-900">Butuh Bantuan Lebih Lanjut?</h3>
            <p class="text-gray-500 text-sm mb-6">Hubungi kami melalui email di support@wisatamadiun.go.id</p>
            <div class="flex justify-center space-x-4 text-xs font-bold text-orange-700">
                <span>INSTAGRAM: @WisataMadiun</span>
                <span>•</span>
                <span>FACEBOOK: Wisata Madiun</span>
            </div>
        </div>
    </div>

</body>
</html>