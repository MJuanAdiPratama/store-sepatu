<?php
/**
 * Ekspor Data Laporan Penjualan ke Excel (.CSV)
 * QxuanStore - Shoe E-Commerce
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

// File Suffix
$periodLabel = 'Semua_Periode';
if (!empty($selectedMonth) && !empty($selectedYear)) {
    $periodLabel = ($months[$selectedMonth] ?? $selectedMonth) . '_' . $selectedYear;
} elseif (!empty($selectedMonth)) {
    $periodLabel = 'Bulan_' . ($months[$selectedMonth] ?? $selectedMonth);
} elseif (!empty($selectedYear)) {
    $periodLabel = 'Tahun_' . $selectedYear;
}
$cleanPeriod = preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $periodLabel));
$filename = "Laporan_Penjualan_QxuanStore_" . $cleanPeriod . ".csv";

// Headers for CSV download
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Open PHP output stream
$output = fopen('php://output', 'w');

// Output UTF-8 BOM so Microsoft Excel opens it cleanly without garbled characters
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Company & Report Header rows in CSV
fputcsv($output, ['QXUANSTORE INDONESIA - LAPORAN PENJUALAN']);
fputcsv($output, ['Periode Laporan', str_replace('_', ' ', $cleanPeriod)]);
fputcsv($output, ['Filter Status', !empty($statusFilter) ? $statusFilter : 'Semua Status']);
fputcsv($output, ['Tanggal Ekspor', date('d-m-Y H:i') . ' WIB']);
fputcsv($output, []); // empty line

// Column Headers
fputcsv($output, [
    'No',
    'No. Invoice',
    'Tanggal Transaksi',
    'Waktu',
    'Nama Pelanggan',
    'No. WhatsApp',
    'Metode Pengambilan / Kurir',
    'Rincian Produk & Ukuran',
    'Total Qty (Pasang)',
    'Metode Pembayaran',
    'Status Pesanan',
    'Total Bayar (Rp)',
    'No. Resi'
]);

$no = 1;
$totalOmzet = 0;
$totalShoesQty = 0;
$totalSelesai = 0;

foreach ($orders as $ord) {
    // Fetch items
    $items = db_fetch_all("
        SELECT od.*, p.nama_produk 
        FROM tb_order_detail od 
        LEFT JOIN tb_produk p ON od.id_produk = p.id_produk 
        WHERE od.id_order = ?
    ", [$ord->id_order]);

    $itemNames = [];
    $orderQty = 0;
    foreach ($items as $it) {
        $itemNames[] = $it->nama_produk . ' (Size ' . $it->ukuran . ', ' . $it->qty . 'x)';
        $orderQty += (int)$it->qty;
    }

    if ($ord->status_order === 'Selesai') {
        $totalSelesai++;
        $totalOmzet += (float)$ord->total_bayar;
        $totalShoesQty += $orderQty;
    }

    $itemSummary = !empty($itemNames) ? implode('; ', $itemNames) : '-';
    $tgl = date('d/m/Y', strtotime($ord->created_at));
    $jam = date('H:i', strtotime($ord->created_at)) . ' WIB';

    fputcsv($output, [
        $no++,
        '#' . $ord->invoice_no,
        $tgl,
        $jam,
        $ord->nama_penerima,
        $ord->no_hp,
        $ord->kurir ?: 'Ambil di Toko Fisik',
        $itemSummary,
        $orderQty,
        $ord->metode_pembayaran ?: 'QRIS/Transfer Bank',
        $ord->status_order,
        (float)$ord->total_bayar,
        $ord->no_resi ?: '-'
    ]);
}

// Summary footer rows
fputcsv($output, []);
fputcsv($output, ['RINGKASAN EKSEKUTIF']);
fputcsv($output, ['Total Transaksi Tercatat', count($orders) . ' pesanan']);
fputcsv($output, ['Pesanan Berhasil Selesai', $totalSelesai . ' pesanan']);
fputcsv($output, ['Total Sepatu Terjual (Selesai)', $totalShoesQty . ' pasang']);
fputcsv($output, ['Total Omzet Terealisasi (Rp)', $totalOmzet]);

fclose($output);
exit;
