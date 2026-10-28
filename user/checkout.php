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

$totalBayar = 0;
foreach ($cart as $item) {
    $totalBayar += ($item['harga'] * $item['qty']);
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
        // Generate Unique Invoice Number
        $invoiceNo = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        // Insert into tb_order
        $sqlOrder = "
            INSERT INTO tb_order (invoice_no, user_id, nama_penerima, no_hp, alamat_pengiriman, catatan, total_bayar, status_order, kurir) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Menunggu Pembayaran', ?)
        ";
        $orderInserted = db_execute($sqlOrder, [$invoiceNo, $userId, $nama_penerima, $no_hp, $alamat, $catatan, $totalBayar, $kurir]);

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
                        Pilih jasa kurir yang Anda inginkan untuk mengantar paket sepatu Anda:
                    </p>

                    <div class="expedition-grid">
                        <!-- J&T -->
                        <label style="border: 2px solid #2563eb; background: #eff6ff; padding: 14px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 12px;">
                            <input type="radio" name="kurir" value="J&T Express" checked>
                            <div>
                                <strong style="display: block; font-size: 0.95rem; color: #0f172a;">J&T Express</strong>
                                <small style="color: #2563eb; font-weight: 600;">Estimasi 1-2 Hari &bull; Populer</small>
                            </div>
                        </label>

                        <!-- JNE -->
                        <label style="border: 1px solid #cbd5e1; background: #fff; padding: 14px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 12px;">
                            <input type="radio" name="kurir" value="JNE Regular">
                            <div>
                                <strong style="display: block; font-size: 0.95rem; color: #0f172a;">JNE Regular</strong>
                                <small style="color: #64748b;">Estimasi 2-3 Hari &bull; Terpercaya</small>
                            </div>
                        </label>

                        <!-- SiCepat -->
                        <label style="border: 1px solid #cbd5e1; background: #fff; padding: 14px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 12px;">
                            <input type="radio" name="kurir" value="SiCepat Reguler">
                            <div>
                                <strong style="display: block; font-size: 0.95rem; color: #0f172a;">SiCepat Reguler</strong>
                                <small style="color: #64748b;">Estimasi 1-2 Hari &bull; Cepat</small>
                            </div>
                        </label>

                        <!-- Ambil di Toko -->
                        <label style="border: 1px solid #cbd5e1; background: #fff; padding: 14px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 12px;">
                            <input type="radio" name="kurir" value="Ambil di Toko Fisik">
                            <div>
                                <strong style="display: block; font-size: 0.95rem; color: #0f172a;">Ambil di Toko</strong>
                                <small style="color: #16a34a; font-weight: 700;">Tanpa Biaya Ongkir</small>
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
                    <strong><?= format_rupiah($totalBayar) ?></strong>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 1.1rem; border-top: 1px dashed #e2e8f0; padding-top: 14px;">
                    <span style="font-weight: 800;">Total Pembayaran:</span>
                    <strong style="color: #2563eb; font-size: 1.35rem;"><?= format_rupiah($totalBayar) ?></strong>
                </div>

                <button type="button" onclick="confirmSubmitOrder()" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px; font-size: 1.05rem; border-radius: 10px;">
                    <i class="fa-solid fa-check"></i> Konfirmasi & Buat Pesanan
                </button>

                <div style="margin-top: 16px; font-size: 0.78rem; color: #64748b; text-align: center; line-height: 1.5;">
                    <i class="fa-solid fa-lock" style="color: #16a34a;"></i> Data pesanan Anda tersimpan aman dan langsung terhubung dengan WhatsApp Admin.
                </div>
            </div>
        </div>
    </form>
</div>

<script>
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

    Swal.fire({
        title: 'Konfirmasi Pesanan?',
        text: 'Pastikan nama penerima, nomor WhatsApp, dan alamat pengiriman sudah benar. Lanjutkan pembuatan pesanan?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Buat Pesanan',
        cancelButtonText: 'Periksa Kembali'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('checkoutForm').submit();
        }
    });
}
</script>

<?php require_once $rootPath . '/includes/footer.php'; ?>
