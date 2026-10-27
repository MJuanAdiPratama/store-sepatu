<?php
/**
 * Admin - Data Kategori Sepatu
 * QxuanStore - Shoe E-Commerce
 */

$pageTitle = 'Kelola Kategori Sepatu';
require_once dirname(__DIR__) . '/includes/header.php';

// Fetch all categories with product counts
$categories = db_fetch_all("
    SELECT c.*, COUNT(p.id_produk) AS total_produk 
    FROM tb_category c 
    LEFT JOIN tb_produk p ON c.id_category = p.id_category 
    GROUP BY c.id_category 
    ORDER BY c.id_category DESC
");
?>

<div class="card-panel">
    <div class="panel-header">
        <h2><i class="fa-solid fa-tags" style="color: #2563eb; margin-right: 8px;"></i> Daftar Kategori Sepatu</h2>
        <a href="<?= admin_url('categories/create.php') ?>" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> Tambah Kategori Sepatu
        </a>
    </div>

    <div class="table-responsive">
        <table class="modern-table">
            <thead>
                <tr>
                    <th width="60">No</th>
                    <th>Nama Kategori Sepatu</th>
                    <th>Jumlah Produk</th>
                    <th width="160" style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($categories)): ?>
                    <?php $no = 1; foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <strong><?= htmlspecialchars($cat->nama_category) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-info"><?= $cat->total_produk ?> Produk</span>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?= admin_url('categories/edit.php?id=' . $cat->id_category) ?>" class="btn btn-secondary btn-sm" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>
                                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteCategory(<?= $cat->id_category ?>, '<?= addslashes(htmlspecialchars($cat->nama_category)) ?>')" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: #64748b; padding: 24px;">Belum ada data kategori sepatu.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmDeleteCategory(id, name) {
    Swal.fire({
        title: 'Hapus Kategori Sepatu?',
        text: `Kategori "${name}" akan dihapus dari sistem.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= admin_url("categories/delete.php?id=") ?>' + id;
        }
    });
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
