<?php
/**
 * Helper Functions
 * QxuanStore - Shoe E-Commerce
 */

if (!function_exists('base_url')) {
    function base_url($path = '') {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $baseFolder = '';
        if (strpos($script, '/store-sepatu') === 0) {
            $baseFolder = '/store-sepatu';
        }
        
        $url = $protocol . $host . $baseFolder;
        if (!empty($path)) {
            $url .= '/' . ltrim($path, '/');
        }
        return $url;
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return base_url('admin/' . ltrim($path, '/'));
    }
}

if (!function_exists('user_url')) {
    function user_url($path = '') {
        return base_url('user/' . ltrim($path, '/'));
    }
}

if (!function_exists('format_rupiah')) {
    function format_rupiah($angka) {
        return 'Rp ' . number_format((float)$angka, 0, ',', '.');
    }
}

if (!function_exists('sanitize')) {
    function sanitize($data) {
        if (is_array($data)) {
            return array_map('sanitize', $data);
        }
        return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        if (!headers_sent()) {
            header("Location: " . $url);
        } else {
            echo "<script>window.location.href='" . $url . "';</script>";
        }
        exit;
    }
}

if (!function_exists('set_flash')) {
    function set_flash($type, $message) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'][$type] = $message;
    }
}

if (!function_exists('get_flash')) {
    function get_flash($type) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['flash'][$type])) {
            $msg = $_SESSION['flash'][$type];
            unset($_SESSION['flash'][$type]);
            return $msg;
        }
        return null;
    }
}

if (!function_exists('render_flash')) {
    function render_flash() {
        $html = '';
        $types = ['success', 'error', 'warning', 'info'];
        foreach ($types as $type) {
            $msg = get_flash($type);
            if ($msg) {
                $alertClass = $type === 'error' ? 'alert-danger' : 'alert-' . $type;
                $html .= '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
                $html .= $msg;
                $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                $html .= '</div>';
            }
        }
        return $html;
    }
}

if (!function_exists('product_image_url')) {
    function product_image_url($filename) {
        if (empty($filename)) {
            return base_url('assets/img/no-image.png');
        }
        
        $baseDir = dirname(__DIR__);
        // Check new uploads path first
        if (file_exists($baseDir . '/assets/uploads/products/' . $filename)) {
            return base_url('assets/uploads/products/' . $filename);
        }
        // Fallback to old path
        if (file_exists($baseDir . '/product/' . $filename)) {
            return base_url('product/' . $filename);
        }
        
        return base_url('assets/uploads/products/' . $filename);
    }
}

if (!function_exists('get_admin_contact')) {
    function get_admin_contact() {
        global $conn;
        $res = db_fetch_one("SELECT admin_name, admin_telp, admin_email, admin_address FROM tb_admin WHERE admin_id = 1");
        if ($res && !empty($res->admin_telp)) {
            return $res;
        }
        // Official random business fallback
        return (object)[
            'admin_name' => 'Admin QxuanStore',
            'admin_telp' => '082188997766',
            'admin_email' => 'support@qxuanstore.com',
            'admin_address' => 'Grand Galaxy City, Rukan Sentra Niaga Blok B-12, Bekasi'
        ];
    }
}

if (!function_exists('store_wa_formatted')) {
    function store_wa_formatted() {
        $contact = get_admin_contact();
        $telp = $contact->admin_telp ?? '082188997766';
        return preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $telp));
    }
}
