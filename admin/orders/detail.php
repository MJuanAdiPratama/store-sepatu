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

$items = db_fetch_all("
    SELECT od.*, p.image_produk 
    FROM tb_order_detail od 
    LEFT JOIN tb_produk p ON od.id_produk = p.id_produk 
    WHERE od.id_order = ?
", [$id]);

// Handle Status & Resi Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = trim($_POST['status_order'] ?? $order->status_order);
    $kurir     = trim($_POST['kurir'] ?? '');
    $no_resi   = trim($_POST['no_resi'] ?? '');

    $upd = db_execute("
        UPDATE tb_order 
        SET status_order = ?, kurir = ?, no_resi = ? 
        WHERE id_order = ?
    ", [$newStatus, $kurir, $no_resi, $id]);

    if ($upd) {
        set_flash('success', 'Status pesanan #' . $order->invoice_no . ' berhasil diperbarui!');
        $order = db_fetch_one("SELECT * FROM tb_order WHERE id_order = ?", [$id]);
    }
}
?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
    <!-- Items & Customer Info -->
    <div>
        <div class="card-panel">
            <div class="panel-header">
                <h2>
                    <i class="fa-solid fa-receipt" style="color: #2563eb; margin-right: 8px;"></i>
                    Invoice #<?= htmlspecialchars($order->invoice_no) ?>
                </h2>
                <a href="<?= admin_url('orders/index.php') ?>" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar
                </a>
            </div>

            <!-- Customer Detail Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; background: #f8fafc; padding: 18px; border-radius: 8px;">
                <div>
                    <small style="color: #64748b; font-weight: 600; text-transform: uppercase;">Penerima Paket</small>
                    <p style="font-size: 1.05rem; font-weight: 700; margin-top: 4px;"><?= htmlspecialchars($order->nama_penerima) ?></p>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <a href="https://api.whatsapp.com/send?phone=<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $order->no_hp)) ?>" target="_blank" style="color: #16a34a; font-weight: 600;">
                            <i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($order->no_hp) ?> (Hubungi WA)
                        </a>
                    </p>
                </div>
                <div>
                    <small style="color: #64748b; font-weight: 600; text-transform: uppercase;">Alamat Pengiriman</small>
                    <p style="font-size: 0.9rem; margin-top: 4px; line-height: 1.5;"><?= nl2br(htmlspecialchars($order->alamat_pengiriman)) ?></p>
                    <?php if (!empty($order->catatan)): ?>
                        <small style="color: #64748b; display: block; margin-top: 8px;">Catatan: <em><?= htmlspecialchars($order->catatan) ?></em></small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products List -->
            <h3 style="font-size: 1rem; margin-bottom: 12px;">Daftar Sepatu yang Dipesan:</h3>
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
                                <img src="<?= product_image_url($it->image_produk) ?>" alt="<?= htmlspecialchars($it->nama_produk) ?>" style="width: 44px; height: 44px; object-fit: cover; border-radius: 6px;">
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

    <!-- Order Processing & Resi Card -->
    <div>
        <div class="card-panel">
            <div class="panel-header">
                <h2><i class="fa-solid fa-truck-fast" style="color: #2563eb; margin-right: 8px;"></i> Status & Pengiriman</h2>
            </div>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="status_order">Status Pesanan</label>
                    <select id="status_order" name="status_order" class="form-control" style="font-weight: 600;">
                        <option value="Menunggu Pembayaran" <?= $order->status_order === 'Menunggu Pembayaran' ? 'selected' : '' ?>>Menunggu Pembayaran</option>
                        <option value="Diproses" <?= $order->status_order === 'Diproses' ? 'selected' : '' ?>>Diproses (Packing)</option>
                        <option value="Dikirim" <?= $order->status_order === 'Dikirim' ? 'selected' : '' ?>>Dikirim (Dalam Ekspedisi)</option>
                        <option value="Selesai" <?= $order->status_order === 'Selesai' ? 'selected' : '' ?>>Selesai (Diterima)</option>
                        <option value="Dibatalkan" <?= $order->status_order === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="kurir">Kurir / Ekspedisi</label>
                    <input type="text" id="kurir" name="kurir" class="form-control" placeholder="Contoh: JNE / J&T / SiCepat" value="<?= htmlspecialchars($order->kurir ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="no_resi">Nomor Resi Pengiriman</label>
                    <input type="text" id="no_resi" name="no_resi" class="form-control" placeholder="Masukkan nomor resi..." value="<?= htmlspecialchars($order->no_resi ?? '') ?>">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">
                    <i class="fa-solid fa-check"></i> Perbarui Status Pesanan
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
