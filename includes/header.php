<?php
/**
 * Public Site Header & Navigation
 * QxuanStore - Shoe E-Commerce
 */

$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';

$storeContact = get_admin_contact();
$user = current_user();

// Calculate cart count only if user is logged in
$cartCount = 0;
if (is_user() && isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)($item['qty'] ?? 1);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | QxuanStore' : 'QxuanStore - Toko Sepatu Sneakers & Casual Original' ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Modern Main CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= file_exists($rootPath . '/assets/css/style.css') ? filemtime($rootPath . '/assets/css/style.css') : time() ?>">
</head>
<body>

    <!-- Top Announcement Bar -->
    <div class="announcement-bar">
        <div class="container">
            👟 <strong>QxuanStore Official:</strong> Koleksi Sepatu Sneakers & Casual Pilihan &bull; Garansi Kualitas &bull; Kirim ke Seluruh Indonesia
        </div>
    </div>

    <!-- Main Navigation Header -->
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <!-- Brand Logo -->
                <a href="<?= base_url('index.php') ?>" class="logo">
                    <i class="fa-solid fa-shoe-prints"></i>
                    <div>Qxuan<span>Store</span></div>
                </a>

                <!-- Expanded Responsive Search Bar (Navbar links removed per request) -->
                <form action="<?= base_url('products.php') ?>" method="GET" class="header-search expanded-search" onsubmit="return handleSearchSubmit(event, this)">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" name="search" placeholder="Cari model sepatu, sneakers impianmu..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button type="submit" class="search-submit-btn">Cari</button>
                </form>

                <!-- Header Actions -->
                <div class="header-actions">
                    <!-- Cart Button (Protected for guests) -->
                    <?php if (is_user()): ?>
                        <a href="<?= user_url('cart.php') ?>" class="icon-btn cart-btn">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span class="action-text">Keranjang</span>
                            <?php if ($cartCount > 0): ?>
                                <span class="cart-count"><?= $cartCount ?></span>
                            <?php endif; ?>
                        </a>
                    <?php else: ?>
                        <a href="javascript:void(0)" onclick="requireLogin('Melihat keranjang belanja')" class="icon-btn cart-btn">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span class="action-text">Keranjang</span>
                        </a>
                    <?php endif; ?>

                    <!-- User Account / Login Button -->
                    <?php if (is_user()): ?>
                        <a href="<?= user_url('profile.php') ?>" class="icon-btn user-btn" title="Akun Saya">
                            <div class="user-avatar-mini"><?= strtoupper(substr($user->nama_lengkap, 0, 1)) ?></div>
                            <span class="action-text"><?= htmlspecialchars(explode(' ', $user->nama_lengkap)[0]) ?></span>
                        </a>
                    <?php else: ?>
                        <a href="<?= base_url('auth/login.php') ?>" class="icon-btn login-btn">
                            <i class="fa-regular fa-user"></i>
                            <span>Masuk / Daftar</span>
                        </a>
                    <?php endif; ?>

                    <!-- Admin Portal Shortcut (HANYA MUNCUL JIKA USER BELUM LOGIN) -->
                    <?php if (!is_user()): ?>
                        <a href="<?= admin_url('login.php') ?>" class="icon-btn admin-btn" title="Masuk sebagai Administrator Toko">
                            <i class="fa-solid fa-lock"></i>
                            <span class="admin-text">Admin</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <script>
    const IS_USER_LOGGED_IN = <?= is_user() ? 'true' : 'false' ?>;

    function requireLogin(actionDesc = 'melihat detail produk dan berbelanja') {
        Swal.fire({
            title: 'Silakan Masuk Terlebih Dahulu',
            text: `Untuk ${actionDesc}, Anda perlu masuk ke akun customer QxuanStore terlebih dahulu.`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa-solid fa-right-to-bracket"></i> Masuk Sekarang',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '<?= base_url("auth/login.php") ?>';
            }
        });
    }

    function handleSearchSubmit(e, form) {
        if (!IS_USER_LOGGED_IN) {
            e.preventDefault();
            requireLogin('melakukan pencarian sepatu');
            return false;
        }
        return true;
    }
    </script>
