<?php
/**
 * Admin - Kelola Pesanan Masuk
 * QxuanStore - Shoe E-Commerce
 */

$pageTitle = 'Manajemen Pesanan Masuk';
require_once dirname(__DIR__) . '/includes/header.php';

$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = "WHERE 1=1";
$params = [];

if (!empty($statusFilter)) {
    $where .= " AND status_order = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $where .= " AND (invoice_no LIKE ? OR nama_penerima LIKE ? OR no_hp LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$orders = db_fetch_all("SELECT * FROM tb_order $where ORDER BY id_order DESC", $params);
?>

<div class="card-panel">
    <div class="panel-header">
        <h2><i class="fa-solid fa-cart-shopping" style="color: #2563eb; margin-right: 8px;"></i> Daftar Pesanan Pelanggan</h2>
    </div>

    <!-- Filter Form -->
    <form action="" method="GET" style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;">
        <input type="text" name="search" class="form-control" placeholder="Cari No. Invoice / Nama / No. HP..." value="<?= htmlspecialchars($search) ?>" style="max-width: 300px;">
        
        <select name="status" class="form-control" style="max-width: 220px;" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <option value="Menunggu Pembayaran" <?= $statusFilter === 'Menunggu Pembayaran' ? 'selected' : '' ?>>Menunggu Pembayaran</option>
            <option value="Diproses" <?= $statusFilter === 'Diproses' ? 'selected' : '' ?>>Diproses</option>
            <option value="Dikirim" <?= $statusFilter === 'Dikirim' ? 'selected' : '' ?>>Dikirim</option>
            <option value="Selesai" <?= $statusFilter === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
            <option value="Dibatalkan" <?= $statusFilter === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
        </select>

        <button type="submit" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-filter"></i> Filter
        </button>
        <?php if (!empty($search) || !empty($statusFilter)): ?>
            <a href="<?= admin_url('orders/index.php') ?>" class="btn btn-sm" style="color: #ef4444;">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>Tanggal</th>
                    <th>Nama Pelanggan</th>
                    <th>No. WhatsApp</th>
                    <th>Total Bayar</th>
                    <th>Status Pesanan</th>
                    <th width="120" style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $ord): ?>
                        <?php
                            $badgeClass = 'badge-warning';
                            if ($ord->status_order === 'Diproses') $badgeClass = 'badge-info';
                            if ($ord->status_order === 'Dikirim') $badgeClass = 'badge-primary';
                            if ($ord->status_order === 'Selesai') $badgeClass = 'badge-success';
                            if ($ord->status_order === 'Dibatalkan') $badgeClass = 'badge-danger';
                        ?>
                        <tr>
                            <td>
                                <strong>#<?= htmlspecialchars($ord->invoice_no) ?></strong>
                            </td>
                            <td><?= date('d M Y, H:i', strtotime($ord->created_at)) ?></td>
                            <td><?= htmlspecialchars($ord->nama_penerima) ?></td>
                            <td>
                                <a href="https://api.whatsapp.com/send?phone=<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $ord->no_hp)) ?>" target="_blank" style="color: #16a34a; font-weight: 600;">
                                    <i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($ord->no_hp) ?>
                                </a>
                            </td>
                            <td>
                                <strong style="color: #2563eb;"><?= format_rupiah($ord->total_bayar) ?></strong>
                            </td>
                            <td>
                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($ord->status_order) ?></span>
                                <?php if ($ord->status_order === 'Dikirim' && !empty($ord->no_resi)): ?>
                                    <div style="font-size: 0.75rem; color: #2563eb; margin-top: 2px;">
                                        Resi: <?= htmlspecialchars($ord->no_resi) ?> (<a href="https://cekresi.com/?noresi=<?= urlencode($ord->no_resi) ?>" target="_blank" style="color: #2563eb; text-decoration: underline;">Lacak</a>)
                                    </div>
                                <?php elseif ($ord->status_order === 'Selesai' && !empty($ord->waktu_diterima)): ?>
                                    <div style="font-size: 0.75rem; color: #166534; margin-top: 2px;">
                                        <i class="fa-solid fa-circle-check"></i> Sampai: <?= date('d/m/Y H:i', strtotime($ord->waktu_diterima)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?= admin_url('orders/detail.php?id=' . $ord->id_order) ?>" class="btn btn-secondary btn-sm" title="Lihat Detail Pesanan">
                                    <i class="fa-solid fa-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #64748b; padding: 32px;">
                            Belum ada pesanan masuk.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
