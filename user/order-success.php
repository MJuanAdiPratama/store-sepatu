<?php
/**
 * Order Success & WhatsApp Confirmation Page - QxuanStore
 * Modern Shoe E-Commerce (User Area)
 */

$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';

require_user();

$invoice = trim($_GET['invoice'] ?? '');
$order = db_fetch_one("SELECT * FROM tb_order WHERE invoice_no = ?", [$invoice]);

if (!$order) {
    redirect(base_url('index.php'));
}

$pageTitle = 'Pesanan Berhasil #' . $order->invoice_no;
require_once $rootPath . '/includes/header.php';

$items = db_fetch_all("
    SELECT od.*, p.image_produk 
    FROM tb_order_detail od 
    LEFT JOIN tb_produk p ON od.id_produk = p.id_produk 
    WHERE od.id_order = ?
", [$order->id_order]);

$formattedWa = store_wa_formatted();

// Build formatted WhatsApp message
$itemText = "";
$no = 1;
foreach ($items as $it) {
    $itemText .= "{$no}. {$it->nama_produk} (Size {$it->ukuran}) x{$it->qty} = " . format_rupiah($it->subtotal) . "\n";
    $no++;
}

$waMessage = "Halo Admin QxuanStore! Saya telah membuat pesanan di website.\n\n"
           . "📋 *NO. INVOICE:* #{$order->invoice_no}\n"
           . "👤 *Penerima:* {$order->nama_penerima}\n"
           . "📱 *No. HP/WA:* {$order->no_hp}\n"
           . "📍 *Alamat:* {$order->alamat_pengiriman}\n"
           . "🚚 *Kurir:* {$order->kurir}\n\n"
           . "👟 *DETAIL SEPATU:*\n"
           . "{$itemText}\n"
           . "💰 *TOTAL PEMBAYARAN:* " . format_rupiah($order->total_bayar) . "\n\n"
           . "Mohon info nomor rekening pembayarannya ya min. Terima kasih!";

$waUrl = "https://api.whatsapp.com/send?phone=" . $formattedWa . "&text=" . urlencode($waMessage);
?>

<div class="container" style="padding-top: 50px; padding-bottom: 70px; max-width: 780px;">
    <!-- Success Banner -->
    <div style="background: #fff; padding: 36px; border-radius: 16px; border: 1px solid #e2e8f0; text-align: center; margin-bottom: 30px; box-shadow: var(--shadow-sm);">
        <div style="width: 72px; height: 72px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 2.4rem; margin: 0 auto 16px;">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <h1 style="font-size: 1.85rem; font-weight: 800; color: #0f172a; margin-bottom: 6px;">Pesanan Anda Berhasil Dibuat!</h1>
        <p style="color: #64748b; font-size: 0.95rem;">
            Nomor Invoice: <strong style="color: #2563eb;">#<?= htmlspecialchars($order->invoice_no) ?></strong> &bull; <?= date('d M Y, H:i', strtotime($order->created_at)) ?>
        </p>

        <!-- Direct WhatsApp Action Button -->
        <div style="margin-top: 24px; padding: 22px; background: #f0fdf4; border: 1.5px dashed #86efac; border-radius: 14px;">
            <p style="font-size: 0.95rem; font-weight: 700; color: #166534; margin-bottom: 12px;">
                <i class="fa-brands fa-whatsapp" style="font-size: 1.25rem;"></i> Konfirmasi Pesanan ke WhatsApp Admin
            </p>
            <a href="<?= $waUrl ?>" target="_blank" class="btn" style="background-color: #25d366; color: #fff; padding: 14px 28px; font-size: 1.05rem; justify-content: center; border-radius: 999px; box-shadow: 0 4px 14px rgba(37, 211, 102, 0.35);">
                <i class="fa-brands fa-whatsapp" style="font-size: 1.3rem;"></i> Kirim Format Pesanan ke WhatsApp Admin
            </a>
            <small style="display: block; color: #15803d; margin-top: 10px; font-size: 0.82rem;">
                Sekali klik, pesan pesanan otomatis terbuka di WhatsApp admin untuk diproses & dikirimkan info rekening/resi.
            </small>
        </div>
    </div>

    <!-- Order Summary Details -->
    <div style="background: #fff; padding: 30px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
            <h2 style="font-size: 1.15rem; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-receipt" style="color: #2563eb;"></i> Rincian Pesanan
            </h2>
            <span class="badge badge-warning"><?= htmlspecialchars($order->status_order) ?></span>
        </div>

        <!-- Items -->
        <div style="margin-bottom: 24px;">
            <?php foreach ($items as $it): ?>
                <div style="display: flex; gap: 14px; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                    <img src="<?= product_image_url($it->image_produk) ?>" alt="<?= htmlspecialchars($it->nama_produk) ?>" style="width: 52px; height: 52px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="flex-grow: 1;">
                        <strong style="font-size: 0.95rem; color: #0f172a;"><?= htmlspecialchars($it->nama_produk) ?></strong>
                        <div style="font-size: 0.82rem; color: #64748b; margin-top: 2px;">
                            Size <?= htmlspecialchars($it->ukuran) ?> &bull; <?= $it->qty ?> pasang x <?= format_rupiah($it->harga) ?>
                        </div>
                    </div>
                    <div style="font-weight: 800; color: #2563eb; font-size: 1rem;">
                        <?= format_rupiah($it->subtotal) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 1.15rem; padding-top: 10px;">
            <span style="font-weight: 700;">Total Pembayaran:</span>
            <strong style="color: #2563eb; font-size: 1.35rem;"><?= format_rupiah($order->total_bayar) ?></strong>
        </div>

        <!-- Shipping Destination -->
        <div style="margin-top: 24px; padding: 18px; background: #f8fafc; border-radius: 10px; font-size: 0.9rem; line-height: 1.6;">
            <p><strong>Penerima:</strong> <?= htmlspecialchars($order->nama_penerima) ?> (<?= htmlspecialchars($order->no_hp) ?>)</p>
            <p style="margin-top: 4px;"><strong>Alamat Pengiriman:</strong> <?= nl2br(htmlspecialchars($order->alamat_pengiriman)) ?></p>
            <p style="margin-top: 4px;"><strong>Kurir Pengiriman:</strong> <?= htmlspecialchars($order->kurir) ?></p>
            <?php if (!empty($order->catatan)): ?>
                <p style="margin-top: 4px; color: #64748b;"><strong>Catatan:</strong> <?= htmlspecialchars($order->catatan) ?></p>
            <?php endif; ?>
        </div>

        <div style="margin-top: 30px; display: flex; justify-content: center; gap: 14px; flex-wrap: wrap;">
            <a href="<?= user_url('orders.php') ?>" class="btn btn-secondary">
                <i class="fa-solid fa-clock-rotate-left"></i> Lihat Riwayat Pesanan
            </a>
            <a href="<?= base_url('products.php') ?>" class="btn btn-primary">
                <i class="fa-solid fa-bag-shopping"></i> Belanja Sepatu Lagi
            </a>
        </div>
    </div>
</div>

<?php require_once $rootPath . '/includes/footer.php'; ?>
