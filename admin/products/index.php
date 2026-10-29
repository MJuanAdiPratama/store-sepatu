<?php
/**
 * Admin - Kelola Produk & Ukuran
 * QxuanStore - Shoe E-Commerce
 */

$pageTitle = 'Kelola Produk Sepatu';
require_once dirname(__DIR__) . '/includes/header.php';

$search = trim($_GET['search'] ?? '');
$categoryFilter = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

$where = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $where .= " AND p.nama_produk LIKE ?";
    $params[] = '%' . $search . '%';
}

if ($categoryFilter > 0) {
    $where .= " AND p.id_category = ?";
    $params[] = $categoryFilter;
}

$products = db_fetch_all("
    SELECT p.*, c.nama_category 
    FROM tb_produk p 
    LEFT JOIN tb_category c ON p.id_category = c.id_category 
    $where 
    ORDER BY p.id_produk DESC
", $params);

$allCategories = db_fetch_all("SELECT * FROM tb_category ORDER BY nama_category ASC");
?>

<div class="card-panel">
    <div class="panel-header">
        <h2><i class="fa-solid fa-boxes-stacked" style="color: #2563eb; margin-right: 8px;"></i> Katalog Produk Sepatu</h2>
        <a href="<?= admin_url('products/create.php') ?>" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> Tambah Sepatu Baru
        </a>
    </div>

    <!-- Search & Filter Bar -->
    <form action="" method="GET" class="admin-filter-bar" style="align-items: center;">
        <div class="filter-group filter-search" style="margin: 0;">
            <input type="text" name="search" class="form-control" placeholder="Cari nama sepatu..." value="<?= htmlspecialchars($search) ?>" style="font-size: 0.85rem;">
        </div>
        
        <div class="filter-group" style="margin: 0;">
            <select name="cat" class="form-control" style="font-size: 0.85rem;" onchange="this.form.submit()">
                <option value="">Semua Kategori</option>
                <?php foreach ($allCategories as $cat): ?>
                    <option value="<?= $cat->id_category ?>" <?= $categoryFilter == $cat->id_category ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat->nama_category) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary btn-sm" style="padding: 9px 14px;">
                <i class="fa-solid fa-magnifying-glass"></i> Filter
            </button>
            <?php if (!empty($search) || $categoryFilter > 0): ?>
                <a href="<?= admin_url('products/index.php') ?>" class="btn btn-sm" style="color: #ef4444; padding: 9px 12px;">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-responsive">
        <table class="modern-table">
            <thead>
                <tr>
                    <th width="70">Foto</th>
                    <th>Nama Sepatu</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Pilihan Ukuran</th>
                    <th>Status</th>
                    <th width="140" style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $p): ?>
                        <?php
                            // Fetch sizes for this product
                            $sizes = db_fetch_all("SELECT ukuran, stok FROM tb_produk_size WHERE id_produk = ? ORDER BY ukuran ASC", [$p->id_produk]);
                        ?>
                        <tr>
                            <td>
                                <a href="<?= product_image_url($p->image_produk) ?>" target="_blank">
                                    <img src="<?= product_image_url($p->image_produk) ?>" alt="<?= htmlspecialchars($p->nama_produk) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                                </a>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($p->nama_produk) ?></strong>
                                <div style="font-size: 0.78rem; color: #64748b; margin-top: 2px;">
                                    ID: #<?= $p->id_produk ?> &bull; Ditambahkan: <?= date('d M Y', strtotime($p->date_created)) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-gray"><?= htmlspecialchars($p->nama_category ?? 'Tanpa Kategori') ?></span>
                            </td>
                            <td>
                                <strong style="color: #2563eb;"><?= format_rupiah($p->harga_produk) ?></strong>
                            </td>
                            <td>
                                <?php if (!empty($sizes)): ?>
                                    <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                        <?php foreach ($sizes as $s): ?>
                                            <span class="badge badge-info" title="Stok: <?= $s->stok ?> pcs" style="font-size: 0.72rem;">
                                                <?= htmlspecialchars($s->ukuran) ?> (<?= $s->stok ?>)
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; color: #94a3b8;">Belum diatur</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p->status_produk == 1): ?>
                                    <span class="badge badge-success">In Stock</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Sold Out</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?= admin_url('products/edit.php?id=' . $p->id_produk) ?>" class="btn btn-secondary btn-sm" title="Edit Produk">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteProduct(<?= $p->id_produk ?>, '<?= addslashes(htmlspecialchars($p->nama_produk)) ?>')" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #64748b; padding: 32px;">
                            Tidak ada produk ditemukan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmDeleteProduct(id, name) {
    Swal.fire({
        title: 'Hapus Sepatu?',
        text: `Sepatu "${name}" beserta seluruh data stok ukurannya akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= admin_url("products/delete.php?id=") ?>' + id;
        }
    });
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
