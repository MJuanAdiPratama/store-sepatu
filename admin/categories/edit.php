<?php
/**
 * Admin - Edit Kategori
 * QxuanStore - Shoe E-Commerce
 */

$pageTitle = 'Edit Kategori';
require_once dirname(__DIR__) . '/includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$cat = db_fetch_one("SELECT * FROM tb_category WHERE id_category = ?", [$id]);

if (!$cat) {
    set_flash('error', 'Kategori tidak ditemukan!');
    redirect(admin_url('categories/index.php'));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = ucwords(trim($_POST['nama_category'] ?? ''));

    if (empty($nama)) {
        $error = 'Nama kategori tidak boleh kosong!';
    } else {
        $update = db_execute("UPDATE tb_category SET nama_category = ? WHERE id_category = ?", [$nama, $id]);
        if ($update) {
            set_flash('success', 'Kategori berhasil diperbarui!');
            redirect(admin_url('categories/index.php'));
        } else {
            $error = 'Gagal memperbarui kategori.';
        }
    }
}
?>

<div class="card-panel" style="max-width: 600px;">
    <div class="panel-header">
        <h2><i class="fa-solid fa-pen-to-square" style="color: #2563eb; margin-right: 8px;"></i> Edit Kategori</h2>
        <a href="<?= admin_url('categories/index.php') ?>" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label for="nama_category">Nama Kategori Sepatu</label>
            <input type="text" id="nama_category" name="nama_category" class="form-control" value="<?= htmlspecialchars($_POST['nama_category'] ?? $cat->nama_category) ?>" required autofocus>
        </div>

        <div style="margin-top: 24px; display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-check"></i> Simpan Perubahan
            </button>
            <a href="<?= admin_url('categories/index.php') ?>" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
