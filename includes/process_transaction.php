<?php
// includes/process_transaction.php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// 1. Cek Autentikasi
if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan login terlebih dahulu.']);
    exit;
}

// 2. Cek Metode Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid.']);
    exit;
}

// 3. Ambil data dari sesi dan POST (Bukan JSON)
$user_id = $_SESSION['user_id'];
$tenant_id = $_SESSION['tenant_id'];
$metode = $_POST['metode_pembayaran'] ?? 'cash';
$uang_dibayar = (int)($_POST['uang_dibayar'] ?? 0);
$items = $_POST['items'] ?? [];

// 4. Validasi Input Dasar
if (empty($items) || !is_array($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Keranjang belanja kosong atau format data salah.']);
    exit;
}

// 5. Memulai Transaksi Database (PENTING untuk integritas data)
$db->beginTransaction();

try {
    // 5a. Validasi Server-Side: Ambil data produk asli dari DB
    $produk_ids = array_column($items, 'produk_id');
    if (empty($produk_ids)) {
        throw new Exception("Tidak ada produk yang valid di keranjang.");
    }
    
    $placeholders = implode(',', array_fill(0, count($produk_ids), '?'));
    // Mengunci baris produk untuk mencegah race condition saat update stok
    $stmt_produk = $db->prepare("SELECT id, nama_produk, harga, stok FROM produk WHERE id IN ($placeholders) AND tenant_id = ? FOR UPDATE");
    $stmt_produk->execute(array_merge($produk_ids, [$tenant_id]));
    
    // Buat map produk dari database untuk akses mudah: [id => data_produk]
    $produk_db_map = [];
    while ($row = $stmt_produk->fetch(PDO::FETCH_ASSOC)) {
        $produk_db_map[$row['id']] = $row;
    }

    // 5b. Hitung total belanja dari harga di DB & validasi stok
    $total_belanja = 0;
    $validated_items = [];
    foreach ($items as $item) {
        $produk_id = (int)$item['produk_id'];
        $qty = (int)$item['qty'];

        // Cek apakah produk ada di toko ini
        if (!isset($produk_db_map[$produk_id])) {
            throw new Exception("Produk dengan ID $produk_id tidak ditemukan di toko Anda.");
        }
        
        $produk_db = $produk_db_map[$produk_id];

        // Cek stok
        if ($qty > $produk_db['stok']) {
            throw new Exception("Stok untuk produk '{$produk_db['nama_produk']}' tidak mencukupi (tersisa {$produk_db['stok']}).");
        }
        
        // Gunakan harga dari database, bukan dari client
        $harga_asli = (int)$produk_db['harga'];
        $total_belanja += $qty * $harga_asli;
        $validated_items[] = ['produk_id' => $produk_id, 'qty' => $qty, 'harga_satuan' => $harga_asli];
    }
    
    // 5c. Hitung kembalian
    $uang_kembali = ($metode === 'cash') ? $uang_dibayar - $total_belanja : 0;
    if ($metode === 'cash' && $uang_kembali < 0) {
        throw new Exception('Uang yang dibayarkan kurang.');
    }

    // 5d. Buat kode transaksi unik
    $kode_transaksi = 'TRX-' . strtoupper(uniqid());

    // --- PERBAIKAN WAKTU ---
    // Dapatkan waktu saat ini menggunakan zona waktu PHP ('Asia/Jakarta')
    $waktu_sekarang = date('Y-m-d H:i:s');

    // 5e. Masukkan ke tabel 'transaksi' dengan waktu yang sudah benar
    $stmt_transaksi = $db->prepare(
        "INSERT INTO transaksi (tenant_id, user_id, kode_transaksi, total, metode_pembayaran, uang_dibayar, uang_kembali, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt_transaksi->execute([$tenant_id, $user_id, $kode_transaksi, $total_belanja, $metode, $uang_dibayar, $uang_kembali, $waktu_sekarang]);
    $transaksi_id = $db->lastInsertId();

    // 5f. Siapkan statement untuk detail dan update stok
    $stmt_detail = $db->prepare(
        "INSERT INTO transaksi_detail (tenant_id, transaksi_id, produk_id, qty, harga_satuan, subtotal) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt_update_stok = $db->prepare(
        "UPDATE produk SET stok = stok - ? WHERE id = ? AND tenant_id = ?"
    );

    // 5g. Loop untuk memasukkan detail dan update stok
    foreach ($validated_items as $item) {
        $subtotal = $item['qty'] * $item['harga_satuan'];
        $stmt_detail->execute([$tenant_id, $transaksi_id, $item['produk_id'], $item['qty'], $item['harga_satuan'], $subtotal]);
        $stmt_update_stok->execute([$item['qty'], $item['produk_id'], $tenant_id]);
    }

    // 6. Jika semua berhasil, commit transaksi
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Transaksi berhasil disimpan.']);

} catch (Exception $e) {
    // 7. Jika ada error, batalkan semua (rollback)
    $db->rollBack();
    http_response_code(400); // Bad Request untuk error logika
    error_log("Transaction Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Gagal memproses transaksi: ' . $e->getMessage()]);
}
?>