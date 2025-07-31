<?php
// includes/delete_product.php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// 1. Cek Autentikasi & Hak Akses Admin
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

// 2. Cek Metode Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid.']);
    exit;
}

// 3. Ambil tenant_id dari session dan ID produk dari POST
$tenant_id = $_SESSION['tenant_id'];
$id = $_POST['id'] ?? 0;

if (empty($id) || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID produk tidak valid.']);
    exit;
}

// 4. Proses Hapus dengan Keamanan Multi-Tenant
try {
    // Query DELETE sekarang mencakup tenant_id untuk memastikan
    // admin hanya bisa menghapus produk dari tokonya sendiri.
    $stmt = $db->prepare("DELETE FROM produk WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tenant_id]);

    // Cek apakah ada baris yang terpengaruh (berhasil dihapus)
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Produk berhasil dihapus.']);
    } else {
        // Jika tidak ada baris yang terpengaruh, berarti produk tidak ditemukan
        // atau bukan milik tenant ini.
        throw new Exception('Produk tidak ditemukan atau Anda tidak memiliki izin untuk menghapusnya.');
    }
} catch (PDOException $e) {
    // Handle error jika produk terikat dengan transaksi (foreign key constraint)
    if ($e->getCode() == '23000') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus: Produk ini sudah pernah tercatat dalam transaksi.']);
    } else {
        http_response_code(500);
        error_log("Database Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada database.']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
