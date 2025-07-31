<?php
// Selalu mulai session di baris paling atas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//stockify_stocikfy (user & db)
//vLtj4v7rUHnxALt3xECq (pass)

/* ============================================================
   1. KONFIGURASI DATABASE
   ============================================================ */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
// Menggunakan nama database baru yang sudah mendukung multi-tenant.
define('DB_NAME', 'stockify_multitenant');

/* ============================================================
   2. KONEKSI PDO
   ============================================================ */
try {
    $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';

    $db  = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT         => false
    ]);

} catch (PDOException $e) {
    error_log('[DB] '.$e->getMessage());
    die('Koneksi database gagal — silakan hubungi administrator.');
}

/* ============================================================
   3. TIMEZONE
   ============================================================ */
date_default_timezone_set('Asia/Jakarta');

/* ============================================================
   4. HELPER & FUNGSI OTENTIKASI
   ============================================================ */

// Fungsi redirect
function redirect(string $url): void {
    header('Location: '.$url);
    exit;
}

/**
 * Cek apakah pengguna sudah login dengan memeriksa keberadaan 'user_id' di session.
 * @return bool
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Cek apakah pengguna yang sedang login memiliki peran 'admin'.
 * @return bool
 */
function is_admin(): bool {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Memaksa pengguna untuk login jika belum.
 * Panggil fungsi ini di bagian atas setiap halaman yang memerlukan login.
 */
function require_login(): void {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

/**
 * Memaksa pengguna harus admin untuk mengakses halaman.
 * Panggil fungsi ini di bagian atas setiap halaman yang hanya boleh diakses oleh admin.
 */
function require_admin(): void {
    if (!is_admin()) {
        $_SESSION['flash_error'] = 'Akses ditolak. Anda bukan admin.';
        redirect('index.php');
    }
}
?>