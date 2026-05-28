<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/helpers/application_spam_guard.php';
require_once dirname(__DIR__) . '/helpers/role.php';

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Login required.']);
    exit;
}

if (!pcvc_is_superadmin_role($_SESSION['role'] ?? '')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Superadmin only.']);
    exit;
}

$scope = strtolower(trim((string) ($_POST['scope'] ?? $_GET['scope'] ?? 'form_20')));
$dryRun = isset($_POST['dry_run']) || isset($_GET['dry_run']);
$bulk = !isset($_POST['bulk']) && !isset($_GET['bulk']) ? true : filter_var($_POST['bulk'] ?? $_GET['bulk'] ?? true, FILTER_VALIDATE_BOOLEAN);
$limit = max(1, min(500, (int) ($_POST['limit'] ?? $_GET['limit'] ?? 200)));

try {
    if ($scope === 'form_20' || $scope === 'i20') {
        $result = $bulk
            ? pcvc_spam_purge_form_20_bulk($conn, $limit, $dryRun)
            : pcvc_spam_purge_form_20_applications($conn, $limit, $dryRun, false);
    } elseif ($scope === 'all') {
        $result = pcvc_spam_purge_all($conn, $limit, $dryRun, false);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid scope. Use form_20 or all.']);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

$logDir = dirname(__DIR__) . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
@file_put_contents(
    $logDir . '/spam_guard_purge_all.log',
    '[' . date('Y-m-d H:i:s') . '] admin scope=' . $scope . ' ' . json_encode($result) . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

$deleted = (int) ($result['deleted'] ?? ($result['totals']['deleted'] ?? 0));
$flagged = count($result['ids'] ?? []);

echo json_encode([
    'status'  => 'ok',
    'scope'   => $scope,
    'dry_run' => $dryRun,
    'deleted' => $deleted,
    'flagged' => $flagged,
    'result'  => $result,
], JSON_UNESCAPED_UNICODE);
