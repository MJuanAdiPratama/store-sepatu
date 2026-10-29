<?php
/**
 * Cetak & Simpan PDF Laporan Penjualan Resmi
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
$periodLabel = '';
if (!empty($selectedMonth) && !empty($selectedYear)) {
    $periodLabel = ($months[$selectedMonth] ?? $selectedMonth) . ' ' . $selectedYear;
} elseif (!empty($selectedMonth)) {
    $periodLabel = 'Bulan ' . ($months[$selectedMonth] ?? $selectedMonth);
} elseif (!empty($selectedYear)) {
    $periodLabel = 'Tahun ' . $selectedYear;
}

$contact = get_admin_contact();
$tglCetak = date('d') . ' ' . ($months[date('m')] ?? date('F')) . ' ' . date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan_Penjualan_QxuanStore_<?= !empty($periodLabel) ? preg_replace('/[^A-Za-z0-9]/', '_', $periodLabel) : date('Ymd') ?></title>
    
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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .btn-toolbar {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 6px;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-print {
            background: #dc2626;
            color: #fff;
            box-shadow: 0 2px 4px rgba(220, 38, 38, 0.3);
        }
        .btn-print:hover {
            background: #b91c1c;
        }
        .btn-close {
            background: #334155;
            color: #fff;
        }
        .btn-close:hover {
            background: #475569;
        }

        /* Kop Surat Resmi Toko */
        .kop-surat {
            text-align: center;
            border-bottom: 3px double #0f172a;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }
        .kop-surat h1 {
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: #0f172a;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .kop-surat .kop-sub {
            font-size: 0.88rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 3px;
        }
        .kop-surat .kop-address {
            font-size: 0.84rem;
            color: #475569;
            margin-bottom: 3px;
            line-height: 1.4;
        }
        .kop-surat .kop-contact {
            font-size: 0.83rem;
            color: #475569;
        }

        /* Judul Laporan */
        .report-heading {
            text-align: center;
            margin-bottom: 20px;
        }
        .report-heading h2 {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .report-heading p {
            font-size: 0.88rem;
            color: #475569;
            margin-top: 4px;
            font-weight: 600;
        }

        /* Table Scroll Wrapper for Mobile */
        .table-scroll-wrap {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 24px;
        }

        /* Report Table */
        table.report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
            min-width: 780px;
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
            white-space: nowrap;
        }
        .status-selesai { background: #dcfce7; color: #166534; }
        .status-diproses { background: #e0f2fe; color: #075985; }
        .status-dikirim { background: #ede9fe; color: #5b21b6; }
        .status-menunggu { background: #fef3c7; color: #92400e; }
        .status-batal { background: #fee2e2; color: #991b1b; }

        /* Signature Section */
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 230px;
            text-align: center;
            font-size: 0.85rem;
        }
        .sig-title, .sig-date {
            margin-bottom: 3px;
            color: #1e293b;
        }
        .sig-role {
            font-weight: 700;
            color: #0f172a;
        }
        .signature-space {
            height: 75px;
        }
        .signature-name {
            font-weight: 700;
            color: #0f172a;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        /* Mobile Breakpoints */
        @media (max-width: 768px) {
            body {
                padding: 10px 8px;
            }
            .no-print-toolbar {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
                padding: 14px;
            }
            .no-print-toolbar > div:first-child {
                text-align: center;
            }
            .no-print-toolbar > div:last-child {
                display: flex;
                gap: 8px;
                width: 100%;
            }
            .btn-toolbar {
                flex: 1;
                justify-content: center;
                text-align: center;
                padding: 10px;
            }
            .report-page {
                padding: 20px 14px;
                border-radius: 8px;
            }
            .kop-surat h1 {
                font-size: 1.35rem;
            }
            .signature-section {
                gap: 12px;
            }
            .signature-box {
                width: 48%;
                font-size: 0.78rem;
            }
            .signature-space {
                height: 60px;
            }
            .signature-name {
                font-size: 0.82rem;
            }
        }

        /* Print Media Styles */
        @media print {
            @page {
                size: auto;
                margin: 15mm 12mm 15mm 12mm;
            }
            body {
                background: #fff !important;
                padding: 0 !important;
                color: #000 !important;
            }
            .no-print-toolbar {
                display: none !important;
            }
            .report-page {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
            }
            .table-scroll-wrap {
                overflow: visible !important;
                margin-bottom: 20px;
            }
            table.report-table {
                min-width: 0 !important;
                width: 100% !important;
            }
            table.report-table th {
                background: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            table.report-table th, 
            table.report-table td {
                border-color: #333 !important;
            }
            .status-pill {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .signature-section {
                page-break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>

    <!-- Non-Print Toolbar -->
    <div class="no-print-toolbar">
        <div>
            <strong style="font-size: 0.95rem;"><i class="fa-solid fa-file-pdf"></i> Pratinjau Dokumen Laporan Penjualan</strong>
            <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 2px;">Gunakan 'Cetak / Simpan PDF' untuk mencetak langsung atau simpan file .pdf</div>
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
        <!-- Kop Surat Resmi Toko -->
        <div class="kop-surat">
            <h1>QXUAN STORE</h1>
            <p class="kop-sub">Toko Sepatu Sneakers & Casual Original</p>
            <p class="kop-address"><?= htmlspecialchars($contact->admin_address ?? 'Perum Taman Raya Rajeg Jl. Melati 1, Mekarsari, Kec. Rajeg, Kab. Tangerang, Banten 15540') ?></p>
            <p class="kop-contact">No. Telp / WhatsApp: <?= htmlspecialchars($contact->admin_telp ?? '082188997766') ?> &bull; Email: <?= htmlspecialchars($contact->admin_email ?? 'support@qxuanstore.com') ?></p>
        </div>

        <!-- Judul Laporan -->
        <div class="report-heading">
            <h2>LAPORAN PENJUALAN</h2>
            <?php if (!empty($periodLabel)): ?>
                <p>Periode: <?= htmlspecialchars($periodLabel) ?></p>
            <?php endif; ?>
        </div>

        <!-- Sales Data Table (Dengan Scroll Wrapper Responsif di Mobile) -->
        <div class="table-scroll-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th width="30" class="text-center">No</th>
                        <th width="110">No. Invoice</th>
                        <th width="85">Tanggal</th>
                        <th>Nama Pelanggan</th>
                        <th>Rincian Produk & Ukuran</th>
                        <th width="45" class="text-center">Qty</th>
                        <th width="105">Metode / Kurir</th>
                        <th width="85" class="text-center">Status</th>
                        <th width="115" class="text-right">Total (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orderReport)): ?>
                        <?php 
                        $no = 1; 
                        foreach ($orderReport as $row): 
                            $statusClass = 'status-menunggu';
                            if ($row->status_order === 'Diproses') $statusClass = 'status-diproses';
                            if ($row->status_order === 'Dikirim') $statusClass = 'status-dikirim';
                            if ($row->status_order === 'Selesai') $statusClass = 'status-selesai';
                            if ($row->status_order === 'Dibatalkan') $statusClass = 'status-batal';
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
                    <tr style="background: #f8fafc; font-size: 0.88rem;">
                        <td colspan="8" class="text-right" style="font-weight: 700; padding: 10px;">
                            TOTAL PENDAPATAN (STATUS SELESAI):
                        </td>
                        <td class="text-right" style="font-weight: 800; color: #0f172a; font-size: 0.95rem; padding: 10px;">
                            <?= format_rupiah($totalPendapatan) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Section Tanda Tangan Resmi -->
        <div class="signature-section">
            <div class="signature-box">
                <p class="sig-title">Disiapkan Oleh,</p>
                <p class="sig-role">Admin Operasional</p>
                <div class="signature-space"></div>
                <div class="signature-name">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>
            </div>

            <div class="signature-box">
                <p class="sig-date">Tangerang, <?= $tglCetak ?></p>
                <p class="sig-role">Pemilik Usaha / Owner</p>
                <div class="signature-space"></div>
                <div class="signature-name">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>
            </div>
        </div>
    </div>

</body>
</html>
