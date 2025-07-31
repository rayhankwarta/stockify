<?php
// includes/delete_user.php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id_to_delete = $input['id'] ?? 0;
$tenant_id = $_SESSION['tenant_id'];
$admin_id = $_SESSION['user_id'];

if (empty($id_to_delete)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID User tidak valid.']);
    exit;
}

// Keamanan: Admin tidak bisa menghapus dirinya sendiri
if ($id_to_delete == $admin_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Anda tidak dapat menghapus akun Anda sendiri.']);
    exit;
}

try {
    // Hapus user HANYA dari tenant yang sama
    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id_to_delete, $tenant_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'User berhasil dihapus.']);
    } else {
        throw new Exception('User tidak ditemukan.');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
