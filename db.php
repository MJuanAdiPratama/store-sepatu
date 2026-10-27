<?php
/**
 * Root Database Loader (Backward Compatible)
 * Redirects to config/database.php and loads helpers
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/session.php';