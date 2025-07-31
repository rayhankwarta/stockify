<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Mendaftarkan Tenant baru beserta user Admin pertamanya.
 * @param string $nama_toko Nama untuk tenant/toko baru.
 * @param string $email Email yang akan digunakan user untuk login.
 * @param string $password Password untuk user baru.
 * @param string $nama_lengkap Nama lengkap dari user admin.
 * @return array Mengembalikan array berisi status ('success' atau 'error') dan pesan.
 */
function register_tenant_and_user(string $nama_toko, string $email, string $password, string $nama_lengkap): array
{
    global $db; 

    // 1. Cek dulu apakah email sudah pernah terdaftar di seluruh sistem
    // --- DIUBAH --- Menggunakan kolom 'email'
    $stmt_check = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt_check->execute([$email]);
    if ($stmt_check->rowCount() > 0) {
        return ['status' => 'error', 'message' => 'Email sudah terdaftar. Silakan gunakan email lain.'];
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $db->beginTransaction();

    try {
        // 3a. Masukkan data tenant baru
        $stmt_tenant = $db->prepare("INSERT INTO tenants (nama_toko) VALUES (?)");
        $stmt_tenant->execute([$nama_toko]);
        $tenant_id = $db->lastInsertId();

        // 3c. Masukkan data user baru dengan role 'admin'
        // --- DIUBAH --- Menggunakan kolom 'email'
        $stmt_user = $db->prepare(
            "INSERT INTO users (tenant_id, email, password, nama_lengkap, role) VALUES (?, ?, ?, ?, 'admin')"
        );
        $stmt_user->execute([$tenant_id, $email, $hashed_password, $nama_lengkap]);

        $db->commit();
        return ['status' => 'success', 'message' => 'Toko dan Akun Admin berhasil dibuat. Silakan login.'];

    } catch (PDOException $e) {
        $db->rollBack();
        error_log('[Register Error] ' . $e->getMessage());
        return ['status' => 'error', 'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.'];
    }
}

/**
 * Login user dan menyimpan tenant_id ke dalam session.
 * @param string $email Email yang digunakan untuk login.
 * @param string $password Password yang dimasukkan.
 * @return bool True jika login berhasil, false jika gagal.
 */
function login_user(string $email, string $password): bool
{
    global $db;

    // --- DIUBAH --- Mengambil 'email' dan mencari berdasarkan 'email'
    $stmt = $db->prepare("SELECT id, tenant_id, email, password, nama_lengkap, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['tenant_id'] = $user['tenant_id']; // Kunci utama multi-tenancy
        $_SESSION['email'] = $user['email']; // --- DIUBAH --- Menyimpan email, bukan username
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['role'];
        return true;
    }

    return false; // Login gagal
}

/**
 * Fungsi untuk cek apakah email sudah ada.
 */
function check_user_exists($email)
{
    global $db;
    // --- DIUBAH --- Menggunakan kolom 'email'
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}


/**
 * Logout user dengan membersihkan semua session.
 */
function logout_user()
{
    session_unset();
    session_destroy();
}