<?php
/**
 * Customer Profile Page - QxuanStore
 * Modern Shoe E-Commerce (User Area)
 */

$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';

require_user();

$userId = $_SESSION['user_id'];
$userData = db_fetch_one("SELECT * FROM tb_user WHERE user_id = ?", [$userId]);

// Statistics for user
$totalOrders = db_fetch_one("SELECT COUNT(*) AS total FROM tb_order WHERE user_id = ?", [$userId])->total ?? 0;
$activeOrders = db_fetch_one("SELECT COUNT(*) AS total FROM tb_order WHERE user_id = ? AND status_order IN ('Menunggu Pembayaran', 'Diproses', 'Dikirim')", [$userId])->total ?? 0;
$completedOrders = db_fetch_one("SELECT COUNT(*) AS total FROM tb_order WHERE user_id = ? AND status_order = 'Selesai'", [$userId])->total ?? 0;

$pageTitle = 'Profil Saya';
require_once $rootPath . '/includes/header.php';

$error = '';
$success = '';
$errorPass = '';
$successPass = '';

// Update Profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama   = trim($_POST['nama_lengkap'] ?? '');
    $no_hp  = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if (empty($nama) || empty($no_hp)) {
        $error = 'Nama dan nomor WhatsApp wajib diisi!';
    } else {
        $upd = db_execute("UPDATE tb_user SET nama_lengkap = ?, no_hp = ?, alamat = ? WHERE user_id = ?", [$nama, $no_hp, $alamat, $userId]);
        if ($upd) {
            $_SESSION['nama_lengkap'] = $nama;
            $_SESSION['no_hp'] = $no_hp;
            $userData = db_fetch_one("SELECT * FROM tb_user WHERE user_id = ?", [$userId]);
            $success = 'Data profil dan alamat pengiriman Anda berhasil diperbarui!';
        } else {
            $error = 'Gagal menyimpan perubahan profil.';
        }
    }
}

// Update Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $oldPass  = $_POST['old_password'] ?? '';
    $newPass  = $_POST['new_password'] ?? '';
    $confPass = $_POST['confirm_password'] ?? '';

    if (empty($oldPass) || empty($newPass) || empty($confPass)) {
        $errorPass = 'Semua field password wajib diisi!';
    } elseif ($newPass !== $confPass) {
        $errorPass = 'Password baru dan konfirmasi tidak cocok!';
    } elseif (strlen($newPass) < 5) {
        $errorPass = 'Password baru minimal 5 karakter!';
    } else {
        if (password_verify($oldPass, $userData->password)) {
            $newHashed = password_hash($newPass, PASSWORD_DEFAULT);
            $upd = db_execute("UPDATE tb_user SET password = ? WHERE user_id = ?", [$newHashed, $userId]);
            if ($upd) {
                $successPass = 'Password Anda berhasil diperbarui!';
            } else {
                $errorPass = 'Gagal memperbarui password.';
            }
        } else {
            $errorPass = 'Password lama Anda tidak sesuai!';
        }
    }
}
?>

<div class="container" style="padding-top: 40px; padding-bottom: 70px;">
    <!-- Profile Grid Layout -->
    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 32px; align-items: start;">
        <!-- Customer Sidebar -->
        <div style="background: #fff; padding: 26px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm); position: sticky; top: 100px;">
            <div style="text-align: center; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9;">
                <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 800; margin: 0 auto 12px; box-shadow: 0 4px 12px rgba(37,99,235,0.25);">
                    <?= strtoupper(substr($userData->nama_lengkap, 0, 1)) ?>
                </div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin-bottom: 4px;"><?= htmlspecialchars($userData->nama_lengkap) ?></h3>
                <small style="color: #64748b; font-size: 0.85rem; word-break: break-all;"><?= htmlspecialchars($userData->email) ?></small>
                <div style="margin-top: 8px;">
                    <span class="badge badge-success" style="font-size: 0.72rem;">Customer Terdaftar</span>
                </div>
            </div>

            <!-- Sidebar Navigation Links -->
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 8px;">
                <li>
                    <a href="<?= user_url('profile.php') ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 10px; font-weight: 700; background: #2563eb; color: #fff; font-size: 0.92rem;">
                        <i class="fa-solid fa-user"></i> Profil & Alamat
                    </a>
                </li>
                <li>
                    <a href="<?= user_url('orders.php') ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 10px; font-weight: 600; color: #475569; font-size: 0.92rem;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                        <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Pesanan
                    </a>
                </li>
                <li>
                    <a href="<?= user_url('cart.php') ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 10px; font-weight: 600; color: #475569; font-size: 0.92rem;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                        <i class="fa-solid fa-cart-shopping"></i> Keranjang Belanja
                    </a>
                </li>
                <li style="border-top: 1px solid #f1f5f9; margin-top: 10px; padding-top: 10px;">
                    <a href="javascript:void(0)" onclick="confirmUserLogout()" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 10px; font-weight: 600; color: #ef4444; font-size: 0.92rem;">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar Akun
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content Area -->
        <div>
            <!-- Stats Highlights -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
                <div style="background: #fff; padding: 20px; border-radius: 14px; border: 1px solid #e2e8f0; text-align: center; box-shadow: var(--shadow-sm);">
                    <small style="color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem;">Total Pesanan</small>
                    <div style="font-size: 1.75rem; font-weight: 800; color: #2563eb; margin-top: 4px;"><?= $totalOrders ?></div>
                </div>
                <div style="background: #fff; padding: 20px; border-radius: 14px; border: 1px solid #e2e8f0; text-align: center; box-shadow: var(--shadow-sm);">
                    <small style="color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem;">Sedang Diproses</small>
                    <div style="font-size: 1.75rem; font-weight: 800; color: #ea580c; margin-top: 4px;"><?= $activeOrders ?></div>
                </div>
                <div style="background: #fff; padding: 20px; border-radius: 14px; border: 1px solid #e2e8f0; text-align: center; box-shadow: var(--shadow-sm);">
                    <small style="color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem;">Pesanan Selesai</small>
                    <div style="font-size: 1.75rem; font-weight: 800; color: #16a34a; margin-top: 4px;"><?= $completedOrders ?></div>
                </div>
            </div>

            <!-- Profile Info Form -->
            <div style="background: #fff; padding: 32px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 24px; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px;">
                    <h2 style="font-size: 1.25rem; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-address-card" style="color: #2563eb;"></i> Informasi Profil & Alamat
                    </h2>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" style="margin-bottom: 20px;"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" style="background: #dcfce7; color: #166534; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px;">
                        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <input type="hidden" name="update_profile" value="1">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="nama_lengkap">Nama Lengkap</label>
                            <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($userData->nama_lengkap) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="no_hp">Nomor WhatsApp Aktif</label>
                            <input type="tel" id="no_hp" name="no_hp" class="form-control" value="<?= htmlspecialchars($userData->no_hp) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Alamat Email</label>
                        <input type="email" id="email" class="form-control" value="<?= htmlspecialchars($userData->email) ?>" disabled style="background: #f8fafc; color: #64748b; cursor: not-allowed;">
                        <small style="color: #64748b; font-size: 0.8rem;">Email digunakan sebagai identitas akun dan tidak dapat diubah.</small>
                    </div>

                    <div class="form-group">
                        <label for="alamat">Alamat Pengiriman Utama (Default Checkout)</label>
                        <textarea id="alamat" name="alamat" class="form-control" rows="3" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan, kota, kode pos..."><?= htmlspecialchars($userData->alamat ?? '') ?></textarea>
                        <small style="color: #64748b; font-size: 0.8rem;">Alamat ini akan otomatis muncul saat Anda checkout sepatu.</small>
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding: 12px 24px; border-radius: 10px;">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Profil & Alamat
                    </button>
                </form>
            </div>

            <!-- Password Security Form -->
            <div style="background: #fff; padding: 32px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm);">
                <h2 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-lock" style="color: #2563eb;"></i> Keamanan & Kata Sandi
                </h2>

                <?php if (!empty($errorPass)): ?>
                    <div class="alert alert-danger" style="margin-bottom: 20px;"><?= htmlspecialchars($errorPass) ?></div>
                <?php endif; ?>
                <?php if (!empty($successPass)): ?>
                    <div class="alert alert-success" style="background: #dcfce7; color: #166534; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px;">
                        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($successPass) ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <input type="hidden" name="change_password" value="1">

                    <div class="form-group">
                        <label for="old_password">Password Saat Ini</label>
                        <input type="password" id="old_password" name="old_password" class="form-control" placeholder="Masukkan password saat ini" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="new_password">Password Baru</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Minimal 5 karakter" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Konfirmasi Password Baru</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Ulangi password baru" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-secondary" style="padding: 12px 24px; border-radius: 10px;">
                        <i class="fa-solid fa-key"></i> Perbarui Kata Sandi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmUserLogout() {
    Swal.fire({
        title: 'Konfirmasi Keluar',
        text: 'Apakah Anda yakin ingin keluar dari akun customer Anda?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Keluar Akun',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= base_url("auth/logout.php") ?>';
        }
    });
}
</script>

<?php require_once $rootPath . '/includes/footer.php'; ?>
