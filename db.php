<?php
/**
 * Database connection — credentials live here (not in .env).
 * cPanel: xanderglobalscholars.com → xandhqav_db
 * Local XAMPP: database "rwanda_xander" (root, no password)
 */

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
    // cPanel: getenv() reads server env if set; otherwise use defaults below
    $db_host = getenv('DB_HOST') ?: 'localhost';
    $db_user = getenv('DB_USER') ?: 'xandhqav_user';
    $db_pass = getenv('DB_PASS') ?: 'Xander@2026';
    $db_name = getenv('DB_NAME') ?: 'xandhqav_db';
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    error_log('DB connection failed: ' . $conn->connect_error);
    die('Connection failed: ' . $conn->connect_error);
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
