<?php
/**
 * Order Success & Midtrans Payment Gateway Page - QxuanStore
 * Modern Shoe E-Commerce (User Area)
 */

$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';
require_once $rootPath . '/config/midtrans.php';

require_user();

$invoice = trim($_GET['invoice'] ?? '');
$order = db_fetch_one("SELECT * FROM tb_order WHERE invoice_no = ?", [$invoice]);

if (!$order) {
    redirect(base_url('index.php'));
}

$pageTitle = 'Pembayaran Pesanan #' . $order->invoice_no;
require_once $rootPath . '/includes/header.php';

$items = db_fetch_all("
    SELECT od.*, p.image_produk 
    FROM tb_order_detail od 
    LEFT JOIN tb_produk p ON od.id_produk = p.id_produk 
    WHERE od.id_order = ?
", [$order->id_order]);

// Generate / Get Snap Token
$snapToken = $order->snap_token;
if (empty($snapToken) && $order->status_order === 'Menunggu Pembayaran') {
    $snapToken = midtrans_get_snap_token($order, $items);
}

$isWaitingPayment = ($order->status_order === 'Menunggu Pembayaran');
$statusBadgeClass = 'badge-warning';
if ($order->status_order === 'Diproses') $statusBadgeClass = 'badge-info';
if ($order->status_order === 'Dikirim') $statusBadgeClass = 'badge-primary';
if ($order->status_order === 'Selesai') $statusBadgeClass = 'badge-success';
if ($order->status_order === 'Dibatalkan') $statusBadgeClass = 'badge-danger';
?>

<!-- Midtrans Snap JS Script -->
<script src="<?= MIDTRANS_SNAP_URL ?>" data-client-key="<?= MIDTRANS_CLIENT_KEY ?>"></script>

<div class="container" style="padding-top: 40px; padding-bottom: 70px; max-width: 820px;">
    
    <?= render_flash() ?>

    <?php if ($isWaitingPayment): ?>
        <!-- Card 1: Menunggu Pembayaran & Midtrans Gateway Action -->
        <div style="background: #fff; padding: 36px 30px; border-radius: 16px; border: 1px solid #e2e8f0; text-align: center; margin-bottom: 30px; box-shadow: var(--shadow-sm);">
            <div style="width: 72px; height: 72px; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin: 0 auto 16px;">
                <i class="fa-solid fa-credit-card"></i>
            </div>
            
            <span class="badge badge-warning" style="font-size: 0.82rem; padding: 6px 14px; margin-bottom: 8px;">
                <i class="fa-solid fa-clock"></i> Menunggu Pembayaran
            </span>
            
            <h1 style="font-size: 1.85rem; font-weight: 800; color: #0f172a; margin-top: 8px; margin-bottom: 6px;">Selesaikan Pembayaran Pesanan</h1>
            <p style="color: #64748b; font-size: 0.95rem;">
                No. Invoice: <strong style="color: #2563eb;">#<?= htmlspecialchars($order->invoice_no) ?></strong> &bull; <?= date('d M Y, H:i', strtotime($order->created_at)) ?>
            </p>

            <div style="margin: 24px auto; max-width: 480px; padding: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                <small style="color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.78rem; display: block; margin-bottom: 4px;">Total Tagihan Pembayaran</small>
                <div style="font-size: 2.2rem; font-weight: 800; color: #2563eb; letter-spacing: -0.5px;">
                    <?= format_rupiah($order->total_bayar) ?>
                </div>
            </div>

            <!-- Payment Channels Info -->
            <div style="margin-bottom: 24px;">
                <small style="color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; display: block; margin-bottom: 12px;">Metode Pembayaran Tersedia (Midtrans Snap)</small>
                <div style="display: flex; justify-content: center; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <span style="background: #f1f5f9; border: 1px solid #e2e8f0; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-qrcode" style="color: #ea580c;"></i> QRIS (GoPay/ShopeePay/Dana)
                    </span>
                    <span style="background: #f1f5f9; border: 1px solid #e2e8f0; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-building-columns" style="color: #2563eb;"></i> Virtual Account (BCA, BNI, BRI, Mandiri)
                    </span>
                    <span style="background: #f1f5f9; border: 1px solid #e2e8f0; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-credit-card" style="color: #16a34a;"></i> Transfer Bank Otomatis
                    </span>
                </div>
            </div>

            <!-- Primary Payment Gateway Action -->
            <div style="max-width: 480px; margin: 0 auto;">
                <button type="button" id="pay-button" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px 24px; font-size: 1.05rem; border-radius: 12px; box-shadow: 0 8px 20px rgba(37,99,235,0.35);">
                    <i class="fa-solid fa-lock"></i> Bayar Sekarang via Midtrans Gateway
                </button>

                <!-- Sandbox Testing Simulation Button -->
                <div style="margin-top: 14px; padding-top: 14px; border-top: 1px dashed #e2e8f0;">
                    <button type="button" onclick="triggerPaymentSimulator()" class="btn btn-secondary" style="width: 100%; justify-content: center; padding: 10px 16px; font-size: 0.85rem; border-radius: 10px;">
                        <i class="fa-solid fa-bolt-lightning" style="color: #f59e0b;"></i> Simulasi Bayar Langsung (Testing Sandbox)
                    </button>
                    <small style="display: block; color: #64748b; font-size: 0.78rem; margin-top: 6px;">
                        Gunakan tombol simulasi di atas untuk langsung menguji perubahan status otomatis menjadi <strong>Diproses</strong> tanpa harus membuka gateway.
                    </small>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Card 1: Pembayaran Berhasil (Diproses / Selesai) -->
        <div style="background: #fff; padding: 36px 30px; border-radius: 16px; border: 1px solid #e2e8f0; text-align: center; margin-bottom: 30px; box-shadow: var(--shadow-sm);">
            <div style="width: 76px; height: 76px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 2.6rem; margin: 0 auto 16px;">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            
            <span class="badge badge-success" style="font-size: 0.85rem; padding: 6px 14px; margin-bottom: 8px;">
                <i class="fa-solid fa-circle-check"></i> Pembayaran Berhasil &bull; <?= htmlspecialchars($order->status_order) ?>
            </span>

            <h1 style="font-size: 1.85rem; font-weight: 800; color: #0f172a; margin-top: 8px; margin-bottom: 6px;">
                Pembayaran Anda Telah Diterima!
            </h1>
            <p style="color: #64748b; font-size: 0.95rem;">
                No. Invoice: <strong style="color: #2563eb;">#<?= htmlspecialchars($order->invoice_no) ?></strong>
            </p>

            <div style="margin: 24px auto; max-width: 520px; padding: 20px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; text-align: left;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.92rem;">
                    <span style="color: #166534;">Metode Pembayaran:</span>
                    <strong style="color: #14532d;"><?= htmlspecialchars($order->metode_pembayaran ?? 'Midtrans Payment Gateway') ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.92rem;">
                    <span style="color: #166534;">Waktu Pembayaran:</span>
                    <strong style="color: #14532d;"><?= !empty($order->waktu_bayar) ? date('d M Y, H:i', strtotime($order->waktu_bayar)) : date('d M Y, H:i', strtotime($order->created_at)) ?> WIB</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 1rem; padding-top: 8px; border-top: 1px dashed #86efac;">
                    <span style="font-weight: 700; color: #166534;">Total Dibayar:</span>
                    <strong style="color: #15803d; font-size: 1.15rem;"><?= format_rupiah($order->total_bayar) ?></strong>
                </div>
            </div>

            <p style="color: #15803d; font-weight: 600; font-size: 0.92rem;">
                <i class="fa-solid fa-boxes-packing"></i> Pesanan sepatu Anda sedang dipersiapkan dan segera dikirim ke alamat tujuan!
            </p>
        </div>
    <?php endif; ?>

    <!-- Card 2: Order Detail Summary -->
    <div style="background: #fff; padding: 30px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
            <h2 style="font-size: 1.15rem; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-receipt" style="color: #2563eb;"></i> Rincian Pesanan Sepatu
            </h2>
            <span class="badge <?= $statusBadgeClass ?>"><?= htmlspecialchars($order->status_order) ?></span>
        </div>

        <!-- Items List -->
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
            <?php if (!empty($order->no_resi)): ?>
                <p style="margin-top: 4px; color: #2563eb;"><strong>No. Resi Pelacakan:</strong> <span class="badge badge-info"><?= htmlspecialchars($order->no_resi) ?></span></p>
            <?php endif; ?>
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

<script>
const INVOICE_NO = '<?= addslashes($order->invoice_no) ?>';
const SNAP_TOKEN = '<?= addslashes($snapToken ?? '') ?>';

// Handle Midtrans Snap Payment
const payBtn = document.getElementById('pay-button');
if (payBtn) {
    payBtn.addEventListener('click', function () {
        if (!SNAP_TOKEN) {
            Swal.fire({
                title: 'Token Pembayaran',
                text: 'Memproses koneksi gateway...',
                icon: 'info'
            });
            return;
        }

        // Check if Snap is loaded
        if (typeof window.snap !== 'undefined' && typeof window.snap.pay === 'function') {
            window.snap.pay(SNAP_TOKEN, {
                onSuccess: function (result) {
                    const method = (result.payment_type ? 'Midtrans (' + result.payment_type.toUpperCase() + ')' : 'Midtrans QRIS / Virtual Account');
                    verifyPaymentSuccess(method);
                },
                onPending: function (result) {
                    const method = (result.payment_type ? 'Midtrans (' + result.payment_type.toUpperCase() + ')' : 'Midtrans Virtual Account');
                    Swal.fire({
                        title: 'Menunggu Penyelesaian Pembayaran',
                        text: 'Silakan selesaikan pembayaran sesuai petunjuk yang tertera.',
                        icon: 'info'
                    }).then(() => {
                        window.location.reload();
                    });
                },
                onError: function (result) {
                    Swal.fire({
                        title: 'Pembayaran Gagal',
                        text: 'Terjadi kendala saat memproses pembayaran via gateway.',
                        icon: 'error'
                    });
                },
                onClose: function () {
                    Swal.fire({
                        title: 'Pembayaran Belum Selesai',
                        text: 'Anda menutup jendela pembayaran. Anda dapat melanjutkannya kapan saja.',
                        icon: 'warning',
                        confirmButtonColor: '#2563eb'
                    });
                }
            });
        } else {
            // Fallback simulator if external Snap JS is blocked/offline
            triggerPaymentSimulator();
        }
    });
}

// Function to call verification API and flip order status to Diproses
function verifyPaymentSuccess(methodName) {
    Swal.fire({
        title: 'Verifikasi Pembayaran...',
        text: 'Memperbarui status pesanan Anda ke sistem toko.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const formData = new FormData();
    formData.append('invoice', INVOICE_NO);
    formData.append('method', methodName);

    fetch('<?= base_url("api/verify_payment.php") ?>', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Pembayaran Diterima!',
                text: 'Status pesanan otomatis diubah menjadi DIPROSES.',
                icon: 'success',
                confirmButtonColor: '#16a34a',
                confirmButtonText: 'Bagus, Lihat Detail'
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire('Error', data.message || 'Gagal memverifikasi', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Info', 'Pembayaran sedang diproses sistem toko.', 'info').then(() => {
            window.location.reload();
        });
    });
}

// Trigger Sandbox Testing Payment Simulator
function triggerPaymentSimulator() {
    Swal.fire({
        title: 'Pilih Metode Pembayaran (Simulator)',
        text: 'Simulasikan pembayaran instan untuk mengubah status pesanan menjadi "Diproses":',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-qrcode"></i> Bayar via QRIS (GoPay/ShopeePay)',
        showDenyButton: true,
        denyButtonText: '<i class="fa-solid fa-building-columns"></i> Bayar via Virtual Account BCA',
        denyButtonColor: '#059669',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            verifyPaymentSuccess('Midtrans QRIS (GoPay/ShopeePay)');
        } else if (result.isDenied) {
            verifyPaymentSuccess('Midtrans Virtual Account (BCA)');
        }
    });
}
</script>

<?php require_once $rootPath . '/includes/footer.php'; ?>
