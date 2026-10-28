<?php
/**
 * Public Site Footer Layout
 * QxuanStore - Shoe E-Commerce
 */

$storeContact = get_admin_contact();
$formattedWa = store_wa_formatted();
?>

    <!-- Footer Section (Navigasi & Label UMKM Dihilangkan Sesuai Request) -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid" style="grid-template-columns: 1.5fr 1fr; gap: 40px;">
                <!-- Brand Info -->
                <div class="footer-brand">
                    <h3>Qxuan<span>Store</span></h3>
                    <p style="max-width: 480px; margin-bottom: 20px; line-height: 1.7; font-size: 0.92rem; color: #94a3b8;">
                        Pusat sepatu sneakers dan casual terbaik untuk kebutuhan harian, olahraga, dan gaya hidup Anda. Kami menghadirkan produk berkualitas dengan harga ramah untuk semua kalangan.
                    </p>
                    <div style="display: flex; gap: 14px; font-size: 1.25rem;">
                        <a href="#" style="color: #cbd5e1;"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" style="color: #cbd5e1;"><i class="fa-brands fa-facebook"></i></a>
                        <a href="#" style="color: #cbd5e1;"><i class="fa-brands fa-tiktok"></i></a>
                    </div>
                </div>

                <!-- Contact & Store Address -->
                <div class="footer-contact">
                    <h4>Kontak & Lokasi Toko</h4>
                    <p style="font-size: 0.92rem; margin-bottom: 10px; display: flex; gap: 10px; color: #cbd5e1;">
                        <i class="fa-solid fa-location-dot" style="color: #38bdf8; margin-top: 4px;"></i>
                        <span><?= htmlspecialchars($storeContact->admin_address ?? 'Grand Galaxy City, Bekasi, Indonesia') ?></span>
                    </p>
                    <p style="font-size: 0.92rem; margin-bottom: 10px; display: flex; gap: 10px; color: #cbd5e1;">
                        <i class="fa-solid fa-phone" style="color: #38bdf8; margin-top: 4px;"></i>
                        <span><?= htmlspecialchars($storeContact->admin_telp ?? '082188997766') ?></span>
                    </p>
                    <p style="font-size: 0.92rem; display: flex; gap: 10px; color: #cbd5e1;">
                        <i class="fa-solid fa-envelope" style="color: #38bdf8; margin-top: 4px;"></i>
                        <span><?= htmlspecialchars($storeContact->admin_email ?? 'support@qxuanstore.com') ?></span>
                    </p>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> QxuanStore. Hak Cipta Dilindungi.</p>
                <p>Belanja aman, cepat, dan terpercaya.</p>
            </div>
        </div>
    </footer>

</body>
</html>
