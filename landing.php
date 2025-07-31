<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Stockify - Sistem Kasir & Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />
    <link rel="icon" type="image/jpeg" href="assets/img/logo-stockify.jpg">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .hero {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #60a5fa 100%);
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="80" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="60" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="60" cy="40" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px rgba(37, 99, 235, 0.15);
        }
        
        .feature-icon {
            background: linear-gradient(135deg, #2563eb, #60a5fa);
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1);
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.4);
        }
        
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }
        
        .shape:nth-child(1) { width: 80px; height: 80px; top: 20%; left: 10%; animation-delay: 0s; }
        .shape:nth-child(2) { width: 60px; height: 60px; top: 60%; right: 10%; animation-delay: 2s; }
        .shape:nth-child(3) { width: 40px; height: 40px; bottom: 20%; left: 20%; animation-delay: 4s; }
        
        @media (min-width: 768px) {
            .shape:nth-child(1) { width: 120px; height: 120px; }
            .shape:nth-child(2) { width: 80px; height: 80px; }
            .shape:nth-child(3) { width: 60px; height: 60px; }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #2563eb, #60a5fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-primary:hover::before { left: 100%; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(37, 99, 235, 0.4); }
        
        .stats-counter {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .testimonial-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
        }
    </style>
</head>
<body class="text-gray-800 font-sans">

    <!-- Navbar dengan Menu Mobile -->
    <nav class="bg-white/95 backdrop-blur-md shadow-lg sticky top-0 z-50 border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-blue-400 rounded-xl flex items-center justify-center">
                        <i class="fas fa-cube text-white text-lg"></i>
                    </div>
                    <h1 class="text-2xl font-bold gradient-text">Stockify</h1>
                </div>

                <!-- Menu Desktop -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="login.php" class="px-6 py-2 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition-all duration-300 shadow-md hover:shadow-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i> Login
                    </a>
                    <a href="register.php" class="px-6 py-2 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition-all duration-300 shadow-md hover:shadow-lg">
                        <i class="fas fa-user-plus mr-2"></i> Register
                    </a>
                </div>

                <!-- Tombol Hamburger untuk Mobile -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-button" class="text-gray-700 hover:text-blue-600 focus:outline-none">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Menu Mobile (disembunyikan secara default) -->
        <div id="mobile-menu" class="hidden md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="login.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-white hover:bg-blue-600">Login</a>
                <a href="register.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-white hover:bg-blue-600">Register</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero relative py-24 sm:py-32 text-white text-center px-4 sm:px-6 min-h-screen flex items-center">
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>

        <div class="relative z-10 max-w-6xl mx-auto">
            <div class="inline-flex items-center px-4 py-2 sm:px-6 mb-8 bg-white/20 backdrop-blur-md border border-white/30 text-white text-sm font-semibold rounded-full shadow-lg">
                <i class="fas fa-rocket mr-2 text-blue-300"></i>
                Solusi UMKM Masa Kini
            </div>

            <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold mb-8 leading-tight">
                Kelola Bisnis Anda <span class="text-blue-300">Lebih Cepat</span> & <span class="text-blue-300">Mudah</span>
            </h1>
            
            <p class="text-lg sm:text-xl text-blue-100 mb-12 max-w-3xl mx-auto leading-relaxed">
                Stockify adalah sistem kasir dan manajemen stok modern yang cocok untuk UMKM, angkringan, toko kelontong, hingga warung makan.
            </p>

            <div class="flex flex-col sm:flex-row justify-center items-center gap-6 mb-16">
                <a href="login.php" class="btn-primary w-full sm:w-auto px-8 py-3 sm:px-10 sm:py-4 text-white rounded-xl text-lg font-semibold shadow-2xl">
                    <i class="fas fa-rocket mr-3"></i> Mulai Sekarang
                </a>
                <a href="https://youtu.be/4P_ZVe9sBCk?si=LWAYP9BPaGZMxGml" target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto px-8 py-3 sm:px-10 sm:py-4 bg-white/20 backdrop-blur-md border border-white/30 text-white rounded-xl text-lg font-semibold hover:bg-white/30 transition-all duration-300">
                    <i class="fas fa-play-circle mr-3"></i> Lihat Demo
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8 max-w-4xl mx-auto">
                <div class="stats-counter rounded-2xl p-6 text-center">
                    <div class="text-3xl font-bold text-blue-300 mb-2">1000+</div>
                    <div class="text-blue-100">UMKM Terdaftar</div>
                </div>
                <div class="stats-counter rounded-2xl p-6 text-center">
                    <div class="text-3xl font-bold text-blue-300 mb-2">50K+</div>
                    <div class="text-blue-100">Transaksi/Bulan</div>
                </div>
                <div class="stats-counter rounded-2xl p-6 text-center">
                    <div class="text-3xl font-bold text-blue-300 mb-2">99%</div>
                    <div class="text-blue-100">Kepuasan Pengguna</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 sm:py-24 bg-gradient-to-b from-gray-50 to-white px-4 sm:px-6">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16 sm:mb-20">
                <div class="inline-block px-4 py-2 bg-blue-100 text-blue-600 text-sm font-semibold rounded-full mb-4">
                    <i class="fas fa-star mr-2"></i>Fitur Unggulan
                </div>
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-bold text-gray-800 mb-6">
                    Mengapa Memilih <span class="gradient-text">Stockify?</span>
                </h2>
                <p class="text-lg sm:text-xl text-gray-600 max-w-3xl mx-auto">
                    Didesain khusus untuk memberikan kemudahan dan kontrol penuh atas bisnis kecil Anda dengan teknologi terdepan.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature Card 1 -->
                <div class="feature-card rounded-3xl p-8 text-center">
                    <div class="w-20 h-20 mx-auto mb-6 rounded-2xl flex items-center justify-center feature-icon shadow-xl"><i class="fas fa-cash-register text-3xl text-white"></i></div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Kasir Modern</h3>
                    <p class="text-gray-600 leading-relaxed">Transaksi cepat, otomatisasi kembalian, dan sistem pembayaran yang responsif untuk semua jenis bisnis.</p>
                </div>
                <!-- Feature Card 2 -->
                <div class="feature-card rounded-3xl p-8 text-center">
                    <div class="w-20 h-20 mx-auto mb-6 rounded-2xl flex items-center justify-center feature-icon shadow-xl"><i class="fas fa-box-open text-3xl text-white"></i></div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Manajemen Stok</h3>
                    <p class="text-gray-600 leading-relaxed">Pantau stok secara real-time, sistem pengingat otomatis, dan laporan inventori yang komprehensif.</p>
                </div>
                <!-- Feature Card 3 -->
                <div class="feature-card rounded-3xl p-8 text-center">
                    <div class="w-20 h-20 mx-auto mb-6 rounded-2xl flex items-center justify-center feature-icon shadow-xl"><i class="fas fa-chart-line text-3xl text-white"></i></div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Analisis Cerdas</h3>
                    <p class="text-gray-600 leading-relaxed">Dashboard analitik, insight performa bisnis, dan rekomendasi untuk meningkatkan penjualan.</p>
                </div>
                <!-- Feature Card 4 -->
                <div class="feature-card rounded-3xl p-8 text-center">
                    <div class="w-20 h-20 mx-auto mb-6 rounded-2xl flex items-center justify-center feature-icon shadow-xl"><i class="fas fa-mobile-alt text-3xl text-white"></i></div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Mobile Friendly</h3>
                    <p class="text-gray-600 leading-relaxed">Akses dari mana saja dengan interface yang responsif, aplikasi mobile yang ringan dan cepat.</p>
                </div>
                <!-- Feature Card 5 -->
                <div class="feature-card rounded-3xl p-8 text-center">
                    <div class="w-20 h-20 mx-auto mb-6 rounded-2xl flex items-center justify-center feature-icon shadow-xl"><i class="fas fa-shield-alt text-3xl text-white"></i></div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Keamanan Tinggi</h3>
                    <p class="text-gray-600 leading-relaxed">Data bisnis Anda dilindungi dengan enkripsi tingkat enterprise dan backup otomatis setiap hari.</p>
                </div>
                <!-- Feature Card 6 -->
                <div class="feature-card rounded-3xl p-8 text-center">
                    <div class="w-20 h-20 mx-auto mb-6 rounded-2xl flex items-center justify-center feature-icon shadow-xl"><i class="fas fa-headset text-3xl text-white"></i></div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Support 24/7</h3>
                    <p class="text-gray-600 leading-relaxed">Tim support yang siap membantu kapan saja, panduan lengkap, dan training gratis untuk semua pengguna.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-20 sm:py-24 bg-gradient-to-r from-blue-50 to-indigo-50 px-4 sm:px-6">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16 sm:mb-20">
                <div class="inline-block px-4 py-2 bg-blue-100 text-blue-600 text-sm font-semibold rounded-full mb-4"><i class="fas fa-quote-left mr-2"></i>Testimoni</div>
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-bold text-gray-800 mb-6">Apa Kata <span class="gradient-text">Pengguna</span> Kami?</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Testimonial Card 1 -->
                <div class="testimonial-card rounded-3xl p-8">
                    <div class="flex items-center mb-4"><div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">S</div><div class="ml-4"><h4 class="font-bold text-gray-800">Sari</h4><p class="text-sm text-gray-600">Warung Nasi Sari</p></div></div>
                    <p class="text-gray-600 italic">"Stockify sangat membantu warung saya. Sekarang saya bisa pantau stok dan penjualan dengan mudah!"</p>
                </div>
                <!-- Testimonial Card 2 -->
                <div class="testimonial-card rounded-3xl p-8">
                    <div class="flex items-center mb-4"><div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">B</div><div class="ml-4"><h4 class="font-bold text-gray-800">Budi</h4><p class="text-sm text-gray-600">Angkringan Pak Budi</p></div></div>
                    <p class="text-gray-600 italic">"Interface yang mudah dipahami, transaksi jadi lebih cepat. Pelanggan tidak perlu menunggu lama."</p>
                </div>
                <!-- Testimonial Card 3 -->
                <div class="testimonial-card rounded-3xl p-8">
                    <div class="flex items-center mb-4"><div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">L</div><div class="ml-4"><h4 class="font-bold text-gray-800">Lina</h4><p class="text-sm text-gray-600">Toko Kelontong Lina</p></div></div>
                    <p class="text-gray-600 italic">"Laporan penjualan yang detail membantu saya mengambil keputusan bisnis yang tepat. Recommended!"</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 sm:py-24 bg-gradient-to-r from-blue-600 to-blue-800 text-white text-center px-4 sm:px-6">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-3xl sm:text-4xl md:text-5xl font-bold mb-6">Siap Untuk Mengembangkan Bisnis Anda?</h2>
            <p class="text-lg sm:text-xl text-blue-100 mb-10 max-w-2xl mx-auto">Bergabunglah dengan ribuan UMKM yang telah merasakan kemudahan Stockify. Mulai gratis hari ini!</p>
            <div class="flex flex-col sm:flex-row justify-center items-center gap-6">
                <a href="register.php" class="w-full sm:w-auto btn-primary px-8 py-3 sm:px-12 sm:py-4 bg-white text-white-600 rounded-xl text-lg font-semibold hover:bg-gray-100 transition-all duration-300 shadow-2xl"><i class="fas fa-rocket mr-3"></i> Daftar Gratis Sekarang</a>
                <a href="#features" class="w-full sm:w-auto px-8 py-3 sm:px-12 sm:py-4 border-2 border-white text-white rounded-xl text-lg font-semibold hover:bg-white hover:text-blue-600 transition-all duration-300"><i class="fas fa-phone mr-3"></i> Hubungi Kami</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
                <div>
                    <div class="flex items-center space-x-3 mb-4"><div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-blue-400 rounded-xl flex items-center justify-center"><i class="fas fa-cube text-white text-lg"></i></div><h3 class="text-2xl font-bold">Stockify</h3></div>
                    <p class="text-gray-400">Solusi kasir dan inventory terpercaya untuk UMKM Indonesia.</p>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Produk</h4>
                    <ul class="space-y-2 text-gray-400"><li><a href="#" class="hover:text-white transition">Kasir</a></li><li><a href="#" class="hover:text-white transition">Inventory</a></li><li><a href="#" class="hover:text-white transition">Laporan</a></li></ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Dukungan</h4>
                    <ul class="space-y-2 text-gray-400"><li><a href="#" class="hover:text-white transition">Help Center</a></li><li><a href="#" class="hover:text-white transition">Tutorial</a></li><li><a href="#" class="hover:text-white transition">Kontak</a></li></ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Ikuti Kami</h4>
                    <div class="flex space-x-4"><a href="#" class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center hover:bg-blue-700 transition"><i class="fab fa-facebook-f"></i></a><a href="#" class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center hover:bg-blue-700 transition"><i class="fab fa-instagram"></i></a><a href="#" class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center hover:bg-blue-700 transition"><i class="fab fa-twitter"></i></a></div>
                </div>
            </div>
            <div class="section-divider mb-8"></div>
            <div class="text-center">
                <p>&copy; 2025 <strong>Stockify</strong>. All rights reserved.</p>
                <p class="mt-2 text-gray-400">Dibangun dengan <i class="fas fa-heart text-red-400"></i> untuk UMKM Indonesia</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript untuk Menu Mobile -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');

            mobileMenuButton.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        });
    </script>
</body>
</html>
