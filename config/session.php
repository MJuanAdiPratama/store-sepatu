<?php
/**
 * Session Management & Role Authentication
 * QxuanStore - Shoe E-Commerce
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/helpers.php';

/**
 * Check if the current user is an authenticated Admin
 */
function is_admin() {
    return (
        (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) ||
        (isset($_SESSION['status_login']) && $_SESSION['status_login'] === true && isset($_SESSION['admin_id']))
    );
}

/**
 * Enforce Admin Authentication
 */
function require_admin() {
    if (!is_admin()) {
        redirect(admin_url('login.php'));
    }
}

/**
 * Check if the current user is an authenticated Customer
 */
function is_user() {
    return (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true && isset($_SESSION['user_id']));
}

/**
 * Enforce Customer Authentication
 */
function require_user() {
    if (!is_user()) {
        set_flash('warning', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        redirect(base_url('auth/login.php'));
    }
}

/**
 * Get current admin session data
 */
function current_admin() {
    if (is_admin()) {
        return $_SESSION['a_global'] ?? (object)[
            'admin_id' => $_SESSION['admin_id'] ?? 1,
            'username' => $_SESSION['admin_username'] ?? 'Admin',
            'admin_name' => $_SESSION['admin_name'] ?? 'Administrator'
        ];
    }
    return null;
}

/**
 * Get current customer session data
 */
function current_user() {
    if (is_user()) {
        return (object)[
            'user_id' => $_SESSION['user_id'],
            'nama_lengkap' => $_SESSION['nama_lengkap'] ?? '',
            'email' => $_SESSION['email'] ?? '',
            'no_hp' => $_SESSION['no_hp'] ?? ''
        ];
    }
    return null;
}
