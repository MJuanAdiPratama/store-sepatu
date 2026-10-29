<?php
/**
 * Keranjang Belanja - QxuanStore
 * Modern Shoe E-Commerce (User Area)
 */

$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';

require_user();

// Handle AJAX Qty Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_qty') {
    header('Content-Type: application/json');
    $key = trim($_POST['key'] ?? '');
    $qty = max(1, min(10, (int)($_POST['qty'] ?? 1)));

    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['qty'] = $qty;
    }

    $itemSubtotal = ($_SESSION['cart'][$key]['harga'] ?? 0) * $qty;
    $grandTotal = 0;
    $totalCount = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $it) {
            $grandTotal += ($it['harga'] * $it['qty']);
            $totalCount += (int)$it['qty'];
        }
    }

    echo json_encode([
        'success'      => true,
        'itemSubtotal' => format_rupiah($itemSubtotal),
        'grandTotal'   => format_rupiah($grandTotal),
        'cartCount'    => $totalCount
    ]);
    exit;
}

// Handle Direct GET Update Qty
if (isset($_GET['update_qty'])) {
    $cartKey = $_GET['key'] ?? '';
    $newQty  = (int)($_GET['qty'] ?? 1);
    if (isset($_SESSION['cart'][$cartKey])) {
        if ($newQty <= 0) {
            unset($_SESSION['cart'][$cartKey]);
            set_flash('info', 'Item sepatu berhasil dihapus dari keranjang.');
        } else {
            $_SESSION['cart'][$cartKey]['qty'] = min(10, $newQty);
        }
    }
    redirect(user_url('cart.php'));
    exit;
}

// Handle Item Removal
if (isset($_GET['remove'])) {
    $removeKey = $_GET['remove'];
    if (isset($_SESSION['cart'][$removeKey])) {
        unset($_SESSION['cart'][$removeKey]);
        set_flash('info', 'Item sepatu berhasil dihapus dari keranjang.');
        redirect(user_url('cart.php'));
        exit;
    }
}

// Handle Clear Cart
if (isset($_GET['clear'])) {
    unset($_SESSION['cart']);
    set_flash('info', 'Keranjang belanja berhasil dikosongkan.');
    redirect(user_url('cart.php'));
    exit;
}

$pageTitle = 'Keranjang Belanja';
require_once $rootPath . '/includes/header.php';

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
        <p style="color: #64748b; font-size: 0.95rem;">Periksa kembali ukuran dan jumlah pasang sepatu sebelum melanjutkan ke pembayaran.</p>
    </div>

    <?php if (!empty($cart)): ?>
        <div class="cart-layout-grid">
            <!-- Cart Table Items -->
            <div style="background: #fff; padding: 24px; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm);">
                <div class="table-responsive">
                    <table class="modern-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th width="70">Foto</th>
                                <th>Nama Sepatu & Ukuran</th>
                                <th>Harga</th>
                                <th width="140" style="text-align: center;">Jumlah (Pasang)</th>
                                <th>Subtotal</th>
                                <th width="50" style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart as $key => $item): ?>
                                <?php 
                                    $subtotal = $item['harga'] * $item['qty'];
                                    $totalBayar += $subtotal;
                                    $keyHash = md5($key);
                                ?>
                                <tr id="row_<?= $keyHash ?>">
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
                                    <td style="text-align: center;">
                                        <!-- Interactive Quantity Stepper -->
                                        <div style="display: inline-flex; align-items: center; border: 1.5px solid #cbd5e1; border-radius: 8px; overflow: hidden; background: #fff;">
                                            <button type="button" onclick="modifyQty('<?= htmlspecialchars($key) ?>', -1, '<?= $keyHash ?>')" style="width: 32px; height: 32px; border: none; background: #f8fafc; font-weight: 800; cursor: pointer; color: #334155; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f8fafc'" title="Kurangi 1 pasang">
                                                -
                                            </button>
                                            <span id="qty_val_<?= $keyHash ?>" style="width: 38px; text-align: center; font-weight: 700; font-size: 0.92rem; color: #0f172a;">
                                                <?= $item['qty'] ?>
                                            </span>
                                            <button type="button" onclick="modifyQty('<?= htmlspecialchars($key) ?>', 1, '<?= $keyHash ?>')" style="width: 32px; height: 32px; border: none; background: #f8fafc; font-weight: 800; cursor: pointer; color: #334155; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f8fafc'" title="Tambah 1 pasang">
                                                +
                                            </button>
                                        </div>
                                    </td>
                                    <td id="subtotal_<?= $keyHash ?>">
                                        <strong style="color: #2563eb; font-size: 1rem;"><?= format_rupiah($subtotal) ?></strong>
                                    </td>
                                    <td style="text-align: right;">
                                        <button type="button" onclick="confirmRemoveCartItem('<?= htmlspecialchars($key) ?>', '<?= addslashes(htmlspecialchars($item['nama'])) ?> (Size <?= htmlspecialchars($item['ukuran']) ?>)')" style="background: none; border: none; color: #ef4444; font-size: 1.15rem; cursor: pointer;" title="Hapus sepatu dari keranjang">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 24px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 16px; flex-wrap: wrap; gap: 10px;">
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
                    <strong id="totalBayarText" style="color: #2563eb; font-size: 1.35rem;"><?= format_rupiah($totalBayar) ?></strong>
                </div>

                <a href="<?= user_url('checkout.php') ?>" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px; font-size: 1rem; border-radius: 10px; font-weight: 700;">
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
// Interactive Cart Quantity Modifier
function modifyQty(key, delta, keyHash) {
    const qtySpan = document.getElementById('qty_val_' + keyHash);
    if (!qtySpan) return;

    let currentQty = parseInt(qtySpan.textContent.trim(), 10) || 1;
    let newQty = currentQty + delta;

    if (newQty < 1) {
        confirmRemoveCartItem(key, 'sepatu ini');
        return;
    }
    if (newQty > 10) {
        Swal.fire({
            title: 'Batas Maksimal Pembelian',
            text: 'Maksimal pembelian untuk satu model dan ukuran sepatu adalah 10 pasang per transaksi.',
            icon: 'info',
            confirmButtonColor: '#2563eb'
        });
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update_qty');
    formData.append('key', key);
    formData.append('qty', newQty);

    fetch('<?= user_url("cart.php") ?>', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            qtySpan.textContent = newQty;
            
            // Update subtotal cell
            const subtotalEl = document.getElementById('subtotal_' + keyHash);
            if (subtotalEl) {
                subtotalEl.innerHTML = `<strong style="color: #2563eb; font-size: 1rem;">${data.itemSubtotal}</strong>`;
            }

            // Update grand total tagihan
            const grandTotalEl = document.getElementById('totalBayarText');
            if (grandTotalEl) {
                grandTotalEl.textContent = data.grandTotal;
            }

            // Update cart badge in header
            const cartCountBadge = document.querySelector('.cart-count');
            if (cartCountBadge) {
                cartCountBadge.textContent = data.cartCount;
            }
        }
    })
    .catch(err => {
        console.error(err);
        window.location.href = `<?= user_url("cart.php") ?>?update_qty=1&key=${encodeURIComponent(key)}&qty=${newQty}`;
    });
}

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
