<?php
/**
 * Admin - Kelola Pesanan & Laporan Penjualan
 * QxuanStore - Shoe E-Commerce
 */

$pageTitle = 'Manajemen Pesanan';
require_once dirname(__DIR__) . '/includes/header.php';

$selectedMonth = !empty($_GET['month']) ? (int)$_GET['month'] : 0;
$selectedYear  = trim($_GET['year'] ?? '');
$statusFilter  = trim($_GET['status'] ?? '');
$search        = trim($_GET['search'] ?? '');

$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
    4 => 'April',   5 => 'Mei',      6 => 'Juni',
    7 => 'Juli',    8 => 'Agustus',  9 => 'September',
    10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Get distinct years in database
$dbYears = db_fetch_all("SELECT DISTINCT YEAR(created_at) AS yr FROM tb_order WHERE created_at IS NOT NULL ORDER BY yr DESC");
$availableYears = [];
foreach ($dbYears as $y) {
    if (!empty($y->yr)) $availableYears[] = (string)$y->yr;
}
$currentYear = date('Y');
if (!in_array($currentYear, $availableYears)) {
    array_unshift($availableYears, $currentYear);
}

// Build query
$where = "WHERE 1=1";
$params = [];

if ($selectedMonth > 0) {
    $where .= " AND MONTH(created_at) = ?";
    $params[] = $selectedMonth;
}

if (!empty($selectedYear)) {
    $where .= " AND YEAR(created_at) = ?";
    $params[] = (int)$selectedYear;
}

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

// Calculate Executive Summary Metrics
$totalOrders = count($orders);
$totalSelesai = 0;
$totalDibatalkan = 0;

foreach ($orders as $ord) {
    if ($ord->status_order === 'Selesai') {
        $totalSelesai++;
    } elseif ($ord->status_order === 'Dibatalkan') {
        $totalDibatalkan++;
    }
}

// Query parameters for print report link
$exportParams = $_GET;
$exportQuery = http_build_query($exportParams);

// Period description string
$periodLabel = 'Semua Periode';
if ($selectedMonth > 0 && !empty($selectedYear)) {
    $periodLabel = ($months[$selectedMonth] ?? 'Bulan ' . $selectedMonth) . ' ' . $selectedYear;
} elseif ($selectedMonth > 0) {
    $periodLabel = 'Bulan ' . ($months[$selectedMonth] ?? $selectedMonth);
} elseif (!empty($selectedYear)) {
    $periodLabel = 'Tahun ' . $selectedYear;
}
?>

<!-- Executive Summary Cards -->
<div class="stats-grid" style="margin-bottom: 24px;">
    <!-- Metric 1: Total Orders -->
    <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; align-items: center; gap: 16px;">
        <div style="width: 48px; height: 48px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
            <i class="fa-solid fa-receipt"></i>
        </div>
        <div>
            <div style="font-size: 0.8rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Transaksi</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #0f172a; margin-top: 2px;"><?= number_format($totalOrders, 0, ',', '.') ?></div>
            <div style="font-size: 0.75rem; color: #64748b;"><?= htmlspecialchars($periodLabel) ?></div>
        </div>
    </div>

    <!-- Metric 2: Completed Orders -->
    <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; align-items: center; gap: 16px;">
        <div style="width: 48px; height: 48px; border-radius: 10px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <div style="font-size: 0.8rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Pesanan Selesai</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #16a34a; margin-top: 2px;"><?= number_format($totalSelesai, 0, ',', '.') ?></div>
            <div style="font-size: 0.75rem; color: #64748b;">Transaksi sukses diterima</div>
        </div>
    </div>

    <!-- Metric 3: Cancelled Orders -->
    <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; align-items: center; gap: 16px;">
        <div style="width: 48px; height: 48px; border-radius: 10px; background: #fef2f2; color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
            <i class="fa-solid fa-ban"></i>
        </div>
        <div>
            <div style="font-size: 0.8rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Pesanan Dibatalkan</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #ef4444; margin-top: 2px;"><?= number_format($totalDibatalkan, 0, ',', '.') ?></div>
            <div style="font-size: 0.75rem; color: #64748b;">Transaksi tidak dilanjutkan</div>
        </div>
    </div>
</div>

<div class="card-panel">
    <div class="panel-header" style="flex-wrap: wrap; gap: 12px;">
        <div>
            <h2><i class="fa-solid fa-cart-shopping" style="color: #2563eb; margin-right: 8px;"></i> Daftar Pesanan Pelanggan</h2>
            <p style="font-size: 0.84rem; color: #64748b; margin-top: 4px;">Kelola pesanan pelanggan dan cetak laporan penjualan toko QxuanStore.</p>
        </div>
        
        <!-- Print Action Button (Warna Merah untuk PDF) -->
        <div>
            <a href="<?= admin_url('orders/report_print.php?' . $exportQuery) ?>" target="_blank" class="btn btn-sm" style="background: #ef4444; color: #fff; border: 1px solid #dc2626; border-radius: 8px; font-weight: 700; box-shadow: 0 2px 5px rgba(239, 68, 68, 0.25);" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'" title="Cetak atau Simpan PDF Laporan Penjualan">
                <i class="fa-solid fa-file-pdf"></i> Cetak Laporan Penjualan (PDF)
            </a>
        </div>
    </div>

    <!-- Monthly & Yearly Filter Form -->
    <form action="" method="GET" class="admin-filter-bar">
        
        <!-- Filter Bulan & Tahun Berdampingan (Mobile & Desktop) -->
        <div class="filter-group-pair">
            <!-- Filter Bulan -->
            <div class="filter-group" style="flex: 1.3;">
                <label style="font-size: 0.76rem; font-weight: 700; color: #475569; text-transform: uppercase;">Bulan</label>
                <select name="month" class="form-control" style="font-size: 0.85rem;" onchange="this.form.submit()">
                    <option value="">Semua Bulan</option>
                    <?php foreach ($months as $num => $name): ?>
                        <option value="<?= $num ?>" <?= ($selectedMonth === $num) ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Tahun -->
            <div class="filter-group" style="flex: 1;">
                <label style="font-size: 0.76rem; font-weight: 700; color: #475569; text-transform: uppercase;">Tahun</label>
                <select name="year" class="form-control" style="font-size: 0.85rem;" onchange="this.form.submit()">
                    <option value="">Semua Tahun</option>
                    <?php foreach ($availableYears as $yr): ?>
                        <option value="<?= $yr ?>" <?= $selectedYear === (string)$yr ? 'selected' : '' ?>><?= $yr ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Filter Status -->
        <div class="filter-group">
            <label style="font-size: 0.76rem; font-weight: 700; color: #475569; text-transform: uppercase;">Status</label>
            <select name="status" class="form-control" style="font-size: 0.85rem;" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="Menunggu Pembayaran" <?= $statusFilter === 'Menunggu Pembayaran' ? 'selected' : '' ?>>Menunggu Pembayaran</option>
                <option value="Diproses" <?= $statusFilter === 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                <option value="Dikirim" <?= $statusFilter === 'Dikirim' ? 'selected' : '' ?>>Dikirim</option>
                <option value="Selesai" <?= $statusFilter === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                <option value="Dibatalkan" <?= $statusFilter === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
            </select>
        </div>

        <!-- Search Bar -->
        <div class="filter-group filter-search">
            <label style="font-size: 0.76rem; font-weight: 700; color: #475569; text-transform: uppercase;">Pencarian</label>
            <input type="text" name="search" class="form-control" placeholder="Cari No. Invoice / Nama / WhatsApp..." value="<?= htmlspecialchars($search) ?>" style="font-size: 0.85rem; width: 100%;">
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary btn-sm" style="padding: 9px 14px; white-space: nowrap;">
                <i class="fa-solid fa-filter"></i> Terapkan
            </button>
            <?php if (!empty($search) || !empty($statusFilter) || $selectedMonth > 0 || !empty($selectedYear)): ?>
                <a href="<?= admin_url('orders/index.php') ?>" class="btn btn-sm" style="color: #ef4444; padding: 9px 12px; white-space: nowrap;" title="Reset Semua Filter">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-responsive">
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="white-space: nowrap;">No. Invoice</th>
                    <th style="white-space: nowrap;">Tanggal</th>
                    <th>Pelanggan</th>
                    <th style="white-space: nowrap;">Pengiriman</th>
                    <th style="white-space: nowrap;">Total Bayar</th>
                    <th>Status Pesanan</th>
                    <th width="110" style="text-align: right; white-space: nowrap;">Aksi</th>
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
                            $isAmbilToko = ($ord->kurir === 'Ambil di Toko Fisik');
                        ?>
                        <tr>
                            <td style="white-space: nowrap;">
                                <strong>#<?= htmlspecialchars($ord->invoice_no) ?></strong>
                                <?php if (isset($ord->is_read) && $ord->is_read == 0): ?>
                                    <span class="badge" style="background: #ef4444; color: #fff; font-size: 0.65rem; padding: 2px 6px; border-radius: 99px; font-weight: 800; margin-left: 4px;">
                                        Baru
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space: nowrap;">
                                <div style="font-weight: 600;"><?= date('d M Y', strtotime($ord->created_at)) ?></div>
                                <div style="font-size: 0.78rem; color: #64748b; margin-top: 1px;"><?= date('H:i', strtotime($ord->created_at)) ?> WIB</div>
                            </td>
                            <td>
                                <strong style="color: #0f172a; display: block; line-height: 1.3;"><?= htmlspecialchars($ord->nama_penerima) ?></strong>
                                <div style="margin-top: 3px;">
                                    <a href="https://api.whatsapp.com/send?phone=<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $ord->no_hp)) ?>" target="_blank" style="color: #16a34a; font-weight: 600; font-size: 0.8rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($ord->no_hp) ?>
                                    </a>
                                </div>
                            </td>
                            <td style="white-space: nowrap;">
                                <?php if ($isAmbilToko): ?>
                                    <span class="badge badge-success" style="font-size: 0.72rem;">
                                        <i class="fa-solid fa-store"></i> Ambil di Toko
                                    </span>
                                <?php else: ?>
                                    <span style="font-size: 0.84rem; color: #334155;">
                                        <i class="fa-solid fa-truck" style="color: #2563eb;"></i> <?= htmlspecialchars($ord->kurir ?: 'Ekspedisi') ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space: nowrap;">
                                <strong style="color: #2563eb; font-size: 0.95rem;"><?= format_rupiah($ord->total_bayar) ?></strong>
                            </td>
                            <td>
                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($ord->status_order) ?></span>
                                <?php if (!$isAmbilToko && $ord->status_order === 'Dikirim' && !empty($ord->no_resi)): ?>
                                    <div style="font-size: 0.74rem; color: #2563eb; margin-top: 3px; white-space: nowrap;">
                                        Resi: <?= htmlspecialchars($ord->no_resi) ?> (<a href="https://cekresi.com/?noresi=<?= urlencode($ord->no_resi) ?>" target="_blank" style="color: #2563eb; text-decoration: underline;">Lacak</a>)
                                    </div>
                                <?php elseif ($ord->status_order === 'Selesai' && !empty($ord->waktu_diterima)): ?>
                                    <div style="font-size: 0.74rem; color: #166534; margin-top: 3px; white-space: nowrap;">
                                        <i class="fa-solid fa-circle-check"></i> <?= $isAmbilToko ? 'Diambil' : 'Sampai' ?>: <?= date('d/m/y H:i', strtotime($ord->waktu_diterima)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                    <a href="<?= admin_url('orders/print.php?id=' . $ord->id_order) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Cetak Struk Pesanan">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                    <a href="<?= admin_url('orders/detail.php?id=' . $ord->id_order) ?>" class="btn btn-secondary btn-sm" title="Lihat Detail Pesanan">
                                        <i class="fa-solid fa-eye"></i> Detail
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #64748b; padding: 40px;">
                            <i class="fa-solid fa-folder-open" style="font-size: 2rem; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                            Tidak ada data pesanan pada filter periode yang dipilih.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
