<?php
/**
 * Customer Order Received Confirmation Endpoint
 * QxuanStore - Shoe E-Commerce
 */

header('Content-Type: application/json; charset=UTF-8');

$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';

if (!is_user()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Silakan masuk ke akun customer Anda terlebih dahulu.']);
    exit;
}

$userId  = $_SESSION['user_id'];
$invoice = trim($_POST['invoice'] ?? ($_GET['invoice'] ?? ''));

if (empty($invoice)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nomor invoice pesanan wajib disertakan!']);
    exit;
}

// Fetch order belonging to this customer
$order = db_fetch_one("SELECT * FROM tb_order WHERE invoice_no = ? AND user_id = ? LIMIT 1", [$invoice, $userId]);

if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan atau bukan milik akun Anda!']);
    exit;
}

if ($order->status_order === 'Selesai') {
    echo json_encode([
        'success'        => true,
        'message'        => 'Pesanan ini sudah selesai sebelumnya.',
        'status'         => 'Selesai',
        'waktu_diterima' => $order->waktu_diterima
    ]);
    exit;
}

// Update order status to Selesai with receipt timestamp
$now = date('Y-m-d H:i:s');
$updated = db_execute("
    UPDATE tb_order 
    SET status_order = 'Selesai', waktu_diterima = ? 
    WHERE id_order = ?
", [$now, $order->id_order]);

if ($updated) {
    set_flash('success', 'Terima kasih! Pesanan #' . $invoice . ' telah berhasil dikonfirmasi sampai. Selamat menikmati sepatu baru Anda!');
    echo json_encode([
        'success'        => true,
        'message'        => 'Konfirmasi penerimaan berhasil dicatat! Status pesanan kini Selesai.',
        'status'         => 'Selesai',
        'invoice'        => $invoice,
        'waktu_diterima' => $now
    ]);
    exit;
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kendala sistem saat memperbarui status pesanan.']);
    exit;
}
