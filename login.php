<?php 
// Memuat file konfigurasi dan otentikasi
require_once 'includes/config.php'; 
require_once 'includes/auth.php';  

// --- PENINGKATAN KECIL ---
// Jika pengguna sudah login, jangan tampilkan halaman ini,
// langsung arahkan ke halaman dashboard.
if (is_logged_in()) {
    redirect('index.php');
}

// Inisialisasi variabel untuk pesan error
$error = ''; 

// Blok ini dieksekusi hanya jika form disubmit dengan metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {     
    $username = trim($_POST['username']); // Ini adalah email
    $password = trim($_POST['password']);      
    
    if (empty($username) || empty($password)) {         
        $error = 'Email dan password wajib diisi';     
    } else {         
        // Memanggil fungsi login_user() dari auth.php.
        // Fungsi ini sudah diubah untuk menyimpan tenant_id ke session.
        // Jadi, tidak perlu ada perubahan di baris ini.
        if (login_user($username, $password)) {             
            redirect('index.php'); // Arahkan ke dashboard jika berhasil
        } else {             
            $error = 'Email atau password salah';         
        }     
    } 
} 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stockify - Login</title>
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
        .shape:nth-child(1) { width: 60px; height: 60px; top: 15%; left: 10%; animation-delay: 0s; }
        .shape:nth-child(2) { width: 80px; height: 80px; top: 60%; right: 20%; animation-delay: 2s; }
        .shape:nth-child(3) { width: 40px; height: 40px; bottom: 30%; left: 75%; animation-delay: 4s; }
        @media (min-width: 640px) { .shape:nth-child(1) { width: 100px; height: 100px; } .shape:nth-child(2) { width: 120px; height: 120px; } .shape:nth-child(3) { width: 70px; height: 70px; } }
        @media (min-width: 1024px) { .shape:nth-child(1) { width: 140px; height: 140px; } .shape:nth-child(2) { width: 160px; height: 160px; } .shape:nth-child(3) { width: 90px; height: 90px; } }
        @keyframes float { 0%, 100% { transform: translateY(0px) rotate(0deg); } 50% { transform: translateY(-20px) rotate(180deg); } }
        .login-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1); }
        .input-group { position: relative; }
        .input-field { background: rgba(255, 255, 255, 0.9); border: 2px solid rgba(37, 99, 235, 0.1); transition: all 0.3s ease; }
        .input-field:focus { background: rgba(255, 255, 255, 1); border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .input-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6b7280; transition: color 0.3s ease; }
        @media (min-width: 768px) { .input-icon { left: 14px; } }
        .input-field:focus + .input-icon { color: #3b82f6; }
        .btn-login { background: linear-gradient(135deg, #2563eb, #3b82f6); transition: all 0.3s ease; position: relative; overflow: hidden; }
        .btn-login::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); transition: left 0.5s; }
        .btn-login:hover::before { left: 100%; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(37, 99, 235, 0.4); }
        .gradient-text { background: linear-gradient(135deg, #2563eb, #60a5fa); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .error-alert { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #dc2626; backdrop-filter: blur(10px); }
        .logo-icon { background: linear-gradient(135deg, #2563eb, #60a5fa); animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        .back-button { background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); color: white; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; pointer-events: auto; }
        .back-button:hover { background: rgba(255, 255, 255, 0.3); transform: translateX(-5px); text-decoration: none; }
        .login-container { width: 100%; max-width: 400px; }
        @media (min-width: 640px) { .login-container { max-width: 420px; } }
        @media (min-width: 768px) { .login-container { max-width: 450px; } }
        @media (min-width: 1024px) { .login-container { max-width: 380px; } }
        @media (min-width: 1280px) { .login-container { max-width: 400px; } }
        .logo-text { font-size: 1.75rem; }
        @media (min-width: 640px) { .logo-text { font-size: 2rem; } }
        @media (min-width: 768px) { .logo-text { font-size: 2.25rem; } }
        @media (min-width: 1024px) { .logo-text { font-size: 2rem; } }
        .card-padding { padding: 1.5rem; }
        @media (min-width: 640px) { .card-padding { padding: 2rem; } }
        @media (min-width: 768px) { .card-padding { padding: 2.5rem; } }
        @media (min-width: 1024px) { .card-padding { padding: 2rem; } }
        .form-spacing { margin-bottom: 1.5rem; }
        @media (min-width: 640px) { .form-spacing { margin-bottom: 2rem; } }
        @media (min-width: 768px) { .form-spacing { margin-bottom: 2.5rem; } }
        .input-responsive { padding: 0.75rem 1rem 0.75rem 2.5rem; font-size: 0.875rem; }
        @media (min-width: 640px) { .input-responsive { padding: 1rem 1.25rem 1rem 3rem; font-size: 1rem; } }
        @media (min-width: 768px) { .input-responsive { padding: 1.125rem 1.5rem 1.125rem 3.5rem; font-size: 1rem; } }
        @media (min-width: 1024px) { .input-responsive { padding: 1rem 1.25rem 1rem 3rem; font-size: 0.95rem; } }
        .btn-responsive { padding: 0.75rem 1.5rem; font-size: 0.875rem; }
        @media (min-width: 640px) { .btn-responsive { padding: 1rem 2rem; font-size: 1rem; } }
        @media (min-width: 768px) { .btn-responsive { padding: 1.125rem 2.5rem; font-size: 1.125rem; } }
        @media (min-width: 1024px) { .btn-responsive { padding: 1rem 2rem; font-size: 1rem; } }
    </style>
</head>
<body class="login-bg min-h-screen">
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <div class="absolute top-4 left-4 sm:top-6 sm:left-6 z-50">
        <a href="landing.php" class="back-button px-3 py-2 sm:px-4 sm:py-2 md:px-5 md:py-3 rounded-lg sm:rounded-xl font-medium inline-flex items-center text-sm sm:text-base cursor-pointer">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>
    
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="login-card login-container card-padding rounded-2xl sm:rounded-3xl">
            <div class="text-center form-spacing">
                <div class="w-16 h-16 sm:w-20 sm:h-20 md:w-24 md:h-24 lg:w-20 lg:h-20 mx-auto mb-4 logo-icon rounded-xl sm:rounded-2xl flex items-center justify-center shadow-2xl">
                    <i class="fas fa-cube text-white text-xl sm:text-2xl md:text-3xl lg:text-2xl"></i>
                </div>
                <h1 class="logo-text font-bold gradient-text mb-2">Stockify</h1>
                <p class="text-sm sm:text-base md:text-lg text-gray-600">Masuk ke Dashboard Anda</p>
            </div>
            
            <?php if ($error): ?>
            <div class="error-alert px-4 py-3 rounded-xl sm:rounded-2xl mb-6 flex items-center text-sm sm:text-base">
                <i class="fas fa-exclamation-triangle mr-3"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-5 sm:space-y-6 md:space-y-7">
                <div class="input-group">
                    <div class="relative">
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="input-field input-responsive w-full rounded-xl focus:outline-none text-gray-700 placeholder-gray-400" 
                            placeholder="Email"
                            required
                        >
                        <i class="input-icon fas fa-envelope"></i>
                    </div>
                </div>
                
                <div class="input-group">
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="input-field input-responsive w-full rounded-xl focus:outline-none text-gray-700 placeholder-gray-400" 
                            placeholder="Password"
                            required
                        >
                        <i class="input-icon fas fa-lock"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-login btn-responsive w-full text-white rounded-xl font-semibold shadow-2xl">
                    <i class="fas fa-sign-in-alt mr-2"></i> 
                    Masuk
                </button>
            </form>
            
            <div class="text-center mt-6 sm:mt-8">
                <p class="text-gray-600 mb-4 text-sm sm:text-base">Belum punya akun?</p>
                <a href="register.php" class="inline-flex items-center px-4 py-2 sm:px-6 sm:py-3 md:px-8 md:py-4 border-2 border-blue-600 text-blue-600 rounded-xl font-semibold hover:bg-blue-600 hover:text-white transition-all duration-300 text-sm sm:text-base">
                    <i class="fas fa-user-plus mr-2"></i> 
                    Daftar Sekarang
                </a>
            </div>
        </div>
    </div>
    
    <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-blue-900 to-transparent opacity-50"></div>
</body>
</html>