<?php
/**
 * Admin - Hapus Kategori
 * QxuanStore - Shoe E-Commerce
 */

$rootPath = dirname(dirname(__DIR__));
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';

require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Check if category has products
    $check = db_fetch_one("SELECT COUNT(*) AS total FROM tb_produk WHERE id_category = ?", [$id]);
    if ($check && $check->total > 0) {
        set_flash('error', 'Kategori tidak dapat dihapus karena masih memiliki ' . $check->total . ' produk terkait!');
    } else {
        $del = db_execute("DELETE FROM tb_category WHERE id_category = ?", [$id]);
        if ($del) {
            set_flash('success', 'Kategori berhasil dihapus.');
        } else {
            set_flash('error', 'Gagal menghapus kategori.');
        }
    }
}

redirect(admin_url('categories/index.php'));
