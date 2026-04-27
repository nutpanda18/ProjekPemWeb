<?php
session_start();

// Since Login.php and koneksi.php are in the same 'api' folder
include 'koneksi.php'; 

// --- LOGOUT LOGIC ---
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    // Redirect back to index.php in the root folder
    header("Location: ../index.php"); 
    exit();
}

$error_message = "";

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action']; 
    
    /**
     * CONNECTION CHECK: 
     * Ensures $koneksi exists before calling mysqli functions
     */
    if (!isset($koneksi) || !$koneksi) {
        $error_message = "Koneksi database terputus. Silakan cek file koneksi.php Anda.";
    } else {
        // 1. REGISTER LOGIC
        if ($action === 'register') {
            $username = mysqli_real_escape_string($koneksi, $_POST['username']);
            $email = mysqli_real_escape_string($koneksi, $_POST['email']);
            $password = mysqli_real_escape_string($koneksi, $_POST['password']);

            $checkUser = mysqli_query($koneksi, "SELECT * FROM register WHERE username='$username' OR email='$email'");
            if (mysqli_num_rows($checkUser) > 0) {
                $error_message = "Username atau Email sudah terdaftar!";
            } else {
                $query = "INSERT INTO register (username, email, password, role) VALUES ('$username', '$email', '$password', 'user')";
                if (mysqli_query($koneksi, $query)) {
                    $_SESSION['isLoggedIn'] = true;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = 'user';
                    // Redirect to Home.php in the same 'api' folder
                    header("Location: Home.php");
                    exit();
                }
            }
        } 
        
        // 2. LOGIN LOGIC
        else if ($action === 'login') {
            $user_input = mysqli_real_escape_string($koneksi, $_POST['user_input']);
            $password = mysqli_real_escape_string($koneksi, $_POST['password']);

            $query = "SELECT * FROM register WHERE (username='$user_input' OR email='$user_input') AND password='$password'";
            $result = mysqli_query($koneksi, $query);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $user_data = mysqli_fetch_assoc($result);
                $_SESSION['isLoggedIn'] = true;
                $_SESSION['username'] = $user_data['username'];
                $_SESSION['role'] = $user_data['role'];

                // Redirect to files in the same 'api' folder
                if ($user_data['role'] === 'admin') {
                    header("Location: dashboard_admin.php");
                } else {
                    header("Location: Home.php");
                }
                exit();
            } else {
                $error_message = "Akun tidak ditemukan atau password salah!";
            }
        }
    }
}

$isLoggedIn = isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Auth - Laporan Keluhan Wisata</title>
</head>
<body class="bg-stone-100 flex items-center justify-center min-h-screen p-4">
    
    <div class="bg-white rounded-[32px] shadow-2xl w-full max-w-5xl flex overflow-hidden min-h-[600px]">
        
        <div class="hidden md:flex md:w-1/2 relative bg-orange-950 items-center justify-center p-12 overflow-hidden">
            <img src="https://madiunkota.go.id/wp-content/uploads/2022/10/PSC-1.jpg" 
                 class="absolute inset-0 w-full h-full object-cover opacity-40 scale-110" alt="Madiun">
            <div class="relative z-10 text-white space-y-4">
                <div class="bg-orange-600/20 backdrop-blur-md border border-white/20 p-8 rounded-3xl">
                    <h2 class="text-4xl font-black italic tracking-tighter">Laporan Wisata <span class="text-orange-500">Madiun</span></h2>
                    <p class="text-orange-100/80 text-sm mt-4 leading-relaxed">Aspirasi Anda membantu kami membangun fasilitas wisata yang lebih baik.</p>
                </div>
            </div>
        </div>

        <div class="w-full md:w-1/2 p-8 md:p-16 flex flex-col justify-center">
            
            <?php if ($isLoggedIn): ?>
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-orange-100 rounded-full mb-6 text-4xl">👋</div>
                    <h1 class="text-3xl font-black text-stone-900">Halo, <?= htmlspecialchars($_SESSION['username']); ?>!</h1>
                    <div class="mt-10 space-y-3">
                        <a href="<?= ($_SESSION['role'] == 'admin') ? 'dashboard_admin.php' : 'Home.php'; ?>" 
                           class="block w-full bg-orange-600 text-white font-bold py-4 rounded-2xl shadow-lg hover:bg-orange-700 transition text-center">
                           Ke Dashboard
                        </a>
                        <a href="Login.php?logout=true" 
                           class="block w-full bg-stone-100 text-stone-600 font-bold py-4 rounded-2xl hover:bg-red-50 hover:text-red-600 transition text-center">
                           Keluar Akun
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <div id="authContainer">
                    <?php if ($error_message): ?>
                        <div class="mb-6 p-4 bg-red-50 text-red-600 text-xs font-bold rounded-xl border border-red-100">⚠️ <?= $error_message; ?></div>
                    <?php endif; ?>

                    <div id="loginSection">
                        <h1 class="text-3xl font-black text-stone-900 tracking-tight mb-8">Selamat Datang!</h1>
                        <form action="Login.php" method="POST" class="space-y-5">
                            <input type="hidden" name="action" value="login">
                            <input type="text" name="user_input" class="w-full px-5 py-4 bg-stone-50 border border-stone-200 rounded-2xl outline-none focus:ring-2 focus:ring-orange-500" placeholder="Username atau Email" required>
                            <input type="password" name="password" class="w-full px-5 py-4 bg-stone-50 border border-stone-200 rounded-2xl outline-none focus:ring-2 focus:ring-orange-500" placeholder="Password" required>
                            <button type="submit" class="w-full bg-orange-600 text-white font-bold py-4 rounded-2xl shadow-xl hover:bg-orange-700 transition">Masuk Sekarang</button>
                        </form>
                        <p class="mt-8 text-center text-sm text-stone-500">Belum punya akun? <button onclick="toggleAuth()" class="text-orange-600 font-bold hover:underline">Daftar di sini</button></p>
                    </div>

                    <div id="registerSection" class="hidden">
                        <h1 class="text-3xl font-black text-stone-900 tracking-tight mb-8">Buat Akun</h1>
                        <form action="Login.php" method="POST" class="space-y-5">
                            <input type="hidden" name="action" value="register">
                            <input type="text" name="username" class="w-full px-5 py-4 bg-stone-50 border border-stone-200 rounded-2xl outline-none focus:ring-2 focus:ring-orange-500" placeholder="Username" required>
                            <input type="email" name="email" class="w-full px-5 py-4 bg-stone-50 border border-stone-200 rounded-2xl outline-none focus:ring-2 focus:ring-orange-500" placeholder="Alamat Email" required>
                            <input type="password" name="password" class="w-full px-5 py-4 bg-stone-50 border border-stone-200 rounded-2xl outline-none focus:ring-2 focus:ring-orange-500" placeholder="Buat Password" required>
                            <button type="submit" class="w-full bg-stone-900 text-white font-bold py-4 rounded-2xl shadow-xl hover:bg-black transition">Daftar & Masuk</button>
                        </form>
                        <p class="mt-8 text-center text-sm text-stone-500">Sudah punya akun? <button onclick="toggleAuth()" class="text-orange-600 font-bold hover:underline">Login di sini</button></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleAuth() {
            document.getElementById('loginSection').classList.toggle('hidden');
            document.getElementById('registerSection').classList.toggle('hidden');
        }
        
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('action') === 'register') {
            toggleAuth();
        }
    </script>
</body>
</html>