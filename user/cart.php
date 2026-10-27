<?php
/**
 * Shopping Cart Page - QxuanStore
 * Modern Shoe E-Commerce (User Area)
 */

$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';

require_user();

$pageTitle = 'Keranjang Belanja';
require_once $rootPath . '/includes/header.php';

// Handle Item Removal
if (isset($_GET['remove'])) {
    $removeKey = $_GET['remove'];
    if (isset($_SESSION['cart'][$removeKey])) {
        unset($_SESSION['cart'][$removeKey]);
        set_flash('info', 'Item sepatu berhasil dihapus dari keranjang.');
        redirect(user_url('cart.php'));
    }
}

// Handle Clear Cart
if (isset($_GET['clear'])) {
    unset($_SESSION['cart']);
    set_flash('info', 'Keranjang belanja berhasil dikosongkan.');
    redirect(user_url('cart.php'));
}

$cart = $_SESSION['cart'] ?? [];
$totalBayar = 0;
?>

<div class="container" style="padding-top: 40px; min-height: 65vh; padding-bottom: 60px;">
    <!-- Flash Messages -->
    <?php 
        $infoMsg = get_flash('info');
        $successMsg = get_flash('success');
    ?>
    <?php if ($infoMsg): ?>
        <div class="alert alert-info" style="background: #e0f2fe; color: #0369a1; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fa-solid fa-circle-info"></i> <?= htmlspecialchars($infoMsg) ?>
        </div>
    <?php endif; ?>
    <?php if ($successMsg): ?>
        <div class="alert alert-success" style="background: #dcfce7; color: #166534; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($successMsg) ?>
        </div>
    <?php endif; ?>

    <div style="margin-bottom: 28px;">
        <h1 style="font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;">Keranjang Belanja Sepatu</h1>
        <p style="color: #64748b; font-size: 0.95rem;">Periksa kembali ukuran dan jumlah sepatu sebelum melanjutkan ke checkout.</p>
    </div>

    <?php if (!empty($cart)): ?>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px; align-items: start;">
            <!-- Cart Table Items -->
            <div style="background: #fff; padding: 24px; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm);">
                <div class="table-responsive">
                    <table class="modern-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th width="70">Foto</th>
                                <th>Nama Sepatu & Ukuran</th>
                                <th>Harga</th>
                                <th width="80">Qty</th>
                                <th>Subtotal</th>
                                <th width="50" style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart as $key => $item): ?>
                                <?php 
                                    $subtotal = $item['harga'] * $item['qty'];
                                    $totalBayar += $subtotal;
                                ?>
                                <tr>
                                    <td>
                                        <img src="<?= product_image_url($item['gambar']) ?>" alt="<?= htmlspecialchars($item['nama']) ?>" style="width: 56px; height: 56px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    </td>
                                    <td>
                                        <strong style="color: #0f172a; font-size: 0.95rem;"><?= htmlspecialchars($item['nama']) ?></strong>
                                        <div style="margin-top: 4px;">
                                            <span class="badge badge-info" style="font-size: 0.75rem;">Size <?= htmlspecialchars($item['ukuran']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= format_rupiah($item['harga']) ?></td>
                                    <td>
                                        <strong style="background: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-size: 0.88rem;"><?= $item['qty'] ?> pcs</strong>
                                    </td>
                                    <td>
                                        <strong style="color: #2563eb; font-size: 1rem;"><?= format_rupiah($subtotal) ?></strong>
                                    </td>
                                    <td style="text-align: right;">
                                        <button type="button" onclick="confirmRemoveCartItem('<?= htmlspecialchars($key) ?>', '<?= addslashes(htmlspecialchars($item['nama'])) ?> (Size <?= htmlspecialchars($item['ukuran']) ?>)')" style="background: none; border: none; color: #ef4444; font-size: 1.15rem; cursor: pointer;" title="Hapus dari keranjang">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 24px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 16px;">
                    <a href="<?= base_url('products.php') ?>" style="color: #2563eb; font-weight: 600; font-size: 0.92rem; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-arrow-left"></i> Tambah Sepatu Lainnya
                    </a>
                    <button type="button" onclick="confirmClearCart()" style="background: none; border: none; color: #ef4444; font-size: 0.88rem; font-weight: 600; cursor: pointer;">
                        <i class="fa-solid fa-trash"></i> Kosongkan Keranjang
                    </button>
                </div>
            </div>

            <!-- Order Summary Card -->
            <div style="background: #fff; padding: 26px; border-radius: 14px; border: 1px solid #e2e8f0; position: sticky; top: 100px; box-shadow: var(--shadow-sm);">
                <h3 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 18px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
                    Ringkasan Belanja
                </h3>

                <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.95rem;">
                    <span style="color: #64748b;">Total Jenis Sepatu:</span>
                    <strong><?= count($cart) ?> Item</strong>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 1.1rem; border-top: 1px dashed #e2e8f0; padding-top: 14px;">
                    <span style="font-weight: 700;">Total Tagihan:</span>
                    <strong style="color: #2563eb; font-size: 1.35rem;"><?= format_rupiah($totalBayar) ?></strong>
                </div>

                <a href="<?= user_url('checkout.php') ?>" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px; font-size: 1rem; border-radius: 10px;">
                    <i class="fa-solid fa-credit-card"></i> Lanjut ke Checkout
                </a>

                <div style="margin-top: 16px; font-size: 0.8rem; color: #64748b; text-align: center; line-height: 1.5;">
                    <i class="fa-solid fa-shield-halved" style="color: #16a34a;"></i> Transaksi aman & garansi ukuran pas
                </div>
            </div>
        </div>
    <?php else: ?>
        <div style="text-align: center; background: #fff; padding: 60px 20px; border-radius: 16px; border: 1px solid #e2e8f0; max-width: 500px; margin: 0 auto; box-shadow: var(--shadow-sm);">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 16px;">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
            <h2 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 8px;">Keranjang Belanja Kosong</h2>
            <p style="color: #64748b; margin-bottom: 24px; font-size: 0.92rem;">
                Anda belum memilih sepatu apapun ke dalam keranjang belanja.
            </p>
            <a href="<?= base_url('products.php') ?>" class="btn btn-primary">
                <i class="fa-solid fa-shoe-prints"></i> Mulai Pilih Sepatu
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmRemoveCartItem(key, itemName) {
    Swal.fire({
        title: 'Hapus dari Keranjang?',
        text: `Hapus "${itemName}" dari keranjang belanja Anda?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= user_url("cart.php?remove=") ?>' + encodeURIComponent(key);
        }
    });
}

function confirmClearCart() {
    Swal.fire({
        title: 'Kosongkan Keranjang?',
        text: 'Semua item sepatu dalam keranjang akan dihapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Kosongkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= user_url("cart.php?clear=1") ?>';
        }
    });
}
</script>

<?php require_once $rootPath . '/includes/footer.php'; ?>
