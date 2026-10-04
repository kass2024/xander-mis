<?php
declare(strict_types=1);

ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!empty($_SESSION['admin_id']) || !empty($_SESSION['id'])) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only the customer can sign this contract.']);
    exit;
}
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/service_contract_lib.php';

header('Content-Type: application/json; charset=utf-8');

function xander_sc_sign_out(array $payload, int $code = 200): void
{
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) {
    xander_sc_sign_out(['success' => false, 'error' => 'Invalid request.'], 400);
}

$token = trim((string) ($data['token'] ?? ''));
$signature = (string) ($data['signature'] ?? '');
$agreed = !empty($data['agreement']);
$signedName = trim((string) ($data['student_name'] ?? $data['full_name'] ?? ''));

$result = xander_sc_sign($conn, $token, $signature, $agreed, $signedName);
if (empty($result['ok'])) {
    $message = (string) ($result['error'] ?? 'Could not sign this contract.');
    $code = 400;
    if (str_contains($message, 'invalid')) {
        $code = 404;
    }
    xander_sc_sign_out(['success' => false, 'error' => $message], $code);
}

xander_sc_sign_out([
    'success' => true,
    'status'  => 'signed',
    'message' => 'Contract signed successfully.',
]);
