<?php
/**
 * Payment Verification & Proof Upload Endpoint
 * QxuanStore - Direct Interactive Payment
 */

header('Content-Type: application/json; charset=UTF-8');

$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';

$invoice = trim($_POST['invoice'] ?? ($_GET['invoice'] ?? ''));
$method  = trim($_POST['method'] ?? ($_GET['method'] ?? 'QRIS (E-Wallet)'));

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

// Handle file upload for proof of payment (bukti_pembayaran)
$uploadedFileName = null;
if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] === UPLOAD_ERR_OK) {
    $fileTmp  = $_FILES['bukti_pembayaran']['tmp_name'];
    $fileSize = $_FILES['bukti_pembayaran']['size'];
    $fileInfo = pathinfo($_FILES['bukti_pembayaran']['name']);
    $fileExt  = strtolower($fileInfo['extension'] ?? '');

    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($fileExt, $allowedExts)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Format file bukti tidak valid! Gunakan format JPG, JPEG, PNG, atau WEBP.']);
        exit;
    }

    if ($fileSize > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ukuran file bukti maksimal 5MB!']);
        exit;
    }

    $buktiDir = $rootPath . '/assets/uploads/bukti';
    if (!is_dir($buktiDir)) {
        mkdir($buktiDir, 0777, true);
    }

    $safeInvoice = preg_replace('/[^a-zA-Z0-9_-]/', '', $invoice);
    $newFileName = 'bukti_' . $safeInvoice . '_' . time() . '.' . $fileExt;
    $targetPath  = $buktiDir . '/' . $newFileName;

    if (move_uploaded_file($fileTmp, $targetPath)) {
        $uploadedFileName = $newFileName;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal mengunggah file bukti pembayaran ke server.']);
        exit;
    }
}

// Update order status if still waiting for payment
if ($order->status_order === 'Menunggu Pembayaran' || !empty($uploadedFileName)) {
    $now = date('Y-m-d H:i:s');
    
    if (!empty($uploadedFileName)) {
        $updated = db_execute("
            UPDATE tb_order 
            SET status_order = 'Diproses', metode_pembayaran = ?, bukti_pembayaran = ?, waktu_bayar = ? 
            WHERE id_order = ?
        ", [$method, $uploadedFileName, $now, $order->id_order]);
    } else {
        $updated = db_execute("
            UPDATE tb_order 
            SET status_order = 'Diproses', metode_pembayaran = ?, waktu_bayar = ? 
            WHERE id_order = ?
        ", [$method, $now, $order->id_order]);
    }

    if ($updated) {
        $successMsg = !empty($uploadedFileName) 
            ? 'Bukti pembayaran berhasil diunggah! Status pesanan kini Diproses.' 
            : 'Pembayaran pesanan #' . $invoice . ' berhasil dikonfirmasi via ' . $method . '!';
            
        set_flash('success', $successMsg);
        echo json_encode([
            'success'          => true,
            'message'          => $successMsg,
            'status'           => 'Diproses',
            'invoice'          => $invoice,
            'method'           => $method,
            'bukti_pembayaran' => $uploadedFileName,
            'waktu_bayar'      => $now
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
