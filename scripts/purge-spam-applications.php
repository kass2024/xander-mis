<?php
/**
 * Remove bot/spam rows from student_applications (heuristics + OpenAI).
 *
 * CLI:  php scripts/purge-spam-applications.php [--dry-run] [--limit=200]
 * Web:  /scripts/purge-spam-applications.php?key=YOUR_CRON_SECRET&limit=200
 *       Set SPAM_GUARD_CRON_SECRET in .env (or use superadmin session).
 */
declare(strict_types=1);

$isCli = (PHP_SAPI === 'cli');

require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/helpers/application_spam_guard.php';
require_once dirname(__DIR__) . '/helpers/env_load.php';

$dryRun = false;
$limit = 200;

if ($isCli) {
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--dry-run') {
            $dryRun = true;
        } elseif (str_starts_with($arg, '--limit=')) {
            $limit = (int) substr($arg, 8);
        }
    }
} else {
    session_start();
    require_once dirname(__DIR__) . '/helpers/role.php';

    $secret = (string) xander_env_get('SPAM_GUARD_CRON_SECRET', '');
    $key = (string) ($_GET['key'] ?? '');
    $sessionRole = trim((string) ($_SESSION['role'] ?? ''));
    $allowed = ($secret !== '' && hash_equals($secret, $key))
        || xander_is_superadmin_role($sessionRole);

    if (!$allowed) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    $dryRun = isset($_GET['dry_run']);
    $limit = (int) ($_GET['limit'] ?? 200);
    header('Content-Type: application/json; charset=utf-8');
}

$result = pcvc_spam_purge_database($conn, $limit, $dryRun);
$result['dry_run'] = $dryRun;
$result['limit'] = $limit;
$result['at'] = gmdate('c');

$logDir = dirname(__DIR__) . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
@file_put_contents(
    $logDir . '/spam_purge.log',
    '[' . date('Y-m-d H:i:s') . '] ' . json_encode($result) . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ($isCli ? PHP_EOL : '');
