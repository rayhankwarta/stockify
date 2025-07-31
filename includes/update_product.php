<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if (!is_logged_in() || !is_admin()) {
    // Memberi tahu browser akses ditolak
    http_response_code(403); // 403 Forbidden
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

// Memastikan ID produk yang dikirim itu valid
if (isset($_POST['id']) && !empty($_POST['id'])) {
    $id = $_POST['id'];
    $kode_produk = trim($_POST['kode_produk'] ?? '');
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $harga = isset($_POST['harga']) ? intval($_POST['harga']) : 0;
    $stok = isset($_POST['stok']) ? intval($_POST['stok']) : 0;
    $gambar = trim($_POST['gambar'] ?? '');

    // Validasi input dasar untuk memastikan data tidak kosong
    if (empty($kode_produk) || empty($nama_produk) || empty($kategori) || $harga <= 0) {
        // Memberi tahu browser bahwa data yang dikirim tidak benar
        http_response_code(400); // 400 Bad Request
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap atau tidak valid']);
        exit;
    }

    try {
        // ✅ Cek duplikasi kode produk dengan produk LAIN
        $check = $db->prepare("SELECT id FROM produk WHERE kode_produk = ? AND id != ?");
        $check->execute([$kode_produk, $id]);
        
        if ($check->fetchColumn()) {
            // Memberi tahu browser ada konflik data (duplikasi)
            http_response_code(409); // 409 Conflict
            echo json_encode(['success' => false, 'message' => 'Kode produk sudah digunakan oleh produk lain']);
            exit;
        }

        // Jika tidak ada duplikasi, siapkan query untuk update
        $query = "UPDATE produk SET 
                    kode_produk = :kode_produk, 
                    nama_produk = :nama_produk, 
                    kategori = :kategori, 
                    harga = :harga, 
                    stok = :stok, 
                    gambar = :gambar, 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        // Bind semua parameter ke query
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':kode_produk', $kode_produk, PDO::PARAM_STR);
        $stmt->bindParam(':nama_produk', $nama_produk, PDO::PARAM_STR);
        $stmt->bindParam(':kategori', $kategori, PDO::PARAM_STR);
        $stmt->bindParam(':harga', $harga, PDO::PARAM_INT);
        $stmt->bindParam(':stok', $stok, PDO::PARAM_INT);
        $stmt->bindParam(':gambar', $gambar, PDO::PARAM_STR);

        // Eksekusi query dan kirim respons
        if ($stmt->execute()) {
            // Respons sukses (HTTP 200 OK secara default)
            echo json_encode(['success' => true, 'message' => 'Produk berhasil diperbarui']);
        } else {
            // Memberi tahu browser ada masalah di server saat eksekusi
            http_response_code(500); // 500 Internal Server Error
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui produk']);
        }
    } catch (PDOException $e) {
        // Memberi tahu browser ada masalah koneksi atau query ke database
        http_response_code(500); // 500 Internal Server Error
        // Sebaiknya jangan tampilkan $e->getMessage() di production untuk keamanan
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada database.']);
    }
} else {
    // Memberi tahu browser bahwa ID produk tidak dikirim
    http_response_code(400); // 400 Bad Request
    echo json_encode(['success' => false, 'message' => 'ID produk tidak valid']);
}