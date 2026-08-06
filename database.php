<?php
/**
 * Secondary DB (Cyprus applications). Never block admin dashboard if unavailable.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$isLocalCyprus = defined('XANDER_IS_LOCAL_XAMPP') && XANDER_IS_LOCAL_XAMPP;

if ($isLocalCyprus) {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db = 'visaeofi_cyprus';
} else {
    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'xandhqav_user';
    $pass = getenv('DB_PASS') ?: 'Xander@2026';
    $db = getenv('CYPRUS_DB_NAME') ?: getenv('DB_NAME') ?: 'xandhqav_db';
}

$conn2 = @new mysqli($host, $user, $pass, $db);

if ($conn2->connect_error) {
    error_log('[database.php] Secondary DB unavailable: ' . $conn2->connect_error);
    $conn2 = null;
} else {
    $conn2->set_charset('utf8mb4');
}
