<?php
// pages/inventory.php

// Pengecekan hak akses admin sudah dilakukan di index.php
$tenant_id = $_SESSION['tenant_id'];

// --- PERUBAHAN --- Query produk disederhanakan, tidak perlu JOIN
$stmt = $db->prepare("SELECT * FROM produk WHERE tenant_id = ? ORDER BY created_at DESC");
$stmt->execute([$tenant_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- PERUBAHAN --- Daftar kategori sekarang ditetapkan secara statis (hardcoded)
$kategori_list = ['Makanan', 'Minuman', 'Snack'];

// Kalkulasi statistik (logika tidak berubah)
$total_produk = count($products);
$produk_tersedia = 0;
$produk_stok_habis_data = [];

foreach ($products as $p) {
    if ($p['stok'] > 0) {
        $produk_tersedia++;
    }
    if ($p['stok'] <= 0) {
        $produk_stok_habis_data[] = $p;
    }
}
$stok_kosong = count($produk_stok_habis_data);

?>

<link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* UI Styles (Tidak diubah) */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes modal-pop { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    .animate-fadeIn { animation: fadeIn 0.3s ease-out forwards; }
    .animate-modal-pop { animation: modal-pop 0.3s cubic-bezier(0.165, 0.84, 0.44, 1) forwards; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); }
    .stat-card.clickable { cursor: pointer; }
    .gradient-blue { background: linear-gradient(135deg, #60a5fa, #3b82f6); }
    .gradient-green { background: linear-gradient(135deg, #4ade80, #16a34a); }
    .gradient-red { background: linear-gradient(135deg, #f87171, #ef4444); }
    .filter-btn.active { background-color: #2563eb; color: white; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); }
</style>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- --- REVISI RESPONSIVE: Header --- -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div><h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight">Manajemen Inventaris</h1></div>
        <button id="add-btn" class="w-full md:w-auto inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-1 focus:outline-none focus:ring-4 focus:ring-blue-300">
            <i class="fas fa-plus-circle text-xl"></i><span>Tambah Produk Baru</span>
        </button>
    </div>

    <!-- --- REVISI RESPONSIVE: Stat Cards --- -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="stat-card gradient-blue text-white rounded-2xl shadow-lg p-6 flex items-center gap-6 transition-all duration-300"><i class="fas fa-boxes-stacked fa-3x opacity-70"></i><div><p class="text-lg font-medium">Total Jenis Produk</p><h4 class="text-4xl font-bold"><?= $total_produk ?></h4></div></div>
        <div class="stat-card gradient-green text-white rounded-2xl shadow-lg p-6 flex items-center gap-6 transition-all duration-300"><i class="fas fa-check-circle fa-3x opacity-70"></i><div><p class="text-lg font-medium">Produk Tersedia</p><h4 class="text-4xl font-bold"><?= $produk_tersedia ?></h4></div></div>
        <div id="stok-habis-card" class="stat-card gradient-red text-white rounded-2xl shadow-lg p-6 flex items-center gap-6 transition-all duration-300 sm:col-span-2 lg:col-span-1 <?= $stok_kosong > 0 ? 'clickable' : '' ?>"><i class="fas fa-exclamation-triangle fa-3x opacity-70"></i><div><p class="text-lg font-medium">Stok Habis</p><h4 class="text-4xl font-bold"><?= $stok_kosong ?></h4></div></div>
    </div>

    <!-- --- REVISI RESPONSIVE: Filter Bar --- -->
    <div class="bg-white p-4 rounded-xl shadow-md mb-6 sticky top-4 z-20 space-y-4">
        <div class="relative">
             <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400"></i></div>
            <input type="text" id="search-bar" placeholder="Cari produk berdasarkan nama atau kode..." class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-shadow" />
        </div>
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div id="category-filters" class="flex items-center gap-2 bg-gray-100 p-1 rounded-full flex-wrap">
                <button class="filter-btn px-4 py-1.5 text-sm font-semibold text-gray-600 rounded-full transition-all active" data-kategori="semua">Semua</button>
                <?php foreach ($kategori_list as $kat): ?>
                <button class="filter-btn px-4 py-1.5 text-sm font-semibold text-gray-600 rounded-full transition-all" data-kategori="<?= htmlspecialchars($kat) ?>"><?= htmlspecialchars($kat) ?></button>
                <?php endforeach; ?>
            </div>
            <div class="relative w-full sm:w-auto">
                <select id="sort-by" class="w-full appearance-none bg-white border border-gray-300 rounded-lg py-2 pl-4 pr-10 text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="terbaru">Urutkan: Terbaru</option><option value="terlama">Urutkan: Terlama</option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"><i class="fas fa-chevron-down text-gray-400 text-xs"></i></div>
            </div>
        </div>
    </div>

    <!-- --- REVISI RESPONSIVE: Product Grid --- -->
    <div id="product-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php if (empty($products)): ?>
            <div class="col-span-full text-center py-16 bg-white rounded-lg shadow-md"><i class="fas fa-box-open fa-4x text-gray-300 mb-4"></i><h3 class="text-2xl font-semibold text-gray-600">Belum ada produk</h3><p class="text-gray-400 mt-2">Silakan tambahkan produk baru untuk memulai.</p></div>
        <?php else: ?>
            <?php foreach ($products as $p): ?>
            <div class="product-card bg-white rounded-2xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 animate-fadeIn flex flex-col"
                 data-nama="<?= htmlspecialchars(strtolower($p['nama_produk'])) ?>"
                 data-kode="<?= htmlspecialchars(strtolower($p['kode_produk'])) ?>"
                 data-kategori="<?= htmlspecialchars($p['kategori']) ?>"
                 data-created-at="<?= htmlspecialchars($p['created_at']) ?>">
                <div class="p-5 flex-grow flex flex-col">
                    <p class="text-sm font-semibold text-blue-600"><?= htmlspecialchars($p['kategori']) ?></p>
                    <h3 class="text-xl font-bold text-gray-800 mt-1 truncate" title="<?= htmlspecialchars($p['nama_produk']) ?>"><?= htmlspecialchars($p['nama_produk']) ?></h3>
                    <p class="text-sm text-gray-400 mt-1">Kode: <?= htmlspecialchars($p['kode_produk']) ?></p>
                    <div class="mt-4 flex-grow"></div>
                    <div class="flex justify-between items-center mt-4">
                        <p class="text-2xl font-extrabold text-gray-900">Rp<?= number_format($p['harga'],0,',','.') ?></p>
                        <?php
                            $stok_bg = 'bg-green-100 text-green-800';
                            if ($p['stok'] <= 0) { $stok_bg = 'bg-red-100 text-red-800'; }
                            elseif ($p['stok'] <= 10) { $stok_bg = 'bg-yellow-100 text-yellow-800'; }
                        ?>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full <?= $stok_bg ?>">Stok: <?= $p['stok'] ?></span>
                    </div>
                </div>
                <div class="p-4 bg-gray-50 grid grid-cols-2 gap-3 border-t">
                    <button class="edit-btn inline-flex items-center justify-center gap-2 bg-yellow-400 hover:bg-yellow-500 text-yellow-900 px-4 py-2 rounded-lg font-semibold transition-all text-sm" data-id="<?= $p['id'] ?>" data-kode="<?= htmlspecialchars($p['kode_produk']) ?>" data-nama="<?= htmlspecialchars($p['nama_produk']) ?>" data-kategori="<?= htmlspecialchars($p['kategori']) ?>" data-harga="<?= $p['harga'] ?>" data-stok="<?= $p['stok'] ?>"><i class="fas fa-pencil-alt"></i> Edit</button>
                    <button class="delete-btn inline-flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-semibold transition-all text-sm" data-id="<?= $p['id'] ?>"><i class="fas fa-trash-alt"></i> Hapus</button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div id="product-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-2xl w-full max-w-lg animate-modal-pop relative">
        <button class="modal-close-btn absolute top-5 right-5 text-gray-400 hover:text-gray-800 transition"><i class="fas fa-times fa-2x"></i></button>
        <div class="flex items-center gap-4 mb-8">
            <div id="modal-icon-container" class="w-12 h-12 rounded-full flex items-center justify-center"><i id="modal-icon" class="fa-lg"></i></div>
            <h3 id="modal-title" class="text-2xl sm:text-3xl font-bold text-gray-800"></h3>
        </div>
        <form id="product-form" class="space-y-5">
            <input type="hidden" name="id" id="product-id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="relative"><div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fas fa-barcode text-gray-400"></i></div><input type="text" id="kode_produk" name="kode_produk" placeholder="Kode Produk" required class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm transition" /></div>
                <div class="relative"><div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fas fa-tag text-gray-400"></i></div><input type="text" id="nama_produk" name="nama_produk" placeholder="Nama Produk" required class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm transition" /></div>
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fas fa-sitemap text-gray-400"></i></div>
                <select id="kategori" name="kategori" required class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm transition appearance-none bg-white">
                    <option value="" disabled selected>Pilih Kategori...</option>
                     <?php foreach ($kategori_list as $kat): ?>
                    <option value="<?= htmlspecialchars($kat) ?>"><?= htmlspecialchars($kat) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none"><i class="fas fa-chevron-down text-gray-400"></i></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="relative"><div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="text-gray-500 font-semibold">Rp</span></div><input type="number" id="harga" name="harga" min="1" placeholder="Harga" required class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm transition" /></div>
                <div class="relative"><div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fas fa-boxes-stacked text-gray-400"></i></div><input type="number" id="stok" name="stok" min="0" placeholder="Jumlah Stok" required class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm transition" /></div>
            </div>
            <div class="flex justify-end items-center space-x-4 pt-6">
                <button type="button" class="modal-cancel-btn px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 font-semibold transition">Batal</button>
                <button type="submit" id="modal-save" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold shadow-md hover:shadow-lg transition"></button>
            </div>
        </form>
    </div>
</div>

<div id="stok-habis-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4"><div class="bg-white p-6 sm:p-8 rounded-2xl shadow-2xl w-full max-w-lg animate-modal-pop relative"><button class="modal-close-btn absolute top-4 right-4 text-gray-400 hover:text-gray-800 transition"><i class="fas fa-times fa-2x"></i></button><div class="flex items-center gap-4 mb-6"><i class="fas fa-exclamation-triangle text-3xl text-red-500"></i><h3 class="text-2xl sm:text-3xl font-bold text-gray-800">Daftar Produk Stok Habis</h3></div><div id="stok-habis-list-container" class="max-h-[60vh] overflow-y-auto pr-2"></div></div></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const productModal = document.getElementById('product-modal');
    const stokHabisModal = document.getElementById('stok-habis-modal');
    const form = document.getElementById('product-form');
    const productGrid = document.getElementById('product-grid');
    const modalTitle = document.getElementById('modal-title');
    const modalIcon = document.getElementById('modal-icon');
    const modalIconContainer = document.getElementById('modal-icon-container');
    const saveButton = document.getElementById('modal-save');
    const openModal = (modal) => modal.classList.remove('hidden');
    const closeModal = (modal) => modal.classList.add('hidden');

    const outOfStockProducts = <?= json_encode($produk_stok_habis_data) ?>;
    async function postForm(url, fd) { const res = await fetch(url, { method:'POST', body:fd }); const data = await res.json(); if (!res.ok) throw data; return data; }

    document.getElementById('add-btn').addEventListener('click', () => {
        form.reset();
        document.getElementById('product-id').value = '';
        modalTitle.textContent = 'Tambah Produk Baru';
        saveButton.textContent = 'Tambah Produk';
        modalIcon.className = 'fas fa-plus fa-lg';
        modalIconContainer.className = 'w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center';
        openModal(productModal);
    });

    document.querySelectorAll('.modal-close-btn, .modal-cancel-btn').forEach(btn => {
        btn.addEventListener('click', () => { closeModal(productModal); closeModal(stokHabisModal); });
    });

    const openEditForm = (dataset) => {
        const { id, kode, nama, kategori, harga, stok } = dataset;
        document.getElementById('product-id').value = id;
        document.getElementById('kode_produk').value = kode;
        document.getElementById('nama_produk').value = nama;
        document.getElementById('kategori').value = kategori;
        document.getElementById('harga').value = harga;
        document.getElementById('stok').value = stok;
        modalTitle.textContent = 'Edit Produk';
        saveButton.textContent = 'Simpan Perubahan';
        modalIcon.className = 'fas fa-pencil-alt fa-lg';
        modalIconContainer.className = 'w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center';
        openModal(productModal);
    };

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const id = document.getElementById('product-id').value;
        const url = id ? 'includes/update_product.php' : 'includes/add_product.php';
        const fd = new FormData(form);
        const originalButtonText = saveButton.textContent;
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
        try {
            const res = await postForm(url, fd);
            closeModal(productModal);
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 1500, showConfirmButton: false }).then(() => location.reload());
        } catch (err) {
            Swal.fire('Gagal', err.message || 'Terjadi kesalahan', 'error');
        } finally {
            saveButton.disabled = false;
            saveButton.textContent = originalButtonText;
        }
    });
    
    productGrid.addEventListener('click', e => {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            openEditForm(editBtn.dataset);
        }
        
        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            Swal.fire({
                title: 'Anda yakin?', text: "Produk ini akan dihapus permanen!", icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                cancelButtonText: 'Batal', confirmButtonText: 'Ya, hapus!'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const fd = new FormData();
                        fd.append('id', deleteBtn.dataset.id);
                        // Menggunakan file terpusat untuk aksi hapus (best practice)
                        fd.append('action', 'delete_product');
                        const res = await postForm('includes/api.php', fd);
                        Swal.fire('Terhapus!', 'Produk berhasil dihapus.', 'success').then(() => location.reload());
                    } catch (err) {
                        Swal.fire('Gagal', err.message || 'Gagal menghapus produk.', 'error');
                    }
                }
            });
        }
    });

    const searchInput = document.getElementById('search-bar');
    const categoryFilters = document.getElementById('category-filters');
    const sortSelect = document.getElementById('sort-by');
    let currentCategory = 'semua';
    let currentSort = 'terbaru';
    let currentKeyword = '';

    function filterAndSortProducts() {
        const cards = Array.from(productGrid.querySelectorAll('.product-card'));
        cards.sort((a, b) => {
            const dateA = new Date(a.dataset.createdAt);
            const dateB = new Date(b.dataset.createdAt);
            return currentSort === 'terbaru' ? dateB - dateA : dateA - dateB;
        });
        cards.forEach(card => {
            const matchesCategory = currentCategory === 'semua' || card.dataset.kategori === currentCategory;
            const matchesKeyword = currentKeyword === '' || card.dataset.nama.includes(currentKeyword) || card.dataset.kode.includes(currentKeyword);
            card.style.display = (matchesCategory && matchesKeyword) ? 'flex' : 'none';
        });
        cards.forEach(card => productGrid.appendChild(card));
    }

    searchInput.addEventListener('input', () => { currentKeyword = searchInput.value.toLowerCase().trim(); filterAndSortProducts(); });
    categoryFilters.addEventListener('click', (e) => {
        if (e.target.classList.contains('filter-btn')) {
            categoryFilters.querySelector('.active').classList.remove('active');
            e.target.classList.add('active');
            currentCategory = e.target.dataset.kategori;
            filterAndSortProducts();
        }
    });
    sortSelect.addEventListener('change', () => { currentSort = sortSelect.value; filterAndSortProducts(); });
    
    document.getElementById('stok-habis-card').addEventListener('click', () => {
        if (outOfStockProducts.length === 0) return;
        const container = document.getElementById('stok-habis-list-container');
        container.innerHTML = '';
        const list = document.createElement('ul');
        list.className = 'space-y-3';
        outOfStockProducts.forEach(p => {
            const listItem = document.createElement('li');
            listItem.className = 'flex items-center justify-between bg-gray-50 p-4 rounded-lg';
            listItem.innerHTML = `<div><p class="font-bold text-gray-800">${p.nama_produk}</p><p class="text-sm text-gray-500">Kode: ${p.kode_produk}</p></div><button class="edit-stok-btn inline-flex items-center gap-2 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold transition text-sm" data-id="${p.id}" data-kode="${p.kode_produk}" data-nama="${p.nama_produk}" data-kategori="${p.kategori}" data-harga="${p.harga}" data-stok="${p.stok}"><i class="fas fa-pencil-alt"></i> Edit Stok</button>`;
            list.appendChild(listItem);
        });
        container.appendChild(list);
        openModal(stokHabisModal);
    });
    stokHabisModal.addEventListener('click', e => {
        const editBtn = e.target.closest('.edit-stok-btn');
        if (editBtn) { closeModal(stokHabisModal); openEditForm(editBtn.dataset); }
    });
});
</script>