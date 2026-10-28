<?php
/**
 * Admin - Cetak Struk / Invoice Pembelian
 * QxuanStore - Shoe E-Commerce
 */

$rootPath = dirname(dirname(__DIR__));
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';

require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = db_fetch_one("SELECT * FROM tb_order WHERE id_order = ?", [$id]);

if (!$order) {
    die("Pesanan tidak ditemukan!");
}

$items = db_fetch_all("
    SELECT od.*, p.image_produk 
    FROM tb_order_detail od 
    LEFT JOIN tb_produk p ON od.id_produk = p.id_produk 
    WHERE od.id_order = ?
", [$id]);

$storeContact = get_admin_contact();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembelian #<?= htmlspecialchars($order->invoice_no) ?> - QxuanStore</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 30px 20px;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .receipt-container {
            max-width: 680px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 36px 40px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        }

        .action-bar {
            max-width: 680px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #2563eb;
            color: #fff;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
        }

        .btn-secondary {
            background-color: #e2e8f0;
            color: #334155;
        }
        .btn-secondary:hover {
            background-color: #cbd5e1;
        }

        /* Receipt Typography & Layout */
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #cbd5e1;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }

        .receipt-brand {
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 4px;
        }
        .receipt-brand span { color: #2563eb; }

        .receipt-store-info {
            font-size: 0.82rem;
            color: #64748b;
            line-height: 1.5;
        }

        .receipt-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
            padding: 14px 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .receipt-meta-col small {
            color: #64748b;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }
        .receipt-meta-col p {
            font-size: 0.88rem;
            color: #0f172a;
            font-weight: 600;
        }

        /* Items Table */
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .receipt-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 0.8rem;
            text-transform: uppercase;
            padding: 10px 12px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }

        .receipt-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
        }

        .receipt-totals {
            margin-left: auto;
            max-width: 320px;
            margin-bottom: 28px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 0.88rem;
            color: #475569;
        }

        .total-row.grand-total {
            border-top: 2px solid #0f172a;
            padding-top: 10px;
            margin-top: 6px;
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
        }

        .receipt-footer {
            border-top: 2px dashed #cbd5e1;
            padding-top: 20px;
            text-align: center;
            font-size: 0.82rem;
            color: #64748b;
            line-height: 1.6;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .receipt-container {
                border: none !important;
                box-shadow: none !important;
                padding: 10px !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>

    <!-- Action Bar (Hidden on print) -->
    <div class="action-bar no-print">
        <a href="<?= admin_url('orders/detail.php?id=' . $order->id_order) ?>" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Detail
        </a>
        <button type="button" onclick="window.print()" class="btn btn-primary">
            <i class="fa-solid fa-print"></i> Cetak Struk Sekarang (Print / PDF)
        </button>
    </div>

    <!-- Official Receipt Container -->
    <div class="receipt-container">
        <!-- Store Header -->
        <div class="receipt-header">
            <div class="receipt-brand">
                <i class="fa-solid fa-shoe-prints"></i> Qxuan<span>Store</span>
            </div>
            <div class="receipt-store-info">
                Pusat Sepatu Sneakers & Casual Berkualitas<br>
                <?= htmlspecialchars($storeContact->admin_address ?? 'Bekasi, Indonesia') ?><br>
                WhatsApp: <?= htmlspecialchars($storeContact->admin_telp ?? '082188997766') ?> &bull; Email: <?= htmlspecialchars($storeContact->admin_email ?? 'support@qxuanstore.com') ?>
            </div>
        </div>

        <!-- Receipt Meta Information -->
        <div class="receipt-meta-grid">
            <div class="receipt-meta-col">
                <small>Nomor Invoice</small>
                <p style="color: #2563eb; font-family: 'Space Mono', monospace;">#<?= htmlspecialchars($order->invoice_no) ?></p>
                <small style="margin-top: 8px;">Waktu Transaksi</small>
                <p><?= date('d F Y, H:i', strtotime($order->created_at)) ?> WIB</p>
                <small style="margin-top: 8px;">Status Pesanan</small>
                <p style="text-transform: uppercase; color: #166534;"><?= htmlspecialchars($order->status_order) ?></p>
            </div>
            <div class="receipt-meta-col">
                <small>Penerima Paket</small>
                <p><?= htmlspecialchars($order->nama_penerima) ?> (<?= htmlspecialchars($order->no_hp) ?>)</p>
                <small style="margin-top: 8px;">Ekspedisi / Kurir</small>
                <p><?= htmlspecialchars($order->kurir ?: 'J&T Express') ?></p>
                <small style="margin-top: 8px;">Nomor Resi</small>
                <p style="font-family: 'Space Mono', monospace;"><?= htmlspecialchars($order->no_resi ?: '-') ?></p>
            </div>
        </div>

        <!-- Alamat Pengiriman -->
        <div style="margin-bottom: 20px; font-size: 0.85rem; color: #334155; padding: 10px 14px; background: #fff; border-left: 3px solid #2563eb; background: #f8fafc;">
            <strong>Alamat Pengiriman:</strong> <?= htmlspecialchars($order->alamat_pengiriman) ?>
            <?php if (!empty($order->catatan)): ?>
                <br><em>Catatan: <?= htmlspecialchars($order->catatan) ?></em>
            <?php endif; ?>
        </div>

        <!-- Items Table -->
        <table class="receipt-table">
            <thead>
                <tr>
                    <th width="30">No</th>
                    <th>Nama Sepatu</th>
                    <th>Size</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Harga</th>
                    <th style="text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($items as $it): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($it->nama_produk) ?></strong></td>
                        <td>Size <?= htmlspecialchars($it->ukuran) ?></td>
                        <td style="text-align: center;"><?= $it->qty ?></td>
                        <td style="text-align: right;"><?= format_rupiah($it->harga) ?></td>
                        <td style="text-align: right; font-weight: 700;"><?= format_rupiah($it->subtotal) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals & Payment Summary -->
        <div class="receipt-totals">
            <div class="total-row">
                <span>Metode Pembayaran:</span>
                <strong><?= htmlspecialchars($order->metode_pembayaran ?: 'Midtrans Payment Gateway') ?></strong>
            </div>
            <div class="total-row">
                <span>Status Pembayaran:</span>
                <strong style="color: #166534;"><?= $order->status_order !== 'Menunggu Pembayaran' ? 'LUNAS' : 'MENUNGGU PEMBAYARAN' ?></strong>
            </div>
            <?php if (!empty($order->waktu_bayar)): ?>
                <div class="total-row">
                    <span>Waktu Pembayaran:</span>
                    <span><?= date('d/m/Y H:i', strtotime($order->waktu_bayar)) ?> WIB</span>
                </div>
            <?php endif; ?>
            <div class="total-row grand-total">
                <span>TOTAL BAYAR:</span>
                <span style="color: #2563eb;"><?= format_rupiah($order->total_bayar) ?></span>
            </div>
        </div>

        <!-- Footer Notice -->
        <div class="receipt-footer">
            <p><strong>Terima kasih telah berbelanja di QxuanStore!</strong></p>
            <p style="margin-top: 4px;">Barang yang sudah dibeli dapat ditukar ukuran maksimal 2x24 jam setelah paket diterima.</p>
            <p style="margin-top: 2px;">Simpan struk ini sebagai bukti transaksi pembelian yang sah.</p>
        </div>
    </div>

</body>
</html>

