<?php
/**
 * Admin - Detail & Pemrosesan Pesanan
 * QxuanStore - Shoe E-Commerce
 */

$pageTitle = 'Detail Pesanan';
require_once dirname(__DIR__) . '/includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = db_fetch_one("SELECT * FROM tb_order WHERE id_order = ?", [$id]);

if (!$order) {
    set_flash('error', 'Data pesanan tidak ditemukan!');
    redirect(admin_url('orders/index.php'));
}

// Mark order as read when admin views it
if (isset($order->is_read) && $order->is_read == 0) {
    db_execute("UPDATE tb_order SET is_read = 1 WHERE id_order = ?", [$id]);
    $order->is_read = 1;
}

// Auto-fill No. Resi if empty
if (empty($order->no_resi)) {
    $autoResi = generate_no_resi($order->kurir ?? 'J&T Express');
    db_execute("UPDATE tb_order SET no_resi = ? WHERE id_order = ?", [$autoResi, $id]);
    $order->no_resi = $autoResi;
}

// Quick action: Mark as Delivered (Selesai)
if (isset($_GET['action']) && $_GET['action'] === 'mark_delivered' && $order->status_order !== 'Selesai') {
    $now = date('Y-m-d H:i:s');
    db_execute("UPDATE tb_order SET status_order = 'Selesai', waktu_diterima = ? WHERE id_order = ?", [$now, $id]);
    set_flash('success', 'Pesanan #' . $order->invoice_no . ' berhasil ditandai SELESAI (Barang telah diterima customer)!');
    redirect(admin_url('orders/detail.php?id=' . $id));
}

// Handle Status & Resi Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($order->status_order === 'Selesai') {
        set_flash('error', 'Pesanan ini sudah SELESAI (barang telah diterima customer). Status pesanan sudah final dan dikunci oleh sistem.');
        redirect(admin_url('orders/detail.php?id=' . $id));
    }

    $newStatus = trim($_POST['status_order'] ?? $order->status_order);
    $kurir     = trim($_POST['kurir'] ?? '');
    $no_resi   = trim($_POST['no_resi'] ?? '');

    $waktuDiterima = $order->waktu_diterima;
    if ($newStatus === 'Selesai' && empty($waktuDiterima)) {
        $waktuDiterima = date('Y-m-d H:i:s');
    } elseif ($newStatus !== 'Selesai') {
        $waktuDiterima = null;
    }

    $upd = db_execute("
        UPDATE tb_order 
        SET status_order = ?, kurir = ?, no_resi = ?, waktu_diterima = ? 
        WHERE id_order = ?
    ", [$newStatus, $kurir, $no_resi, $waktuDiterima, $id]);

    if ($upd) {
        set_flash('success', 'Status pesanan #' . $order->invoice_no . ' berhasil diperbarui menjadi ' . $newStatus . '!');
        $order = db_fetch_one("SELECT * FROM tb_order WHERE id_order = ?", [$id]);
    }
}

$items = db_fetch_all("
    SELECT od.*, p.image_produk 
    FROM tb_order_detail od 
    LEFT JOIN tb_produk p ON od.id_produk = p.id_produk 
    WHERE od.id_order = ?
", [$id]);

// Prepare personalized WhatsApp message
$itemSummaryList = [];
foreach ($items as $it) {
    $qtyText = (isset($it->qty) && $it->qty > 1) ? ' (' . $it->qty . ' pasang)' : '';
    $itemSummaryList[] = $it->nama_produk . ' [Size ' . $it->ukuran . ']' . $qtyText;
}
$shoeText = !empty($itemSummaryList) ? implode(', ', $itemSummaryList) : 'sepatu pesanan Anda';

$waMsg = "Halo Kak *" . $order->nama_penerima . "*,\n\n";
$waMsg .= "Kami dari Admin *QxuanStore* mengenai pesanan sepatu *" . $shoeText . "* (Invoice: *#" . $order->invoice_no . "*).\n";
if (!empty($order->kurir)) {
    $waMsg .= "🚚 Ekspedisi: " . $order->kurir . "\n";
}
if (!empty($order->no_resi)) {
    $waMsg .= "📦 No. Resi: " . $order->no_resi . "\n";
}
$waMsg .= "\nMau menanyakan apakah pesanan sepatu tersebut sudah sampai di alamat Kakak?\n\n";
$waMsg .= "Jika sudah sampai dan sesuai, mohon konfirmasinya ya Kak, atau Kakak bisa langsung pilih tombol *\"Pesanan Sudah Diterima\"* di menu riwayat pesanan akun website QxuanStore Kakak.\n\n";
$waMsg .= "Terima kasih banyak telah berbelanja di QxuanStore! 🙏👟✨";

$customerPhoneClean = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $order->no_hp));
$waUrl = "https://api.whatsapp.com/send?phone=" . $customerPhoneClean . "&text=" . urlencode($waMsg);

// Calculate steps for stepper
$s = $order->status_order;
$isCancelled = ($s === 'Dibatalkan');
$step1Done = true;
$step2Done = in_array($s, ['Diproses', 'Dikirim', 'Selesai']);
$step3Done = in_array($s, ['Dikirim', 'Selesai']);
$step4Done = ($s === 'Selesai');

$badgeClass = 'badge-warning';
if ($s === 'Diproses') $badgeClass = 'badge-info';
if ($s === 'Dikirim') $badgeClass = 'badge-primary';
if ($s === 'Selesai') $badgeClass = 'badge-success';
if ($s === 'Dibatalkan') $badgeClass = 'badge-danger';
?>

<style>
/* Stepper Timeline UI */
.order-stepper-box {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
.order-stepper {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-top: 16px;
}
.stepper-node {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    transition: all 0.2s;
}
.stepper-node.completed {
    background: #f0fdf4;
    border-color: #86efac;
}
.stepper-node.active {
    background: #eff6ff;
    border-color: #93c5fd;
    box-shadow: 0 0 0 2px rgba(37,99,235,0.15);
}
.stepper-title {
    font-size: 0.92rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #475569;
}
.stepper-node.completed .stepper-title { color: #16a34a; }
.stepper-node.active .stepper-title { color: #2563eb; }
.stepper-desc {
    font-size: 0.8rem;
    color: #64748b;
    line-height: 1.4;
}

@media (max-width: 900px) {
    .order-stepper {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 520px) {
    .order-stepper {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Top Progress Stepper (Alur Pesanan) -->
<div class="order-stepper-box">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <h2 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-timeline" style="color: #2563eb;"></i> Alur Pesanan #<?= htmlspecialchars($order->invoice_no) ?>
        </h2>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="<?= admin_url('orders/print.php?id=' . $order->id_order) ?>" target="_blank" class="btn btn-sm" style="background: #0284c7; color: #fff; border-radius: 8px; font-weight: 600;">
                <i class="fa-solid fa-print"></i> Cetak Struk
            </a>
            <span class="badge <?= $badgeClass ?>" style="padding: 6px 14px; font-size: 0.85rem;">Status: <?= htmlspecialchars($order->status_order) ?></span>
        </div>
    </div>

    <div class="order-stepper">
        <!-- Step 1: Pesanan Masuk -->
        <div class="stepper-node <?= $step1Done ? 'completed' : '' ?>">
            <div class="stepper-title">
                <i class="fa-solid <?= $step1Done ? 'fa-circle-check' : 'fa-circle' ?>"></i>
                1. Pesanan Dibuat
            </div>
            <div class="stepper-desc">
                <?= date('d M Y, H:i', strtotime($order->created_at)) ?> WIB<br>
                Total: <strong><?= format_rupiah($order->total_bayar) ?></strong>
            </div>
        </div>

        <!-- Step 2: Pembayaran Midtrans -->
        <div class="stepper-node <?= $step2Done ? 'completed' : ($s === 'Menunggu Pembayaran' ? 'active' : '') ?>">
            <div class="stepper-title">
                <i class="fa-solid <?= $step2Done ? 'fa-circle-check' : ($s === 'Menunggu Pembayaran' ? 'fa-clock' : 'fa-circle') ?>"></i>
                2. Pembayaran
            </div>
            <div class="stepper-desc">
                <?php if ($step2Done): ?>
                    <span style="color: #166534; font-weight: 600;">Lunas Diterima</span><br>
                    <?= !empty($order->waktu_bayar) ? date('d M Y, H:i', strtotime($order->waktu_bayar)) . ' WIB' : 'Otomatis Gateway' ?>
                <?php else: ?>
                    <span style="color: #d97706; font-weight: 600;">Menunggu Pembayaran</span><br>
                    Customer belum menyelesaikan transaksi
                <?php endif; ?>
            </div>
        </div>

        <!-- Step 3: Pengiriman & Resi -->
        <div class="stepper-node <?= $step3Done ? ($step4Done ? 'completed' : 'active') : '' ?>">
            <div class="stepper-title">
                <i class="fa-solid <?= $step4Done ? 'fa-circle-check' : ($step3Done ? 'fa-truck-fast' : 'fa-circle') ?>"></i>
                3. Pengiriman Ekspedisi
            </div>
            <div class="stepper-desc">
                <?php if ($step3Done): ?>
                    <strong><?= htmlspecialchars($order->kurir ?: 'Ekspedisi') ?></strong><br>
                    Resi: <span style="font-family: monospace; color: #2563eb; font-weight: 700;"><?= htmlspecialchars($order->no_resi ?: 'Sedang diproses') ?></span>
                <?php else: ?>
                    <span style="color: #64748b;">Belum dikirim</span><br>
                    Input resi saat packing selesai
                <?php endif; ?>
            </div>
        </div>

        <!-- Step 4: Konfirmasi Diterima -->
        <div class="stepper-node <?= $step4Done ? 'completed' : '' ?>">
            <div class="stepper-title">
                <i class="fa-solid <?= $step4Done ? 'fa-circle-check' : 'fa-circle' ?>"></i>
                4. Pesanan Selesai
            </div>
            <div class="stepper-desc">
                <?php if ($step4Done): ?>
                    <span style="color: #166534; font-weight: 600;">Barang Telah Diterima</span><br>
                    <?= !empty($order->waktu_diterima) ? date('d M Y, H:i', strtotime($order->waktu_diterima)) . ' WIB' : 'Dikonfirmasi Selesai' ?>
                <?php else: ?>
                    <span style="color: #64748b;">Menunggu Penerimaan</span><br>
                    Konfirmasi customer / cek resi
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="order-detail-grid">
    <!-- Items & Customer Info -->
    <div>
        <div class="card-panel">
            <div class="panel-header">
                <h2>
                    <i class="fa-solid fa-receipt" style="color: #2563eb; margin-right: 8px;"></i>
                    Rincian Invoice #<?= htmlspecialchars($order->invoice_no) ?>
                </h2>
                <a href="<?= admin_url('orders/index.php') ?>" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar
                </a>
            </div>

            <!-- Customer Detail Grid -->
            <div class="grid-2-col" style="margin-bottom: 24px; background: #f8fafc; padding: 18px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div>
                    <small style="color: #64748b; font-weight: 600; text-transform: uppercase;">Penerima Paket</small>
                    <p style="font-size: 1.05rem; font-weight: 700; margin-top: 4px; color: #0f172a;"><?= htmlspecialchars($order->nama_penerima) ?></p>
                    <p style="font-size: 0.9rem; margin-top: 6px;">
                        <a href="<?= $waUrl ?>" target="_blank" style="color: #16a34a; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;" title="Kirim Pesan WhatsApp Tanya Penerimaan">
                            <i class="fa-brands fa-whatsapp" style="font-size: 1.15rem;"></i> <?= htmlspecialchars($order->no_hp) ?> (Tanya via WhatsApp)
                        </a>
                    </p>
                </div>
                <div>
                    <small style="color: #64748b; font-weight: 600; text-transform: uppercase;">Alamat Pengiriman</small>
                    <p style="font-size: 0.9rem; margin-top: 4px; line-height: 1.5; color: #334155;"><?= nl2br(htmlspecialchars($order->alamat_pengiriman)) ?></p>
                    <?php if (!empty($order->catatan)): ?>
                        <small style="color: #64748b; display: block; margin-top: 8px;">Catatan: <em><?= htmlspecialchars($order->catatan) ?></em></small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products List -->
            <h3 style="font-size: 1rem; margin-bottom: 12px; font-weight: 700;">Daftar Sepatu yang Dipesan:</h3>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th width="60">Foto</th>
                        <th>Nama Sepatu</th>
                        <th>Ukuran</th>
                        <th>Harga</th>
                        <th>Qty</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td>
                                <img src="<?= product_image_url($it->image_produk) ?>" alt="<?= htmlspecialchars($it->nama_produk) ?>" style="width: 44px; height: 44px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0;">
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($it->nama_produk) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-info">Size <?= htmlspecialchars($it->ukuran) ?></span>
                            </td>
                            <td><?= format_rupiah($it->harga) ?></td>
                            <td><?= $it->qty ?>x</td>
                            <td style="text-align: right; font-weight: 700; color: #2563eb;">
                                <?= format_rupiah($it->subtotal) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" style="text-align: right; font-weight: 700; font-size: 1.05rem;">Total Tagihan:</td>
                        <td style="text-align: right; font-weight: 800; font-size: 1.15rem; color: #2563eb;">
                            <?= format_rupiah($order->total_bayar) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Order Processing & Resi & Receipt Intelligence Card -->
    <div>
        <div class="card-panel">
            <div class="panel-header">
                <h2><i class="fa-solid fa-truck-fast" style="color: #2563eb; margin-right: 8px;"></i> Status & Pengiriman</h2>
            </div>

            <!-- Payment Gateway Summary Badge -->
            <?php if ($order->status_order !== 'Menunggu Pembayaran'): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 14px; border-radius: 8px; margin-bottom: 18px; font-size: 0.88rem; line-height: 1.5;">
                    <div style="font-weight: 700; display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <i class="fa-solid fa-circle-check" style="color: #16a34a;"></i> Pembayaran Lunas Diterima
                    </div>
                    <div><strong>Metode:</strong> <?= htmlspecialchars($order->metode_pembayaran ?? 'Midtrans Payment Gateway') ?></div>
                    <div><strong>Waktu Bayar:</strong> <?= !empty($order->waktu_bayar) ? date('d M Y, H:i', strtotime($order->waktu_bayar)) : date('d M Y, H:i', strtotime($order->created_at)) ?> WIB</div>
                </div>
            <?php else: ?>
                <div style="background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 12px 14px; border-radius: 8px; margin-bottom: 18px; font-size: 0.88rem; line-height: 1.5;">
                    <div style="font-weight: 700; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-clock" style="color: #d97706;"></i> Menunggu Pembayaran
                    </div>
                    <div style="margin-top: 4px; font-size: 0.82rem; color: #b45309;">Menunggu customer menyelesaikan transaksi via Midtrans Gateway.</div>
                </div>
            <?php endif; ?>

            <!-- DELIVERY INTELLIGENCE: CARA ADMIN TAHU BARANG SUDAH DITERIMA ATAU BELUM -->
            <?php if ($order->status_order === 'Dikirim'): ?>
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 16px; margin-bottom: 20px;">
                    <div style="font-weight: 800; color: #1e40af; font-size: 0.92rem; display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <i class="fa-solid fa-location-dot"></i> Paket Sedang Dalam Perjalanan
                    </div>
                    <p style="font-size: 0.84rem; color: #334155; line-height: 1.5; margin-bottom: 12px;">
                        Kurir: <strong><?= htmlspecialchars($order->kurir ?: 'Ekspedisi') ?></strong> &bull; Resi: <strong style="color: #2563eb;"><?= htmlspecialchars($order->no_resi ?: '-') ?></strong>
                    </p>

                    <div style="font-size: 0.82rem; color: #475569; background: #fff; padding: 12px; border-radius: 8px; border: 1px solid #dbeafe; margin-bottom: 14px;">
                        <strong style="color: #0f172a; display: block; margin-bottom: 4px;">💡 Informasi Pengiriman Paket</strong>
                        <p style="margin-bottom: 0; line-height: 1.5;">Paket sedang dalam perjalanan ekspedisi. Customer dapat mengonfirmasi penerimaan sepatu di akun mereka saat barang tiba, atau Admin dapat memantau status resi di bawah ini.</p>
                    </div>

                    <!-- Action button for tracking only -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <?php if (!empty($order->no_resi)): ?>
                            <a href="https://cekresi.com/?noresi=<?= urlencode($order->no_resi) ?>" target="_blank" class="btn btn-primary btn-sm" style="justify-content: center; padding: 11px 16px; border-radius: 8px; font-weight: 700;">
                                <i class="fa-solid fa-magnifying-glass-location"></i> Lacak Resi Pengiriman (CekResi.com)
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($order->status_order === 'Selesai'): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 16px; margin-bottom: 20px;">
                    <div style="font-weight: 800; color: #166534; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                        <i class="fa-solid fa-circle-check" style="color: #16a34a; font-size: 1.2rem;"></i> Pesanan Selesai (Barang Diterima)
                    </div>
                    <p style="font-size: 0.85rem; color: #15803d; line-height: 1.5;">
                        Barang pesanan telah berhasil sampai dan diterima customer.
                    </p>
                    <div style="margin-top: 8px; font-size: 0.82rem; color: #166534; padding-top: 8px; border-top: 1px dashed #86efac;">
                        <strong>Waktu Diterima:</strong> <?= !empty($order->waktu_diterima) ? date('d M Y, H:i', strtotime($order->waktu_diterima)) . ' WIB' : 'Telah diverifikasi selesai' ?>
                    </div>
                </div>

            <?php elseif ($order->status_order === 'Diproses'): ?>
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 16px; margin-bottom: 20px;">
                    <div style="font-weight: 800; color: #1e40af; font-size: 0.92rem; display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <i class="fa-solid fa-boxes-packing"></i> Siap Dikemas & Dikirim
                    </div>
                    <p style="font-size: 0.84rem; color: #334155; line-height: 1.5;">
                        Pembayaran sudah masuk. Silakan siapkan produk sepatu sesuai size, packing kardus, serahkan ke kurir, lalu input Nomor Resi di form bawah ini dan ubah status ke <strong>Dikirim</strong>.
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($order->status_order === 'Selesai'): ?>
                <!-- Panel Terkunci Ketika Pesanan Sudah Selesai (Diterima Customer) -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; margin-top: 10px;">
                    <div style="display: flex; align-items: center; gap: 8px; color: #166534; font-weight: 700; margin-bottom: 8px; font-size: 0.95rem;">
                        <i class="fa-solid fa-lock" style="color: #16a34a;"></i> Status Pesanan Terkunci (Selesai)
                    </div>
                    <p style="font-size: 0.84rem; color: #64748b; line-height: 1.5; margin-bottom: 14px;">
                        Customer telah mengonfirmasi bahwa pesanan sepatu telah sampai dan diterima dengan baik. Untuk menjaga keaslian data riwayat transaksi, status pesanan ini sudah <strong>final</strong> dan tidak dapat diubah lagi oleh Admin.
                    </p>
                    <div style="background: #fff; padding: 14px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 0.85rem; line-height: 1.8;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #64748b;">Status Terkunci:</span>
                            <span class="badge badge-success"><i class="fa-solid fa-check-circle"></i> Selesai</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-top: 1px solid #f1f5f9; padding-top: 6px; margin-top: 6px;">
                            <span style="color: #64748b;">Kurir Pengiriman:</span>
                            <strong><?= htmlspecialchars($order->kurir ?: 'Ekspedisi') ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-top: 1px solid #f1f5f9; padding-top: 6px; margin-top: 6px;">
                            <span style="color: #64748b;">Nomor Resi:</span>
                            <strong style="color: #2563eb; font-family: monospace;"><?= htmlspecialchars($order->no_resi ?: '-') ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-top: 1px solid #f1f5f9; padding-top: 6px; margin-top: 6px;">
                            <span style="color: #64748b;">Waktu Diterima:</span>
                            <strong><?= !empty($order->waktu_diterima) ? date('d M Y, H:i', strtotime($order->waktu_diterima)) . ' WIB' : 'Telah diverifikasi' ?></strong>
                        </div>
                    </div>
                    <?php if (!empty($order->no_resi)): ?>
                        <a href="https://cekresi.com/?noresi=<?= urlencode($order->no_resi) ?>" target="_blank" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center; margin-top: 12px; padding: 10px;">
                            <i class="fa-solid fa-magnifying-glass-location"></i> Cek Riwayat Pelacakan Resi
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Form Edit Status & Resi Manual -->
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="status_order">Ubah Status Pesanan</label>
                        <select id="status_order" name="status_order" class="form-control" style="font-weight: 600;">
                            <option value="Menunggu Pembayaran" <?= $order->status_order === 'Menunggu Pembayaran' ? 'selected' : '' ?>>Menunggu Pembayaran</option>
                            <option value="Diproses" <?= $order->status_order === 'Diproses' ? 'selected' : '' ?>>Diproses (Packing Produk)</option>
                            <option value="Dikirim" <?= $order->status_order === 'Dikirim' ? 'selected' : '' ?>>Dikirim (Dalam Ekspedisi)</option>
                            <option value="Selesai" <?= $order->status_order === 'Selesai' ? 'selected' : '' ?>>Selesai (Barang Diterima)</option>
                            <option value="Dibatalkan" <?= $order->status_order === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="kurir">Kurir / Ekspedisi Pengiriman</label>
                        <input type="text" id="kurir" name="kurir" class="form-control" placeholder="Contoh: JNE / J&T / SiCepat" value="<?= htmlspecialchars($order->kurir ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="no_resi">Nomor Resi Pelacakan (Tracking No)</label>
                        <input type="text" id="no_resi" name="no_resi" class="form-control" placeholder="Masukkan nomor resi ekspedisi..." value="<?= htmlspecialchars($order->no_resi ?? '') ?>">
                        <small style="color: #64748b; font-size: 0.78rem; margin-top: 4px; display: block;">Nomor resi ini dapat dilacak langsung oleh Admin maupun Customer.</small>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Pembaruan Pesanan
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmAdminMarkDelivered(orderId, invoiceNo) {
    Swal.fire({
        title: 'Konfirmasi Barang Diterima?',
        text: `Tandai pesanan #${invoiceNo} sebagai SELESAI? Pastikan kurir telah menyerahkan paket atau customer telah konfirmasi penerimaan.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-check"></i> Ya, Tandai Selesai',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `<?= admin_url('orders/detail.php') ?>?id=${orderId}&action=mark_delivered`;
        }
    });
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
