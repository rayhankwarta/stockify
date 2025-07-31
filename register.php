<?php
// Memuat file konfigurasi dan otentikasi
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Jika pengguna sudah login, langsung arahkan ke halaman dashboard.
if (is_logged_in()) {
    redirect('index.php');
}

// Inisialisasi variabel untuk pesan error dan sukses
$error = '';
$success = '';

// Blok ini dieksekusi hanya jika form disubmit dengan metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Ambil semua data dari form
    $nama_toko = trim($_POST['nama_toko']);
    $email = trim($_POST['username']); // Di form namanya 'username', tapi isinya email
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nama_lengkap = trim($_POST['nama_lengkap']);

    // 2. Lakukan validasi input
    if (empty($nama_toko) || empty($email) || empty($password) || empty($confirm_password) || empty($nama_lengkap)) {
        $error = 'Semua kolom wajib diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok';
    } elseif (check_user_exists($email)) { // Fungsi ini dari auth.php
        $error = 'Email sudah terdaftar, silakan gunakan email lain';
    } else {
        // 3. Jika validasi lolos, panggil fungsi registrasi multi-tenant
        $result = register_tenant_and_user($nama_toko, $email, $password, $nama_lengkap);

        if ($result['status'] === 'success') {
            // PENYEMPURNAAN: Kosongkan data form setelah berhasil
            $_POST = [];
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stockify - Daftar Akun Baru</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/jpeg" href="assets/img/logo-stockify.jpg">
    
    <style>
        /* UI Styles (Tidak diubah) */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .login-bg { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #60a5fa 100%); position: relative; overflow: hidden; }
        .login-bg::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="80" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="60" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="60" cy="40" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grain)"/></svg>'); opacity: 0.3; }
        .floating-shapes { position: absolute; width: 100%; height: 100%; overflow: hidden; pointer-events: none; }
        .shape { position: absolute; border-radius: 50%; background: rgba(255, 255, 255, 0.1); animation: float 6s ease-in-out infinite; }
        .shape:nth-child(1) { width: 80px; height: 80px; top: 10%; left: 10%; animation-delay: 0s; }
        .shape:nth-child(2) { width: 60px; height: 60px; top: 70%; right: 15%; animation-delay: 2s; }
        .shape:nth-child(3) { width: 40px; height: 40px; bottom: 20%; left: 80%; animation-delay: 4s; }
        @media (min-width: 640px) { .shape:nth-child(1) { width: 120px; height: 120px; } .shape:nth-child(2) { width: 80px; height: 80px; } .shape:nth-child(3) { width: 60px; height: 60px; } }
        @keyframes float { 0%, 100% { transform: translateY(0px) rotate(0deg); } 50% { transform: translateY(-20px) rotate(180deg); } }
        .login-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1); }
        .input-group { position: relative; }
        .input-field { background: rgba(255, 255, 255, 0.9); border: 2px solid rgba(37, 99, 235, 0.1); transition: all 0.3s ease; }
        .input-field:focus { background: rgba(255, 255, 255, 1); border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .input-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #6b7280; transition: color 0.3s ease; }
        @media (min-width: 640px) { .input-icon { left: 12px; } }
        .input-field:focus + .input-icon { color: #3b82f6; }
        .btn-login { background: linear-gradient(135deg, #2563eb, #3b82f6); transition: all 0.3s ease; position: relative; overflow: hidden; }
        .btn-login::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); transition: left 0.5s; }
        .btn-login:hover::before { left: 100%; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(37, 99, 235, 0.4); }
        .gradient-text { background: linear-gradient(135deg, #2563eb, #60a5fa); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .error-alert { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #dc2626; backdrop-filter: blur(10px); }
        .success-alert { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2); color: #16a34a; backdrop-filter: blur(10px); }
        .logo-icon { background: linear-gradient(135deg, #2563eb, #60a5fa); animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        .back-button { background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); color: white; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; pointer-events: auto; }
        .back-button:hover { background: rgba(255, 255, 255, 0.3); transform: translateX(-5px); text-decoration: none; }
    </style>
</head>
<body class="login-bg min-h-screen">
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="login-card p-6 sm:p-8 md:p-8 rounded-2xl sm:rounded-3xl w-full max-w-sm sm:max-w-md lg:max-w-md xl:max-w-lg">
            <div class="text-center mb-6 sm:mb-8">
                <div class="w-14 h-14 sm:w-16 sm:h-16 mx-auto mb-2 sm:mb-3 logo-icon rounded-xl sm:rounded-2xl flex items-center justify-center shadow-2xl">
                    <i class="fas fa-user-plus text-white text-lg sm:text-xl"></i>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold gradient-text mb-1 sm:mb-2">Stockify</h1>
                <p class="text-sm sm:text-base text-gray-600">Daftarkan Toko & Akun Anda</p>
            </div>
            
            <?php if ($error): ?>
            <div class="error-alert px-3 sm:px-4 py-2 sm:py-3 rounded-xl sm:rounded-2xl mb-3 sm:mb-4 flex items-center text-sm sm:text-base">
                <i class="fas fa-exclamation-triangle mr-2 sm:mr-3 text-sm sm:text-base"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="success-alert px-3 sm:px-4 py-2 sm:py-3 rounded-xl sm:rounded-2xl mb-3 sm:mb-4 flex items-center text-sm sm:text-base">
                <i class="fas fa-check-circle mr-2 sm:mr-3 text-sm sm:text-base"></i>
                <span><?= htmlspecialchars($success) ?>. Silakan <a href="login.php" class="font-bold hover:underline">login</a>.</span>
            </div>
            <?php else: ?>
            
            <form method="POST" action="register.php" class="space-y-3 sm:space-y-4">
                
                <div class="input-group">
                    <div class="relative">
                        <input 
                            type="text" 
                            id="nama_toko" 
                            name="nama_toko" 
                            class="input-field w-full pl-10 sm:pl-12 pr-3 sm:pr-4 py-2.5 sm:py-3 rounded-lg sm:rounded-xl focus:outline-none text-gray-700 placeholder-gray-400 text-sm sm:text-base" 
                            placeholder="Nama Toko / Bisnis Anda"
                            value="<?= isset($_POST['nama_toko']) ? htmlspecialchars($_POST['nama_toko']) : '' ?>"
                            required
                        >
                        <i class="input-icon fas fa-store text-sm sm:text-base"></i>
                    </div>
                </div>

                <div class="input-group">
                    <div class="relative">
                        <input 
                            type="text" 
                            id="nama_lengkap" 
                            name="nama_lengkap" 
                            class="input-field w-full pl-10 sm:pl-12 pr-3 sm:pr-4 py-2.5 sm:py-3 rounded-lg sm:rounded-xl focus:outline-none text-gray-700 placeholder-gray-400 text-sm sm:text-base" 
                            placeholder="Nama Lengkap Anda (Admin)"
                            value="<?= isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : '' ?>"
                            required
                        >
                        <i class="input-icon fas fa-user text-sm sm:text-base"></i>
                    </div>
                </div>
                
                <div class="input-group">
                    <div class="relative">
                        <input 
                            type="email" 
                            id="username" 
                            name="username" 
                            class="input-field w-full pl-10 sm:pl-12 pr-3 sm:pr-4 py-2.5 sm:py-3 rounded-lg sm:rounded-xl focus:outline-none text-gray-700 placeholder-gray-400 text-sm sm:text-base" 
                            placeholder="Email"
                            value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                            required
                        >
                        <i class="input-icon fas fa-envelope text-sm sm:text-base"></i>
                    </div>
                </div>
                
                <div class="input-group">
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="input-field w-full pl-10 sm:pl-12 pr-3 sm:pr-4 py-2.5 sm:py-3 rounded-lg sm:rounded-xl focus:outline-none text-gray-700 placeholder-gray-400 text-sm sm:text-base" 
                            placeholder="Password (minimal 8 karakter)"
                            required
                        >
                        <i class="input-icon fas fa-lock text-sm sm:text-base"></i>
                    </div>
                </div>
                
                <div class="input-group">
                    <div class="relative">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="input-field w-full pl-10 sm:pl-12 pr-3 sm:pr-4 py-2.5 sm:py-3 rounded-lg sm:rounded-xl focus:outline-none text-gray-700 placeholder-gray-400 text-sm sm:text-base" 
                            placeholder="Konfirmasi Password"
                            required
                        >
                        <i class="input-icon fas fa-lock text-sm sm:text-base"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-login w-full text-white py-2.5 sm:py-3 px-4 sm:px-6 rounded-lg sm:rounded-xl font-semibold text-sm sm:text-base shadow-2xl">
                    <i class="fas fa-user-plus mr-2"></i> 
                    Daftarkan Toko Saya
                </button>
                
                <div class="text-center mt-3 sm:mt-4">
                    <p class="text-gray-600 text-sm sm:text-base">
                        Sudah punya akun? 
                        <a href="login.php" class="text-blue-600 hover:underline font-semibold">
                            Login disini
                        </a>
                    </p>
                </div>
            </form>
            
            <?php endif; ?>
        </div>
    </div>
    
    <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-blue-900 to-transparent opacity-50"></div>
</body>
</html>