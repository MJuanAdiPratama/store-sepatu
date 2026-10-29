<?php
/**
 * Customer Order History Page - QxuanStore
 * Modern Shoe E-Commerce (User Area)
 */

$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';

require_user();

$userId = $_SESSION['user_id'];
$userData = db_fetch_one("SELECT * FROM tb_user WHERE user_id = ?", [$userId]);

$orders = db_fetch_all("SELECT * FROM tb_order WHERE user_id = ? ORDER BY id_order DESC", [$userId]);

$pageTitle = 'Riwayat Pesanan Saya';
require_once $rootPath . '/includes/header.php';
?>

<div class="container" style="padding-top: 40px; padding-bottom: 70px;">
    <div class="profile-layout-grid">
        <!-- Customer Sidebar -->
        <div class="customer-sidebar-card">
            <div style="text-align: center; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9;">
                <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 800; margin: 0 auto 12px; box-shadow: 0 4px 12px rgba(37,99,235,0.25);">
                    <?= strtoupper(substr($userData->nama_lengkap, 0, 1)) ?>
                </div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin-bottom: 4px;"><?= htmlspecialchars($userData->nama_lengkap) ?></h3>
                <small style="color: #64748b; font-size: 0.85rem; word-break: break-all;"><?= htmlspecialchars($userData->email) ?></small>
            </div>

            <!-- Sidebar Links -->
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 8px;">
                <li>
                    <a href="<?= user_url('profile.php') ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 10px; font-weight: 600; color: #475569; font-size: 0.92rem;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                        <i class="fa-solid fa-user"></i> Profil & Alamat
                    </a>
                </li>
                <li>
                    <a href="<?= user_url('orders.php') ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 10px; font-weight: 700; background: #2563eb; color: #fff; font-size: 0.92rem;">
                        <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Pesanan
                    </a>
                </li>
                <li>
                    <a href="<?= user_url('cart.php') ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 10px; font-weight: 600; color: #475569; font-size: 0.92rem;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                        <i class="fa-solid fa-cart-shopping"></i> Keranjang Belanja
                    </a>
                </li>
                <li style="border-top: 1px solid #f1f5f9; margin-top: 10px; padding-top: 10px;">
                    <a href="javascript:void(0)" onclick="confirmUserLogout()" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 10px; font-weight: 600; color: #ef4444; font-size: 0.92rem;">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar Akun
                    </a>
                </li>
            </ul>
        </div>

        <!-- Orders List Card -->
        <div style="background: #fff; padding: 32px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px;">
                <h1 style="font-size: 1.4rem; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-clock-rotate-left" style="color: #2563eb;"></i> Riwayat Pesanan
                </h1>
                <span class="badge badge-info"><?= count($orders) ?> Total Pesanan</span>
            </div>

            <?php if (!empty($orders)): ?>
                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <?php foreach ($orders as $ord): ?>
                        <?php
                            $badgeClass = 'badge-warning';
                            if ($ord->status_order === 'Diproses') $badgeClass = 'badge-info';
                            if ($ord->status_order === 'Dikirim') $badgeClass = 'badge-primary';
                            if ($ord->status_order === 'Selesai') $badgeClass = 'badge-success';
                            if ($ord->status_order === 'Dibatalkan') $badgeClass = 'badge-danger';

                            // Fetch items
                            $ordItems = db_fetch_all("
                                SELECT od.*, p.image_produk 
                                FROM tb_order_detail od 
                                LEFT JOIN tb_produk p ON od.id_produk = p.id_produk 
                                WHERE od.id_order = ?
                            ", [$ord->id_order]);
                        ?>
                        <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; transition: var(--transition);" onmouseover="this.style.borderColor='#cbd5e1'" onmouseout="this.style.borderColor='#e2e8f0'">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                                <div>
                                    <strong style="color: #2563eb; font-size: 1.05rem;">#<?= htmlspecialchars($ord->invoice_no) ?></strong>
                                    <span style="font-size: 0.82rem; color: #64748b; margin-left: 8px;">
                                        &bull; <?= date('d M Y, H:i', strtotime($ord->created_at)) ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="badge <?= $badgeClass ?>" style="padding: 6px 12px;"><?= htmlspecialchars($ord->status_order) ?></span>
                                </div>
                            </div>

                            <!-- Items Summary -->
                            <div style="margin-bottom: 16px; display: flex; flex-direction: column; gap: 10px;">
                                <?php foreach ($ordItems as $it): ?>
                                    <div style="display: flex; gap: 12px; align-items: center;">
                                        <img src="<?= product_image_url($it->image_produk) ?>" alt="<?= htmlspecialchars($it->nama_produk) ?>" style="width: 44px; height: 44px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                                        <div>
                                            <strong style="font-size: 0.92rem; color: #0f172a;"><?= htmlspecialchars($it->nama_produk) ?></strong>
                                            <div style="font-size: 0.8rem; color: #64748b;">
                                                Size <?= htmlspecialchars($it->ukuran) ?> &bull; <?= $it->qty ?> pasang x <?= format_rupiah($it->harga) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($ord->kurir === 'Ambil di Toko Fisik'): ?>
                                <div style="background: #f0fdf4; border: 1.5px solid #bbf7d0; padding: 12px 14px; border-radius: 8px; font-size: 0.85rem; color: #166534; margin-bottom: 14px;">
                                    <div style="font-weight: 700; display: flex; align-items: center; gap: 6px; margin-bottom: 6px; color: #14532d;">
                                        <i class="fa-solid fa-store" style="color: #16a34a; font-size: 1rem;"></i> Pengambilan: Ambil di Toko Fisik QxuanStore
                                    </div>
                                    <div style="font-size: 0.82rem; color: #15803d; line-height: 1.5;">
                                        <div><i class="fa-solid fa-clock"></i> <strong>Jam Operasional:</strong> Buka Setiap Hari (Senin – Minggu), 09.00 – 21.00 WIB</div>
                                        <div><i class="fa-solid fa-location-dot"></i> <strong>Alamat Toko:</strong> Perum Taman Raya Rajeg Jl. Melati 1, Mekarsari, Kec. Rajeg, Kab. Tangerang</div>
                                        <div style="margin-top: 4px; color: #166534; font-weight: 600;">
                                            Tunjukkan nomor invoice #<?= htmlspecialchars($ord->invoice_no) ?> kepada kasir saat mengambil pesanan.
                                        </div>
                                    </div>
                                </div>
                            <?php elseif (!empty($ord->no_resi)): ?>
                                <div style="background: #eff6ff; padding: 10px 14px; border-radius: 8px; font-size: 0.85rem; color: #1e40af; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-truck"></i>
                                    <span><strong>No. Resi (<?= htmlspecialchars($ord->kurir) ?>):</strong> <?= htmlspecialchars($ord->no_resi) ?></span>
                                </div>
                            <?php endif; ?>

                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #e2e8f0; padding-top: 14px; flex-wrap: wrap; gap: 10px;">
                                <div style="font-size: 0.92rem; color: #64748b;">
                                    Total: <strong style="color: #2563eb; font-size: 1.15rem;"><?= format_rupiah($ord->total_bayar) ?></strong>
                                    <?php if (!empty($ord->metode_pembayaran) && $ord->status_order !== 'Menunggu Pembayaran'): ?>
                                        <div style="font-size: 0.78rem; color: #166534; margin-top: 2px;">
                                            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($ord->metode_pembayaran) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div style="display: flex; justify-content: flex-end;">
                                    <?php if ($ord->status_order === 'Menunggu Pembayaran'): ?>
                                        <a href="<?= user_url('order-success.php?invoice=' . urlencode($ord->invoice_no)) ?>" class="btn btn-primary btn-sm" style="border-radius: 8px;">
                                            <i class="fa-solid fa-credit-card"></i> Bayar Sekarang
                                        </a>
                                    <?php elseif ($ord->status_order === 'Dikirim'): ?>
                                        <div class="order-card-actions">
                                            <div class="btn-group-row">
                                                <?php if ($ord->kurir !== 'Ambil di Toko Fisik' && !empty($ord->no_resi)): ?>
                                                    <a href="https://cekresi.com/?noresi=<?= urlencode($ord->no_resi) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Lacak Ekspedisi">
                                                        <i class="fa-solid fa-truck-fast"></i> Lacak Resi
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?= user_url('order-success.php?invoice=' . urlencode($ord->invoice_no)) ?>" class="btn btn-secondary btn-sm">
                                                    <i class="fa-solid fa-receipt"></i> Detail
                                                </a>
                                            </div>
                                            <button type="button" onclick="confirmOrderReceived('<?= $ord->invoice_no ?>')" class="btn btn-sm btn-order-received">
                                                <i class="fa-solid fa-box-open"></i> <?= $ord->kurir === 'Ambil di Toko Fisik' ? 'Sepatu Sudah Diambil' : 'Pesanan Sudah Diterima' ?>
                                            </button>
                                        </div>
                                    <?php elseif ($ord->status_order === 'Diproses' && $ord->kurir === 'Ambil di Toko Fisik'): ?>
                                        <div class="order-card-actions">
                                            <div class="btn-group-row">
                                                <a href="<?= user_url('order-success.php?invoice=' . urlencode($ord->invoice_no)) ?>" class="btn btn-secondary btn-sm">
                                                    <i class="fa-solid fa-receipt"></i> Detail & Bukti
                                                </a>
                                            </div>
                                            <button type="button" onclick="confirmOrderReceived('<?= $ord->invoice_no ?>')" class="btn btn-sm btn-order-received">
                                                <i class="fa-solid fa-store"></i> Konfirmasi Sudah Ambil di Toko
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <a href="<?= user_url('order-success.php?invoice=' . urlencode($ord->invoice_no)) ?>" class="btn btn-secondary btn-sm" style="border-radius: 8px;">
                                            <i class="fa-solid fa-receipt"></i> Detail & Bukti Bayar
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 50px 20px; color: #64748b;">
                    <div style="width: 70px; height: 70px; border-radius: 50%; background: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 16px;">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Belum Ada Riwayat Pesanan</h3>
                    <p style="font-size: 0.9rem; margin-bottom: 20px;">Anda belum melakukan pemesanan sepatu apapun di QxuanStore.</p>
                    <a href="<?= base_url('products.php') ?>" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-shoe-prints"></i> Mulai Belanja Sepatu
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmOrderReceived(invoiceNo) {
    Swal.fire({
        title: 'Konfirmasi Pesanan Diterima',
        text: `Apakah Anda sudah menerima paket sepatu pesanan #${invoiceNo} dalam kondisi baik?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-check"></i> Ya, Sudah Diterima',
        cancelButtonText: 'Belum'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Menyimpan Konfirmasi...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append('invoice', invoiceNo);

            fetch('<?= base_url("api/confirm_order_received.php") ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Pesanan Selesai!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#16a34a'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Perhatian', data.message || 'Gagal mengonfirmasi', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
            });
        }
    });
}

function confirmUserLogout() {
    Swal.fire({
        title: 'Konfirmasi Keluar',
        text: 'Apakah Anda yakin ingin keluar dari akun customer Anda?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Keluar Akun',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= base_url("auth/logout.php") ?>';
        }
    });
}
</script>

<?php require_once $rootPath . '/includes/footer.php'; ?>
