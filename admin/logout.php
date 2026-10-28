<?php
/**
 * Admin Logout Handler
 * QxuanStore - Shoe E-Commerce
 */

$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/helpers.php';
require_once $rootPath . '/config/session.php';

// Unset admin session variables
unset($_SESSION['admin_logged_in']);
unset($_SESSION['status_login']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_name']);
unset($_SESSION['a_global']);
unset($_SESSION['flash']);

redirect(admin_url('login.php'));
