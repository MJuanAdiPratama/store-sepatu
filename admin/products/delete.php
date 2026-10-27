<?php
/**
 * Admin - Hapus Produk Sepatu
 * QxuanStore - Shoe E-Commerce
 */

$rootPath = dirname(dirname(__DIR__));
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';

require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $product = db_fetch_one("SELECT * FROM tb_produk WHERE id_produk = ?", [$id]);
    if ($product) {
        // Delete image file if exists in uploads directory
        $uploadDir = $rootPath . '/assets/uploads/products/';
        if (!empty($product->image_produk) && file_exists($uploadDir . $product->image_produk)) {
            @unlink($uploadDir . $product->image_produk);
        }

        // Delete sizes
        db_execute("DELETE FROM tb_produk_size WHERE id_produk = ?", [$id]);

        // Delete product record
        $del = db_execute("DELETE FROM tb_produk WHERE id_produk = ?", [$id]);

        if ($del) {
            set_flash('success', 'Produk sepatu berhasil dihapus.');
        } else {
            set_flash('error', 'Gagal menghapus produk dari database.');
        }
    } else {
        set_flash('error', 'Produk tidak ditemukan.');
    }
}

redirect(admin_url('products/index.php'));
