<?php
/**
 * Customer Login Page - QxuanStore
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
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi!';
    } else {
        $user = db_fetch_one("SELECT * FROM tb_user WHERE email = ? LIMIT 1", [$email]);

        if ($user && password_verify($password, $user->password)) {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $user->user_id;
            $_SESSION['nama_lengkap'] = $user->nama_lengkap;
            $_SESSION['email'] = $user->email;
            $_SESSION['no_hp'] = $user->no_hp;

            set_flash('success', 'Selamat datang kembali, ' . $user->nama_lengkap . '!');
            
            // Redirect to cart if items are waiting, otherwise homepage
            if (!empty($_SESSION['cart'])) {
                redirect(base_url('cart.php'));
            } else {
                redirect(base_url('index.php'));
            }
        } else {
            $error = 'Email atau password yang Anda masukkan salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Customer | QxuanStore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>">
</head>
<body style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <i class="fa-solid fa-shoe-prints" style="color: #2563eb; font-size: 2.2rem; margin-bottom: 8px;"></i>
                <h1>Masuk Akun Customer</h1>
                <p>Selamat datang di QxuanStore</p>
            </div>

            <?= render_flash() ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="email">Alamat Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="nama@email.com" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input-group">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password', 'passwordIcon')" aria-label="Lihat password">
                            <i class="fa-solid fa-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                </div>

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-size: 1rem;">
                        <i class="fa-solid fa-right-to-bracket"></i> Masuk Akun
                    </button>
                </div>
            </form>

            <div style="margin-top: 20px; text-align: center; font-size: 0.88rem; color: #64748b;">
                Belum punya akun? 
                <a href="<?= base_url('auth/register.php') ?>" style="color: #2563eb; font-weight: 700; text-decoration: none;">Daftar di sini</a>
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
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    </script>
</body>
</html>
