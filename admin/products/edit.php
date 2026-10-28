<?php
/**
 * Admin - Edit Produk Sepatu
 * QxuanStore - Shoe E-Commerce
 */

$pageTitle = 'Edit Produk Sepatu';
require_once dirname(__DIR__) . '/includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = db_fetch_one("SELECT * FROM tb_produk WHERE id_produk = ?", [$id]);

if (!$product) {
    set_flash('error', 'Produk tidak ditemukan!');
    redirect(admin_url('products/index.php'));
}

$categories = db_fetch_all("SELECT * FROM tb_category ORDER BY nama_category ASC");
$existingSizes = db_fetch_all("SELECT ukuran, stok FROM tb_produk_size WHERE id_produk = ?", [$id]);

// Map existing sizes to array [size => stock]
$sizeMap = [];
foreach ($existingSizes as $s) {
    $sizeMap[$s->ukuran] = (int)$s->stok;
}

$availableSizes = ['38', '39', '40', '41', '42', '43', '44'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int)($_POST['category'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $price       = (int)str_replace(['.', ',', ' '], '', $_POST['price'] ?? '0');
    $description = trim($_POST['description'] ?? '');
    $status      = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    $selectedSizes = $_POST['sizes'] ?? [];
    $sizeStocks    = $_POST['stock'] ?? [];

    if (empty($name) || $category_id <= 0 || $price <= 0) {
        $error = 'Nama sepatu, kategori, dan harga wajib diisi!';
    } else {
        $imageToSave = $product->image_produk;

        // If a new image was uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileInfo = pathinfo($_FILES['image']['name']);
            $ext = strtolower($fileInfo['extension'] ?? '');
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowedExts)) {
                $error = 'Format foto tidak valid! Gunakan JPG, PNG, atau WEBP.';
            } else {
                $newFilename = 'shoe_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $uploadDir = dirname(dirname(__DIR__)) . '/assets/uploads/products/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFilename)) {
                    // Remove old image if stored in uploads directory
                    if (!empty($product->image_produk) && file_exists($uploadDir . $product->image_produk)) {
                        @unlink($uploadDir . $product->image_produk);
                    }
                    $imageToSave = $newFilename;
                }
            }
        }

        if (empty($error)) {
            $update = db_execute("
                UPDATE tb_produk 
                SET id_category = ?, nama_produk = ?, harga_produk = ?, deskripsi_produk = ?, image_produk = ?, status_produk = ? 
                WHERE id_produk = ?
            ", [$category_id, $name, $price, $description, $imageToSave, $status, $id]);

            // Update sizes: delete existing and re-insert selected
            db_execute("DELETE FROM tb_produk_size WHERE id_produk = ?", [$id]);
            if (!empty($selectedSizes)) {
                foreach ($selectedSizes as $sz) {
                    $stk = isset($sizeStocks[$sz]) ? (int)$sizeStocks[$sz] : 0;
                    db_execute("INSERT INTO tb_produk_size (id_produk, ukuran, stok) VALUES (?, ?, ?)", [$id, $sz, $stk]);
                }
            }

            set_flash('success', 'Produk sepatu "' . $name . '" berhasil diperbarui!');
            redirect(admin_url('products/index.php'));
        }
    }
}
?>

<div class="card-panel" style="max-width: 800px;">
    <div class="panel-header">
        <h2><i class="fa-solid fa-pen-to-square" style="color: #2563eb; margin-right: 8px;"></i> Edit Produk Sepatu</h2>
        <a href="<?= admin_url('products/index.php') ?>" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="grid-2-col">
            <div class="form-group">
                <label for="name">Nama Sepatu <span style="color: #ef4444;">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? $product->nama_produk) ?>" required>
            </div>

            <div class="form-group">
                <label for="category">Kategori <span style="color: #ef4444;">*</span></label>
                <select id="category" name="category" class="form-control" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat->id_category ?>" <?= ((isset($_POST['category']) ? $_POST['category'] : $product->id_category) == $cat->id_category) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat->nama_category) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid-2-col">
            <div class="form-group">
                <label for="price">Harga Sepatu (Rp) <span style="color: #ef4444;">*</span></label>
                <input type="number" id="price" name="price" class="form-control" value="<?= htmlspecialchars($_POST['price'] ?? $product->harga_produk) ?>" min="1000" required>
            </div>

            <div class="form-group">
                <label for="status">Status Ketersediaan</label>
                <select id="status" name="status" class="form-control">
                    <option value="1" <?= ((isset($_POST['status']) ? $_POST['status'] : $product->status_produk) == 1) ? 'selected' : '' ?>>In Stock (Tersedia)</option>
                    <option value="0" <?= ((isset($_POST['status']) ? $_POST['status'] : $product->status_produk) == 0) ? 'selected' : '' ?>>Sold Out (Habis)</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Foto Produk Saat Ini</label>
            <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 10px;">
                <img src="<?= product_image_url($product->image_produk) ?>" alt="Foto Sepatu" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1;">
                <div>
                    <label for="image" style="margin-bottom: 4px;">Ganti Foto (Opsional)</label>
                    <input type="file" id="image" name="image" class="form-control" accept="image/*">
                    <small style="color: #64748b; font-size: 0.8rem;">Biarkan kosong jika tidak ingin mengubah foto saat ini.</small>
                </div>
            </div>
        </div>

        <!-- Shoe Sizes & Stocks -->
        <div class="form-group" style="background: #f8fafc; padding: 18px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <label style="margin-bottom: 4px;">Pilihan Ukuran & Kelola Stok</label>
            <p style="font-size: 0.82rem; color: #64748b; margin-bottom: 12px;">Centang ukuran yang tersedia dan perbarui jumlah stok masing-masing ukuran:</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px;">
                <?php foreach ($availableSizes as $sz): ?>
                    <?php 
                        $isChecked = isset($sizeMap[$sz]);
                        $currentStock = $sizeMap[$sz] ?? 10;
                    ?>
                    <div style="background: #fff; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1;">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: 700; cursor: pointer; margin-bottom: 6px;">
                            <input type="checkbox" name="sizes[]" value="<?= $sz ?>" <?= $isChecked ? 'checked' : '' ?>>
                            Size <?= $sz ?>
                        </label>
                        <input type="number" name="stock[<?= $sz ?>]" class="form-control" value="<?= $currentStock ?>" min="0" placeholder="Stok" style="padding: 6px 8px; font-size: 0.85rem;">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Deskripsi Produk</label>
            <textarea id="description" name="description" class="form-control"><?= htmlspecialchars($_POST['description'] ?? $product->deskripsi_produk) ?></textarea>
        </div>

        <div style="margin-top: 28px; display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                <i class="fa-solid fa-check"></i> Simpan Perubahan
            </button>
            <a href="<?= admin_url('products/index.php') ?>" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
