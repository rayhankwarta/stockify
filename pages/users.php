<?php
// pages/users.php

// Pengecekan hak akses admin sudah dilakukan di index.php
// Ambil tenant_id dari admin yang sedang login
$tenant_id = $_SESSION['tenant_id'];

// --- PERBAIKAN ---
// Query sekarang HANYA mengambil user dari tenant yang sama.
$stmt = $db->prepare("SELECT id, email, nama_lengkap, role FROM users WHERE tenant_id = ? ORDER BY created_at DESC");
$stmt->execute([$tenant_id]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
    .card-bg-blur { content: ''; position: absolute; width: 200px; height: 200px; top: -50px; left: -50px; border-radius: 9999px; filter: blur(60px); opacity: 0.25; z-index: 0; }
    .tooltip .tooltip-text { visibility: hidden; opacity: 0; transition: opacity 0.2s; }
    .tooltip:hover .tooltip-text { visibility: visible; opacity: 1; }
</style>

<!-- Modal untuk Tambah User -->
<div id="userModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden transition-opacity duration-300">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-8 m-4">
        <h4 id="modalTitle" class="text-2xl font-bold mb-6 text-gray-800 border-b pb-3">Tambah User Baru</h4>
        <form id="userForm" class="space-y-5">
            <input type="hidden" name="id">
            <div>
                <!-- --- PERBAIKAN --- Label dan nama input disesuaikan -->
                <label for="email" class="block text-gray-700 mb-2 font-medium">Email</label>
                <input type="email" id="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition">
            </div>
            <div>
                <label for="nama_lengkap" class="block text-gray-700 mb-2 font-medium">Nama Lengkap</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition">
            </div>
            <div class="password-group">
                <label for="password" class="block text-gray-700 mb-2 font-medium">Password</label>
                <input type="password" name="password" id="password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition">
                <p class="text-xs text-gray-500 mt-1">Minimal 8 karakter.</p>
            </div>
            <div class="password-group">
                <label for="confirm_password" class="block text-gray-700 mb-2 font-medium">Konfirmasi Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition">
            </div>
            <div class="flex justify-end space-x-4 pt-4 border-t">
                <button type="button" class="modal-cancel-btn px-6 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 transition font-semibold text-gray-800">Batal</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="container mx-auto px-6 py-8">
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h3 class="text-3xl font-extrabold text-gray-800">Manajemen Pengguna</h3>
                <p class="text-gray-500 mt-1">Kelola akun admin dan kasir untuk toko Anda.</p>
            </div>
            <button id="openAddUserBtn" class="mt-2 md:mt-0 inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold transition shadow hover:shadow-lg">
                <i class="fas fa-user-plus"></i> Tambah User
            </button>
        </div>
        <div class="relative mt-6 border-t pt-6">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 pt-6"><i class="fas fa-search"></i></span>
            <input type="text" id="user-search" placeholder="Cari pengguna berdasarkan nama atau email..." class="w-full pl-12 pr-4 py-3 bg-slate-100 rounded-full border border-slate-200 focus:ring-2 focus:ring-indigo-400 transition">
        </div>
    </div>
    
    <div id="user-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php foreach ($users as $u): ?>
        <!-- --- PERBAIKAN --- data-username diubah menjadi data-email -->
        <div class="user-card relative bg-white rounded-2xl shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-[1.02] overflow-hidden" 
             data-id="<?= $u['id'] ?>" 
             data-nama="<?= strtolower(htmlspecialchars($u['nama_lengkap'])) ?>" 
             data-email="<?= strtolower(htmlspecialchars($u['email'])) ?>" 
             data-role="<?= $u['role'] ?>">
            
            <div class="card-bg-blur <?= $u['role'] === 'admin' ? 'bg-blue-300' : 'bg-green-300' ?>"></div>

            <div class="relative p-6 z-10 flex flex-col h-full">
                <div class="flex flex-col items-center text-center">
                    <div class="w-24 h-24 bg-gradient-to-br from-slate-100 to-slate-200 rounded-full flex items-center justify-center text-4xl font-bold text-slate-600 border-4 border-white shadow-md">
                        <?= strtoupper(substr($u['nama_lengkap'], 0, 1)) ?>
                    </div>
                    <h4 class="font-bold text-xl text-gray-800 mt-4 truncate w-full"><?= htmlspecialchars($u['nama_lengkap']) ?></h4>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($u['email']) ?></p>
                </div>

                <div class="my-6 text-center">
                    <span class="inline-flex items-center gap-2 px-3 py-1 text-sm font-semibold rounded-full <?= $u['role'] === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>">
                        <i class="fas <?= $u['role'] === 'admin' ? 'fa-user-shield' : 'fa-user-tag' ?>"></i>
                        <?= htmlspecialchars(ucfirst($u['role'])) ?>
                    </span>
                </div>

                <div class="mt-auto pt-6 border-t border-gray-100 flex justify-center gap-3">
                    <div class="tooltip relative">
                        <button class="update-role-btn flex items-center justify-center w-12 h-12 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full transition">
                            <i class="fas fa-exchange-alt"></i>
                        </button>
                        <span class="tooltip-text absolute bottom-full mb-2 w-max px-2 py-1 bg-gray-800 text-white text-xs rounded-md">Ubah Role</span>
                    </div>
                    <div class="tooltip relative">
                        <button class="delete-btn flex items-center justify-center w-12 h-12 bg-rose-100 hover:bg-rose-200 text-rose-600 rounded-full transition">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                         <span class="tooltip-text absolute bottom-full mb-2 w-max px-2 py-1 bg-gray-800 text-white text-xs rounded-md">Hapus User</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    async function postForm(url, data) {
        const resp = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const payload = await resp.json();
        if (!resp.ok) throw payload;
        return payload;
    }

    const userModal = document.getElementById('userModal');
    const userForm = document.getElementById('userForm');
    const openAddUserBtn = document.getElementById('openAddUserBtn');
    const cancelBtn = userModal.querySelector('.modal-cancel-btn');

    const openModal = () => userModal.classList.remove('hidden');
    const closeModal = () => {
        userModal.classList.add('hidden');
        userForm.reset();
    };

    openAddUserBtn.addEventListener('click', openModal);
    cancelBtn.addEventListener('click', closeModal);
    userModal.addEventListener('click', (e) => {
        if (e.target === userModal) closeModal();
    });

    userForm.addEventListener('submit', async e => {
        e.preventDefault();
        const f = e.target;
        const password = f.password.value;
        const confirmPassword = f.confirm_password.value;

        if (password.length < 8) {
            Swal.fire('Gagal', 'Password minimal harus 8 karakter.', 'error');
            return;
        }
        if (password !== confirmPassword) {
            Swal.fire('Gagal', 'Password dan konfirmasi password tidak sama.', 'error');
            return;
        }
        try {
            // --- PERBAIKAN --- Mengirim 'email' bukan 'username'
            const res = await postForm('includes/add_user.php', {
                email: f.email.value.trim(),
                nama_lengkap: f.nama_lengkap.value.trim(),
                password: password
            });
            Swal.fire('Berhasil', res.message, 'success').then(() => location.reload());
        } catch(err) {
            Swal.fire('Gagal', err.message || 'Terjadi kesalahan', 'error');
        }
    });

    const userGrid = document.getElementById('user-grid');
    userGrid.addEventListener('click', async (e) => {
        const card = e.target.closest('.user-card');
        if (!card) return;

        const userId = card.dataset.id;
        const userEmail = card.dataset.email;
        const currentRole = card.dataset.role;

        if (e.target.closest('.update-role-btn')) {
            const newRole = currentRole === 'admin' ? 'kasir' : 'admin';
            Swal.fire({
                title: 'Ubah Role?',
                html: `Anda akan mengubah role untuk <b>${userEmail}</b> dari <span class="font-semibold text-red-500">${currentRole}</span> menjadi <span class="font-semibold text-green-500">${newRole}</span>.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Ubah',
                cancelButtonText: 'Batal'
            }).then(async (result) => {
                if (!result.isConfirmed) return;
                try {
                    const res = await postForm('includes/update_user.php', { id: userId, role: newRole });
                    Swal.fire('Berhasil!', res.message, 'success').then(() => location.reload());
                } catch(err) {
                    Swal.fire('Gagal', err.message || 'Terjadi kesalahan', 'error');
                }
            });
        }

        if (e.target.closest('.delete-btn')) {
            Swal.fire({
                title: `Hapus user "${userEmail}"?`,
                text: "Tindakan ini tidak dapat diurungkan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Ya, Hapus Permanen',
                cancelButtonText: 'Batal'
            }).then(async (result) => {
                if (!result.isConfirmed) return;
                try {
                    const res = await postForm('includes/delete_user.php', { id: userId });
                    card.style.transition = 'all 0.5s ease';
                    card.style.transform = 'scale(0.8)';
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 500);
                    Swal.fire('Dihapus!', res.message, 'success');
                } catch(err) {
                    Swal.fire('Gagal', err.message || 'Terjadi kesalahan', 'error');
                }
            });
        }
    });

    const searchInput = document.getElementById('user-search');
    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        document.querySelectorAll('.user-card').forEach(card => {
            const nama = card.dataset.nama;
            const email = card.dataset.email; // --- PERBAIKAN ---
            if (nama.includes(query) || email.includes(query)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
});
</script>
