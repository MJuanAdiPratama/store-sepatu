<?php
/**
 * Admin - Tambah Kategori
 * QxuanStore - Shoe E-Commerce
 */

$pageTitle = 'Tambah Kategori Baru';
require_once dirname(__DIR__) . '/includes/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = ucwords(trim($_POST['nama_category'] ?? ''));

    if (empty($nama)) {
        $error = 'Nama kategori wajib diisi!';
    } else {
        $insert = db_execute("INSERT INTO tb_category (nama_category) VALUES (?)", [$nama]);
        if ($insert) {
            set_flash('success', 'Kategori "' . $nama . '" berhasil ditambahkan!');
            redirect(admin_url('categories/index.php'));
        } else {
            $error = 'Gagal menyimpan kategori ke database.';
        }
    }
}
?>

<div class="card-panel" style="max-width: 600px;">
    <div class="panel-header">
        <h2><i class="fa-solid fa-plus" style="color: #2563eb; margin-right: 8px;"></i> Form Tambah Kategori</h2>
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
            <input type="text" id="nama_category" name="nama_category" class="form-control" placeholder="Contoh: Sneakers, Running, Formal, Boots" required autofocus value="<?= htmlspecialchars($_POST['nama_category'] ?? '') ?>">
        </div>

        <div style="margin-top: 24px; display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Kategori
            </button>
            <a href="<?= admin_url('categories/index.php') ?>" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
