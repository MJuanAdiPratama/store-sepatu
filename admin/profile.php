<?php
/**
 * Admin - Profil & Pengaturan Akun
 * QxuanStore - Shoe E-Commerce
 */

$pageTitle = 'Profil Admin';
require_once __DIR__ . '/includes/header.php';

$adminId = $_SESSION['admin_id'] ?? 1;
$adminData = db_fetch_one("SELECT * FROM tb_admin WHERE admin_id = ?", [$adminId]);

if (!$adminData) {
    set_flash('error', 'Data admin tidak ditemukan.');
    redirect(admin_url('index.php'));
}

$errorProfile = '';
$successProfile = '';
$errorPass = '';
$successPass = '';

// Handle Profile Info Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $admin_name    = trim($_POST['admin_name'] ?? '');
    $username      = trim($_POST['username'] ?? '');
    $admin_telp    = trim($_POST['admin_telp'] ?? '');
    $admin_email   = trim($_POST['admin_email'] ?? '');
    $admin_address = trim($_POST['admin_address'] ?? '');

    if (empty($admin_name) || empty($username) || empty($admin_telp)) {
        $errorProfile = 'Nama lengkap, username, dan nomor telepon/WA admin wajib diisi!';
    } else {
        $update = db_execute("
            UPDATE tb_admin 
            SET admin_name = ?, username = ?, admin_telp = ?, admin_email = ?, admin_address = ? 
            WHERE admin_id = ?
        ", [$admin_name, $username, $admin_telp, $admin_email, $admin_address, $adminId]);

        if ($update) {
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_name'] = $admin_name;
            $adminData = db_fetch_one("SELECT * FROM tb_admin WHERE admin_id = ?", [$adminId]);
            $successProfile = 'Data profil admin berhasil diperbarui!';
        } else {
            $errorProfile = 'Gagal menyimpan perubahan profil admin.';
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $oldPass  = $_POST['old_password'] ?? '';
    $newPass  = $_POST['new_password'] ?? '';
    $confPass = $_POST['confirm_password'] ?? '';

    if (empty($oldPass) || empty($newPass) || empty($confPass)) {
        $errorPass = 'Semua field password wajib diisi!';
    } elseif ($newPass !== $confPass) {
        $errorPass = 'Password baru dan konfirmasi password tidak cocok!';
    } elseif (strlen($newPass) < 5) {
        $errorPass = 'Password baru minimal 5 karakter!';
    } else {
        // Verify old password
        $isOldValid = password_verify($oldPass, $adminData->password) || ($adminData->password === $oldPass);

        if ($isOldValid) {
            $newHashed = password_hash($newPass, PASSWORD_DEFAULT);
            $upd = db_execute("UPDATE tb_admin SET password = ? WHERE admin_id = ?", [$newHashed, $adminId]);
            if ($upd) {
                $successPass = 'Password berhasil diubah dengan enkripsi aman!';
            } else {
                $errorPass = 'Gagal mengubah password di database.';
            }
        } else {
            $errorPass = 'Password lama Anda tidak sesuai!';
        }
    }
}
?>

<div class="profile-cards-grid">
    <!-- Profile Info Card -->
    <div class="card-panel">
        <div class="panel-header">
            <h2><i class="fa-solid fa-user-shield" style="color: #2563eb; margin-right: 8px;"></i> Informasi Admin</h2>
        </div>

        <?php if (!empty($errorProfile)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorProfile) ?></div>
        <?php endif; ?>
        <?php if (!empty($successProfile)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successProfile) ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <input type="hidden" name="update_profile" value="1">

            <div class="form-group">
                <label for="admin_name">Nama Lengkap Admin</label>
                <input type="text" id="admin_name" name="admin_name" class="form-control" value="<?= htmlspecialchars($adminData->admin_name) ?>" required>
            </div>

            <div class="form-group">
                <label for="username">Username Admin</label>
                <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($adminData->username) ?>" required>
            </div>

            <div class="form-group">
                <label for="admin_telp">Nomor WhatsApp Admin</label>
                <input type="text" id="admin_telp" name="admin_telp" class="form-control" placeholder="Contoh: 082188997766" value="<?= htmlspecialchars($adminData->admin_telp) ?>" required>
            </div>

            <div class="form-group">
                <label for="admin_email">Email Admin</label>
                <input type="email" id="admin_email" name="admin_email" class="form-control" value="<?= htmlspecialchars($adminData->admin_email) ?>" required>
            </div>

            <div class="form-group">
                <label for="admin_address">Alamat Domisili Admin</label>
                <textarea id="admin_address" name="admin_address" class="form-control" rows="3"><?= htmlspecialchars($adminData->admin_address) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Profil Admin
            </button>
        </form>
    </div>

    <!-- Password Change Card -->
    <div class="card-panel">
        <div class="panel-header">
            <h2><i class="fa-solid fa-key" style="color: #2563eb; margin-right: 8px;"></i> Ubah Password Admin</h2>
        </div>

        <?php if (!empty($errorPass)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorPass) ?></div>
        <?php endif; ?>
        <?php if (!empty($successPass)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successPass) ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <input type="hidden" name="change_password" value="1">

            <div class="form-group">
                <label for="old_password">Password Lama</label>
                <div class="password-input-group">
                    <input type="password" id="old_password" name="old_password" class="form-control" placeholder="Masukkan password saat ini" required>
                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('old_password', 'oldPassIcon')" aria-label="Lihat password">
                        <i class="fa-solid fa-eye" id="oldPassIcon"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="new_password">Password Baru</label>
                <div class="password-input-group">
                    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Minimal 5 karakter" required>
                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('new_password', 'newPassIcon')" aria-label="Lihat password">
                        <i class="fa-solid fa-eye" id="newPassIcon"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password Baru</label>
                <div class="password-input-group">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Ulangi password baru" required>
                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('confirm_password', 'confPassIcon')" aria-label="Lihat password">
                        <i class="fa-solid fa-eye" id="confPassIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                <i class="fa-solid fa-shield-halved"></i> Perbarui Password
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
