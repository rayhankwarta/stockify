<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Memanggil fungsi logout terpusat dari auth.php
logout_user();

// Arahkan pengguna kembali ke halaman login
redirect('login.php');
?>