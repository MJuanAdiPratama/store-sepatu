<?php
/**
 * Order Success & Direct Interactive Payment Page - QxuanStore
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

$pageTitle = 'Pembayaran Pesanan #' . $order->invoice_no;
require_once $rootPath . '/includes/header.php';

$items = db_fetch_all("
    SELECT od.*, p.image_produk 
    FROM tb_order_detail od 
    LEFT JOIN tb_produk p ON od.id_produk = p.id_produk 
    WHERE od.id_order = ?
", [$order->id_order]);

$isWaitingPayment = ($order->status_order === 'Menunggu Pembayaran');
$statusBadgeClass = 'badge-warning';
if ($order->status_order === 'Diproses') $statusBadgeClass = 'badge-info';
if ($order->status_order === 'Dikirim') $statusBadgeClass = 'badge-primary';
if ($order->status_order === 'Selesai') $statusBadgeClass = 'badge-success';
if ($order->status_order === 'Dibatalkan') $statusBadgeClass = 'badge-danger';

// Get admin WhatsApp contact
$adminContact = get_admin_contact();
$adminPhone = $adminContact ? $adminContact->admin_telp : '082188997766';
$adminPhoneClean = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $adminPhone));

// Customer phone digits for Virtual Account numbers
$customerPhoneDigits = preg_replace('/[^0-9]/', '', $order->no_hp);
if (strlen($customerPhoneDigits) > 10) {
    $customerPhoneDigits = substr($customerPhoneDigits, -9);
}

// Pre-filled WhatsApp confirmation message
$waConfirmMsg = "Halo Admin QxuanStore, saya ingin mengonfirmasi pembayaran untuk pesanan sepatu:\n\n";
$waConfirmMsg .= "🧾 No. Invoice: #" . $order->invoice_no . "\n";
$waConfirmMsg .= "👤 Nama Penerima: " . $order->nama_penerima . "\n";
$waConfirmMsg .= "💰 Total Tagihan: " . format_rupiah($order->total_bayar) . "\n\n";
$waConfirmMsg .= "Berikut saya lampirkan foto bukti pembayaran/struk transfernya. Mohon segera diproses ya. Terima kasih! 🙏👟✨";
$waConfirmUrl = "https://api.whatsapp.com/send?phone=" . $adminPhoneClean . "&text=" . urlencode($waConfirmMsg);

// Dynamic QRIS QR Code URL
$qrData = "00020101021226670014ID.LINKAJA.WWW01189360091100212345670215ID10202610280010303UME51440014ID.CO.QRIS.WWW0215ID10202610280010303UME520458125303360540" . $order->total_bayar . "5802ID5910QXUANSTORE6009TANGERANG61051554062070703A016304";
$qrisImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=260x260&margin=10&data=" . urlencode($qrData);
?>

<div class="container" style="padding-top: 30px; padding-bottom: 70px; max-width: 840px;">
    
    <?= render_flash() ?>

    <?php if ($isWaitingPayment): ?>
        <!-- Card: Menunggu Pembayaran & Interactive Payment Channels -->
        <div style="background: #fff; padding: 32px 24px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 30px; box-shadow: var(--shadow-sm);">
            
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="width: 68px; height: 68px; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 14px;">
                    <i class="fa-solid fa-credit-card"></i>
                </div>
                
                <span class="badge badge-warning" style="font-size: 0.8rem; padding: 6px 14px; margin-bottom: 8px;">
                    <i class="fa-solid fa-clock"></i> Menunggu Pembayaran
                </span>
                
                <h1 style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin-top: 8px; margin-bottom: 6px;">Selesaikan Pembayaran Pesanan</h1>
                <p style="color: #64748b; font-size: 0.92rem;">
                    No. Invoice: <strong style="color: #2563eb;">#<?= htmlspecialchars($order->invoice_no) ?></strong> &bull; <?= date('d M Y, H:i', strtotime($order->created_at)) ?>
                </p>

                <!-- Total Amount Card -->
                <div style="margin: 20px auto 0; max-width: 440px; padding: 18px 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                    <small style="color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.78rem; display: block; margin-bottom: 4px;">Total Tagihan Pembayaran</small>
                    <div style="font-size: 2.1rem; font-weight: 800; color: #2563eb; letter-spacing: -0.5px;">
                        <?= format_rupiah($order->total_bayar) ?>
                    </div>
                </div>
            </div>

            <!-- Payment Methods Selector (Interactive Tabs) -->
            <div style="margin-bottom: 12px; text-align: center;">
                <small style="color: #475569; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.5px; display: block; margin-bottom: 12px;">
                    Pilih Metode Pembayaran di Bawah Ini:
                </small>
            </div>

            <div class="payment-tabs-grid">
                <button type="button" class="payment-tab-btn active" id="tabBtnQris" onclick="switchPaymentTab('qris')">
                    <i class="fa-solid fa-qrcode" style="color: #ea580c;"></i>
                    <div>
                        <strong>QRIS</strong>
                        <small style="display: block;">GoPay, OVO, Shopee, BCA</small>
                    </div>
                </button>
                <button type="button" class="payment-tab-btn" id="tabBtnVa" onclick="switchPaymentTab('va')">
                    <i class="fa-solid fa-building-columns" style="color: #2563eb;"></i>
                    <div>
                        <strong>Virtual Account</strong>
                        <small style="display: block;">BCA, Mandiri, BNI, BRI</small>
                    </div>
                </button>
                <button type="button" class="payment-tab-btn" id="tabBtnTransfer" onclick="switchPaymentTab('transfer')">
                    <i class="fa-solid fa-money-bill-transfer" style="color: #16a34a;"></i>
                    <div>
                        <strong>Transfer Bank</strong>
                        <small style="display: block;">BCA & Mandiri Manual</small>
                    </div>
                </button>
            </div>

            <!-- ===================================================================
                 TAB 1: QRIS PAYMENT PANE
                 =================================================================== -->
            <div class="payment-tab-pane active" id="paneQris">
                <div class="qris-card">
                    <div class="qris-header">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-qrcode" style="font-size: 1.2rem;"></i>
                            <strong style="font-size: 0.95rem; letter-spacing: 0.5px;">QRIS</strong>
                        </div>
                        <span style="font-size: 0.75rem; background: rgba(255,255,255,0.25); padding: 3px 8px; border-radius: 4px; font-weight: 700;">GPN / Bank & E-Wallet</span>
                    </div>

                    <div class="qris-body">
                        <div style="font-weight: 800; font-size: 1rem; color: #0f172a; margin-bottom: 2px;">QXUANSTORE SHOES</div>
                        <small style="color: #64748b; font-size: 0.78rem; display: block; margin-bottom: 12px;">NMID: ID1026102800192 &bull; A01</small>

                        <div class="qris-img-container">
                            <img src="<?= $qrisImageUrl ?>" alt="QRIS Barcode" class="qris-img" id="qrisImage">
                        </div>

                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; margin-bottom: 14px;">
                            <span style="font-size: 0.8rem; color: #64748b; display: block;">Total Pembayaran:</span>
                            <strong style="font-size: 1.25rem; color: #0f172a; font-weight: 800;"><?= format_rupiah($order->total_bayar) ?></strong>
                        </div>

                        <a href="<?= $qrisImageUrl ?>" target="_blank" download="QRIS-<?= $order->invoice_no ?>.png" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center; padding: 9px 12px; font-size: 0.82rem; border-radius: 8px;">
                            <i class="fa-solid fa-download"></i> Unduh / Buka Gambar QRIS Penuh
                        </a>
                    </div>
                </div>

                <div style="max-width: 520px; margin: 0 auto 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; font-size: 0.85rem; line-height: 1.6; color: #334155;">
                    <strong style="color: #0f172a; display: block; margin-bottom: 8px; font-size: 0.9rem;">
                        <i class="fa-solid fa-circle-info" style="color: #2563eb;"></i> Cara Pembayaran via QRIS:
                    </strong>
                    <ol style="margin-left: 18px; margin-bottom: 0;">
                        <li>Buka aplikasi <strong>BCA Mobile, Livin' Mandiri, GoPay, OVO, DANA, ShopeePay</strong>, atau e-wallet/m-banking lainnya.</li>
                        <li>Pilih menu <strong>Scan QR / Bayar QRIS</strong>.</li>
                        <li>Arahkan kamera HP ke barcode QRIS di atas.</li>
                        <li>Pastikan nama merchant adalah <strong>QXUANSTORE SHOES</strong> dan nominal tagihan sesuai (<?= format_rupiah($order->total_bayar) ?>).</li>
                        <li>Selesaikan pembayaran dan simpan bukti transaksi Anda.</li>
                    </ol>
                </div>
            </div>

            <!-- ===================================================================
                 TAB 2: VIRTUAL ACCOUNT PANE
                 =================================================================== -->
            <div class="payment-tab-pane" id="paneVa">
                <div style="max-width: 520px; margin: 0 auto 24px;">
                    
                    <!-- Bank Pills Selection -->
                    <div style="display: flex; gap: 8px; justify-content: center; margin-bottom: 16px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-sm btn-primary" id="btnVaBca" onclick="switchVaBank('bca')" style="padding: 7px 14px; border-radius: 8px;">BCA VA</button>
                        <button type="button" class="btn btn-sm btn-secondary" id="btnVaMandiri" onclick="switchVaBank('mandiri')" style="padding: 7px 14px; border-radius: 8px;">Mandiri VA</button>
                        <button type="button" class="btn btn-sm btn-secondary" id="btnVaBni" onclick="switchVaBank('bni')" style="padding: 7px 14px; border-radius: 8px;">BNI VA</button>
                        <button type="button" class="btn btn-sm btn-secondary" id="btnVaBri" onclick="switchVaBank('bri')" style="padding: 7px 14px; border-radius: 8px;">BRI VA</button>
                    </div>

                    <!-- VA Detail Card -->
                    <div style="background: #fff; border: 1.5px solid #cbd5e1; border-radius: 14px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.04);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <strong style="color: #0f172a; font-size: 1rem;" id="vaBankName">BCA Virtual Account</strong>
                            <span class="badge badge-info" style="font-size: 0.72rem;">Verifikasi Otomatis</span>
                        </div>

                        <label style="font-size: 0.78rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Nomor Virtual Account</label>
                        <div class="copy-box">
                            <span id="vaNumberText">88000<?= $customerPhoneDigits ?></span>
                            <button type="button" class="copy-btn" id="btnCopyVa" onclick="copyToClipboard('vaNumberText', 'btnCopyVa')">
                                <i class="fa-solid fa-copy"></i> Salin
                            </button>
                        </div>

                        <label style="font-size: 0.78rem; color: #64748b; font-weight: 700; text-transform: uppercase; margin-top: 10px; display: block;">Total Tagihan Pembayaran</label>
                        <div class="copy-box" style="font-size: 1.1rem; color: #2563eb;">
                            <span id="vaAmountText"><?= format_rupiah($order->total_bayar) ?></span>
                            <button type="button" class="copy-btn" id="btnCopyVaAmount" onclick="copyRawValue('<?= (int)$order->total_bayar ?>', 'btnCopyVaAmount')">
                                <i class="fa-solid fa-copy"></i> Salin Nominal
                            </button>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div style="margin-top: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; font-size: 0.85rem; line-height: 1.6; color: #334155;">
                        <strong style="color: #0f172a; display: block; margin-bottom: 6px;">Cara Bayar via m-Banking:</strong>
                        <ol style="margin-left: 18px; margin-bottom: 0;">
                            <li>Buka aplikasi m-Banking Anda, pilih menu <strong>Transfer</strong> &rarr; <strong>Virtual Account</strong>.</li>
                            <li>Masukkan Nomor Virtual Account yang telah Anda salin di atas.</li>
                            <li>Periksa detail pembayaran (nama merchant <strong>QxuanStore</strong> & total tagihan).</li>
                            <li>Masukkan PIN m-Banking Anda untuk menyelesaikan transaksi.</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- ===================================================================
                 TAB 3: MANUAL BANK TRANSFER PANE
                 =================================================================== -->
            <div class="payment-tab-pane" id="paneTransfer">
                <div style="max-width: 520px; margin: 0 auto 24px;">
                    
                    <!-- Bank BCA -->
                    <div style="background: #fff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 18px; margin-bottom: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.03);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-building-columns" style="color: #2563eb; font-size: 1.2rem;"></i>
                                <strong style="color: #0f172a; font-size: 0.98rem;">Bank BCA</strong>
                            </div>
                            <span style="font-size: 0.78rem; color: #64748b;">a.n <strong>QxuanStore Official</strong></span>
                        </div>
                        <div class="copy-box">
                            <span id="bcaRekText">812345678901</span>
                            <button type="button" class="copy-btn" id="btnCopyBca" onclick="copyToClipboard('bcaRekText', 'btnCopyBca')">
                                <i class="fa-solid fa-copy"></i> Salin Rekening
                            </button>
                        </div>
                    </div>

                    <!-- Bank Mandiri -->
                    <div style="background: #fff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 18px; margin-bottom: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.03);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-building-columns" style="color: #ea580c; font-size: 1.2rem;"></i>
                                <strong style="color: #0f172a; font-size: 0.98rem;">Bank Mandiri</strong>
                            </div>
                            <span style="font-size: 0.78rem; color: #64748b;">a.n <strong>QxuanStore Official</strong></span>
                        </div>
                        <div class="copy-box">
                            <span id="mandiriRekText">1560098765432</span>
                            <button type="button" class="copy-btn" id="btnCopyMandiri" onclick="copyToClipboard('mandiriRekText', 'btnCopyMandiri')">
                                <i class="fa-solid fa-copy"></i> Salin Rekening
                            </button>
                        </div>
                    </div>

                    <!-- Transfer Amount -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; text-align: center;">
                        <span style="font-size: 0.8rem; color: #64748b; display: block; margin-bottom: 4px;">Nominal yang Harus Ditransfer (Presisi):</span>
                        <div style="font-size: 1.4rem; font-weight: 800; color: #2563eb; margin-bottom: 8px;">
                            <?= format_rupiah($order->total_bayar) ?>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" id="btnCopyTransferAmount" onclick="copyRawValue('<?= (int)$order->total_bayar ?>', 'btnCopyTransferAmount')" style="padding: 6px 14px; border-radius: 6px; font-size: 0.8rem;">
                            <i class="fa-solid fa-copy"></i> Salin Nominal Transfer
                        </button>
                    </div>
                </div>
            </div>

            <!-- ===================================================================
                 SECTION: BUKTI PEMBAYARAN & KONFIRMASI
                 =================================================================== -->
            <div style="margin-top: 24px; padding-top: 24px; border-top: 1.5px dashed #cbd5e1;">
                <h3 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; text-align: center; margin-bottom: 6px;">
                    <i class="fa-solid fa-shield-halved" style="color: #16a34a; margin-right: 6px;"></i> Konfirmasi Pembayaran Anda
                </h3>
                <p style="text-align: center; color: #64748b; font-size: 0.86rem; margin-bottom: 20px;">
                    Setelah menyelesaikan pembayaran, silakan unggah foto bukti transfer atau struk pembayaran Anda di bawah ini:
                </p>

                <div style="max-width: 520px; margin: 0 auto; display: flex; flex-direction: column; gap: 14px;">
                    
                    <!-- Upload Proof Form -->
                    <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 18px;">
                        <label for="buktiInput" style="display: block; font-weight: 700; color: #1e293b; font-size: 0.88rem; margin-bottom: 6px;">
                            <i class="fa-solid fa-image" style="color: #2563eb;"></i> Unggah Bukti Pembayaran / Struk (JPG, PNG)
                        </label>
                        <input type="file" id="buktiInput" accept="image/jpeg,image/png,image/webp" class="form-control" style="font-size: 0.85rem;" onchange="previewBuktiImage(this)">
                        
                        <!-- Image Preview Container -->
                        <div id="previewContainer" style="display: none; margin-top: 12px; text-align: center; background: #fff; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <img id="previewImg" src="" alt="Pratinjau Bukti" style="max-height: 160px; max-width: 100%; border-radius: 6px; object-fit: contain;">
                            <small style="display: block; color: #64748b; margin-top: 4px; font-size: 0.75rem;">Foto bukti siap diunggah</small>
                        </div>

                        <button type="button" onclick="submitUploadBukti()" id="btnSubmitBukti" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; margin-top: 12px; border-radius: 8px; font-weight: 700;">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Upload Bukti & Konfirmasi Bayar
                        </button>
                    </div>

                    <!-- Direct Confirmation Button -->
                    <button type="button" onclick="confirmInstantPayment()" class="btn btn-secondary" style="width: 100%; justify-content: center; padding: 11px; border-radius: 8px; font-size: 0.88rem; color: #475569;">
                        <i class="fa-solid fa-circle-check" style="color: #2563eb;"></i> Saya Sudah Selesai Bayar (Konfirmasi Cepat)
                    </button>
                </div>
            </div>

        </div>

    <?php elseif ($order->status_order === 'Dikirim'): ?>
        <!-- Card: Pesanan Sedang Dikirim / Siap Diambil di Toko -->
        <div style="background: #fff; padding: 36px 30px; border-radius: 16px; border: 1px solid #bfdbfe; text-align: center; margin-bottom: 30px; box-shadow: var(--shadow-sm);">
            <?php if ($order->kurir === 'Ambil di Toko Fisik'): ?>
                <div style="width: 76px; height: 76px; border-radius: 50%; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 2.6rem; margin: 0 auto 16px;">
                    <i class="fa-solid fa-store"></i>
                </div>
                
                <span class="badge badge-success" style="font-size: 0.85rem; padding: 6px 14px; margin-bottom: 8px;">
                    <i class="fa-solid fa-store"></i> Siap Diambil di Toko Fisik
                </span>

                <h1 style="font-size: 1.85rem; font-weight: 800; color: #0f172a; margin-top: 8px; margin-bottom: 6px;">
                    Sepatu Anda Siap Diambil di Toko!
                </h1>
                <p style="color: #64748b; font-size: 0.95rem;">
                    No. Invoice: <strong style="color: #2563eb;">#<?= htmlspecialchars($order->invoice_no) ?></strong>
                </p>

                <div style="margin: 24px auto; max-width: 520px; padding: 20px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; text-align: left;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.92rem;">
                        <span style="color: #166534;">Metode Pengambilan:</span>
                        <strong style="color: #14532d;">Ambil di Toko Fisik QxuanStore</strong>
                    </div>
                    <div style="margin-bottom: 8px; font-size: 0.92rem; color: #14532d;">
                        <strong>Jam Operasional:</strong> Buka Setiap Hari (Senin – Minggu), Pukul <strong>09.00 – 21.00 WIB</strong>
                    </div>
                    <div style="margin-bottom: 8px; font-size: 0.88rem; color: #334155; line-height: 1.5;">
                        <i class="fa-solid fa-location-dot" style="color: #ef4444;"></i> <strong>Alamat Toko:</strong> Perum Taman Raya Rajeg Jl. Melati 1, Mekarsari, Kec. Rajeg, Kab. Tangerang.
                    </div>
                    <div style="font-size: 0.92rem; padding-top: 8px; border-top: 1px dashed #86efac;">
                        <span style="color: #166534;">Status Pembayaran:</span>
                        <strong style="color: #166534;"><i class="fa-solid fa-circle-check"></i> Lunas via <?= htmlspecialchars($order->metode_pembayaran ?: 'QRIS / Transfer Bank') ?></strong>
                    </div>
                </div>

                <div style="max-width: 480px; margin: 0 auto; background: #f8fafc; border: 1px solid #e2e8f0; padding: 18px; border-radius: 12px; margin-bottom: 12px;">
                    <p style="font-size: 0.88rem; color: #334155; font-weight: 600; margin-bottom: 12px;">
                        Sudah datang ke toko dan mengambil sepatu pesanan Anda? Konfirmasikan penerimaan agar transaksi dinyatakan selesai:
                    </p>
                    <button type="button" onclick="confirmOrderReceived('<?= $order->invoice_no ?>')" class="btn btn-sm" style="background: #16a34a; color: #fff; padding: 10px 18px; font-weight: 700; border-radius: 8px;">
                        <i class="fa-solid fa-check-circle"></i> Pesanan Sudah Saya Ambil di Toko
                    </button>
                </div>
            <?php else: ?>
                <div style="width: 76px; height: 76px; border-radius: 50%; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 2.6rem; margin: 0 auto 16px;">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
                
                <span class="badge badge-primary" style="font-size: 0.85rem; padding: 6px 14px; margin-bottom: 8px;">
                    <i class="fa-solid fa-truck-fast"></i> Pesanan Sedang Dikirim
                </span>

                <h1 style="font-size: 1.85rem; font-weight: 800; color: #0f172a; margin-top: 8px; margin-bottom: 6px;">
                    Sepatu Anda Sedang Dalam Perjalanan!
                </h1>
                <p style="color: #64748b; font-size: 0.95rem;">
                    No. Invoice: <strong style="color: #2563eb;">#<?= htmlspecialchars($order->invoice_no) ?></strong>
                </p>

                <div style="margin: 24px auto; max-width: 520px; padding: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; text-align: left;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.92rem;">
                        <span style="color: #64748b;">Kurir Pengiriman:</span>
                        <strong style="color: #0f172a;"><?= htmlspecialchars($order->kurir ?: 'Ekspedisi') ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.92rem;">
                        <span style="color: #64748b;">Nomor Resi:</span>
                        <strong style="color: #2563eb; font-family: monospace; font-size: 1.05rem;"><?= htmlspecialchars($order->no_resi ?: 'Sedang diinput') ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.92rem; padding-top: 8px; border-top: 1px dashed #cbd5e1;">
                        <span style="color: #64748b;">Status Pembayaran:</span>
                        <strong style="color: #166534;"><i class="fa-solid fa-circle-check"></i> Lunas via <?= htmlspecialchars($order->metode_pembayaran ?: 'QRIS / Transfer Bank') ?></strong>
                    </div>
                </div>

                <!-- Customer Receipt Confirmation CTA -->
                <div style="max-width: 480px; margin: 0 auto; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 18px; border-radius: 12px; margin-bottom: 12px;">
                    <p style="font-size: 0.88rem; color: #166534; font-weight: 600; margin-bottom: 12px;">
                        Sudah menerima dan memeriksa sepatu pesanan Anda? Konfirmasikan penerimaan agar transaksi dinyatakan selesai:
                    </p>
                    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <button type="button" onclick="confirmOrderReceived('<?= $order->invoice_no ?>')" class="btn btn-sm" style="background: #16a34a; color: #fff; padding: 10px 18px; font-weight: 700; border-radius: 8px;">
                            <i class="fa-solid fa-box-open"></i> Pesanan Sudah Saya Terima
                        </button>
                        <?php if (!empty($order->no_resi)): ?>
                            <a href="https://cekresi.com/?noresi=<?= urlencode($order->no_resi) ?>" target="_blank" class="btn btn-secondary btn-sm" style="padding: 10px 16px; border-radius: 8px;">
                                <i class="fa-solid fa-magnifying-glass-location"></i> Cek Resi Pengiriman
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        </div>

    <?php elseif ($order->status_order === 'Selesai'): ?>
        <!-- Card: Pesanan Selesai -->
        <div style="background: #fff; padding: 36px 30px; border-radius: 16px; border: 1px solid #86efac; text-align: center; margin-bottom: 30px; box-shadow: var(--shadow-sm);">
            <div style="width: 76px; height: 76px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 2.6rem; margin: 0 auto 16px;">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            
            <span class="badge badge-success" style="font-size: 0.85rem; padding: 6px 14px; margin-bottom: 8px;">
                <i class="fa-solid fa-circle-check"></i> Pesanan Selesai
            </span>

            <h1 style="font-size: 1.85rem; font-weight: 800; color: #0f172a; margin-top: 8px; margin-bottom: 6px;">
                Transaksi Berhasil Selesai!
            </h1>
            <p style="color: #64748b; font-size: 0.95rem;">
                No. Invoice: <strong style="color: #2563eb;">#<?= htmlspecialchars($order->invoice_no) ?></strong>
            </p>

            <div style="margin: 24px auto; max-width: 520px; padding: 20px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; text-align: left;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.92rem;">
                    <span style="color: #166534;">Metode Pembayaran:</span>
                    <strong style="color: #14532d;"><?= htmlspecialchars($order->metode_pembayaran ?: 'QRIS / Transfer Bank') ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.92rem;">
                    <span style="color: #166534;">Waktu Pembayaran:</span>
                    <strong style="color: #14532d;"><?= !empty($order->waktu_bayar) ? date('d M Y, H:i', strtotime($order->waktu_bayar)) : date('d M Y, H:i', strtotime($order->created_at)) ?> WIB</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.92rem;">
                    <span style="color: #166534;">Waktu Diterima:</span>
                    <strong style="color: #14532d;"><?= !empty($order->waktu_diterima) ? date('d M Y, H:i', strtotime($order->waktu_diterima)) . ' WIB' : 'Telah Diterima' ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 1rem; padding-top: 8px; border-top: 1px dashed #86efac;">
                    <span style="font-weight: 700; color: #166534;">Total Dibayar:</span>
                    <strong style="color: #15803d; font-size: 1.15rem;"><?= format_rupiah($order->total_bayar) ?></strong>
                </div>
            </div>

            <p style="color: #15803d; font-weight: 600; font-size: 0.92rem;">
                <i class="fa-solid fa-heart" style="color: #ef4444;"></i> Terima kasih telah berbelanja di QxuanStore! Semoga sepatu baru Anda nyaman digunakan.
            </p>
        </div>

    <?php else: ?>
        <!-- Card: Pembayaran Berhasil & Diproses -->
        <div style="background: #fff; padding: 36px 30px; border-radius: 16px; border: 1px solid #e2e8f0; text-align: center; margin-bottom: 30px; box-shadow: var(--shadow-sm);">
            <div style="width: 76px; height: 76px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 2.6rem; margin: 0 auto 16px;">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            
            <span class="badge badge-info" style="font-size: 0.85rem; padding: 6px 14px; margin-bottom: 8px;">
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
                    <strong style="color: #14532d;"><?= htmlspecialchars($order->metode_pembayaran ?: 'QRIS / Transfer Bank') ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.92rem;">
                    <span style="color: #166534;">Waktu Pembayaran:</span>
                    <strong style="color: #14532d;"><?= !empty($order->waktu_bayar) ? date('d M Y, H:i', strtotime($order->waktu_bayar)) : date('d M Y, H:i', strtotime($order->created_at)) ?> WIB</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 1rem; padding-top: 8px; border-top: 1px dashed #86efac;">
                    <span style="font-weight: 700; color: #166534;">Total Dibayar:</span>
                    <strong style="color: #15803d; font-size: 1.15rem;"><?= format_rupiah($order->total_bayar) ?></strong>
                </div>

                <?php if (!empty($order->bukti_pembayaran)): ?>
                    <div style="margin-top: 12px; padding: 10px; background: #fff; border: 1px solid #86efac; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <small style="color: #166534; font-weight: 700; display: block;">
                                <i class="fa-solid fa-file-invoice"></i> Bukti Pembayaran yang Diunggah:
                            </small>
                            <span style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($order->bukti_pembayaran) ?></span>
                        </div>
                        <button type="button" onclick="showBuktiPopUp('<?= base_url('assets/uploads/bukti/' . htmlspecialchars($order->bukti_pembayaran)) ?>')" class="btn btn-sm btn-secondary" style="border-radius: 6px; font-size: 0.82rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-eye"></i> Lihat Foto Bukti
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($order->kurir === 'Ambil di Toko Fisik'): ?>
                <div style="background: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 10px; padding: 14px; text-align: left; max-width: 520px; margin: 0 auto 12px;">
                    <div style="font-weight: 700; color: #1e40af; font-size: 0.95rem; margin-bottom: 4px;">
                        <i class="fa-solid fa-store"></i> Pengambilan di Toko Fisik QxuanStore
                    </div>
                    <div style="font-size: 0.85rem; color: #334155; line-height: 1.5;">
                        <div><strong>Jam Operasional:</strong> Buka Setiap Hari (Senin – Minggu), 09.00 – 21.00 WIB</div>
                        <div><strong>Alamat Toko:</strong> Perum Taman Raya Rajeg Jl. Melati 1, Mekarsari, Kec. Rajeg, Kab. Tangerang</div>
                        <div style="margin-top: 4px; color: #1d4ed8; font-weight: 600;">Silakan datang langsung ke toko dan tunjukkan nomor invoice #<?= htmlspecialchars($order->invoice_no) ?> saat mengambil sepatu.</div>
                    </div>
                </div>
            <?php else: ?>
                <p style="color: #15803d; font-weight: 600; font-size: 0.92rem;">
                    <i class="fa-solid fa-boxes-packing"></i> Toko sedang menyiapkan dan mengemas sepatu Anda untuk segera diserahkan ke kurir ekspedisi.
                </p>
            <?php endif; ?>
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

        <!-- Shipping Destination / Pickup Info -->
        <div style="margin-top: 24px; padding: 18px; background: #f8fafc; border-radius: 10px; font-size: 0.9rem; line-height: 1.6;">
            <p><strong>Penerima:</strong> <?= htmlspecialchars($order->nama_penerima) ?> (<?= htmlspecialchars($order->no_hp) ?>)</p>
            <?php if ($order->kurir === 'Ambil di Toko Fisik'): ?>
                <p style="margin-top: 4px;"><strong>Metode Pengambilan:</strong> <span class="badge badge-success"><i class="fa-solid fa-store"></i> Ambil di Toko Fisik (Gratis Ongkir)</span></p>
                <div style="margin-top: 10px; padding: 14px; background: #e0f2fe; border: 1px solid #bae6fd; border-radius: 8px;">
                    <strong style="color: #0369a1; display: block; margin-bottom: 6px;"><i class="fa-solid fa-clock"></i> Informasi Toko Fisik QxuanStore:</strong>
                    <div style="color: #0c4a6e; font-size: 0.86rem; line-height: 1.5;">
                        <div><strong>Jam Operasional:</strong> Buka Setiap Hari (Senin – Minggu), Pukul 09.00 – 21.00 WIB</div>
                        <div><strong>Alamat Toko:</strong> Perum Taman Raya Rajeg Jl. Melati 1, Mekarsari, Kec. Rajeg, Kab. Tangerang</div>
                        <div style="margin-top: 6px; color: #0284c7; font-weight: 600;">Silakan tunjukkan nomor invoice #<?= htmlspecialchars($order->invoice_no) ?> saat mengambil barang di kasir.</div>
                    </div>
                </div>
            <?php else: ?>
                <p style="margin-top: 4px;"><strong>Alamat Pengiriman:</strong> <?= nl2br(htmlspecialchars($order->alamat_pengiriman)) ?></p>
                <p style="margin-top: 4px;"><strong>Kurir Pengiriman:</strong> <?= htmlspecialchars($order->kurir) ?></p>
                <?php if (!empty($order->no_resi)): ?>
                    <p style="margin-top: 4px; color: #2563eb;"><strong>No. Resi Pelacakan:</strong> <span class="badge badge-info"><?= htmlspecialchars($order->no_resi) ?></span></p>
                <?php endif; ?>
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
const TOTAL_BAYAR = <?= (int)$order->total_bayar ?>;
const CUSTOMER_PHONE = '<?= addslashes($customerPhoneDigits) ?>';

// Switch Payment Tabs
function switchPaymentTab(tabName) {
    document.querySelectorAll('.payment-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.payment-tab-pane').forEach(p => p.classList.remove('active'));

    if (tabName === 'qris') {
        document.getElementById('tabBtnQris').classList.add('active');
        document.getElementById('paneQris').classList.add('active');
    } else if (tabName === 'va') {
        document.getElementById('tabBtnVa').classList.add('active');
        document.getElementById('paneVa').classList.add('active');
    } else if (tabName === 'transfer') {
        document.getElementById('tabBtnTransfer').classList.add('active');
        document.getElementById('paneTransfer').classList.add('active');
    }
}

// Switch VA Banks
function switchVaBank(bank) {
    const btnBca = document.getElementById('btnVaBca');
    const btnMandiri = document.getElementById('btnVaMandiri');
    const btnBni = document.getElementById('btnVaBni');
    const btnBri = document.getElementById('btnVaBri');

    [btnBca, btnMandiri, btnBni, btnBri].forEach(b => {
        b.className = 'btn btn-sm btn-secondary';
    });

    const vaName = document.getElementById('vaBankName');
    const vaText = document.getElementById('vaNumberText');

    if (bank === 'bca') {
        btnBca.className = 'btn btn-sm btn-primary';
        vaName.textContent = 'BCA Virtual Account';
        vaText.textContent = '88000' + CUSTOMER_PHONE;
    } else if (bank === 'mandiri') {
        btnMandiri.className = 'btn btn-sm btn-primary';
        vaName.textContent = 'Mandiri Virtual Account';
        vaText.textContent = '89508' + CUSTOMER_PHONE;
    } else if (bank === 'bni') {
        btnBni.className = 'btn btn-sm btn-primary';
        vaName.textContent = 'BNI Virtual Account';
        vaText.textContent = '8810' + CUSTOMER_PHONE;
    } else if (bank === 'bri') {
        btnBri.className = 'btn btn-sm btn-primary';
        vaName.textContent = 'BRI Virtual Account';
        vaText.textContent = '12800' + CUSTOMER_PHONE;
    }
}

// Copy to Clipboard Helper
function copyToClipboard(elementId, btnId) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const text = el.innerText || el.textContent;
    copyRawValue(text.trim(), btnId);
}

function copyRawValue(value, btnId) {
    navigator.clipboard.writeText(value).then(() => {
        const btn = document.getElementById(btnId);
        if (btn) {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Berhasil Disalin!';
            btn.classList.add('copied');
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.classList.remove('copied');
            }, 2000);
        }
    }).catch(err => {
        console.error('Copy failed', err);
    });
}

// Preview Uploaded Proof Image
function previewBuktiImage(input) {
    const container = document.getElementById('previewContainer');
    const previewImg = document.getElementById('previewImg');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            container.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        container.style.display = 'none';
    }
}

// Submit Upload Bukti Pembayaran
function submitUploadBukti() {
    const input = document.getElementById('buktiInput');
    if (!input.files || !input.files[0]) {
        Swal.fire({
            title: 'File Belum Dipilih',
            text: 'Silakan pilih foto bukti transfer / struk pembayaran terlebih dahulu.',
            icon: 'warning',
            confirmButtonColor: '#2563eb'
        });
        return;
    }

    const activeTab = document.querySelector('.payment-tab-btn.active');
    let methodText = 'QRIS (E-Wallet)';
    if (activeTab && activeTab.id === 'tabBtnVa') {
        methodText = document.getElementById('vaBankName').textContent;
    } else if (activeTab && activeTab.id === 'tabBtnTransfer') {
        methodText = 'Transfer Bank Manual';
    }

    Swal.fire({
        title: 'Mengunggah Bukti Pembayaran...',
        text: 'Mohon tunggu sebentar, sistem sedang memverifikasi file Anda.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const formData = new FormData();
    formData.append('invoice', INVOICE_NO);
    formData.append('method', methodText);
    formData.append('bukti_pembayaran', input.files[0]);

    fetch('<?= base_url("api/verify_payment.php") ?>', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Bukti Pembayaran Terkirim!',
                text: 'Terima kasih, bukti pembayaran Anda telah berhasil kami terima. Pesanan Anda kini DIPROSES toko.',
                icon: 'success',
                confirmButtonColor: '#16a34a',
                confirmButtonText: 'Lihat Rincian Pesanan'
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire('Gagal Mengunggah', data.message || 'Terjadi kesalahan saat upload.', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Terjadi kesalahan koneksi.', 'error');
    });
}

// Instant Direct Payment Confirmation
function confirmInstantPayment() {
    const activeTab = document.querySelector('.payment-tab-btn.active');
    let methodText = 'QRIS (E-Wallet)';
    if (activeTab && activeTab.id === 'tabBtnVa') {
        methodText = document.getElementById('vaBankName').textContent;
    } else if (activeTab && activeTab.id === 'tabBtnTransfer') {
        methodText = 'Transfer Bank Manual';
    }

    Swal.fire({
        title: 'Konfirmasi Telah Bayar?',
        text: `Apakah Anda sudah menyelesaikan pembayaran pesanan #${INVOICE_NO} via ${methodText}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-check"></i> Ya, Sudah Bayar',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Memverifikasi...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append('invoice', INVOICE_NO);
            formData.append('method', methodText);

            fetch('<?= base_url("api/verify_payment.php") ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Pembayaran Diterima!',
                        text: 'Status pesanan Anda kini berhasil diubah menjadi DIPROSES.',
                        icon: 'success',
                        confirmButtonColor: '#16a34a'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Perhatian', data.message || 'Gagal memproses', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Info', 'Pembayaran sedang diproses sistem.', 'info').then(() => {
                    window.location.reload();
                });
            });
        }
    });
}

// Customer Confirm Order Receipt
function confirmOrderReceived(invoiceNo) {
    Swal.fire({
        title: 'Konfirmasi Pesanan Diterima',
        text: `Apakah Anda sudah menerima paket sepatu pesanan #${invoiceNo} dalam kondisi baik?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-check"></i> Ya, Sudah Diterima',
        cancelButtonText: 'Belum'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Menyimpan Konfirmasi...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append('invoice', invoiceNo);

            fetch('<?= base_url("api/confirm_order_received.php") ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Pesanan Selesai!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#16a34a'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Perhatian', data.message || 'Gagal mengonfirmasi', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
            });
        }
    });
}

// Pop-up Lightbox for Bukti Pembayaran
function showBuktiPopUp(imgUrl) {
    Swal.fire({
        title: 'Foto Bukti Pembayaran',
        imageUrl: imgUrl,
        imageAlt: 'Bukti Pembayaran',
        showConfirmButton: false,
        showCloseButton: true,
        width: 'auto'
    });
}
</script>

<?php require_once $rootPath . '/includes/footer.php'; ?>
