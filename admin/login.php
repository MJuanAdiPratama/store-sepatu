<?php
/**
 * Admin Login Page
 * QxuanStore - Shoe E-Commerce
 */

$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';

// If already logged in as admin, redirect to dashboard
if (is_admin()) {
    redirect(admin_url('index.php'));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } else {
        // Query admin using secure prepared statement
        $admin = db_fetch_one("SELECT * FROM tb_admin WHERE username = ? LIMIT 1", [$username]);

        if ($admin) {
            $isPasswordValid = false;

            // Check using password_verify (modern hashed)
            if (password_verify($password, $admin->password)) {
                $isPasswordValid = true;
            } 
            // Fallback for legacy plaintext password (e.g. '12345')
            elseif ($admin->password === $password) {
                $isPasswordValid = true;
                // Automatically upgrade to bcrypt hash in database for maximum security!
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                db_execute("UPDATE tb_admin SET password = ? WHERE admin_id = ?", [$newHash, $admin->admin_id]);
            }

            if ($isPasswordValid) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['status_login'] = true; // Legacy compatibility
                $_SESSION['admin_id'] = $admin->admin_id;
                $_SESSION['admin_username'] = $admin->username;
                $_SESSION['admin_name'] = $admin->admin_name;
                $_SESSION['a_global'] = $admin; // Legacy compatibility

                set_flash('success', 'Selamat datang kembali, ' . $admin->admin_name . '!');
                redirect(admin_url('index.php'));
            } else {
                $error = 'Password yang Anda masukkan salah!';
            }
        } else {
            $error = 'Akun admin dengan username tersebut tidak ditemukan!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin | QxuanStore</title>
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
                <i class="fa-solid fa-shoe-prints" style="color: #2563eb; font-size: 2.2rem; margin-bottom: 12px;"></i>
                <h1>QxuanStore</h1>
                <p>Portal Manajemen Toko Sepatu (Admin)</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="username">Username Admin</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input-group">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password', 'passwordIcon')" aria-label="Toggle password visibility">
                            <i class="fa-solid fa-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                </div>

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-size: 1rem;">
                        <i class="fa-solid fa-right-to-bracket"></i> Masuk ke Dashboard
                    </button>
                </div>
            </form>

            <div style="margin-top: 24px; text-align: center; font-size: 0.85rem; color: #64748b;">
                <a href="<?= base_url('index.php') ?>" style="color: #2563eb; text-decoration: none; font-weight: 600;">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Halaman Toko
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
