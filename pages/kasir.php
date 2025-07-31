<?php
// pages/kasir.php
// File config dan auth sudah dimuat oleh index.php
if (!is_logged_in()) {
    redirect('login.php');
}

$tenant_id = $_SESSION['tenant_id'];

// Daftar kategori sekarang statis karena menggunakan ENUM di database.
$kategori_list = ['Makanan', 'Minuman', 'Snack'];

// Ambil semua produk yang stoknya > 0 HANYA milik tenant ini.
$stmt_produk = $db->prepare("
    SELECT id, kode_produk, nama_produk, harga, stok, kategori
    FROM produk
    WHERE stok > 0 AND tenant_id = ?
    ORDER BY nama_produk
");
$stmt_produk->execute([$tenant_id]);
$produk_list = $stmt_produk->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kasir - Stockify</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn .5s ease-out forwards; }
        @keyframes scaleUp { from { transform: scale(.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .scale-up { animation: scaleUp .3s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards; }
        .glass-card { background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.2); }
        .swal2-popup.custom-qris { background: transparent; padding: 0; border: none; }
        .category-tab.active { background-color: #4f46e5; color: white; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); }
        .custom-scrollbar::-webkit-scrollbar { width: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body>
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 fade-in">
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
        <!-- Daftar Produk -->
        <div class="lg:col-span-3 bg-white p-6 rounded-2xl shadow-lg">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-2xl font-bold text-gray-800">Daftar Menu</h3>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3"><i class="fas fa-search text-gray-400"></i></span>
                    <input type="text" id="product-search" placeholder="Cari produk..." class="pl-10 pr-4 py-2 border rounded-full w-full sm:w-64 focus:ring-2 focus:ring-indigo-400 transition-all">
                </div>
            </div>
            <div class="border-b border-gray-200 mb-4">
                <nav id="category-nav" class="-mb-px flex space-x-4 overflow-x-auto pb-2" aria-label="Tabs">
                    <button class="category-tab whitespace-nowrap py-2 px-4 border border-transparent rounded-full text-sm font-medium transition-all duration-300 active" data-kategori="semua">
                        Semua
                    </button>
                    <?php foreach ($kategori_list as $kategori): ?>
                    <button class="category-tab whitespace-nowrap py-2 px-4 border border-transparent rounded-full text-sm font-medium transition-all duration-300 text-gray-500 hover:text-gray-700 hover:bg-gray-100" data-kategori="<?= htmlspecialchars($kategori) ?>">
                        <?= htmlspecialchars($kategori) ?>
                    </button>
                    <?php endforeach; ?>
                </nav>
            </div>
            <div id="product-list-container" class="custom-scrollbar max-h-[28rem] overflow-y-auto pr-2 custom-scrollbar max-h-96 overflow-y-auto pr-2">
                <?php if (empty($produk_list)): ?>
                    <p class="text-center text-gray-500 italic mt-10">Belum ada produk yang tersedia di toko Anda.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <?php foreach ($produk_list as $p): ?>
                        <div class="product-card border rounded-lg p-4 flex flex-col transition-all hover:shadow-md hover:border-indigo-300" data-name="<?= strtolower(htmlspecialchars($p['nama_produk'])) ?>" data-kategori="<?= htmlspecialchars($p['kategori']) ?>">
                            <div class="flex-1">
                                <p class="font-bold text-gray-800"><?= htmlspecialchars($p['nama_produk']) ?></p>
                                <p class="text-sm text-green-600 font-semibold">Rp <?= number_format($p['harga'], 0, ',', '.') ?></p>
                            </div>
                            <div class="flex justify-between items-center mt-3">
                                <p class="text-xs text-gray-500">Stok: <span class="font-medium"><?= $p['stok'] ?></span></p>
                                <button class="add-to-cart bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold transition-all transform hover:scale-105 flex items-center gap-1.5" data-id="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['nama_produk']) ?>" data-price="<?= $p['harga'] ?>" data-stock="<?= $p['stok'] ?>">
                                    <i class="fas fa-plus"></i>
                                    <span>Tambah</span>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Keranjang Belanja -->
        <div class="lg:col-span-2">
            <div class="sticky top-8">
                <div class="glass-card p-6 rounded-2xl shadow-xl flex flex-col space-y-5 h-auto">
                    <h3 class="text-2xl font-bold text-gray-900 border-b pb-4">Keranjang Belanja</h3>
                    <div id="cart-container" class="custom-scrollbar flex-1 overflow-y-auto max-h-60 space-y-3 p-1">
                        <table class="w-full text-sm text-gray-800"><tbody id="cart-body" class="divide-y divide-gray-200"></tbody></table>
                        <div id="empty-cart-message" class="text-center py-10 text-gray-500"><i class="fas fa-shopping-cart fa-2x mb-2"></i><p>Keranjang masih kosong</p></div>
                    </div>
                    <div class="border-t pt-4 space-y-4">
                        <div class="flex justify-between items-center font-bold text-lg"><span class="text-gray-700">Total</span><span id="cart-total" class="text-green-700">Rp 0</span></div>
                        <div>
                            <p class="mb-2 font-semibold text-gray-800">Metode Pembayaran</p>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="payment-option cursor-pointer border rounded-lg p-3 text-center transition-all duration-200 active border-indigo-600 ring-2 ring-indigo-300 bg-indigo-50"><input type="radio" name="payment-method" value="cash" class="hidden" checked><i class="fas fa-money-bill-wave text-2xl text-green-600 mb-1"></i><div class="font-semibold text-sm text-gray-800">Cash</div></label>
                                <label class="payment-option cursor-pointer border rounded-lg p-3 text-center transition-all duration-200 border-gray-300 hover:border-indigo-600 hover:bg-indigo-50"><input type="radio" name="payment-method" value="qris" class="hidden"><i class="fas fa-qrcode text-2xl text-blue-600 mb-1"></i><div class="font-semibold text-sm text-gray-800">QRIS</div></label>
                            </div>
                        </div>
                        <div id="cash-fields">
                            <label class="block mb-1 font-semibold text-gray-700 text-sm">Uang Dibayar</label>
                            <div class="relative"><span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-500">Rp</span><input type="text" id="paid-amount-display" class="w-full border rounded-md pl-9 pr-3 py-2 focus:ring-2 focus:ring-indigo-500 text-sm"><input type="hidden" id="paid-amount" value="0"></div>
                            <p class="mt-2 text-gray-600 text-sm flex justify-between"><span>Kembalian:</span><strong id="change" class="text-green-700 text-base">Rp 0</strong></p>
                        </div>
                        <div id="qris-fields" class="hidden text-center text-sm p-3 bg-blue-50 rounded-lg border border-blue-200 text-blue-700">QR Code akan muncul setelah menekan <strong>Proses Transaksi</strong>.</div>
                    </div>
                    <button id="process-btn" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white py-3 rounded-lg font-bold text-base disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5" disabled><i class="fas fa-check-circle mr-2"></i> Proses Transaksi</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    let cart = [];
    const formatRupiah = n => 'Rp ' + (n ? n.toLocaleString('id-ID') : '0');
    const parseRupiah = s => parseInt(s.replace(/[^0-9]/g, '')) || 0;

    function renderCart() {
        const tbody = document.getElementById('cart-body');
        const emptyMsg = document.getElementById('empty-cart-message');
        tbody.innerHTML = '';
        let total = 0;
        if (cart.length === 0) {
            emptyMsg.style.display = 'block';
        } else {
            emptyMsg.style.display = 'none';
            cart.forEach((item, idx) => {
                const sub = item.qty * item.price;
                total += sub;
                const tr = document.createElement('tr');
                tr.className = 'fade-in';
                tr.innerHTML = `
                    <td class="py-3 px-2">
                        <p class="font-semibold text-gray-800">${item.name}</p>
                        <p class="text-xs text-gray-500">${formatRupiah(item.price)}</p>
                    </td>
                    <td class="py-3 px-2 text-center align-middle">
                        <input type="number" min="1" max="${item.stock}" value="${item.qty}" data-idx="${idx}" class="qty-input w-14 border rounded text-center py-1">
                    </td>
                    <td class="py-3 px-2 text-right font-medium text-gray-900 align-middle">${formatRupiah(sub)}</td>
                    <td class="py-3 px-2 text-center align-middle">
                        <button class="remove-item text-red-500 hover:text-red-700 transition-colors" data-idx="${idx}"><i class="fas fa-trash-alt"></i></button>
                    </td>`;
                tbody.appendChild(tr);
            });
        }
        document.getElementById('cart-total').textContent = formatRupiah(total);
        updateChange();
        toggleProcessBtn();
    }

    function addToCart(btn) {
        const id = +btn.dataset.id, name = btn.dataset.name, price = +btn.dataset.price, stock = +btn.dataset.stock;
        const found = cart.find(it => it.id === id);
        if (found) {
            if (found.qty < stock) found.qty++;
            else Swal.fire({ icon: 'warning', title: 'Stok Habis', text: 'Jumlah melebihi stok tersedia.' });
        } else {
            cart.push({ id, name, price, stock, qty: 1 });
        }
        renderCart();
    }

    function updateQty(input) {
        const idx = +input.dataset.idx;
        let value = +input.value;
        const item = cart[idx];
        if (!value || value < 1) { value = 1; input.value = 1; }
        if (value > item.stock) {
            Swal.fire({ icon: 'warning', title: 'Stok Habis', text: 'Jumlah melebihi stok tersedia.' });
            value = item.stock; input.value = item.stock;
        }
        item.qty = value;
        renderCart();
    }

    function removeItem(btn) {
        cart.splice(+btn.dataset.idx, 1);
        renderCart();
    }

    function togglePayment(method) {
        document.querySelectorAll('.payment-option').forEach(lbl => {
            lbl.classList.remove('active', 'border-indigo-600', 'ring-2', 'ring-indigo-300', 'bg-indigo-50');
            lbl.classList.add('border-gray-300', 'hover:border-indigo-600', 'hover:bg-indigo-50');
        });
        const activeLbl = document.querySelector(`input[value="${method}"]`).parentElement;
        activeLbl.classList.add('active', 'border-indigo-600', 'ring-2', 'ring-indigo-300', 'bg-indigo-50');
        activeLbl.classList.remove('border-gray-300');
        document.getElementById('cash-fields').classList.toggle('hidden', method !== 'cash');
        document.getElementById('qris-fields').classList.toggle('hidden', method !== 'qris');
        updateChange();
        toggleProcessBtn();
    }

    function updateChange() {
        const total = parseRupiah(document.getElementById('cart-total').textContent);
        const method = document.querySelector('input[name="payment-method"]:checked').value;
        if (method === 'cash') {
            const paid = +document.getElementById('paid-amount').value || 0;
            document.getElementById('change').textContent = paid - total >= 0 ? formatRupiah(paid - total) : 'Rp 0';
        } else {
            document.getElementById('change').textContent = 'Rp 0';
        }
    }

    function toggleProcessBtn() {
        const btn = document.getElementById('process-btn'), total = parseRupiah(document.getElementById('cart-total').textContent);
        if (cart.length === 0) { btn.disabled = true; return; }
        const method = document.querySelector('input[name="payment-method"]:checked').value;
        if (method === 'cash') {
            const paid = +document.getElementById('paid-amount').value || 0;
            btn.disabled = paid < total;
        } else {
            btn.disabled = false;
        }
    }

    async function processTransaction(method) {
        const fd = new FormData();
        fd.append('metode_pembayaran', method);
        if (method === 'cash') fd.append('uang_dibayar', document.getElementById('paid-amount').value || 0);
        cart.forEach((it, i) => {
            fd.append(`items[${i}][produk_id]`, it.id);
            fd.append(`items[${i}][qty]`, it.qty);
            fd.append(`items[${i}][harga_satuan]`, it.price);
        });
        Swal.fire({ title: 'Memproses Transaksi...', text: 'Mohon tunggu sebentar.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const response = await fetch('includes/process_transaction.php', { method: 'POST', body: fd });
            const data = await response.json();
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Sukses!', text: 'Transaksi berhasil diproses.', timer: 2000, timerProgressBar: true }).then(() => location.reload());
            } else {
                Swal.fire('Gagal', data.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Terjadi kesalahan saat menghubungi server.', 'error');
        }
    }

    // Event Listeners
    document.getElementById('product-list-container').addEventListener('click', e => {
        if (e.target.closest('.add-to-cart')) addToCart(e.target.closest('.add-to-cart'));
    });
    document.getElementById('cart-body').addEventListener('input', e => { if (e.target.classList.contains('qty-input')) updateQty(e.target); });
    document.getElementById('cart-body').addEventListener('click', e => { if (e.target.closest('.remove-item')) removeItem(e.target.closest('.remove-item')); });
    document.querySelectorAll('.payment-option').forEach(lbl => lbl.addEventListener('click', () => togglePayment(lbl.querySelector('input').value)));
    const paidDisplay = document.getElementById('paid-amount-display');
    paidDisplay.addEventListener('input', (e) => {
        let value = parseRupiah(e.target.value);
        document.getElementById('paid-amount').value = value;
        e.target.value = value > 0 ? value.toLocaleString('id-ID') : '';
        updateChange();
        toggleProcessBtn();
    });
    document.getElementById('process-btn').addEventListener('click', () => {
        const method = document.querySelector('input[name="payment-method"]:checked').value;
        const total = parseRupiah(document.getElementById('cart-total').textContent);
        if (method === 'qris') {
            Swal.fire({
                width: 440, background: 'transparent', showConfirmButton: false, showCloseButton: true,
                customClass: { popup: 'custom-qris' },
                html: `<div class="bg-white rounded-2xl shadow-2xl w-[420px] max-w-full mx-auto scale-up"><div class="h-2.5 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-t-2xl"></div><div class="p-8"><h2 class="text-center text-2xl font-bold text-gray-800 mb-4">Pembayaran QRIS</h2><div class="flex justify-center mb-5"><img src="assets/img/qr_partamoon.jpg" alt="QR Code" class="w-56 h-56 rounded-lg border object-contain shadow-inner ring-4 ring-gray-100"></div><p class="text-sm text-gray-500 text-center">Scan menggunakan aplikasi e-wallet Anda</p><div class="mt-6 bg-gray-50 p-4 rounded-lg"><p class="text-gray-700 text-center">Total Bayar</p><p class="text-center text-4xl font-extrabold text-green-600 tracking-wider">${formatRupiah(total)}</p></div><button id="qris-done" class="w-full mt-6 bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-all text-white py-3 rounded-lg font-semibold"><i class="fas fa-check"></i> Selesai Bayar</button></div></div>`,
                didOpen: () => {
                    Swal.getHtmlContainer()?.querySelector('#qris-done')?.addEventListener('click', () => {
                        Swal.close();
                        processTransaction(method);
                    });
                }
            });
        } else {
            processTransaction(method);
        }
    });

    // --- PERBAIKAN --- Filter Logic
    const searchInput = document.getElementById('product-search');
    const categoryNav = document.getElementById('category-nav');
    const productCards = document.querySelectorAll('.product-card');

    function filterProducts() {
        const query = searchInput.value.toLowerCase();
        const activeTab = categoryNav.querySelector('.active');
        const activeCategory = activeTab ? activeTab.dataset.kategori : 'semua';

        productCards.forEach(card => {
            const nameMatch = card.dataset.name.includes(query);
            const categoryMatch = activeCategory === 'semua' || card.dataset.kategori === activeCategory;
            
            if (nameMatch && categoryMatch) {
                card.style.display = 'flex'; // Use flex to match the grid layout
            } else {
                card.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterProducts);

    categoryNav.addEventListener('click', (e) => {
        const clickedTab = e.target.closest('.category-tab');
        if (clickedTab) {
            const currentActive = categoryNav.querySelector('.active');
            if (currentActive) {
                currentActive.classList.remove('active', 'bg-indigo-500', 'text-white');
                currentActive.classList.add('text-gray-500', 'hover:text-gray-700', 'hover:bg-gray-100');
            }
            
            clickedTab.classList.add('active', 'bg-indigo-500', 'text-white');
            clickedTab.classList.remove('text-gray-500', 'hover:text-gray-700', 'hover:bg-gray-100');
            
            filterProducts();
        }
    });

    // Initial Render
    renderCart();
});
</script>
</body>
</html>
