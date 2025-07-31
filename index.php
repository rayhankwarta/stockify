<?php
// File: index.php (Revisi Final dengan Hak Akses)

require_once 'includes/config.php';
require_once 'includes/auth.php';

// --- BLOK PENANGANAN AJAX ---
if (isset($_GET['page']) && $_GET['page'] === 'reports' && !empty($_GET['action'])) {
    include __DIR__ . '/pages/reports.php';
    exit; 
}
// --- AKHIR BLOK PENANGANAN AJAX ---


// Jika bukan permintaan AJAX, lanjutkan untuk merender halaman lengkap seperti biasa.
if (!is_logged_in()) {
    redirect('login.php');
}


// ===================================================================
// --- REVISI UTAMA: LOGIKA HAK AKSES BERDASARKAN PERAN ---
// ===================================================================

// 1. Definisikan semua halaman yang ada
$all_pages = ['dashboard', 'kasir', 'inventory', 'reports', 'users'];

// 2. Definisikan halaman yang HANYA boleh diakses oleh Kasir
$kasir_allowed_pages = ['dashboard', 'kasir', 'reports'];

// 3. Tentukan halaman yang diminta, default ke dashboard
$page = $_GET['page'] ?? 'dashboard';

// 4. Jika halaman yang diminta tidak valid, kembalikan ke dashboard
if (!in_array($page, $all_pages)) {
    $page = 'dashboard';
}

// 5. Terapkan aturan keamanan:
// Jika pengguna BUKAN admin DAN mencoba mengakses halaman di luar daftar yang diizinkan untuknya,
// maka paksa kembali ke halaman dashboard.
if (!is_admin() && !in_array($page, $kasir_allowed_pages)) {
    $page = 'dashboard';
}

// ===================================================================
// --- AKHIR REVISI HAK AKSES ---
// ===================================================================


$titles = [
    'dashboard'=>'Dashboard','kasir'=>'Pemesanan','inventory'=>'Inventory',
    'reports'=>'Laporan Transaksi','users'=>'Manajemen Pengguna'
];
$icons = [
    'dashboard'=>'tachometer-alt','kasir'=>'cash-register','inventory'=>'boxes',
    'reports'=>'chart-line','users'=>'users'
];

// Ambil data notifikasi stok menipis (khusus untuk tenant ini)
$tenant_id = $_SESSION['tenant_id'];
$low_stock_stmt = $db->prepare("
    SELECT kode_produk, nama_produk, stok
    FROM produk
    WHERE stok <= 5 AND tenant_id = ?
    ORDER BY stok ASC, nama_produk
");
$low_stock_stmt->execute([$tenant_id]);
$low_stock = $low_stock_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stockify – <?= htmlspecialchars($titles[$page]) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/jpeg" href="assets/img/logo-stockify.jpg">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        @keyframes dropdownFade { 0% {opacity: 0; transform: translateY(-10px)} 100% {opacity: 1; transform: translateY(0)} }
        .dropdown-show { animation: dropdownFade .2s ease-out forwards; }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; height: 6px }
        .scrollbar-thin::-webkit-scrollbar-thumb { background-color: rgba(100,116,139,.4); border-radius: 9999px }
        #sidebar { background: linear-gradient(180deg, #1d3a98ff, #172554); transition: all 0.3s ease; }
        #sidebar-header { padding: 1.5rem 1rem; text-align: center; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        #sidebar-logo { display: flex; align-items: center; justify-content: center; gap: 0.75rem; text-decoration: none; }
        #sidebar-logo span { font-size: 1.5rem; font-weight: 800; color: #f1f5f9; letter-spacing: 1px; transition: color 0.3s ease; }
        #sidebar-logo:hover span { color: #fff; }
        .sidebar-nav-item { display: flex; align-items: center; padding: 0.8rem 1.5rem; margin: 0.25rem 0.5rem; color: #cbd5e1; border-radius: 0.5rem; transition: all 0.2s ease-in-out; }
        .sidebar-nav-item:hover { background-color: rgba(255, 255, 255, 0.15); color: #ffffff; }
        .sidebar-nav-item.active { background-color: rgba(255, 255, 255, 0.07); color: #ffffff; font-weight: 600; border-left: 4px solid #3b82f6; padding-left: calc(1.5rem - 4px); }
        .sidebar-nav-item i { margin-right: 0.75rem; width: 20px; text-align: center; }
        .sidebar-footer { padding: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>

<body class="bg-gray-100 h-screen flex flex-col md:flex-row">

    <aside id="sidebar" class="text-white w-full md:w-64 flex-shrink-0 md:h-full hidden md:block flex flex-col">
        <div id="sidebar-header">
            <a href="?page=dashboard" id="sidebar-logo">
                <span>STOCKIFY</span>
            </a>
        </div>
        
        <nav class="flex-1 px-2 py-4 space-y-2">
            <?php foreach ($all_pages as $p): ?>
                <?php
                    // Logika untuk menyembunyikan menu dari kasir
                    if (!is_admin() && !in_array($p, $kasir_allowed_pages)) {
                        continue; // Lewati menu ini jika user bukan admin dan menu tidak ada di daftar kasir
                    }
                ?>
                <a href="?page=<?= $p ?>" class="sidebar-nav-item <?= $page === $p ? 'active' : '' ?>">
                    <i class="fas fa-<?= $icons[$p] ?>"></i>
                    <span><?= htmlspecialchars($titles[$p]) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="sidebar-nav-item hover:bg-red-500/20 hover:text-red-400">
                 <i class="fas fa-sign-out-alt"></i>
                 <span>Logout</span>
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden w-full">
        <header class="bg-white shadow-sm p-4 flex justify-between items-center">
            <button id="toggleSidebar" class="md:hidden text-blue-800 mr-4">
                <i class="fas fa-bars fa-lg"></i>
            </button>

            <h2 class="text-xl font-semibold flex-1"><?= htmlspecialchars($titles[$page]) ?></h2>

            <div class="flex items-center gap-4">
                <div class="relative">
                    <button id="notifBtn" class="relative focus:outline-none">
                        <i class="fas fa-bell text-xl text-gray-600 hover:text-blue-600 transition"></i>
                        <?php if (count($low_stock) > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-bold rounded-full px-[5px]">
                                <?= count($low_stock) ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <div id="notifDropdown" class="hidden absolute right-0 mt-3 w-80 max-h-80 overflow-y-auto bg-white rounded-xl shadow-xl z-50 scrollbar-thin">
                        <?php if (count($low_stock) > 0): ?>
                            <?php foreach ($low_stock as $row): ?>
                                <div class="px-4 py-3 border-b last:border-b-0 flex gap-2 hover:bg-gray-50">
                                    <i class="fas fa-box-open text-red-500 mt-[2px]"></i>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($row['nama_produk']) ?></p>
                                        <p class="text-xs text-gray-500">
                                            Kode: <span class="font-semibold"><?= htmlspecialchars($row['kode_produk']) ?></span> ·
                                            Stok: <span class="font-semibold text-red-600"><?= $row['stok'] ?></span>
                                        </p>
                                        <p class="text-xs font-semibold text-red-600">Segera Restock!</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="px-4 py-4 text-sm text-gray-500 text-center">Tidak ada notifikasi</p>
                        <?php endif; ?>
                    </div>
                </div>

                <span class="font-medium text-gray-700 hidden sm:inline"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                    <?= strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)) ?>
                </div>
            </div>
        </header>

        <main class="p-4 overflow-auto">
            <?php include __DIR__ . "/pages/{$page}.php"; ?>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleSidebar');
        const notifBtn = document.getElementById('notifBtn');
        const notifDropdown = document.getElementById('notifDropdown');

        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
            });
        }

        if (notifBtn && notifDropdown) {
            const toggleNotif = () => {
                notifDropdown.classList.toggle('hidden');
                if (!notifDropdown.classList.contains('hidden')) {
                    notifDropdown.classList.add('dropdown-show');
                }
            };

            notifBtn.addEventListener('click', e => {
                e.stopPropagation();
                toggleNotif();
            });

            document.addEventListener('click', e => {
                if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
                       notifDropdown.classList.add('hidden');
                }
            });
        }
    });
    </script>
</body>
</html>