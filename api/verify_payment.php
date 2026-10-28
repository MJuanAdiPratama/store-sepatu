<?php
/**
 * Instant Payment Verification Endpoint
 * QxuanStore - Midtrans Payment Gateway
 */

header('Content-Type: application/json; charset=UTF-8');

$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';

$invoice = trim($_POST['invoice'] ?? ($_GET['invoice'] ?? ''));
$method  = trim($_POST['method'] ?? ($_GET['method'] ?? 'Midtrans QRIS / Virtual Account'));

if (empty($invoice)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nomor invoice wajib disertakan!']);
    exit;
}

$order = db_fetch_one("SELECT * FROM tb_order WHERE invoice_no = ? LIMIT 1", [$invoice]);

if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Pesanan dengan invoice tersebut tidak ditemukan!']);
    exit;
}

// Update order status if still waiting for payment
if ($order->status_order === 'Menunggu Pembayaran') {
    $now = date('Y-m-d H:i:s');
    $updated = db_execute("
        UPDATE tb_order 
        SET status_order = 'Diproses', metode_pembayaran = ?, waktu_bayar = ? 
        WHERE id_order = ?
    ", [$method, $now, $order->id_order]);

    if ($updated) {
        set_flash('success', 'Pembayaran pesanan #' . $invoice . ' berhasil diterima via ' . $method . '! Pesanan Anda kini sedang diproses toko.');
        echo json_encode([
            'success'     => true,
            'message'     => 'Pembayaran berhasil dikonfirmasi! Status pesanan kini Diproses.',
            'status'      => 'Diproses',
            'invoice'     => $invoice,
            'method'      => $method,
            'waktu_bayar' => $now
        ]);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status pesanan di database.']);
        exit;
    }
} else {
    // Already paid/processed
    echo json_encode([
        'success'     => true,
        'message'     => 'Pesanan sudah berstatus ' . $order->status_order . '.',
        'status'      => $order->status_order,
        'invoice'     => $invoice,
        'method'      => $order->metode_pembayaran,
        'waktu_bayar' => $order->waktu_bayar
    ]);
    exit;
}
