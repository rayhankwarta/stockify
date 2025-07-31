<?php
// pages/reports.php

// Pengecekan login dan file config/auth sudah ditangani oleh index.php
$tenant_id = $_SESSION['tenant_id'];

// Bagian ini hanya untuk AJAX request, ditangani oleh index.php
if (!empty($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['action'];
    $start = $_GET['start_date'] ?? date('Y-m-01');
    $end = $_GET['end_date'] ?? date('Y-m-d');

    if ($action === 'get_details' && isset($_GET['tx_id'])) {
        $detailStmt = $db->prepare("
            SELECT p.nama_produk, td.qty, td.harga_satuan, td.subtotal
            FROM transaksi_detail td 
            JOIN produk p ON p.id = td.produk_id
            JOIN transaksi t ON t.id = td.transaksi_id
            WHERE td.transaksi_id = ? AND t.tenant_id = ?
            ORDER BY p.nama_produk
        ");
        $detailStmt->execute([$_GET['tx_id'], $tenant_id]);
        echo json_encode($detailStmt->fetchAll(PDO::FETCH_ASSOC));
        return;
    }

    if ($action === 'filter_data') {
        $txStmt = $db->prepare("SELECT id, kode_transaksi, created_at, total, metode_pembayaran FROM transaksi WHERE DATE(created_at) BETWEEN ? AND ? AND tenant_id = ? ORDER BY created_at DESC");
        $txStmt->execute([$start, $end, $tenant_id]);
        
        $sumStmt = $db->prepare("SELECT metode_pembayaran, COUNT(*) AS jumlah, SUM(total) AS omzet FROM transaksi WHERE DATE(created_at) BETWEEN ? AND ? AND tenant_id = ? GROUP BY metode_pembayaran");
        $sumStmt->execute([$start, $end, $tenant_id]);
        
        $topProductStmt = $db->prepare("SELECT p.nama_produk, SUM(td.qty) as total_qty FROM transaksi_detail td JOIN produk p ON td.produk_id = p.id JOIN transaksi t ON td.transaksi_id = t.id WHERE DATE(t.created_at) BETWEEN ? AND ? AND t.tenant_id = ? GROUP BY p.nama_produk ORDER BY total_qty DESC LIMIT 1");
        $topProductStmt->execute([$start, $end, $tenant_id]);

        echo json_encode([
            'transactions' => $txStmt->fetchAll(PDO::FETCH_ASSOC),
            'summary'      => $sumStmt->fetchAll(PDO::FETCH_ASSOC),
            'topProduct'   => $topProductStmt->fetch(PDO::FETCH_ASSOC)
        ]);
        return;
    }
}

// Bagian ini untuk initial page load
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date']   ?? date('Y-m-d');
if ($start_date > $end_date) [$start_date, $end_date] = [$end_date, $start_date];

// Data untuk 7 hari terakhir (grafik)
$dailyRevenue = [];
$period = new DatePeriod((new DateTime())->modify('-6 days'), new DateInterval('P1D'), (new DateTime())->modify('+1 day'));
$revenueStmt = $db->prepare("SELECT COALESCE(SUM(total), 0) FROM transaksi WHERE DATE(created_at) = ? AND tenant_id = ?");
$daysInIndonesian = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
foreach ($period as $date) {
    $dateString = $date->format('Y-m-d');
    $revenueStmt->execute([$dateString, $tenant_id]);
    $dailyRevenue[] = [
        'day' => $daysInIndonesian[$date->format('w')], 'date' => $date->format('d M'),
        'full_date' => $dateString, 'total' => (int)$revenueStmt->fetchColumn()
    ];
}
$dailyRevenue = array_reverse($dailyRevenue);
?>

<link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    body { font-family: 'Inter', sans-serif; background-color: #f0f4f8; }
    .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .fade-in { animation: fadeIn 0.5s ease-out forwards; }
    .detail-row td { padding: 0 !important; border: 0; }
    .detail-container { background-color: #f1f5f9; transition: all 0.3s ease-in-out; }
    .rotate-180 { transform: rotate(180deg); }
    .stat-card { transition: all 0.3s ease; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1); }
    #transaction-table-container { max-height: 500px; overflow-y: auto; position: relative; }
    #transaction-table-container thead { position: sticky; top: 0; z-index: 10; background-color: #f1f5f9; }
    #transaction-table-container::-webkit-scrollbar { width: 6px; height: 6px; }
    #transaction-table-container::-webkit-scrollbar-thumb { background-color: #a0aec0; border-radius: 3px; }
    #transaction-table-container::-webkit-scrollbar-track { background-color: #edf2f7; }
    .daily-revenue-card { transition: all 0.2s ease-in-out; cursor: pointer; }
    .daily-revenue-card:hover { transform: scale(1.05); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .daily-revenue-card.active { background-color: #4338ca; color: white; transform: scale(1.08); box-shadow: 0 8px 20px rgba(67, 56, 202, 0.4); }
    .daily-revenue-card.active .day-name { color: #c7d2fe; }
    .daily-revenue-card.active .day-date { color: #a5b4fc; }
    .daily-revenue-card.active .day-total { color: white; }
</style>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8 fade-in">
        <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
            <div class="flex-1 min-w-[280px]"><h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Dashboard Laporan</h1></div>
            <div class="font-medium text-gray-600 bg-gray-100 px-4 py-2 rounded-lg"><i class="fas fa-calendar-alt mr-2 text-gray-500"></i>Periode: <span id="periode-display" class="font-bold text-indigo-600"></span></div>
        </div>
        
        <form id="filter-form" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end pt-4 border-t">
            <div><label for="start_date" class="block mb-1 text-sm font-medium text-gray-600">Tanggal Mulai</label><input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="w-full border-gray-300 rounded-lg p-2 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></div>
            <div><label for="end_date" class="block mb-1 text-sm font-medium text-gray-600">Tanggal Selesai</label><input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="w-full border-gray-300 rounded-lg p-2 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></div>
            
            <div class="flex flex-col sm:flex-row sm:justify-end gap-2 md:col-span-2 lg:col-span-2">
                <button type="submit" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-semibold shadow flex items-center justify-center transition-all duration-300 transform hover:scale-105"><i class="fas fa-filter mr-2"></i> Terapkan</button>
                <button type="button" id="reset-btn" class="w-full sm:w-auto bg-gray-500 hover:bg-gray-600 text-white px-5 py-2.5 rounded-lg font-semibold shadow flex items-center justify-center transition-all duration-300 transform hover:scale-105"><i class="fas fa-sync-alt mr-2"></i> Reset</button>
                <a href="#" id="export-link" target="_blank" class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg font-semibold shadow flex items-center justify-center transition-all duration-300 transform hover:scale-105"><i class="fas fa-file-csv mr-2"></i> Export CSV</a>
            </div>
        </form>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <div class="stat-card bg-white rounded-xl shadow-md p-6 flex items-center space-x-4 fade-in">
            <div class="bg-blue-100 text-blue-600 p-4 rounded-full"><i class="fas fa-dollar-sign fa-2x"></i></div>
            <div>
                <p class="text-gray-500 text-sm">Total Omzet</p>
                <p id="total-omzet" class="text-xl md:text-1xl font-bold text-gray-800 break-words">Rp 0</p>
            </div>
        </div>
        <div class="stat-card bg-white rounded-xl shadow-md p-6 flex items-center space-x-4 fade-in">
            <div class="bg-green-100 text-green-600 p-4 rounded-full"><i class="fas fa-receipt fa-2x"></i></div>
            <div>
                <p class="text-gray-500 text-sm">Jumlah Transaksi</p>
                <p id="total-trx" class="text-xl md:text-1xl font-bold text-gray-800">0 Kali</p>
            </div>
        </div>
        <div class="stat-card bg-white rounded-xl shadow-md p-6 flex items-center space-x-4 fade-in">
            <div class="bg-orange-100 text-orange-600 p-4 rounded-full"><i class="fas fa-chart-pie fa-2x"></i></div>
            <div>
                <p class="text-gray-500 text-sm">Rata-rata / Trx</p>
                <p id="avg-trx" class="text-xl md:text-1xl font-bold text-gray-800 break-words">Rp 0</p>
            </div>
        </div>
        <div class="stat-card bg-white rounded-xl shadow-md p-6 flex items-center space-x-4 fade-in">
            <div class="bg-purple-100 text-purple-600 p-4 rounded-full"><i class="fas fa-trophy fa-2x"></i></div>
            <div>
                <p class="text-gray-500 text-sm">Produk Terlaris</p>
                <p id="top-product" class="text-x1 md:text-1xl font-bold text-gray-800 truncate">Tidak Ada</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8 fade-in">
        <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-3">Pendapatan 7 Hari Terakhir</h3>
        <div id="daily-revenue-container" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
            <?php foreach ($dailyRevenue as $data): ?>
            <div class="daily-revenue-card text-center p-4 rounded-lg bg-gray-100" data-date="<?= $data['full_date'] ?>">
                <p class="day-name text-sm font-semibold text-gray-600"><?= $data['day'] ?></p>
                <p class="day-date text-xs text-gray-500 mb-2"><?= $data['date'] ?></p>
                <p class="day-total text-lg font-extrabold text-indigo-700">Rp <?= number_format($data['total'], 0, ',', '.') ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 bg-white rounded-xl shadow-lg fade-in">
            <div class="p-6 flex justify-between items-center"><h3 class="text-xl font-bold text-gray-800">Detail Transaksi</h3></div>
            <div id="transaction-table-container" class="table-responsive"><table class="min-w-full"><thead class="bg-gray-100"><tr><th class="py-3 px-6 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Detail</th><th class="py-3 px-6 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kode Transaksi</th><th class="py-3 px-6 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th><th class="py-3 px-6 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Metode</th><th class="py-3 px-6 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Total</th></tr></thead><tbody id="transaction-tbody" class="divide-y divide-gray-200"></tbody></table></div>
            <div id="loading-indicator" class="hidden p-6 text-center text-gray-500"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Memuat data...</p></div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6 fade-in">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Ringkasan Pembayaran</h3>
            <div id="summary-details" class="space-y-4"></div>
            <div class="border-t pt-4 mt-4"><div class="flex justify-between items-center text-sm"><span class="font-bold text-gray-700">TOTAL OMZET</span><span id="summary-total-omzet" class="font-extrabold text-lg text-emerald-700">Rp 0</span></div></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formatRupiah = (n) => 'Rp ' + (n || 0).toLocaleString('id-ID');

    function updateUI(data) {
        const totalOmzet = data.summary.reduce((sum, item) => sum + parseFloat(item.omzet || 0), 0);
        const totalTrx = data.summary.reduce((sum, item) => sum + parseInt(item.jumlah || 0), 0);
        const avgTrx = totalTrx > 0 ? totalOmzet / totalTrx : 0;
        
        document.getElementById('total-omzet').textContent = formatRupiah(totalOmzet);
        document.getElementById('total-trx').textContent = `${totalTrx} Kali`;
        document.getElementById('avg-trx').textContent = formatRupiah(avgTrx);
        document.getElementById('top-product').textContent = data.topProduct ? data.topProduct.nama_produk : 'Tidak Ada';
        document.getElementById('top-product').title = data.topProduct ? data.topProduct.nama_produk : 'N/A';

        const tbody = document.getElementById('transaction-tbody');
        tbody.innerHTML = '';
        if (!data.transactions || data.transactions.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-10 text-gray-500 italic">Tidak ada transaksi pada periode ini.</td></tr>`;
        } else {
            data.transactions.forEach((tx) => {
                const row = `
                    <tr class="transaction-row hover:bg-gray-50 transition-colors" data-id="${tx.id}">
                        <td class="py-4 px-6 text-center"><button class="text-indigo-500 hover:text-indigo-700 expand-btn"><i class="fas fa-chevron-down transition-transform"></i></button></td>
                        <td class="py-4 px-6 text-sm font-medium text-gray-800">${tx.kode_transaksi}</td>
                        <td class="py-4 px-6 text-sm text-gray-600">${new Date(tx.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })}</td>
                        <td class="py-4 px-6 text-center"><span class="px-2 py-1 text-xs font-semibold leading-5 rounded-full ${tx.metode_pembayaran === 'cash' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'}">${tx.metode_pembayaran.charAt(0).toUpperCase() + tx.metode_pembayaran.slice(1)}</span></td>
                        <td class="py-4 px-6 text-sm text-right font-semibold text-green-700">${formatRupiah(tx.total)}</td>
                    </tr>
                    <tr class="detail-row hidden" data-parent-id="${tx.id}"><td colspan="5" class="p-0"><div class="detail-container p-4 text-center">Memuat detail...</div></td></tr>`;
                tbody.insertAdjacentHTML('beforeend', row);
            });
        }

        const summaryContainer = document.getElementById('summary-details');
        summaryContainer.innerHTML = '';
        if(data.summary.length === 0) {
            summaryContainer.innerHTML = `<p class="text-gray-500 italic">Tidak ada data pembayaran.</p>`;
        } else {
            data.summary.forEach(s => {
                const summaryRow = `<div class="flex justify-between items-center text-sm"><span class="font-medium text-gray-600">${s.metode_pembayaran.charAt(0).toUpperCase() + s.metode_pembayaran.slice(1)} (${s.jumlah} trx)</span><span class="font-bold text-emerald-600">${formatRupiah(s.omzet)}</span></div>`;
                summaryContainer.insertAdjacentHTML('beforeend', summaryRow);
            });
        }
        document.getElementById('summary-total-omzet').textContent = formatRupiah(totalOmzet);
    }

    async function fetchData(startDate, endDate) {
        document.getElementById('loading-indicator').classList.remove('hidden');
        document.getElementById('transaction-tbody').innerHTML = '';

        const startFmt = new Date(startDate + 'T00:00:00').toLocaleDateString('id-ID', {day:'numeric',month:'short',year:'numeric'});
        const endFmt = new Date(endDate + 'T00:00:00').toLocaleDateString('id-ID', {day:'numeric',month:'short',year:'numeric'});
        document.getElementById('periode-display').textContent = (startFmt === endFmt) ? startFmt : `${startFmt} - ${endFmt}`;
        document.getElementById('export-link').href = `includes/export_transaction.php?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
        
        try {
            const url = `index.php?page=reports&action=filter_data&start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
            const response = await fetch(url);
            if (!response.ok) throw new Error(`Gagal mengambil data dari server. Status: ${response.status}`);
            const data = await response.json();
            updateUI(data);
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        } finally {
            document.getElementById('loading-indicator').classList.add('hidden');
        }
    }

    document.getElementById('filter-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        if (!startDate || !endDate) {
            Swal.fire('Oops...', 'Tanggal mulai dan tanggal selesai harus diisi!', 'warning');
            return;
        }
        document.querySelectorAll('.daily-revenue-card.active').forEach(c => c.classList.remove('active'));
        fetchData(startDate, endDate);
    });
    
    document.getElementById('reset-btn').addEventListener('click', function() {
        const defaultStart = '<?= date('Y-m-01') ?>';
        const defaultEnd = '<?= date('Y-m-d') ?>';
        document.getElementById('start_date').value = defaultStart;
        document.getElementById('end_date').value = defaultEnd;
        document.querySelectorAll('.daily-revenue-card.active').forEach(c => c.classList.remove('active'));
        fetchData(defaultStart, defaultEnd);
    });

    document.getElementById('daily-revenue-container').addEventListener('click', function(e) {
        const clickedCard = e.target.closest('.daily-revenue-card');
        if (!clickedCard) return;
        document.querySelectorAll('.daily-revenue-card').forEach(card => card.classList.remove('active'));
        clickedCard.classList.add('active');
        const clickedDate = clickedCard.dataset.date;
        document.getElementById('start_date').value = clickedDate;
        document.getElementById('end_date').value = clickedDate;
        fetchData(clickedDate, clickedDate);
    });

    document.getElementById('transaction-tbody').addEventListener('click', async function(e) {
        const button = e.target.closest('.expand-btn');
        if (!button) return;
        const row = button.closest('.transaction-row');
        const txId = row.dataset.id;
        const detailRow = document.querySelector(`.detail-row[data-parent-id='${txId}']`);
        const icon = row.querySelector('.fa-chevron-down');
        icon.classList.toggle('rotate-180');
        detailRow.classList.toggle('hidden');
        if (!detailRow.classList.contains('hidden') && detailRow.dataset.loaded !== 'true') {
            const detailContainer = detailRow.querySelector('.detail-container');
            try {
                const response = await fetch(`index.php?page=reports&action=get_details&tx_id=${txId}`);
                if(!response.ok) throw new Error('Gagal memuat detail.');
                const details = await response.json();
                detailRow.dataset.loaded = 'true';
                if(details.length === 0) {
                    detailContainer.innerHTML = '<p class="text-gray-500">Tidak ada detail produk.</p>'; return;
                }
                let detailHTML = `<table class="w-full text-sm"><thead class="bg-gray-200"><tr><th class="px-6 py-2 text-left font-semibold">Produk</th><th class="px-6 py-2 text-center font-semibold">Qty</th><th class="px-6 py-2 text-right font-semibold">Harga Satuan</th><th class="px-6 py-2 text-right font-semibold">Subtotal</th></tr></thead><tbody>`;
                details.forEach(d => {
                    detailHTML += `<tr class="border-t border-gray-300"><td class="px-6 py-2">${d.nama_produk}</td><td class="px-6 py-2 text-center">${d.qty}</td><td class="px-6 py-2 text-right">${formatRupiah(d.harga_satuan)}</td><td class="px-6 py-2 text-right font-medium">${formatRupiah(d.subtotal)}</td></tr>`;
                });
                detailHTML += '</tbody></table>';
                detailContainer.innerHTML = detailHTML;
            } catch (error) {
                detailContainer.innerHTML = `<p class="text-red-500">${error.message}</p>`;
            }
        }
    });

    // --- INITIAL LOAD ---
    fetchData(document.getElementById('start_date').value, document.getElementById('end_date').value);
});
</script>