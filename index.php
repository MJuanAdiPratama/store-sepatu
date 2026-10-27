<?php
/**
 * Homepage - QxuanStore
 * Modern Shoe E-Commerce
 */

$pageTitle = 'Beranda - Toko Sepatu Sneakers & Casual Pilihan';
require_once __DIR__ . '/includes/header.php';

// Fetch categories
$categories = db_fetch_all("SELECT * FROM tb_category ORDER BY nama_category ASC");

// Fetch active featured shoes
$featuredShoes = db_fetch_all("
    SELECT p.*, c.nama_category 
    FROM tb_produk p 
    LEFT JOIN tb_category c ON p.id_category = c.id_category 
    WHERE p.status_produk = 1 
    ORDER BY p.id_produk DESC 
    LIMIT 8
");

$isLoggedIn = is_user();
?>

<!-- Hero Banner Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-grid">
            <div>
                <span class="hero-tag">Koleksi Terpopuler 2026</span>
                <h1 class="hero-title">Langkah Percaya Diri dengan <span>Sepatu Terbaik</span></h1>
                <p class="hero-desc">
                    Temukan sneakers lokal & casual berkualitas tinggi dengan desain modern dan kenyamanan maksimal untuk aktivitas harianmu.
                </p>
                <div class="hero-cta">
                    <?php if ($isLoggedIn): ?>
                        <a href="<?= base_url('products.php') ?>" class="btn btn-primary">
                            <i class="fa-solid fa-bag-shopping"></i> Belanja Sekarang
                        </a>
                    <?php else: ?>
                        <a href="javascript:void(0)" onclick="requireLogin('mulai berbelanja')" class="btn btn-primary">
                            <i class="fa-solid fa-bag-shopping"></i> Belanja Sekarang
                        </a>
                    <?php endif; ?>
                    
                    <a href="https://api.whatsapp.com/send?phone=<?= $formattedWa ?>&text=Halo%20Admin%20QxuanStore,%20saya%20mau%20konsultasi%20seputar%20ukuran%20sepatu." class="btn btn-outline-white" target="_blank">
                        <i class="fa-brands fa-whatsapp"></i> Tanya di WhatsApp
                    </a>
                </div>
            </div>
            <div style="text-align: center;">
                <?php if (!empty($featuredShoes[0])): ?>
                    <img src="<?= product_image_url($featuredShoes[0]->image_produk) ?>" alt="Featured Sneaker" style="max-height: 380px; object-fit: contain; margin: 0 auto; filter: drop-shadow(0 20px 30px rgba(0,0,0,0.5)); transform: rotate(-8deg);">
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Main Container Content -->
<div class="container">
    <!-- Category Chips (Membutuhkan login jika belum masuk) -->
    <div class="category-chips">
        <?php if ($isLoggedIn): ?>
            <a href="<?= base_url('products.php') ?>" class="chip active">
                <i class="fa-solid fa-shapes"></i> Semua Koleksi
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?= base_url('products.php?cat=' . $cat->id_category) ?>" class="chip">
                    <i class="fa-solid fa-tag"></i> <?= htmlspecialchars($cat->nama_category) ?>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <a href="javascript:void(0)" onclick="requireLogin('melihat kategori sepatu')" class="chip active">
                <i class="fa-solid fa-shapes"></i> Semua Koleksi
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="javascript:void(0)" onclick="requireLogin('melihat kategori <?= htmlspecialchars($cat->nama_category) ?>')" class="chip">
                    <i class="fa-solid fa-tag"></i> <?= htmlspecialchars($cat->nama_category) ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Product Showcase -->
    <div class="section-header">
        <div>
            <h2 class="section-title">Produk Pilihan Terbaru</h2>
            <p style="color: #64748b; font-size: 0.9rem;">Sneakers paling dicari minggu ini dengan stok terbatas</p>
        </div>
        <?php if ($isLoggedIn): ?>
            <a href="<?= base_url('products.php') ?>" style="color: #2563eb; font-weight: 700; font-size: 0.92rem; display: flex; align-items: center; gap: 6px;">
                Lihat Semua <i class="fa-solid fa-arrow-right"></i>
            </a>
        <?php else: ?>
            <a href="javascript:void(0)" onclick="requireLogin('melihat semua koleksi sepatu')" style="color: #2563eb; font-weight: 700; font-size: 0.92rem; display: flex; align-items: center; gap: 6px;">
                Lihat Semua <i class="fa-solid fa-arrow-right"></i>
            </a>
        <?php endif; ?>
    </div>

    <div class="product-grid">
        <?php if (!empty($featuredShoes)): ?>
            <?php foreach ($featuredShoes as $shoe): ?>
                <div class="product-card">
                    <div class="card-img-wrap">
                        <?php if ($isLoggedIn): ?>
                            <a href="<?= base_url('product-detail.php?id=' . $shoe->id_produk) ?>">
                                <img src="<?= product_image_url($shoe->image_produk) ?>" alt="<?= htmlspecialchars($shoe->nama_produk) ?>" loading="lazy">
                            </a>
                        <?php else: ?>
                            <a href="javascript:void(0)" onclick="requireLogin('melihat detail sepatu ini')">
                                <img src="<?= product_image_url($shoe->image_produk) ?>" alt="<?= htmlspecialchars($shoe->nama_produk) ?>" loading="lazy">
                            </a>
                        <?php endif; ?>
                        <span class="badge badge-success card-badge">In Stock</span>
                    </div>

                    <div class="card-content">
                        <span class="card-category"><?= htmlspecialchars($shoe->nama_category ?? 'Sneakers') ?></span>
                        <h3 class="card-title">
                            <?php if ($isLoggedIn): ?>
                                <a href="<?= base_url('product-detail.php?id=' . $shoe->id_produk) ?>">
                                    <?= htmlspecialchars($shoe->nama_produk) ?>
                                </a>
                            <?php else: ?>
                                <a href="javascript:void(0)" onclick="requireLogin('melihat detail sepatu ini')">
                                    <?= htmlspecialchars($shoe->nama_produk) ?>
                                </a>
                            <?php endif; ?>
                        </h3>
                        <div class="card-price"><?= format_rupiah($shoe->harga_produk) ?></div>
                        
                        <div class="card-action">
                            <?php if ($isLoggedIn): ?>
                                <a href="<?= base_url('product-detail.php?id=' . $shoe->id_produk) ?>" class="btn btn-primary">
                                    <i class="fa-solid fa-eye"></i> Detail & Pilih Ukuran
                                </a>
                            <?php else: ?>
                                <button type="button" onclick="requireLogin('melihat detail dan memilih ukuran sepatu')" class="btn btn-primary">
                                    <i class="fa-solid fa-lock"></i> Masuk untuk Beli
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column: 1 / -1; text-align: center; color: #64748b; padding: 40px;">
                Belum ada produk sepatu yang tersedia saat ini.
            </p>
        <?php endif; ?>
    </div>

    <!-- Features & Guarantee Section -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px; margin: 60px 0; background: #fff; padding: 36px; border-radius: 16px; border: 1px solid #e2e8f0;">
        <div style="display: flex; gap: 16px; align-items: center;">
            <div style="width: 54px; height: 54px; border-radius: 12px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <h4 style="font-weight: 700; font-size: 1rem; margin-bottom: 4px;">Kualitas Terjamin</h4>
                <p style="font-size: 0.85rem; color: #64748b;">Produk sepatu sneakers berkualitas dengan sol kuat dan nyaman dipakai.</p>
            </div>
        </div>

        <div style="display: flex; gap: 16px; align-items: center;">
            <div style="width: 54px; height: 54px; border-radius: 12px; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                <i class="fa-solid fa-ruler-combined"></i>
            </div>
            <div>
                <h4 style="font-weight: 700; font-size: 1rem; margin-bottom: 4px;">Pilihan Ukuran Lengkap</h4>
                <p style="font-size: 0.85rem; color: #64748b;">Tersedia berbagai pilihan size sepatu Indonesia mulai 38 hingga 44.</p>
            </div>
        </div>

        <div style="display: flex; gap: 16px; align-items: center;">
            <div style="width: 54px; height: 54px; border-radius: 12px; background: #ffedd5; color: #ea580c; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                <i class="fa-solid fa-truck-fast"></i>
            </div>
            <div>
                <h4 style="font-weight: 700; font-size: 1rem; margin-bottom: 4px;">Pengiriman Cepat</h4>
                <p style="font-size: 0.85rem; color: #64748b;">Packing rapi dengan bubble wrap dan pengiriman via kurir terpercaya.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>