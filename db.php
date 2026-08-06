<?php
/**
 * Database connection — credentials live here (not in .env).
 * cPanel: xanderglobalscholars.com → read DB_* from .env when present
 * Local XAMPP: database "rwanda_xander" (root, no password)
 */

require_once __DIR__ . '/helpers/env_load.php';

$isLocalXampp = (
    (isset($_SERVER['HTTP_HOST']) && preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', (string) $_SERVER['HTTP_HOST']))
    || (
        PHP_OS_FAMILY === 'Windows'
        && (!isset($_SERVER['HTTP_HOST']) || preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', (string) $_SERVER['HTTP_HOST']))
    )
    || (isset($_SERVER['SERVER_NAME']) && in_array((string) $_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'], true))
);

// Production site must always use cPanel credentials (never local root).
if (isset($_SERVER['HTTP_HOST']) && stripos((string) $_SERVER['HTTP_HOST'], 'xanderglobalscholars.com') !== false) {
    $isLocalXampp = false;
}

if (!defined('XANDER_IS_LOCAL_XAMPP')) {
    define('XANDER_IS_LOCAL_XAMPP', $isLocalXampp);
}

if ($isLocalXampp) {
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'rwanda_xander';
} else {
    $db_host = xander_env_get('DB_HOST') ?: 'localhost';
    $db_user = xander_env_get('DB_USER') ?: 'xandhqav_user';
    $db_pass = xander_env_get('DB_PASS') ?: 'Xander@2026';
    $db_name = xander_env_get('DB_NAME') ?: 'xandhqav_db';
}

mysqli_report(MYSQLI_REPORT_OFF);

try {
    $conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
} catch (Throwable $e) {
    error_log('DB connection exception: ' . $e->getMessage());
    $conn = null;
}

if (!$conn instanceof mysqli || $conn->connect_errno) {
    $err = ($conn instanceof mysqli) ? $conn->connect_error : 'mysqli init failed';
    error_log('DB connection failed: ' . $err);
    http_response_code(500);
    exit('Database connection failed. Check DB_* settings in .env on the server.');
}

$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+00:00'");

if (!defined('XANDER_DB_LIGHT')) {
    define('XANDER_DB_LIGHT', false);
}

// Auto-create / repair / sync tables on connect
require_once __DIR__ . '/includes/db_auto_schema.php';
xander_db_maybe_auto_schema($conn, $isLocalXampp);

if ($isLocalXampp) {
    require_once __DIR__ . '/includes/db_local_admin_seed.php';
    xander_db_seed_local_admin_if_empty($conn);
}
