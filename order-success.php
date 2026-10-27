<?php
require_once __DIR__ . '/config/helpers.php';
$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
redirect(user_url('order-success.php' . $queryString));
