<?php
/**
 * Admin - Tambah Produk Sepatu Baru
 * QxuanStore - Shoe E-Commerce
 */

$pageTitle = 'Tambah Sepatu Baru';
require_once dirname(__DIR__) . '/includes/header.php';

$categories = db_fetch_all("SELECT * FROM tb_category ORDER BY nama_category ASC");
$error = '';

$availableSizes = ['38', '39', '40', '41', '42', '43', '44'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int)($_POST['category'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $price       = (int)str_replace(['.', ',', ' '], '', $_POST['price'] ?? '0');
    $description = trim($_POST['description'] ?? '');
    $status      = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    $selectedSizes = $_POST['sizes'] ?? []; // array of selected sizes
    $sizeStocks    = $_POST['stock'] ?? []; // array size => stock

    if (empty($name) || $category_id <= 0 || $price <= 0) {
        $error = 'Nama sepatu, kategori, dan harga wajib diisi dengan benar!';
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Foto sepatu wajib diunggah!';
    } else {
        // Validate and handle file upload
        $fileInfo = pathinfo($_FILES['image']['name']);
        $ext = strtolower($fileInfo['extension'] ?? '');
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowedExts)) {
            $error = 'Format file tidak diizinkan! Harap unggah foto berekstensi JPG, JPEG, PNG, atau WEBP.';
        } else {
            $newFilename = 'shoe_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $uploadDir = dirname(dirname(__DIR__)) . '/assets/uploads/products/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFilename)) {
                // Insert into tb_produk
                $sql = "INSERT INTO tb_produk (id_category, nama_produk, harga_produk, deskripsi_produk, image_produk, status_produk) VALUES (?, ?, ?, ?, ?, ?)";
                $inserted = db_execute($sql, [$category_id, $name, $price, $description, $newFilename, $status]);

                if ($inserted) {
                    $productId = db_insert_id();

                    // Insert shoe sizes
                    if (!empty($selectedSizes)) {
                        foreach ($selectedSizes as $sz) {
                            $stk = isset($sizeStocks[$sz]) ? (int)$sizeStocks[$sz] : 10;
                            db_execute("INSERT INTO tb_produk_size (id_produk, ukuran, stok) VALUES (?, ?, ?)", [$productId, $sz, $stk]);
                        }
                    }

                    set_flash('success', 'Produk sepatu "' . $name . '" berhasil ditambahkan!');
                    redirect(admin_url('products/index.php'));
                } else {
                    $error = 'Gagal menyimpan data produk ke database.';
                }
            } else {
                $error = 'Gagal mengunggah file foto. Pastikan folder uploads memiliki izin tulis.';
            }
        }
    }
}
?>

<div class="card-panel" style="max-width: 800px;">
    <div class="panel-header">
        <h2><i class="fa-solid fa-plus" style="color: #2563eb; margin-right: 8px;"></i> Form Tambah Sepatu</h2>
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
                <input type="text" id="name" name="name" class="form-control" placeholder="Contoh: Ventela Basic Low Black" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="category">Kategori <span style="color: #ef4444;">*</span></label>
                <select id="category" name="category" class="form-control" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat->id_category ?>" <?= (isset($_POST['category']) && $_POST['category'] == $cat->id_category) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat->nama_category) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid-2-col">
            <div class="form-group">
                <label for="price">Harga Sepatu (Rp) <span style="color: #ef4444;">*</span></label>
                <input type="number" id="price" name="price" class="form-control" placeholder="Contoh: 159000" min="1000" required value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="status">Status Ketersediaan</label>
                <select id="status" name="status" class="form-control">
                    <option value="1" <?= (!isset($_POST['status']) || $_POST['status'] == '1') ? 'selected' : '' ?>>In Stock (Tersedia)</option>
                    <option value="0" <?= (isset($_POST['status']) && $_POST['status'] == '0') ? 'selected' : '' ?>>Sold Out (Habis)</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="image">Foto Produk Sepatu <span style="color: #ef4444;">*</span></label>
            <input type="file" id="image" name="image" class="form-control" accept="image/*" required>
            <small style="color: #64748b; font-size: 0.8rem;">Format: JPG, JPEG, PNG, WEBP. Maksimal 3MB.</small>
        </div>

        <!-- Shoe Sizes & Stocks -->
        <div class="form-group" style="background: #f8fafc; padding: 18px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <label style="margin-bottom: 4px;">Pilihan Ukuran & Stok Awal</label>
            <p style="font-size: 0.82rem; color: #64748b; margin-bottom: 12px;">Centang ukuran yang tersedia untuk sepatu ini dan tentukan stok awalnya:</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px;">
                <?php foreach ($availableSizes as $sz): ?>
                    <div style="background: #fff; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1;">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: 700; cursor: pointer; margin-bottom: 6px;">
                            <input type="checkbox" name="sizes[]" value="<?= $sz ?>" checked>
                            Size <?= $sz ?>
                        </label>
                        <input type="number" name="stock[<?= $sz ?>]" class="form-control" value="10" min="0" placeholder="Stok" style="padding: 6px 8px; font-size: 0.85rem;">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Deskripsi Produk</label>
            <textarea id="description" name="description" class="form-control" placeholder="Tuliskan spesifikasi bahan, insole, outsole, warna, dan keunggulan sepatu..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div style="margin-top: 28px; display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Produk
            </button>
            <a href="<?= admin_url('products/index.php') ?>" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
