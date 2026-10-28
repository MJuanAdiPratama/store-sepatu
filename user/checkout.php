<?php
/**
 * Checkout Page - QxuanStore
 * Modern Shoe E-Commerce (User Area)
 */

$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';
require_once $rootPath . '/config/midtrans.php';

require_user();

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    redirect(base_url('products.php'));
}

$pageTitle = 'Checkout Pesanan Sepatu';
require_once $rootPath . '/includes/header.php';

// Fetch current user data from database to get registered address
$userId = $_SESSION['user_id'];
$currentUser = db_fetch_one("SELECT * FROM tb_user WHERE user_id = ?", [$userId]);

$subtotalSepatu = 0;
foreach ($cart as $item) {
    $subtotalSepatu += ($item['harga'] * $item['qty']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_penerima = trim($_POST['nama_penerima'] ?? '');
    $no_hp         = trim($_POST['no_hp'] ?? '');
    $alamat        = trim($_POST['alamat'] ?? '');
    $kurir         = trim($_POST['kurir'] ?? 'J&T Express');
    $catatan       = trim($_POST['catatan'] ?? '');

    if (empty($nama_penerima) || empty($no_hp) || empty($alamat)) {
        $error = 'Nama penerima, nomor WhatsApp, dan alamat pengiriman wajib diisi lengkap!';
    } else {
        // Calculate total with discount if pickup
        $diskonOngkir = ($kurir === 'Ambil di Toko Fisik') ? 15000 : 0;
        $totalBayar = max(0, $subtotalSepatu - $diskonOngkir);

        // Generate Unique Invoice Number
        $invoiceNo = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        // Auto-generate tracking number
        $noResi = generate_no_resi($kurir);

        // Insert into tb_order with is_read = 0 (Unread notification for admin)
        $sqlOrder = "
            INSERT INTO tb_order (invoice_no, user_id, nama_penerima, no_hp, alamat_pengiriman, catatan, total_bayar, status_order, kurir, no_resi, is_read) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Menunggu Pembayaran', ?, ?, 0)
        ";
        $orderInserted = db_execute($sqlOrder, [$invoiceNo, $userId, $nama_penerima, $no_hp, $alamat, $catatan, $totalBayar, $kurir, $noResi]);

        if ($orderInserted) {
            $orderId = db_insert_id();

            // Insert each cart item into tb_order_detail
            foreach ($cart as $item) {
                $subtotal = $item['harga'] * $item['qty'];
                db_execute("
                    INSERT INTO tb_order_detail (id_order, id_produk, nama_produk, ukuran, harga, qty, subtotal) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ", [$orderId, $item['id_produk'], $item['nama'], $item['ukuran'], $item['harga'], $item['qty'], $subtotal]);

                // Reduce stock in tb_produk_size if available
                db_execute("UPDATE tb_produk_size SET stok = GREATEST(0, stok - ?) WHERE id_produk = ? AND ukuran = ?", [$item['qty'], $item['id_produk'], $item['ukuran']]);
            }

            // Generate Midtrans Snap Token for this order
            $newOrder = db_fetch_one("SELECT * FROM tb_order WHERE id_order = ?", [$orderId]);
            if ($newOrder) {
                midtrans_get_snap_token($newOrder, $cart);
            }

            // Clear Cart Session
            unset($_SESSION['cart']);

            // Redirect to Order Success Page
            redirect(user_url('order-success.php?invoice=' . urlencode($invoiceNo)));
        } else {
            $error = 'Terjadi kesalahan sistem saat memproses pesanan. Silakan coba kembali.';
        }
    }
}
?>

<style>
/* Expedition Card Selection & Hover */
.expedition-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}
.expedition-card {
    border: 1.5px solid #cbd5e1;
    background: #ffffff;
    padding: 16px;
    border-radius: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    user-select: none;
}
.expedition-card:hover {
    border-color: #93c5fd;
    background: #f8fafc;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
}
.expedition-card.selected {
    border: 2px solid #2563eb !important;
    background: #eff6ff !important;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.15) !important;
}
.expedition-card input[type="radio"] {
    accent-color: #2563eb;
    width: 18px;
    height: 18px;
    cursor: pointer;
}
@media (max-width: 640px) {
    .expedition-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container" style="padding-top: 40px; padding-bottom: 70px;">
    <!-- Breadcrumb -->
    <div style="font-size: 0.88rem; color: #64748b; margin-bottom: 24px; display: flex; gap: 8px; align-items: center;">
        <a href="<?= base_url('index.php') ?>">Beranda</a>
        <i class="fa-solid fa-chevron-right" style="font-size: 0.7rem;"></i>
        <a href="<?= user_url('cart.php') ?>">Keranjang</a>
        <i class="fa-solid fa-chevron-right" style="font-size: 0.7rem;"></i>
        <span style="color: #0f172a; font-weight: 600;">Checkout & Pengiriman</span>
    </div>

    <div style="margin-bottom: 28px;">
        <h1 style="font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;">Checkout & Informasi Pengiriman</h1>
        <p style="color: #64748b; font-size: 0.95rem;">Periksa alamat pengiriman dan pilih ekspedisi kurir untuk pesanan sepatu Anda.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-bottom: 24px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="" method="POST" id="checkoutForm">
        <div class="checkout-layout-grid">
            <!-- Shipping Form -->
            <div>
                <!-- Recipient Card -->
                <div style="background: #fff; padding: 28px; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 24px; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px;">
                        <h2 style="font-size: 1.15rem; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                            <i class="fa-solid fa-location-dot" style="color: #2563eb;"></i> Data Penerima & Alamat Pengiriman
                        </h2>
                        <span class="badge badge-info" style="font-size: 0.75rem;">
                            <i class="fa-solid fa-circle-check"></i> Dari Data Akun
                        </span>
                    </div>

                    <div class="grid-2-col">
                        <div class="form-group">
                            <label for="nama_penerima">Nama Lengkap Penerima <span style="color: #ef4444;">*</span></label>
                            <input type="text" id="nama_penerima" name="nama_penerima" class="form-control" required value="<?= htmlspecialchars($_POST['nama_penerima'] ?? ($currentUser->nama_lengkap ?? '')) ?>">
                        </div>

                        <div class="form-group">
                            <label for="no_hp">Nomor WhatsApp Aktif <span style="color: #ef4444;">*</span></label>
                            <input type="tel" id="no_hp" name="no_hp" class="form-control" required value="<?= htmlspecialchars($_POST['no_hp'] ?? ($currentUser->no_hp ?? '')) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label for="alamat" style="margin: 0;">Alamat Lengkap Pengiriman <span style="color: #ef4444;">*</span></label>
                            <small style="color: #2563eb; font-size: 0.78rem;"><i class="fa-solid fa-pen"></i> Masih dapat Anda ubah di sini</small>
                        </div>
                        <textarea id="alamat" name="alamat" class="form-control" rows="3" placeholder="Tuliskan nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan, kota, dan kode pos..." required><?= htmlspecialchars($_POST['alamat'] ?? ($currentUser->alamat ?? '')) ?></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="catatan">Catatan untuk Penjual / Kurir (Opsional)</label>
                        <input type="text" id="catatan" name="catatan" class="form-control" placeholder="Contoh: Warna cadangan, waktu kirim, atau titipkan di pos satpam" value="<?= htmlspecialchars($_POST['catatan'] ?? '') ?>">
                    </div>
                </div>

                <!-- Expedition Selection Card -->
                <div style="background: #fff; padding: 28px; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm);">
                    <h2 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-truck-fast" style="color: #2563eb;"></i> Pilihan Ekspedisi Pengiriman
                    </h2>
                    <p style="font-size: 0.88rem; color: #64748b; margin-bottom: 16px;">
                        Pilih opsi pengiriman yang Anda inginkan (pilih <strong>Ambil di Toko</strong> untuk bebas ongkir dan potongan Rp 15.000):
                    </p>

                    <div class="expedition-grid" id="expeditionGrid">
                        <!-- J&T -->
                        <label class="expedition-card selected">
                            <input type="radio" name="kurir" value="J&T Express" checked onchange="handleExpeditionChange(this)">
                            <div>
                                <strong style="display: block; font-size: 0.95rem; color: #0f172a;">J&T Express</strong>
                                <small style="color: #2563eb; font-weight: 600;">Estimasi 1-2 Hari &bull; Populer</small>
                            </div>
                        </label>

                        <!-- JNE -->
                        <label class="expedition-card">
                            <input type="radio" name="kurir" value="JNE Regular" onchange="handleExpeditionChange(this)">
                            <div>
                                <strong style="display: block; font-size: 0.95rem; color: #0f172a;">JNE Regular</strong>
                                <small style="color: #64748b;">Estimasi 2-3 Hari &bull; Terpercaya</small>
                            </div>
                        </label>

                        <!-- SiCepat -->
                        <label class="expedition-card">
                            <input type="radio" name="kurir" value="SiCepat Reguler" onchange="handleExpeditionChange(this)">
                            <div>
                                <strong style="display: block; font-size: 0.95rem; color: #0f172a;">SiCepat Reguler</strong>
                                <small style="color: #64748b;">Estimasi 1-2 Hari &bull; Cepat</small>
                            </div>
                        </label>

                        <!-- Ambil di Toko -->
                        <label class="expedition-card">
                            <input type="radio" name="kurir" value="Ambil di Toko Fisik" onchange="handleExpeditionChange(this)">
                            <div>
                                <strong style="display: block; font-size: 0.95rem; color: #0f172a;">Ambil di Toko Fisik</strong>
                                <small style="color: #16a34a; font-weight: 700;">Gratis Ongkir &bull; Hemat Rp 15.000</small>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Order Summary Card -->
            <div class="customer-sidebar-card" style="padding: 26px;">
                <h3 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
                    Rincian Pesanan Sepatu
                </h3>

                <div style="max-height: 280px; overflow-y: auto; margin-bottom: 20px;">
                    <?php foreach ($cart as $it): ?>
                        <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9;">
                            <img src="<?= product_image_url($it['gambar']) ?>" alt="<?= htmlspecialchars($it['nama']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <div style="flex-grow: 1;">
                                <div style="font-size: 0.9rem; font-weight: 700; color: #0f172a;"><?= htmlspecialchars($it['nama']) ?></div>
                                <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
                                    Size <?= htmlspecialchars($it['ukuran']) ?> &bull; <?= $it['qty'] ?> pcs
                                </div>
                            </div>
                            <div style="font-size: 0.95rem; font-weight: 800; color: #2563eb;">
                                <?= format_rupiah($it['harga'] * $it['qty']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.92rem;">
                    <span style="color: #64748b;">Subtotal Sepatu:</span>
                    <strong id="displaySubtotal"><?= format_rupiah($subtotalSepatu) ?></strong>
                </div>

                <!-- Row Potongan Ambil di Toko (Muncul jika pilih Ambil di Toko) -->
                <div id="rowPickupDiscount" style="display: none; justify-content: space-between; margin-bottom: 10px; font-size: 0.92rem; color: #16a34a; background: #f0fdf4; padding: 6px 10px; border-radius: 6px; border: 1px dashed #86efac;">
                    <span><i class="fa-solid fa-tag"></i> Potongan Bebas Ongkir:</span>
                    <strong>-Rp 15.000</strong>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 1.1rem; border-top: 1px dashed #e2e8f0; padding-top: 14px;">
                    <span style="font-weight: 800;">Total Pembayaran:</span>
                    <strong id="displayTotal" style="color: #2563eb; font-size: 1.35rem;"><?= format_rupiah($subtotalSepatu) ?></strong>
                </div>

                <button type="button" onclick="confirmSubmitOrder()" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px; font-size: 1.05rem; border-radius: 10px;">
                    <i class="fa-solid fa-check"></i> Konfirmasi & Buat Pesanan
                </button>

                <div style="margin-top: 16px; font-size: 0.78rem; color: #64748b; text-align: center; line-height: 1.5;">
                    <i class="fa-solid fa-lock" style="color: #16a34a;"></i> Data pesanan Anda tersimpan aman dan langsung terhubung dengan Dashboard Admin.
                </div>
            </div>
        </div>
    </form>
</div>

<script>
const BASE_SUBTOTAL = <?= (int)$subtotalSepatu ?>;

function formatRupiah(number) {
    return 'Rp ' + number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function handleExpeditionChange(radioEl) {
    // Remove .selected from all cards
    document.querySelectorAll('.expedition-card').forEach(card => {
        card.classList.remove('selected');
    });

    // Add .selected to the parent label of the checked radio
    const parentLabel = radioEl.closest('.expedition-card');
    if (parentLabel) {
        parentLabel.classList.add('selected');
    }

    // Check if Ambil di Toko
    const isPickup = (radioEl.value === 'Ambil di Toko Fisik');
    const rowDiscount = document.getElementById('rowPickupDiscount');
    const displayTotal = document.getElementById('displayTotal');

    if (isPickup) {
        rowDiscount.style.display = 'flex';
        const finalPrice = Math.max(0, BASE_SUBTOTAL - 15000);
        displayTotal.innerText = formatRupiah(finalPrice);
    } else {
        rowDiscount.style.display = 'none';
        displayTotal.innerText = formatRupiah(BASE_SUBTOTAL);
    }
}

function confirmSubmitOrder() {
    const nama = document.getElementById('nama_penerima').value.trim();
    const hp = document.getElementById('no_hp').value.trim();
    const alamat = document.getElementById('alamat').value.trim();

    if (!nama || !hp || !alamat) {
        Swal.fire({
            title: 'Data Belum Lengkap',
            text: 'Harap isi nama penerima, nomor WhatsApp, dan alamat pengiriman dengan lengkap.',
            icon: 'warning',
            confirmButtonColor: '#2563eb'
        });
        return;
    }

    const selectedKurir = document.querySelector('input[name="kurir"]:checked')?.value || 'J&T Express';
    let summaryText = `Pengiriman via ${selectedKurir}. Lanjutkan pembuatan pesanan?`;
    if (selectedKurir === 'Ambil di Toko Fisik') {
        summaryText = `Opsi Ambil di Toko Fisik (Bebas Ongkir & Potongan Rp 15.000). Lanjutkan pembuatan pesanan?`;
    }

    Swal.fire({
        title: 'Konfirmasi Pesanan Sepatu',
        text: summaryText,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-arrow-right"></i> Ya, Lanjutkan Pembayaran',
        cancelButtonText: 'Periksa Kembali'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('checkoutForm').submit();
        }
    });
}
</script>

<?php require_once $rootPath . '/includes/footer.php'; ?>
