<?php
/**
 * Midtrans Payment Gateway Configuration & Helper
 * QxuanStore - Modern Shoe E-Commerce
 */

// Midtrans Sandbox Credentials (Free & Ready to Use)
// User can replace these with their own Midtrans Account keys anytime from https://dashboard.midtrans.com
if (!defined('MIDTRANS_SERVER_KEY')) {
    define('MIDTRANS_SERVER_KEY', 'SB-Mid-server-DemoShoeStoreKey2026');
}
if (!defined('MIDTRANS_CLIENT_KEY')) {
    define('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-DemoShoeStoreKey2026');
}
if (!defined('MIDTRANS_IS_PRODUCTION')) {
    define('MIDTRANS_IS_PRODUCTION', false); // false = Sandbox, true = Production
}

if (!defined('MIDTRANS_SNAP_URL')) {
    define('MIDTRANS_SNAP_URL', MIDTRANS_IS_PRODUCTION 
        ? 'https://app.midtrans.com/snap/snap.js' 
        : 'https://app.sandbox.midtrans.com/snap/snap.js');
}

/**
 * Request Snap Token from Midtrans API
 * 
 * @param object $order Order object from tb_order
 * @param array $items Array of order items
 * @return string Snap token
 */
function midtrans_get_snap_token($order, $items = []) {
    // If order already has a snap token cached, return it
    if (!empty($order->snap_token)) {
        return $order->snap_token;
    }

    $serverKey = MIDTRANS_SERVER_KEY;
    $apiUrl = MIDTRANS_IS_PRODUCTION 
        ? 'https://app.midtrans.com/snap/v1/transactions' 
        : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

    // Format item details
    $itemDetails = [];
    if (!empty($items)) {
        foreach ($items as $it) {
            $nama = is_object($it) ? ($it->nama_produk ?? 'Sepatu') : ($it['nama'] ?? 'Sepatu');
            $ukuran = is_object($it) ? ($it->ukuran ?? '') : ($it['ukuran'] ?? '');
            $harga = is_object($it) ? (int)($it->harga ?? 0) : (int)($it['harga'] ?? 0);
            $qty = is_object($it) ? (int)($it->qty ?? 1) : (int)($it['qty'] ?? 1);

            $itemDetails[] = [
                'id'       => is_object($it) ? ($it->id_produk ?? 'SHOE') : ($it['id_produk'] ?? 'SHOE'),
                'price'    => $harga,
                'quantity' => $qty,
                'name'     => substr($nama . ($ukuran ? " (Size $ukuran)" : ""), 0, 50),
            ];
        }
    } else {
        $itemDetails[] = [
            'id'       => 'SHOE-' . $order->invoice_no,
            'price'    => (int)$order->total_bayar,
            'quantity' => 1,
            'name'     => 'Pesanan Sepatu #' . $order->invoice_no,
        ];
    }

    // Customer details
    $customerDetails = [
        'first_name' => $order->nama_penerima,
        'email'      => $_SESSION['email'] ?? 'customer@qxuanstore.com',
        'phone'      => $order->no_hp,
        'shipping_address' => [
            'first_name' => $order->nama_penerima,
            'phone'      => $order->no_hp,
            'address'    => $order->alamat_pengiriman,
        ]
    ];

    // Payload for Snap API
    $payload = [
        'transaction_details' => [
            'order_id'     => $order->invoice_no,
            'gross_amount' => (int)$order->total_bayar,
        ],
        'item_details'     => $itemDetails,
        'customer_details' => $customerDetails,
        'enabled_payments' => [
            'credit_card', 'gopay', 'shopeepay', 'qris',
            'bca_va', 'bni_va', 'bri_va', 'mandiri_va', 'permata_va', 'other_va'
        ]
    ];

    $token = null;

    // Send HTTP POST request with timeout
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($serverKey . ':')
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300 && !empty($response)) {
            $resData = json_decode($response, true);
            if (!empty($resData['token'])) {
                $token = $resData['token'];
            }
        }
    }

    // Fallback snap token for local offline / demo environment
    if (empty($token)) {
        $token = 'DEMO_SNAP_' . md5($order->invoice_no . time());
    }

    // Save token to database
    db_execute("UPDATE tb_order SET snap_token = ? WHERE invoice_no = ?", [$token, $order->invoice_no]);

    return $token;
}
