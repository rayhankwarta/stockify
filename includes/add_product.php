<?php
// includes/add_product.php

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

// 3. Ambil tenant_id dari session
$tenant_id = $_SESSION['tenant_id'];

// 4. Ambil & Sanitasi Input dari Form
$kode_produk = trim($_POST['kode_produk'] ?? '');
$nama_produk = trim($_POST['nama_produk'] ?? '');
$kategori    = trim($_POST['kategori'] ?? ''); // Menerima nama kategori (string)
$harga       = isset($_POST['harga']) ? intval($_POST['harga']) : 0;
$stok        = isset($_POST['stok'])  ? intval($_POST['stok'])  : 0;

// 5. Validasi Input
$errors = [];
if (empty($kode_produk)) $errors[] = 'Kode produk wajib diisi.';
if (empty($nama_produk)) $errors[] = 'Nama produk wajib diisi.';
if ($harga <= 0) $errors[] = 'Harga harus lebih besar dari 0.';
if ($stok < 0) $errors[] = 'Stok tidak boleh negatif.';

// Validasi nilai ENUM
$allowed_kategori = ['Makanan', 'Minuman', 'Snack'];
if (empty($kategori) || !in_array($kategori, $allowed_kategori)) {
    $errors[] = 'Kategori yang dipilih tidak valid.';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode("\n", $errors)]);
    exit;
}

// 6. Proses Simpan ke Database
try {
    // Cek duplikat kode produk per tenant
    $stmt = $db->prepare("SELECT id FROM produk WHERE kode_produk = ? AND tenant_id = ?");
    $stmt->execute([$kode_produk, $tenant_id]);
    if ($stmt->fetch()) {
        throw new Exception('Kode produk sudah digunakan di toko Anda.');
    }

    // Query INSERT menggunakan kolom 'kategori'
    $stmt = $db->prepare(
        "INSERT INTO produk (tenant_id, kode_produk, nama_produk, kategori, harga, stok)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$tenant_id, $kode_produk, $nama_produk, $kategori, $harga, $stok]);

    echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan!']);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada database.']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
