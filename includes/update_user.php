<?php
// includes/update_user.php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id_to_update = $input['id'] ?? 0;
$new_role = $input['role'] ?? '';
$tenant_id = $_SESSION['tenant_id'];
$admin_id = $_SESSION['user_id'];

if (empty($id_to_update) || !in_array($new_role, ['admin', 'kasir'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Input tidak valid.']);
    exit;
}

// Keamanan: Admin tidak bisa mengubah role dirinya sendiri
if ($id_to_update == $admin_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Anda tidak dapat mengubah role akun Anda sendiri.']);
    exit;
}

try {
    // Update role HANYA untuk user di dalam tenant yang sama
    $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$new_role, $id_to_update, $tenant_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Role pengguna berhasil diubah.']);
    } else {
        throw new Exception('User tidak ditemukan atau tidak ada perubahan.');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
