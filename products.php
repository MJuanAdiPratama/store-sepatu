<?php
/**
 * Catalog Page - QxuanStore
 * Modern Shoe E-Commerce
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/session.php';

if (!is_user()) {
    set_flash('warning', 'Silakan masuk ke akun Anda terlebih dahulu untuk melihat katalog produk.');
    redirect(base_url('auth/login.php'));
}

$pageTitle = 'Katalog Koleksi Sepatu Lengkap';
require_once __DIR__ . '/includes/header.php';

$search = trim($_GET['search'] ?? '');
$categoryFilter = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$sort = trim($_GET['sort'] ?? 'latest');

$where = "WHERE p.status_produk = 1";
$params = [];

if (!empty($search)) {
    $where .= " AND (p.nama_produk LIKE ? OR p.deskripsi_produk LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categoryFilter > 0) {
    $where .= " AND p.id_category = ?";
    $params[] = $categoryFilter;
}

$orderBy = "ORDER BY p.id_produk DESC";
if ($sort === 'price_asc') {
    $orderBy = "ORDER BY p.harga_produk ASC";
} elseif ($sort === 'price_desc') {
    $orderBy = "ORDER BY p.harga_produk DESC";
}

$shoes = db_fetch_all("
    SELECT p.*, c.nama_category 
    FROM tb_produk p 
    LEFT JOIN tb_category c ON p.id_category = c.id_category 
    $where 
    $orderBy
", $params);

$allCategories = db_fetch_all("SELECT * FROM tb_category ORDER BY nama_category ASC");
?>

<div class="container" style="padding-top: 40px;">
    <!-- Page Header -->
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;">Katalog Sepatu</h1>
        <p style="color: #64748b; font-size: 0.95rem;">
            Menampilkan <?= count($shoes) ?> koleksi sepatu
            <?php if (!empty($search)): ?>
                untuk kata kunci "<strong><?= htmlspecialchars($search) ?></strong>"
            <?php endif; ?>
        </p>
    </div>

    <!-- Filter & Sort Bar -->
    <div style="background: #fff; padding: 18px 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <!-- Categories Filter -->
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <a href="<?= base_url('products.php' . (!empty($search) ? '?search=' . urlencode($search) : '')) ?>" class="chip <?= $categoryFilter == 0 ? 'active' : '' ?>">
                Semua
            </a>
            <?php foreach ($allCategories as $cat): ?>
                <a href="<?= base_url('products.php?cat=' . $cat->id_category . (!empty($search) ? '&search=' . urlencode($search) : '')) ?>" class="chip <?= $categoryFilter == $cat->id_category ? 'active' : '' ?>">
                    <?= htmlspecialchars($cat->nama_category) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Sort Dropdown -->
        <form action="" method="GET" style="display: flex; gap: 10px; align-items: center;">
            <?php if (!empty($search)): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <?php endif; ?>
            <?php if ($categoryFilter > 0): ?>
                <input type="hidden" name="cat" value="<?= $categoryFilter ?>">
            <?php endif; ?>

            <label for="sort" style="font-size: 0.88rem; font-weight: 600; color: #64748b; white-space: nowrap;">Urutkan:</label>
            <select id="sort" name="sort" class="form-control" style="width: auto; padding: 8px 12px; font-size: 0.88rem;" onchange="this.form.submit()">
                <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>Terbaru</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Harga: Rendah ke Tinggi</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Harga: Tinggi ke Rendah</option>
            </select>
        </form>
    </div>

    <!-- Products Grid -->
    <div class="product-grid">
        <?php if (!empty($shoes)): ?>
            <?php foreach ($shoes as $shoe): ?>
                <div class="product-card">
                    <div class="card-img-wrap">
                        <a href="<?= base_url('product-detail.php?id=' . $shoe->id_produk) ?>">
                            <img src="<?= product_image_url($shoe->image_produk) ?>" alt="<?= htmlspecialchars($shoe->nama_produk) ?>" loading="lazy">
                        </a>
                        <span class="badge badge-success card-badge">In Stock</span>
                    </div>

                    <div class="card-content">
                        <span class="card-category"><?= htmlspecialchars($shoe->nama_category ?? 'Sneakers') ?></span>
                        <h3 class="card-title">
                            <a href="<?= base_url('product-detail.php?id=' . $shoe->id_produk) ?>">
                                <?= htmlspecialchars($shoe->nama_produk) ?>
                            </a>
                        </h3>
                        <div class="card-price"><?= format_rupiah($shoe->harga_produk) ?></div>
                        
                        <div class="card-action">
                            <a href="<?= base_url('product-detail.php?id=' . $shoe->id_produk) ?>" class="btn btn-primary">
                                <i class="fa-solid fa-eye"></i> Detail & Pilih Ukuran
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; background: #fff; padding: 50px 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <i class="fa-solid fa-shoe-prints" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 16px;"></i>
                <h3 style="font-size: 1.25rem; font-weight: 700; color: #334155; margin-bottom: 8px;">Produk Tidak Ditemukan</h3>
                <p style="color: #64748b; margin-bottom: 20px;">Maaf, tidak ada produk sepatu yang sesuai dengan pencarian Anda.</p>
                <a href="<?= base_url('products.php') ?>" class="btn btn-primary btn-sm">Reset Pencarian</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
