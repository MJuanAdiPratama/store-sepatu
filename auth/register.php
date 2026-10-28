<?php
/**
 * Customer Registration Page - QxuanStore
 * Modern Shoe E-Commerce
 */

$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';

if (is_user()) {
    redirect(base_url('index.php'));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama_lengkap'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $no_hp    = trim($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? '';
    $alamat   = trim($_POST['alamat'] ?? '');

    if (empty($nama) || empty($email) || empty($no_hp) || empty($password)) {
        $error = 'Semua field wajib diisi lengkap!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif (strlen($password) < 5) {
        $error = 'Password minimal 5 karakter!';
    } else {
        // Check if email already used
        $check = db_fetch_one("SELECT user_id FROM tb_user WHERE email = ?", [$email]);
        if ($check) {
            $error = 'Email tersebut sudah terdaftar! Silakan login.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $inserted = db_execute("
                INSERT INTO tb_user (nama_lengkap, email, no_hp, password, alamat) 
                VALUES (?, ?, ?, ?, ?)
            ", [$nama, $email, $no_hp, $hashedPassword, $alamat]);

            if ($inserted) {
                $newUserId = db_insert_id();
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['nama_lengkap'] = $nama;
                $_SESSION['email'] = $email;
                $_SESSION['no_hp'] = $no_hp;

                set_flash('success', 'Akun berhasil dibuat! Selamat datang di QxuanStore.');
                redirect(base_url('index.php'));
            } else {
                $error = 'Terjadi kesalahan sistem saat mendaftar. Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Customer | QxuanStore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>">
</head>
<body style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">

    <div class="login-wrapper">
        <div class="login-card" style="max-width: 480px;">
            <div class="login-header">
                <i class="fa-solid fa-shoe-prints" style="color: #2563eb; font-size: 2.2rem; margin-bottom: 8px;"></i>
                <h1>Buat Akun Customer</h1>
                <p>Daftar untuk kemudahan belanja & lacak pesanan sepatu</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" placeholder="Contoh: Budi Santoso" required value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>">
                </div>

                <div class="grid-2-col">
                    <div class="form-group">
                        <label for="email">Alamat Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="nama@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="no_hp">No. WhatsApp</label>
                        <input type="tel" id="no_hp" name="no_hp" class="form-control" placeholder="08123456789" required value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input-group">
                        <input type="text" id="password" name="password" class="form-control password-mask" placeholder="Minimal 5 karakter" required autocomplete="off" spellcheck="false">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password', 'regPasswordIcon')" aria-label="Lihat password">
                            <i class="fa-solid fa-eye" id="regPasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="alamat">Alamat Pengiriman Default</label>
                    <textarea id="alamat" name="alamat" class="form-control" rows="2" placeholder="Nama jalan, kota, kode pos..."><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                </div>

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-size: 1rem;">
                        <i class="fa-solid fa-user-plus"></i> Daftar Sekarang
                    </button>
                </div>
            </form>

            <div style="margin-top: 20px; text-align: center; font-size: 0.88rem; color: #64748b;">
                Sudah punya akun? 
                <a href="<?= base_url('auth/login.php') ?>" style="color: #2563eb; font-weight: 700; text-decoration: none;">Masuk di sini</a>
            </div>

            <div style="margin-top: 14px; text-align: center; font-size: 0.82rem;">
                <a href="<?= base_url('index.php') ?>" style="color: #64748b; text-decoration: none;">
                    &larr; Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

    <script>
    function togglePasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (!input || !icon) return;
        if (input.classList.contains('password-mask')) {
            input.classList.remove('password-mask');
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.classList.add('password-mask');
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    </script>
</body>
</html>
