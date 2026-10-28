<?php
/**
 * Admin Panel Header & Sidebar Layout
 * QxuanStore - Shoe E-Commerce
 */

$rootPath = dirname(dirname(__DIR__));
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';

// Enforce admin login
require_admin();

$admin = current_admin();
$currentUri = str_replace('\\', '/', $_SERVER['REQUEST_URI'] ?? '');

$unreadOrdersCount = 0;
try {
    $unreadOrdersCount = (int)(db_fetch_one("SELECT COUNT(*) AS total FROM tb_order WHERE is_read = 0")->total ?? 0);
} catch (\Throwable $e) {}

function is_menu_active($keyword, $currentUri) {
    return (strpos($currentUri, $keyword) !== false) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | Admin QxuanStore' : 'Admin Panel | QxuanStore' ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome / Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Admin CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>?v=<?= file_exists($rootPath . '/assets/css/admin.css') ? filemtime($rootPath . '/assets/css/admin.css') : time() ?>">
</head>
<body>

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-shoe-prints" style="color: #38bdf8; font-size: 1.4rem;"></i>
            <h2>Qxuan<span>Store</span></h2>
        </div>
        
        <ul class="sidebar-menu">
            <li class="sidebar-heading">Menu Utama</li>
            <li>
                <a href="<?= admin_url('index.php') ?>" class="<?= is_menu_active('admin/index.php', $currentUri) ?: (basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($currentUri, 'categories') === false && strpos($currentUri, 'products') === false && strpos($currentUri, 'orders') === false ? 'active' : '') ?>">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="sidebar-heading">Katalog Toko</li>
            <li>
                <a href="<?= admin_url('categories/index.php') ?>" class="<?= is_menu_active('categories', $currentUri) ?>">
                    <i class="fa-solid fa-tags"></i>
                    <span>Kategori Sepatu</span>
                </a>
            </li>
            <li>
                <a href="<?= admin_url('products/index.php') ?>" class="<?= is_menu_active('products', $currentUri) ?>">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <span>Produk Sepatu & Ukuran</span>
                </a>
            </li>
            
            <li class="sidebar-heading">Penjualan</li>
            <li>
                <a href="<?= admin_url('orders/index.php') ?>" class="<?= is_menu_active('orders', $currentUri) ?>" style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span>Pesanan Masuk</span>
                    </div>
                    <?php if ($unreadOrdersCount > 0): ?>
                        <span class="badge" style="background: #ef4444; color: #fff; font-size: 0.72rem; padding: 2px 7px; border-radius: 99px; font-weight: 800;">
                            <?= $unreadOrdersCount ?> Baru
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="sidebar-heading">Pengaturan</li>
            <li>
                <a href="<?= admin_url('profile.php') ?>" class="<?= is_menu_active('profile.php', $currentUri) ?>">
                    <i class="fa-solid fa-user-gear"></i>
                    <span>Profil Admin</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0)" onclick="confirmAdminLogout()">
                    <i class="fa-solid fa-arrow-right-from-bracket" style="color: #f87171;"></i>
                    <span style="color: #f87171;">Log Out</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <small>QxuanStore &bull; Panel Admin</small>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="admin-main">
        <!-- Topbar with Mobile Toggle Button -->
        <header class="admin-topbar">
            <div class="topbar-title" style="display: flex; align-items: center;">
                <button type="button" class="sidebar-toggle-btn" onclick="toggleSidebar()" aria-label="Toggle Navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1><?= $pageTitle ?? 'Dashboard Admin' ?></h1>
            </div>
            
            <div class="topbar-actions" style="display: flex; align-items: center; gap: 14px;">
                <a href="<?= admin_url('orders/index.php') ?>" title="<?= $unreadOrdersCount ?> Pesanan Belum Dilihat" style="position: relative; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 10px; background: #fff; border: 1px solid #e2e8f0; color: #475569; text-decoration: none;">
                    <i class="fa-solid fa-bell" style="font-size: 1.15rem;"></i>
                    <?php if ($unreadOrdersCount > 0): ?>
                        <span style="position: absolute; top: -5px; right: -5px; background: #ef4444; color: #fff; font-size: 0.68rem; font-weight: 800; min-width: 18px; height: 18px; border-radius: 99px; display: flex; align-items: center; justify-content: center; padding: 0 4px; box-shadow: 0 2px 6px rgba(239,68,68,0.4);">
                            <?= $unreadOrdersCount ?>
                        </span>
                    <?php endif; ?>
                </a>

                <div class="admin-profile-badge">
                    <div class="avatar"><?= strtoupper(substr($admin->admin_name ?? 'A', 0, 1)) ?></div>
                    <span><?= htmlspecialchars($admin->admin_name ?? 'Admin') ?></span>
                </div>
            </div>
        </header>

        <!-- Dynamic Body Container -->
        <div class="admin-body">
            <!-- Flash Alert Messages -->
            <?php 
                $successMsg = get_flash('success');
                $errorMsg = get_flash('error');
            ?>
            <?php if ($successMsg): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($successMsg) ?>
                </div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar && overlay) {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    }
}

function confirmAdminLogout() {
    Swal.fire({
        title: 'Konfirmasi Logout',
        text: 'Apakah Anda yakin ingin keluar dari sesi admin?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Logout',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= admin_url("logout.php") ?>';
        }
    });
}

function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
