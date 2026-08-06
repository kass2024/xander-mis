<?php
/**
 * Secondary DB (Cyprus applications). Never block admin dashboard if unavailable.
 */
declare(strict_types=1);

require_once __DIR__ . '/helpers/env_load.php';

$isLocalCyprus = defined('XANDER_IS_LOCAL_XAMPP') && XANDER_IS_LOCAL_XAMPP;

if (!$isLocalCyprus && isset($_SERVER['HTTP_HOST']) && preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', (string) $_SERVER['HTTP_HOST'])) {
    $isLocalCyprus = true;
}

if ($isLocalCyprus) {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db = 'visaeofi_cyprus';
} else {
    $host = xander_env_get('DB_HOST') ?: 'localhost';
    $user = xander_env_get('DB_USER') ?: 'xandhqav_user';
    $pass = xander_env_get('DB_PASS') ?: 'Xander@2026';
    $db = xander_env_get('CYPRUS_DB_NAME') ?: xander_env_get('DB_NAME') ?: 'xandhqav_db';
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn2 = null;

try {
    $candidate = @new mysqli($host, $user, $pass, $db);
    if ($candidate instanceof mysqli && $candidate->connect_errno === 0) {
        $candidate->set_charset('utf8mb4');
        $conn2 = $candidate;
    } else {
        $msg = ($candidate instanceof mysqli) ? $candidate->connect_error : 'connect failed';
        error_log('[database.php] Secondary DB unavailable: ' . $msg);
    }
} catch (Throwable $e) {
    error_log('[database.php] Secondary DB exception: ' . $e->getMessage());
}
