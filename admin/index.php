<?php
/**
 * Admin Dashboard
 * QxuanStore - Shoe E-Commerce
 */

$pageTitle = 'Dashboard Ringkasan';
require_once __DIR__ . '/includes/header.php';

// Fetch quick statistics
$totalProduk = db_fetch_one("SELECT COUNT(*) AS total FROM tb_produk")->total ?? 0;
$totalKategori = db_fetch_one("SELECT COUNT(*) AS total FROM tb_category")->total ?? 0;
$totalPesanan = db_fetch_one("SELECT COUNT(*) AS total FROM tb_order")->total ?? 0;
$totalUser = db_fetch_one("SELECT COUNT(*) AS total FROM tb_user")->total ?? 0;

// Fetch all products
$allProducts = db_fetch_all("
    SELECT p.*, c.nama_category 
    FROM tb_produk p 
    LEFT JOIN tb_category c USING(id_category) 
    ORDER BY p.id_produk DESC
");
?>

<!-- Statistics Overview -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <h3>Total Produk</h3>
            <p><?= number_format($totalProduk) ?></p>
        </div>
        <div class="stat-icon blue">
            <i class="fa-solid fa-boxes-stacked"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h3>Kategori Sepatu</h3>
            <p><?= number_format($totalKategori) ?></p>
        </div>
        <div class="stat-icon purple">
            <i class="fa-solid fa-tags"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h3>Total Pesanan</h3>
            <p><?= number_format($totalPesanan) ?></p>
        </div>
        <div class="stat-icon orange">
            <i class="fa-solid fa-cart-shopping"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h3>Customer Terdaftar</h3>
            <p><?= number_format($totalUser) ?></p>
        </div>
        <div class="stat-icon green">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>
</div>

<!-- Products Overview Section -->
<div class="card-panel">
    <div class="panel-header">
        <h2><i class="fa-solid fa-shoe-prints" style="color: #2563eb; margin-right: 8px;"></i> Daftar Produk Sepatu</h2>
    </div>

    <div class="table-responsive">
        <table class="modern-table">
            <thead>
                <tr>
                    <th width="70">Foto</th>
                    <th>Nama Sepatu</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($allProducts)): ?>
                    <?php foreach ($allProducts as $item): ?>
                        <tr>
                            <td>
                                <img src="<?= product_image_url($item->image_produk) ?>" alt="<?= htmlspecialchars($item->nama_produk) ?>" style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($item->nama_produk) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-gray"><?= htmlspecialchars($item->nama_category ?? 'Uncategorized') ?></span>
                            </td>
                            <td>
                                <strong style="color: #2563eb;"><?= format_rupiah($item->harga_produk) ?></strong>
                            </td>
                            <td>
                                <?php if ($item->status_produk == 1): ?>
                                    <span class="badge badge-success">In Stock</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Sold Out</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b; padding: 24px;">Belum ada produk yang ditambahkan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
