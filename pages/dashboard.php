<?php
// pages/dashboard.php
// Pengecekan login dan file config/auth sudah ditangani oleh index.php

// Ambil tenant_id dari session untuk memfilter semua query
$tenant_id = $_SESSION['tenant_id'];

/* ---------- Timezone & Tanggal ---------- */
date_default_timezone_set('Asia/Jakarta');
$start_date = $_GET['start'] ?? date('Y-m-d', strtotime('-6 days'));
$end_date   = $_GET['end']   ?? date('Y-m-d');
if ($start_date > $end_date) {
    [$start_date, $end_date] = [$end_date, $start_date];
}

/* ---------- Data Kartu Statistik (Sudah Disesuaikan dengan Tenant) ---------- */
$stmt = $db->prepare("SELECT COUNT(*) FROM produk WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$total_produk = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM produk WHERE stok <= 0 AND tenant_id = ?");
$stmt->execute([$tenant_id]);
$stok_habis   = (int)$stmt->fetchColumn();

$today = date('Y-m-d');
$stmt = $db->prepare("SELECT COUNT(*) FROM transaksi WHERE DATE(created_at)=? AND tenant_id = ?");
$stmt->execute([$today, $tenant_id]);
$total_transaksi_today   = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COALESCE(SUM(total),0) FROM transaksi WHERE DATE(created_at)=? AND tenant_id = ?");
$stmt->execute([$today, $tenant_id]);
$total_pendapatan_today = (int)$stmt->fetchColumn();


/* ---------- Data Grafik Garis (Pendapatan Harian) (Sudah Disesuaikan dengan Tenant) ---------- */
$revenue_chart_data = [];
$period = new DatePeriod(new DateTime($start_date), new DateInterval('P1D'), (new DateTime($end_date))->modify('+1 day'));
$stmtLine = $db->prepare("SELECT COALESCE(SUM(total),0) FROM transaksi WHERE DATE(created_at)=? AND tenant_id = ?");
foreach ($period as $dt) {
    $d = $dt->format('Y-m-d');
    $stmtLine->execute([$d, $tenant_id]);
    $revenue_chart_data[] = ['date' => $d, 'total' => (int)$stmtLine->fetchColumn()];
}

/* ---------- Data Grafik Donat (Omzet per Kategori) (Sudah Disesuaikan dengan Tenant) ---------- */
$cat_stmt = $db->prepare("
    SELECT p.kategori, SUM(td.subtotal) as total_omzet
    FROM transaksi_detail td
    JOIN transaksi t ON td.transaksi_id = t.id 
    JOIN produk p ON td.produk_id = p.id
    WHERE DATE(t.created_at) BETWEEN ? AND ? AND t.tenant_id = ?
    GROUP BY p.kategori HAVING total_omzet > 0 ORDER BY total_omzet DESC
");
$cat_stmt->execute([$start_date, $end_date, $tenant_id]);
$category_revenue_data = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Data Grafik Batang (Transaksi per Jam) (Sudah Disesuaikan dengan Tenant) ---------- */
$hourly_stmt = $db->prepare("
    SELECT HOUR(created_at) as jam, COUNT(*) as jumlah_transaksi
    FROM transaksi
    WHERE DATE(created_at) BETWEEN ? AND ? AND tenant_id = ?
    GROUP BY jam ORDER BY jam ASC
");
$hourly_stmt->execute([$start_date, $end_date, $tenant_id]);
$hourly_data_raw = $hourly_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$hourly_data = array_fill(0, 24, 0);
foreach ($hourly_data_raw as $hour => $count) {
    $hourly_data[$hour] = (int)$count;
}

/* Data untuk Analisis Metode Pembayaran (Sudah Disesuaikan dengan Tenant) */
$payment_stmt = $db->prepare("
    SELECT metode_pembayaran, COUNT(id) as jumlah_transaksi, SUM(total) as total_omzet
    FROM transaksi
    WHERE DATE(created_at) BETWEEN ? AND ? AND tenant_id = ?
    GROUP BY metode_pembayaran ORDER BY jumlah_transaksi DESC
");
$payment_stmt->execute([$start_date, $end_date, $tenant_id]);
$payment_method_data = $payment_stmt->fetchAll(PDO::FETCH_ASSOC);

/* Mengambil data untuk Papan Peringkat per Kategori (Sudah Disesuaikan dengan Tenant) */
$leaderboard_data = [];
$stmt = $db->prepare("SELECT DISTINCT kategori FROM produk WHERE tenant_id = ? ORDER BY kategori");
$stmt->execute([$tenant_id]);
$kategori_list_for_ranking = $stmt->fetchAll(PDO::FETCH_COLUMN);

$all_ranked_stmt = $db->prepare("
    SELECT p.nama_produk, SUM(td.qty) AS total_qty, p.kategori
    FROM transaksi_detail td
    JOIN transaksi t ON td.transaksi_id = t.id JOIN produk p ON td.produk_id = p.id
    WHERE DATE(t.created_at) BETWEEN ? AND ? AND t.tenant_id = ?
    GROUP BY p.id, p.nama_produk, p.kategori ORDER BY total_qty DESC LIMIT 7
");
$all_ranked_stmt->execute([$start_date, $end_date, $tenant_id]);
$leaderboard_data['semua'] = $all_ranked_stmt->fetchAll(PDO::FETCH_ASSOC);

$cat_ranked_stmt = $db->prepare("
    SELECT p.nama_produk, SUM(td.qty) AS total_qty, p.kategori
    FROM transaksi_detail td
    JOIN transaksi t ON td.transaksi_id = t.id JOIN produk p ON td.produk_id = p.id
    WHERE p.kategori = ? AND DATE(t.created_at) BETWEEN ? AND ? AND t.tenant_id = ?
    GROUP BY p.id, p.nama_produk, p.kategori ORDER BY total_qty DESC LIMIT 7
");
foreach ($kategori_list_for_ranking as $kat) {
    $cat_ranked_stmt->execute([$kat, $start_date, $end_date, $tenant_id]);
    $leaderboard_data[$kat] = $cat_ranked_stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* Palet Warna */
$color_palette = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#14B8A6'];
?>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #f0f2f5 0%, #e6eaf8 100%); min-height: 100vh; }
    .stat-card { transition: all 0.3s ease; border: 1px solid #e2e8f0; background: white; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15); }
    .leaderboard-tab { transition: all 0.2s ease-in-out; }
    .leaderboard-tab.active { background-color: #093FB4; color: white; }
    .leaderboard-scrollable { max-height: 280px; overflow-y: auto; padding-right: 0.5rem; }
    .glass-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }
    .icon-blue { color: #3B82F6; } .icon-emerald { color: #10B981; } .icon-amber { color: #F59E0B; } .icon-rose { color: #EF4444; }
    .bg-blue-light { background-color: #EBF4FF; } .bg-emerald-light { background-color: #D1FAE5; } .bg-amber-light { background-color: #FEF3C7; } .bg-rose-light { background-color: #FEE2E2; }
    .text-blue-dark { color: #1E40AF; } .text-emerald-dark { color: #065F46; } .text-amber-dark { color: #92400E; } .text-rose-dark { color: #991B1B; }
</style>
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <section class="mb-8">
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div><h1 class="text-2xl sm:text-3xl font-bold text-black flex items-center"><i class="fas fa-chart-line mr-3 text-indigo-400"></i>Dashboard Analitik</h1></div>
                <form method="get" class="flex flex-wrap items-center gap-2 sm:gap-4">
                    <input type="hidden" name="page" value="dashboard">
                    <input type="date" name="start" value="<?= htmlspecialchars($start_date) ?>" class="bg-slate-100 border-slate-300 text-slate-800 rounded-lg p-2 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <span class="text-slate-500 hidden sm:inline"><i class="fas fa-arrow-right"></i></span>
                    <input type="date" name="end" value="<?= htmlspecialchars($end_date) ?>" class="bg-slate-100 border-slate-300 text-slate-800 rounded-lg p-2 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <button type="submit" class="bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 text-white px-4 py-2.5 rounded-lg font-semibold shadow-md flex items-center transition-all transform hover:scale-105 text-sm"><i class="fas fa-filter mr-2"></i>Filter</button>
                    <a href="?page=dashboard" class="border border-slate-300 hover:bg-slate-100 text-slate-800 px-4 py-2.5 rounded-lg font-semibold flex items-center transition-colors text-sm"><i class="fas fa-sync-alt mr-2"></i>Reset</a>
                </form>
            </div>
        </div>
    </section>
    <section class="mb-8 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
        <?php $cards = [['icon' => 'arrow-trend-up', 'color' => 'blue', 'label' => "Pendapatan Hari Ini", 'value' => 'Rp ' . number_format($total_pendapatan_today, 0, ',', '.')], ['icon' => 'receipt', 'color' => 'emerald', 'label' => "Transaksi Hari Ini", 'value' => $total_transaksi_today . ' Kali'], ['icon' => 'shapes', 'color' => 'amber', 'label' => 'Total Jenis Produk', 'value' => $total_produk . ' Varian'], ['icon' => 'triangle-exclamation', 'color' => 'rose', 'label' => 'Produk Stok Habis', 'value' => $stok_habis . ' Produk']]; foreach ($cards as $c): ?>
        <div class="stat-card bg-white rounded-xl p-5 flex items-center space-x-4 transition-all duration-300 shadow-md">
            <div class="p-3 sm:p-4 rounded-full bg-<?= $c['color'] ?>-light"><i class="fas fa-<?= $c['icon'] ?> text-xl sm:text-2xl icon-<?= $c['color'] ?>"></i></div>
            <div>
                <p class="text-sm text-gray-500 font-semibold"><?= $c['label'] ?></p>
                <h2 class="text-xl sm:text-2xl font-extrabold text-<?= $c['color'] ?>-dark break-words"><?= $c['value'] ?></h2>
            </div>
        </div>
        <?php endforeach; ?>
    </section>
    <section class="mb-8 p-4 sm:p-6 glass-card rounded-2xl shadow-lg">
        <h3 class="text-lg sm:text-xl font-bold mb-4 text-gray-800 flex items-center"><i class="fas fa-chart-line mr-2 text-indigo-500"></i>Grafik Tren Pendapatan</h3>
        <div class="h-80"><canvas id="revenueLineChart"></canvas></div>
    </section>
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <div class="p-4 sm:p-6 glass-card rounded-2xl shadow-lg lg:col-span-2">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                <h3 class="text-lg sm:text-xl font-bold text-gray-800 flex items-center"><i class="fas fa-trophy mr-2 text-yellow-500"></i>Papan Peringkat Produk Terlaris</h3>
                <div id="leaderboard-filter" class="flex items-center bg-slate-100 rounded-full p-1 text-sm overflow-x-auto">
                    <button class="leaderboard-tab px-3 py-1 rounded-full active flex-shrink-0" data-kategori="semua">Semua</button>
                    <?php foreach ($kategori_list_for_ranking as $kat): ?>
                    <button class="leaderboard-tab px-3 py-1 rounded-full flex-shrink-0" data-kategori="<?= htmlspecialchars($kat) ?>"><?= ucfirst(htmlspecialchars($kat)) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div id="leaderboard-list-container" class="space-y-3"></div>
        </div>
        <div class="p-4 sm:p-6 glass-card rounded-2xl shadow-lg lg:col-span-1">
            <h3 class="text-lg sm:text-xl font-bold mb-4 text-gray-800 flex items-center"><i class="fas fa-pie-chart mr-2 text-green-500"></i>Kontribusi Kategori</h3>
            <div class="h-[24rem]"><canvas id="categoryRevenueChart"></canvas></div>
        </div>
    </section>
    <section class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="p-4 sm:p-6 glass-card rounded-2xl shadow-lg">
            <h3 class="text-lg sm:text-xl font-bold mb-4 text-gray-800 flex items-center"><i class="fas fa-clock mr-2 text-blue-500"></i>Analisis Jam Sibuk</h3>
            <div class="h-80"><canvas id="hourlyTrafficChart"></canvas></div>
        </div>
        <div class="p-4 sm:p-6 glass-card rounded-2xl shadow-lg">
            <h3 class="text-lg sm:text-xl font-bold mb-4 text-gray-800 flex items-center"><i class="fas fa-credit-card mr-2 text-purple-500"></i>Metode Pembayaran</h3>
            <div class="h-80"><canvas id="paymentMethodChart"></canvas></div>
        </div>
    </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const colorPalette = <?= json_encode($color_palette) ?>;
    const formatRupiah = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
    const leaderboardData = <?= json_encode($leaderboard_data) ?>;
    const renderLeaderboard = (kategori = 'semua') => {
        const container = document.getElementById('leaderboard-list-container');
        const products = leaderboardData[kategori] || [];
        container.classList.toggle('leaderboard-scrollable', products.length > 4);
        container.innerHTML = ''; 
        if (products.length === 0) {
            container.innerHTML = `<p class="text-center py-10 text-slate-500 italic">Tidak ada produk terjual.</p>`; return;
        }
        products.forEach((p, index) => {
            container.insertAdjacentHTML('beforeend', `<div class="flex items-center gap-4 p-2 rounded-lg hover:bg-slate-50"><div class="flex-none w-8 h-8 rounded-full bg-slate-200 text-slate-600 font-bold flex items-center justify-center text-sm">${index + 1}</div><div class="flex-1 min-w-0"><p class="font-semibold text-slate-800 truncate">${p.nama_produk}</p><p class="text-xs text-slate-500">${p.kategori.charAt(0).toUpperCase() + p.kategori.slice(1)}</p></div><div class="text-right"><p class="font-bold text-indigo-600">${p.total_qty}</p><p class="text-xs text-slate-500">Terjual</p></div></div>`);
        });
    };
    document.getElementById('leaderboard-filter').addEventListener('click', (e) => {
        if (e.target.classList.contains('leaderboard-tab')) {
            document.querySelectorAll('.leaderboard-tab').forEach(tab => tab.classList.remove('active'));
            e.target.classList.add('active');
            renderLeaderboard(e.target.dataset.kategori);
        }
    });
    (function () {
        const ctx = document.getElementById('revenueLineChart').getContext('2d'); const gradient = ctx.createLinearGradient(0, 0, 0, 320); gradient.addColorStop(0, 'rgba(79, 70, 229, 0.5)'); gradient.addColorStop(1, 'rgba(79, 70, 229, 0)');
        new Chart(ctx, { type: 'line', data: { labels: <?= json_encode(array_column($revenue_chart_data, 'date')) ?>, datasets: [{ label: 'Pendapatan', data: <?= json_encode(array_column($revenue_chart_data, 'total')) ?>, borderColor: '#4F46E5', borderWidth: 3, pointBackgroundColor: '#fff', pointBorderColor: '#4F46E5', pointHoverRadius: 7, pointHoverBackgroundColor: '#4F46E5', pointHoverBorderColor: '#fff', tension: 0.4, fill: true, backgroundColor: gradient }] }, options: { maintainAspectRatio: false, responsive: true, scales: { x: { type: 'time', time: { unit: 'day' }, grid: { display: false }, ticks: { display: false } }, y: { beginAtZero: true, ticks: { callback: (value) => 'Rp ' + (value / 1000) + 'k' } } }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => `Omzet: ${formatRupiah(c.raw)}` } } } } });
    })();
    (function () {
        const ctx = document.getElementById('hourlyTrafficChart').getContext('2d'); const data = <?= json_encode(array_values($hourly_data)) ?>; const maxData = Math.max(...data) > 0 ? Math.max(...data) : 1; const backgroundColors = data.map(count => count === 0 ? 'rgba(22, 163, 74, 0.1)' : `rgba(22, 163, 74, ${0.2 + (count / maxData) * 0.8})`);
        new Chart(ctx, { type: 'bar', data: { labels: Array.from({length: 24}, (_, i) => i.toString().padStart(2, '0')), datasets: [{ label: 'Jumlah Transaksi', data: data, backgroundColor: backgroundColors, borderColor: 'rgba(22, 163, 74, 1)', borderWidth: 0, borderRadius: 4, hoverBackgroundColor: 'rgba(22, 163, 74, 1)' }] }, options: { maintainAspectRatio: false, responsive: true, plugins: { legend: { display: false }, tooltip: { callbacks: { title: (c) => `Jam ${c[0].label}:00`, label: (c) => `Jumlah Transaksi: ${c.raw}` } } }, scales: { x: { grid: { display: false }, ticks: { font: { size: 10 } } }, y: { beginAtZero: true, ticks: { precision: 0 } } } } });
    })();
    (function () {
        const data = <?= json_encode($category_revenue_data) ?>; const container = document.getElementById('categoryRevenueChart').parentElement; if(data.length === 0) { container.innerHTML = `<div class="h-full flex items-center justify-center"><p class="text-slate-500 italic">Tidak ada data omzet.</p></div>`; return; }
        const ctx = document.getElementById('categoryRevenueChart').getContext('2d');
        new Chart(ctx, { type: 'doughnut', data: { labels: data.map(row => row.kategori.charAt(0).toUpperCase() + row.kategori.slice(1)), datasets: [{ data: data.map(row => row.total_omzet), backgroundColor: colorPalette, hoverOffset: 8, borderWidth: 4, borderColor: '#fff' }] }, options: { maintainAspectRatio: false, responsive: true, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 20 } }, tooltip: { callbacks: { label: (c) => `${c.label}: ${formatRupiah(c.raw)}` } } }, cutout: '65%' } });
    })();
    (function () {
        const data = <?= json_encode($payment_method_data) ?>; const container = document.getElementById('paymentMethodChart').parentElement; if(data.length === 0) { container.innerHTML = `<div class="h-full flex items-center justify-center"><p class="text-slate-500 italic">Tidak ada data pembayaran.</p></div>`; return; }
        const ctx = document.getElementById('paymentMethodChart').getContext('2d');
        new Chart(ctx, { type: 'doughnut', data: { labels: data.map(row => row.metode_pembayaran.charAt(0).toUpperCase() + row.metode_pembayaran.slice(1)), datasets: [{ label: 'Jumlah Transaksi', data: data.map(row => row.jumlah_transaksi), backgroundColor: colorPalette, hoverOffset: 8, borderWidth: 4, borderColor: '#fff' }] }, options: { maintainAspectRatio: false, responsive: true, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 15 } }, tooltip: { callbacks: { label: (c) => `${c.label}: ${c.raw} transaksi` } } }, cutout: '65%' } });
    })();
    renderLeaderboard('semua');
});
</script>
