<?php
/**
 * Midtrans Webhook Notification Handler
 * QxuanStore - Shoe E-Commerce
 */

header('Content-Type: application/json; charset=UTF-8');

$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/midtrans.php';

$jsonStr = file_get_contents('php://input');
if (empty($jsonStr)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Empty payload']);
    exit;
}

$notif = json_decode($jsonStr, true);
if (!$notif || !isset($notif['order_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$orderId     = $notif['order_id'];
$statusCode  = $notif['status_code'] ?? '';
$grossAmount = $notif['gross_amount'] ?? '';
$serverKey   = MIDTRANS_SERVER_KEY;
$signature   = $notif['signature_key'] ?? '';
$transStatus = $notif['transaction_status'] ?? '';
$fraudStatus = $notif['fraud_status'] ?? 'accept';
$paymentType = $notif['payment_type'] ?? 'midtrans';

// Optional signature check in production or when key is real
$expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
$signatureValid = ($signature === $expectedSignature) || (strpos($serverKey, 'Demo') !== false);

if (!$signatureValid) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid signature key']);
    exit;
}

// Map payment type to human friendly name
$methodName = 'Midtrans Gateway (' . strtoupper(str_replace('_', ' ', $paymentType)) . ')';

// Process order status based on Midtrans transaction status
if ($transStatus === 'capture') {
    if ($fraudStatus === 'accept') {
        db_execute("UPDATE tb_order SET status_order = 'Diproses', metode_pembayaran = ?, waktu_bayar = NOW() WHERE invoice_no = ?", [$methodName, $orderId]);
    }
} elseif ($transStatus === 'settlement') {
    db_execute("UPDATE tb_order SET status_order = 'Diproses', metode_pembayaran = ?, waktu_bayar = NOW() WHERE invoice_no = ?", [$methodName, $orderId]);
} elseif (in_array($transStatus, ['cancel', 'deny', 'expire'])) {
    db_execute("UPDATE tb_order SET status_order = 'Dibatalkan' WHERE invoice_no = ?", [$orderId]);
} elseif ($transStatus === 'pending') {
    db_execute("UPDATE tb_order SET status_order = 'Menunggu Pembayaran', metode_pembayaran = ? WHERE invoice_no = ?", [$methodName, $orderId]);
}

echo json_encode(['status' => 'ok', 'order_id' => $orderId, 'transaction_status' => $transStatus]);
