<?php
/**
 * Cetak & Simpan PDF Laporan Penjualan Bulanan
 * QxuanStore - Professional Business Reporting
 */

$rootPath = dirname(dirname(__DIR__));
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';

// Enforce admin access
require_admin();

$selectedMonth = trim($_GET['month'] ?? '');
$selectedYear  = trim($_GET['year'] ?? '');
$statusFilter  = trim($_GET['status'] ?? '');
$search        = trim($_GET['search'] ?? '');

$months = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April',   '05' => 'Mei',      '06' => 'Juni',
    '07' => 'Juli',    '08' => 'Agustus',  '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// Query
$where = "WHERE 1=1";
$params = [];

if (!empty($selectedMonth)) {
    $where .= " AND MONTH(created_at) = ?";
    $params[] = (int)$selectedMonth;
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

$orders = db_fetch_all("SELECT * FROM tb_order $where ORDER BY id_order ASC", $params);

// Fetch items for each order
$orderReport = [];
$totalPendapatan = 0;
$totalShoesQty = 0;
$totalSelesai = 0;

foreach ($orders as $ord) {
    $items = db_fetch_all("
        SELECT od.*, p.nama_produk 
        FROM tb_order_detail od 
        LEFT JOIN tb_produk p ON od.id_produk = p.id_produk 
        WHERE od.id_order = ?
    ", [$ord->id_order]);

    $itemNames = [];
    $orderQty = 0;
    foreach ($items as $it) {
        $itemNames[] = $it->nama_produk . ' (Sz ' . $it->ukuran . ', ' . $it->qty . 'x)';
        $orderQty += (int)$it->qty;
    }

    if ($ord->status_order === 'Selesai') {
        $totalSelesai++;
        $totalPendapatan += (float)$ord->total_bayar;
        $totalShoesQty += $orderQty;
    }

    $ord->item_summary = !empty($itemNames) ? implode(', ', $itemNames) : '-';
    $ord->total_qty = $orderQty;
    $orderReport[] = $ord;
}

// Period Label
$periodLabel = 'Semua Periode';
if (!empty($selectedMonth) && !empty($selectedYear)) {
    $periodLabel = ($months[$selectedMonth] ?? $selectedMonth) . ' ' . $selectedYear;
} elseif (!empty($selectedMonth)) {
    $periodLabel = 'Bulan ' . ($months[$selectedMonth] ?? $selectedMonth);
} elseif (!empty($selectedYear)) {
    $periodLabel = 'Tahun ' . $selectedYear;
}

$currentAdmin = current_admin();
$adminName = $currentAdmin->nama_lengkap ?? ($currentAdmin->username ?? 'Administrator');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan_Penjualan_QxuanStore_<?= preg_replace('/[^A-Za-z0-9]/', '_', $periodLabel) ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        body {
            background: #f1f5f9;
            color: #1e293b;
            padding: 24px;
        }
        .report-page {
            max-width: 1060px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        /* Top Action Bar (hidden in print) */
        .no-print-toolbar {
            max-width: 1060px;
            margin: 0 auto 18px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #0f172a;
            color: #fff;
            padding: 14px 20px;
            border-radius: 10px;
        }
        .btn-toolbar {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }
        .btn-print {
            background: #2563eb;
            color: #fff;
        }
        .btn-print:hover {
            background: #1d4ed8;
        }
        .btn-close {
            background: #334155;
            color: #fff;
        }
        .btn-close:hover {
            background: #475569;
        }

        /* Company Header */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2.5px solid #0f172a;
            padding-bottom: 18px;
            margin-bottom: 24px;
        }
        .company-info h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .company-info p {
            font-size: 0.84rem;
            color: #64748b;
            margin-top: 3px;
            line-height: 1.4;
        }
        .report-title-box {
            text-align: right;
        }
        .report-title-box h2 {
            font-size: 1.25rem;
            font-weight: 800;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .report-title-box .badge-period {
            display: inline-block;
            margin-top: 4px;
            background: #eff6ff;
            color: #1e40af;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid #bfdbfe;
        }

        /* Metadata Grid */
        .meta-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 24px;
            font-size: 0.84rem;
        }
        .meta-strip div span {
            color: #64748b;
            font-size: 0.76rem;
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
            font-weight: 600;
        }
        .meta-strip div strong {
            color: #0f172a;
            font-size: 0.9rem;
        }

        /* Executive KPI Summary */
        .kpi-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        .kpi-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 14px;
            background: #fff;
        }
        .kpi-card small {
            font-size: 0.72rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            display: block;
        }
        .kpi-card .value {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
            margin-top: 4px;
        }

        /* Table */
        table.report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
            margin-bottom: 24px;
        }
        table.report-table th, 
        table.report-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            text-align: left;
            vertical-align: middle;
        }
        table.report-table th {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.74rem;
            letter-spacing: 0.3px;
        }
        table.report-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        
        /* Status Badges */
        .status-pill {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
        }
        .status-selesai { background: #dcfce7; color: #166534; }
        .status-diproses { background: #e0f2fe; color: #075985; }
        .status-dikirim { background: #ede9fe; color: #5b21b6; }
        .status-menunggu { background: #fef3c7; color: #92400e; }
        .status-batal { background: #fee2e2; color: #991b1b; }

        /* Signature block */
        .signature-section {
            margin-top: 36px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 240px;
            text-align: center;
            font-size: 0.84rem;
        }
        .signature-space {
            height: 70px;
        }
        .signature-name {
            font-weight: 700;
            border-top: 1px solid #334155;
            padding-top: 6px;
            color: #0f172a;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .no-print-toolbar {
                display: none !important;
            }
            .report-page {
                border: none;
                box-shadow: none;
                padding: 0;
                width: 100%;
                max-width: 100%;
            }
            table.report-table th {
                background: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .badge-period, .status-pill, .meta-strip, .kpi-card {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <!-- Non-Print Toolbar -->
    <div class="no-print-toolbar">
        <div>
            <strong style="font-size: 0.95rem;"><i class="fa-solid fa-file-invoice"></i> Pratinjau Dokumen Laporan Penjualan</strong>
            <span style="font-size: 0.8rem; color: #94a3b8; margin-left: 8px;">Gunakan 'Cetak / Simpan PDF' untuk mencetak langsung atau simpan file .pdf</span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn-toolbar btn-print">
                <i class="fa-solid fa-print"></i> Cetak / Simpan PDF
            </button>
            <button onclick="window.close()" class="btn-toolbar btn-close">
                <i class="fa-solid fa-xmark"></i> Tutup
            </button>
        </div>
    </div>

    <!-- Printable Report Page -->
    <div class="report-page">
        <!-- Header Usaha -->
        <div class="report-header">
            <div class="company-info">
                <h1>QXUANSTORE INDONESIA</h1>
                <p>Spesialis Sepatu Original & Fashion Footwear Terlengkap</p>
                <p>Perum Taman Raya Rajeg Jl. Melati 1, Mekarsari, Kec. Rajeg, Kab. Tangerang, Banten</p>
                <p>Telp / WhatsApp: +62 822-1081-3446 &bull; Website: www.qxuanstore.com</p>
            </div>
            <div class="report-title-box">
                <h2>Laporan Penjualan</h2>
                <div class="badge-period">
                    <i class="fa-solid fa-calendar-days"></i> <?= htmlspecialchars($periodLabel) ?>
                </div>
            </div>
        </div>

        <!-- Meta Strip -->
        <div class="meta-strip">
            <div>
                <span>Tanggal Dicetak</span>
                <strong><?= date('d M Y, H:i') ?> WIB</strong>
            </div>
            <div>
                <span>Filter Status</span>
                <strong><?= !empty($statusFilter) ? htmlspecialchars($statusFilter) : 'Semua Status' ?></strong>
            </div>
            <div>
                <span>Dicetak Oleh</span>
                <strong><?= htmlspecialchars($adminName) ?></strong>
            </div>
            <div>
                <span>Total Data</span>
                <strong><?= count($orderReport) ?> Transaksi</strong>
            </div>
        </div>

        <!-- Executive KPI Cards -->
        <div class="kpi-cards">
            <div class="kpi-card">
                <small>Total Transaksi</small>
                <div class="value"><?= count($orderReport) ?> Pesanan</div>
            </div>
            <div class="kpi-card">
                <small>Pesanan Selesai</small>
                <div class="value" style="color: #16a34a;"><?= $totalSelesai ?> Pesanan</div>
            </div>
            <div class="kpi-card">
                <small>Sepatu Terjual</small>
                <div class="value" style="color: #db2777;"><?= $totalShoesQty ?> Pasang</div>
            </div>
            <div class="kpi-card">
                <small>Total Pendapatan</small>
                <div class="value" style="color: #2563eb;"><?= format_rupiah($totalPendapatan) ?></div>
            </div>
        </div>

        <!-- Sales Data Table -->
        <table class="report-table">
            <thead>
                <tr>
                    <th width="30" class="text-center">No</th>
                    <th width="110">No. Invoice</th>
                    <th width="90">Tanggal</th>
                    <th>Nama Pelanggan</th>
                    <th>Rincian Produk & Ukuran</th>
                    <th width="45" class="text-center">Qty</th>
                    <th width="100">Metode / Kurir</th>
                    <th width="85" class="text-center">Status</th>
                    <th width="115" class="text-right">Total (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($orderReport)): ?>
                    <?php 
                    $no = 1; 
                    $sumSubtotal = 0;
                    foreach ($orderReport as $row): 
                        $statusClass = 'status-menunggu';
                        if ($row->status_order === 'Diproses') $statusClass = 'status-diproses';
                        if ($row->status_order === 'Dikirim') $statusClass = 'status-dikirim';
                        if ($row->status_order === 'Selesai') $statusClass = 'status-selesai';
                        if ($row->status_order === 'Dibatalkan') $statusClass = 'status-batal';
                        $sumSubtotal += (float)$row->total_bayar;
                    ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td><strong>#<?= htmlspecialchars($row->invoice_no) ?></strong></td>
                            <td><?= date('d/m/Y', strtotime($row->created_at)) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row->nama_penerima) ?></strong><br>
                                <small style="color: #64748b;"><?= htmlspecialchars($row->no_hp) ?></small>
                            </td>
                            <td><?= htmlspecialchars($row->item_summary) ?></td>
                            <td class="text-center"><strong><?= $row->total_qty ?></strong></td>
                            <td>
                                <div style="font-size: 0.78rem; font-weight: 600;"><?= htmlspecialchars($row->metode_pembayaran ?: 'QRIS/Transfer') ?></div>
                                <div style="font-size: 0.72rem; color: #64748b;"><?= htmlspecialchars($row->kurir ?: 'Ambil Toko') ?></div>
                            </td>
                            <td class="text-center">
                                <span class="status-pill <?= $statusClass ?>">
                                    <?= htmlspecialchars($row->status_order) ?>
                                </span>
                            </td>
                            <td class="text-right" style="font-weight: 700;">
                                <?= format_rupiah($row->total_bayar) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center" style="padding: 24px; color: #64748b;">
                            Tidak ada transaksi penjualan pada filter periode yang dipilih.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f8fafc; font-size: 0.9rem;">
                    <td colspan="8" class="text-right" style="font-weight: 800; padding: 10px;">
                        TOTAL PENDAPATAN (STATUS SELESAI):
                    </td>
                    <td class="text-right" style="font-weight: 800; color: #2563eb; font-size: 1rem; padding: 10px;">
                        <?= format_rupiah($totalPendapatan) ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures -->
        <div class="signature-section">
            <div class="signature-box">
                <p>Disiapkan Oleh,</p>
                <p style="color: #64748b; font-size: 0.78rem;">Admin Operasional</p>
                <div class="signature-space"></div>
                <div class="signature-name">( <?= htmlspecialchars($adminName) ?> )</div>
            </div>

            <div class="signature-box">
                <p>Tangerang, <?= date('d F Y') ?></p>
                <p style="color: #64748b; font-size: 0.78rem;">Pemilik / Penanggung Jawab Usaha</p>
                <div class="signature-space"></div>
                <div class="signature-name">( Manajemen QxuanStore )</div>
            </div>
        </div>
    </div>

</body>
</html>

