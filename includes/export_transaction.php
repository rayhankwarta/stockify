<?php
// includes/export_transaction.php
// Ekspor CSV untuk laporan transaksi Stockify

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// Pastikan user login
if (!is_logged_in()) {
    // Sebaiknya tidak ada output sebelum header, tapi ini sebagai fallback
    header('Location: ../login.php');
    exit;
}

// --- PERBAIKAN ---
// Ambil tenant_id dari session untuk memastikan isolasi data
$tenant_id = $_SESSION['tenant_id'];

// Ambil parameter tanggal
$start = $_GET['start_date'] ?? date('Y-m-01');
$end   = $_GET['end_date']   ?? date('Y-m-d');

// Tukar jika start > end
if ($start > $end) {
    [$start, $end] = [$end, $start];
}

// --- PERBAIKAN ---
// Query utama sekarang menyertakan "WHERE t.tenant_id = ?"
// untuk hanya mengambil data dari tenant yang sedang aktif.
$stmt = $db->prepare("
    SELECT
        t.kode_transaksi,
        t.created_at,
        GROUP_CONCAT(CONCAT(p.nama_produk, ' (', td.qty, 'x)') SEPARATOR '; ') AS detail_produk,
        t.total,
        t.metode_pembayaran,
        t.uang_dibayar,
        t.uang_kembali
    FROM transaksi AS t
    JOIN transaksi_detail AS td ON td.transaksi_id = t.id
    JOIN produk AS p ON td.produk_id = p.id
    WHERE DATE(t.created_at) BETWEEN ? AND ? AND t.tenant_id = ?
    GROUP BY t.id
    ORDER BY t.created_at DESC
");

// Eksekusi dengan menyertakan tenant_id
$stmt->execute([$start, $end, $tenant_id]);

// Persiapkan output CSV
$filename = "laporan_transaksi_{$start}_sampai_{$end}.csv";
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");

// Buka stream output
$output = fopen('php://output', 'w');

// Header kolom
fputcsv($output, [
    'Kode Transaksi',
    'Tanggal',
    'Waktu',
    'Detail Produk',
    'Total Belanja',
    'Metode Pembayaran',
    'Uang Dibayar',
    'Uang Kembali'
]);

// Data isi
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $tanggal = new DateTime($row['created_at']);
    fputcsv($output, [
        $row['kode_transaksi'],
        $tanggal->format('Y-m-d'),
        $tanggal->format('H:i:s'),
        $row['detail_produk'],
        $row['total'],
        ucfirst($row['metode_pembayaran']),
        $row['uang_dibayar'],
        $row['uang_kembali']
    ]);
}

fclose($output);
exit;
