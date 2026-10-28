<?php
/**
 * Customer Logout Handler - QxuanStore
 * Modern Shoe E-Commerce
 */

$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';

// Clear user session and shopping cart
unset($_SESSION['user_logged_in']);
unset($_SESSION['user_id']);
unset($_SESSION['nama_lengkap']);
unset($_SESSION['email']);
unset($_SESSION['no_hp']);
unset($_SESSION['cart']);
unset($_SESSION['flash']);

redirect(base_url('index.php'));
