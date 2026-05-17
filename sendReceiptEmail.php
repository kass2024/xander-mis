<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$LOG = $logDir . '/receipt_email.log';

function logMsg(string $msg, $data = null): void
{
    global $LOG;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    if ($data !== null) {
        $line .= ' :: ' . (is_scalar($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    @file_put_contents($LOG, $line . PHP_EOL, FILE_APPEND);
}

register_shutdown_function(static function (): void {
    $err = error_get_last();
    if ($err !== null) {
        logMsg('FATAL ERROR', $err);
    }
});

header('Content-Type: application/json; charset=utf-8');

logMsg('========== EMAIL ENDPOINT START ==========');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    logMsg('INVALID METHOD');
    http_response_code(405);
    echo json_encode(['status' => 'error', 'reason' => 'method']);
    exit;
}

$secret = $_POST['secret'] ?? '';
if ($secret !== 'RCP_9fA8kKx_2026_SECURE') {
    logMsg('INVALID SECRET');
    http_response_code(403);
    echo json_encode(['status' => 'error', 'reason' => 'secret']);
    exit;
}

$receiptNo = trim((string) ($_POST['receipt_no'] ?? ''));
if ($receiptNo === '') {
    logMsg('RECEIPT NO EMPTY');
    echo json_encode(['status' => 'error', 'reason' => 'receipt_no']);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/receipt_email.php';

$result = xander_send_receipt_email($conn, $receiptNo, 'logMsg');

if (!empty($result['ok'])) {
    logMsg('EMAIL SENT SUCCESSFULLY', $receiptNo);
    echo json_encode(['status' => 'ok']);
} else {
    $reason = (string) ($result['reason'] ?? 'unknown');
    logMsg('EMAIL FAILED', ['receipt' => $receiptNo, 'reason' => $reason]);
    if ($reason === 'mail') {
        http_response_code(500);
    }
    echo json_encode(['status' => 'error', 'reason' => $reason]);
}

$conn->close();
logMsg('========== EMAIL ENDPOINT END ==========');
