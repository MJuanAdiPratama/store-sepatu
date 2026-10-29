<?php
/**
 * Detail Produk & Pemesanan Sepatu - QxuanStore
 * Modern Shoe E-Commerce
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/session.php';

if (!is_user()) {
    set_flash('warning', 'Silakan masuk ke akun Anda terlebih dahulu untuk melihat detail dan memesan sepatu.');
    redirect(base_url('auth/login.php'));
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = db_fetch_one("
    SELECT p.*, c.nama_category 
    FROM tb_produk p 
    LEFT JOIN tb_category c ON p.id_category = c.id_category 
    WHERE p.id_produk = ?
", [$id]);

if (!$product) {
    redirect(base_url('products.php'));
}

// Fetch available sizes for this shoe
$sizes = db_fetch_all("SELECT * FROM tb_produk_size WHERE id_produk = ? AND stok > 0 ORDER BY ukuran ASC", [$id]);

// Handle Form Submission (MUST RUN BEFORE header.php to reflect cart count immediately)
$cartMsg = '';
$addedSuccessItem = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionType = $_POST['action_type'] ?? '';
    $selectedSize = trim($_POST['ukuran'] ?? '');
    $qty = max(1, min(10, (int)($_POST['qty'] ?? 1)));

    if (empty($selectedSize)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Silakan pilih ukuran sepatu terlebih dahulu!']);
            exit;
        }
        $cartMsg = '<div class="alert alert-danger" style="margin-bottom: 16px;">Silakan pilih ukuran sepatu terlebih dahulu!</div>';
    } else {
        if ($actionType === 'buy_now') {
            // DIRECT CHECKOUT (Tidak memasukkan ke dalam keranjang, langsung ke halaman checkout)
            $_SESSION['direct_checkout'] = [
                'id_produk' => $id,
                'nama'      => $product->nama_produk,
                'harga'     => $product->harga_produk,
                'ukuran'    => $selectedSize,
                'gambar'    => $product->image_produk,
                'qty'       => $qty
            ];
            redirect(user_url('checkout.php?direct=1'));
            exit;
        } elseif ($actionType === 'add_to_cart') {
            // TAMBAH KE KERANJANG
            $cartKey = $id . '_' . $selectedSize;
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            if (isset($_SESSION['cart'][$cartKey])) {
                $_SESSION['cart'][$cartKey]['qty'] = min(10, $_SESSION['cart'][$cartKey]['qty'] + $qty);
            } else {
                $_SESSION['cart'][$cartKey] = [
                    'id_produk' => $id,
                    'nama'      => $product->nama_produk,
                    'harga'     => $product->harga_produk,
                    'ukuran'    => $selectedSize,
                    'gambar'    => $product->image_produk,
                    'qty'       => $qty
                ];
            }

            $totalCartItems = 0;
            foreach ($_SESSION['cart'] as $c) {
                $totalCartItems += (int)($c['qty'] ?? 1);
            }

            // Return JSON if AJAX
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success'   => true,
                    'cartCount' => $totalCartItems,
                    'nama'      => $product->nama_produk,
                    'ukuran'    => $selectedSize,
                    'qty'       => $qty
                ]);
                exit;
            }

            $addedSuccessItem = [
                'nama'   => $product->nama_produk,
                'ukuran' => $selectedSize,
                'qty'    => $qty
            ];
        }
    }
}

$pageTitle = $product->nama_produk;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 36px; padding-bottom: 60px;">
    <!-- Breadcrumb -->
    <div style="font-size: 0.88rem; color: #64748b; margin-bottom: 24px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <a href="<?= base_url('index.php') ?>">Beranda</a>
        <i class="fa-solid fa-chevron-right" style="font-size: 0.7rem;"></i>
        <a href="<?= base_url('products.php') ?>">Katalog Sepatu</a>
        <i class="fa-solid fa-chevron-right" style="font-size: 0.7rem;"></i>
        <a href="<?= base_url('products.php?cat=' . $product->id_category) ?>"><?= htmlspecialchars($product->nama_category ?? 'Kategori') ?></a>
        <i class="fa-solid fa-chevron-right" style="font-size: 0.7rem;"></i>
        <span style="color: #0f172a; font-weight: 600;"><?= htmlspecialchars($product->nama_produk) ?></span>
    </div>

    <!-- Product Detail Container -->
    <div class="detail-grid">
        <!-- Shoe Photo -->
        <div class="detail-img-box">
            <img src="<?= product_image_url($product->image_produk) ?>" alt="<?= htmlspecialchars($product->nama_produk) ?>">
        </div>

        <!-- Shoe Info & Size Selector -->
        <div>
            <?= $cartMsg ?>

            <span class="badge badge-info" style="margin-bottom: 10px;"><?= htmlspecialchars($product->nama_category ?? 'Sneakers') ?></span>
            <h1 class="detail-title"><?= htmlspecialchars($product->nama_produk) ?></h1>
            <div class="detail-price"><?= format_rupiah($product->harga_produk) ?></div>

            <form action="" method="POST" id="orderForm">
                <!-- Shoe Size Selector -->
                <div class="size-selector">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <label for="size-group" style="margin: 0; font-weight: 700;">Pilih Ukuran Sepatu (EU):</label>
                        <a href="#sizeGuide" onclick="document.getElementById('sizeModal').style.display='block'" style="font-size: 0.82rem; color: #2563eb; font-weight: 600; text-decoration: underline;">
                            <i class="fa-solid fa-ruler"></i> Panduan Ukuran
                        </a>
                    </div>

                    <?php if (!empty($sizes)): ?>
                        <div class="size-options">
                            <?php foreach ($sizes as $idx => $s): ?>
                                <input type="radio" name="ukuran" id="size_<?= $s->ukuran ?>" value="<?= $s->ukuran ?>" class="size-option-input" <?= $idx === 0 ? 'checked' : '' ?>>
                                <label for="size_<?= $s->ukuran ?>" class="size-option-label" title="Tersedia <?= $s->stok ?> pasang">
                                    Size <?= htmlspecialchars($s->ukuran) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 8px; font-size: 0.88rem;">
                            Maaf, semua ukuran untuk sepatu ini sedang habis.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quantity -->
                <div style="margin-bottom: 24px;">
                    <label for="qty" style="display: block; font-weight: 700; font-size: 0.95rem; margin-bottom: 8px;">Jumlah Pasang:</label>
                    <input type="number" id="qty" name="qty" value="1" min="1" max="10" class="form-control" style="width: 100px; padding: 8px 12px;">
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 28px;">
                    <!-- Direct Checkout Button -->
                    <button type="submit" name="action_type" value="buy_now" class="btn" style="background-color: #16a34a; color: #fff; padding: 14px; justify-content: center; font-size: 1rem; border-radius: 10px; font-weight: 700; box-shadow: 0 4px 6px -1px rgba(22, 163, 74, 0.25);">
                        <i class="fa-solid fa-bolt"></i> Beli Langsung (Checkout Sekarang)
                    </button>

                    <!-- Add to Cart Button -->
                    <button type="submit" name="action_type" value="add_to_cart" id="btnAddToCart" class="btn btn-primary" style="padding: 14px; justify-content: center; font-size: 1rem; border-radius: 10px; font-weight: 700;">
                        <i class="fa-solid fa-cart-plus"></i> Tambah ke Keranjang
                    </button>
                </div>
            </form>

            <!-- Shoe Description -->
            <div style="border-top: 1px solid #e2e8f0; padding-top: 20px;">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px;">Deskripsi & Detail Sepatu:</h3>
                <div style="color: #475569; font-size: 0.95rem; line-height: 1.8;">
                    <?= !empty($product->deskripsi_produk) ? nl2br($product->deskripsi_produk) : 'Sepatu sneakers berkualitas tinggi, nyaman untuk dipakai sehari-hari, berolahraga, maupun jalan-jalan.' ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Size Chart Guide Modal -->
<div id="sizeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #fff; max-width: 500px; width: 90%; margin: 60px auto; padding: 28px; border-radius: 12px; position: relative;">
        <button onclick="document.getElementById('sizeModal').style.display='none'" style="position: absolute; right: 16px; top: 16px; border: none; background: none; font-size: 1.25rem; cursor: pointer;">&times;</button>
        <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 16px;">Panduan Ukuran Sepatu (Size Chart)</h3>
        <p style="font-size: 0.88rem; color: #64748b; margin-bottom: 16px;">Ukur panjang telapak kaki Anda dari tumit hingga ujung jari terpanjang:</p>
        
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: left;">
            <thead>
                <tr style="background: #f1f5f9; border-bottom: 2px solid #cbd5e1;">
                    <th style="padding: 8px;">Ukuran (EU)</th>
                    <th style="padding: 8px;">Panjang Kaki (cm)</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px solid #e2e8f0;"><td style="padding: 8px;">Size 38</td><td style="padding: 8px;">24.0 cm</td></tr>
                <tr style="border-bottom: 1px solid #e2e8f0;"><td style="padding: 8px;">Size 39</td><td style="padding: 8px;">24.5 cm</td></tr>
                <tr style="border-bottom: 1px solid #e2e8f0;"><td style="padding: 8px;">Size 40</td><td style="padding: 8px;">25.0 cm</td></tr>
                <tr style="border-bottom: 1px solid #e2e8f0;"><td style="padding: 8px;">Size 41</td><td style="padding: 8px;">26.0 cm</td></tr>
                <tr style="border-bottom: 1px solid #e2e8f0;"><td style="padding: 8px;">Size 42</td><td style="padding: 8px;">26.5 cm</td></tr>
                <tr style="border-bottom: 1px solid #e2e8f0;"><td style="padding: 8px;">Size 43</td><td style="padding: 8px;">27.5 cm</td></tr>
                <tr><td style="padding: 8px;">Size 44</td><td style="padding: 8px;">28.0 cm</td></tr>
            </tbody>
        </table>
        
        <div style="margin-top: 20px; text-align: right;">
            <button onclick="document.getElementById('sizeModal').style.display='none'" class="btn btn-secondary btn-sm">Tutup Panduan</button>
        </div>
    </div>
</div>

<script>
// Helper to update cart badge in header
function updateHeaderCartBadge(count) {
    const cartBtn = document.querySelector('.icon-btn.cart-btn');
    if (!cartBtn) return;
    let badge = cartBtn.querySelector('.cart-count');
    if (!badge && count > 0) {
        badge = document.createElement('span');
        badge.className = 'cart-count';
        cartBtn.appendChild(badge);
    }
    if (badge) {
        badge.textContent = count;
        if (count <= 0) badge.remove();
    }
}

// Intercept form submission
let lastClickedAction = 'add_to_cart';
document.querySelectorAll('#orderForm button[type="submit"]').forEach(btn => {
    btn.addEventListener('click', function() {
        lastClickedAction = this.value || 'add_to_cart';
    });
});

document.getElementById('orderForm').addEventListener('submit', function(e) {
    const sizeSelected = document.querySelector('input[name="ukuran"]:checked');
    if (!sizeSelected) {
        e.preventDefault();
        Swal.fire({
            title: 'Pilih Ukuran Sepatu',
            text: 'Silakan pilih ukuran (size) sepatu terlebih dahulu.',
            icon: 'warning',
            confirmButtonColor: '#2563eb'
        });
        return false;
    }

    if (lastClickedAction === 'add_to_cart') {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action_type', 'add_to_cart');
        formData.append('ajax', '1');

        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Instantly update badge in header
                updateHeaderCartBadge(data.cartCount);

                // Show SweetAlert popup with options: Lihat Keranjang & Tutup
                Swal.fire({
                    title: 'Berhasil Ditambahkan!',
                    text: `${data.nama} (Size ${data.ukuran}) berhasil dimasukkan ke keranjang belanja Anda.`,
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fa-solid fa-cart-shopping"></i> Lihat Keranjang',
                    cancelButtonText: 'Tutup'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '<?= user_url("cart.php") ?>';
                    }
                });
            } else {
                Swal.fire('Perhatian', data.message || 'Gagal menambahkan sepatu ke keranjang.', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            // Fallback submit
            const inputAction = document.createElement('input');
            inputAction.type = 'hidden';
            inputAction.name = 'action_type';
            inputAction.value = 'add_to_cart';
            this.appendChild(inputAction);
            this.submit();
        });
    }
});

<?php if (!empty($addedSuccessItem)): ?>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: 'Berhasil Ditambahkan!',
        text: '<?= addslashes($addedSuccessItem['nama']) ?> (Size <?= addslashes($addedSuccessItem['ukuran']) ?>) berhasil dimasukkan ke keranjang belanja Anda.',
        icon: 'success',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-cart-shopping"></i> Lihat Keranjang',
        cancelButtonText: 'Tutup'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= user_url("cart.php") ?>';
        }
    });
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
