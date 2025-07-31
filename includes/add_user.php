<?php
// includes/add_user.php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$nama_lengkap = trim($input['nama_lengkap'] ?? '');
$password = $input['password'] ?? '';
$tenant_id = $_SESSION['tenant_id'];

$errors = [];
if (empty($email)) $errors[] = 'Format email tidak valid.';
if (empty($nama_lengkap)) $errors[] = 'Nama lengkap wajib diisi.';
if (strlen($password) < 8) $errors[] = 'Password minimal 8 karakter.';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode("\n", $errors)]);
    exit;
}

try {
    // Cek duplikat email HANYA di dalam tenant yang sama
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND tenant_id = ?");
    $stmt->execute([$email, $tenant_id]);
    if ($stmt->fetch()) {
        throw new Exception('Email sudah terdaftar di toko Anda.');
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    // Tambahkan user baru dengan role 'kasir' sebagai default
    $stmt = $db->prepare(
        "INSERT INTO users (tenant_id, email, nama_lengkap, password, role) VALUES (?, ?, ?, ?, 'kasir')"
    );
    $stmt->execute([$tenant_id, $email, $nama_lengkap, $hashed_password]);

    echo json_encode(['success' => true, 'message' => 'User baru berhasil ditambahkan.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
