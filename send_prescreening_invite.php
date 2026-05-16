<?php
declare(strict_types=1);

ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();

function invite_respond(array $data, int $code = 200): void
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['id']) && empty($_SESSION['admin_id'])) {
    invite_respond(['status' => 'error', 'message' => 'Unauthorized'], 401);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    invite_respond(['status' => 'error', 'message' => 'Invalid method'], 405);
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/prescreening_whatsapp_schema.php';
require_once __DIR__ . '/helpers/prescreening_whatsapp_flow.php';

xander_ensure_prescreening_whatsapp_tables($conn);

$phone = trim((string) ($_POST['whatsapp_number'] ?? ''));
$name = trim((string) ($_POST['student_name'] ?? ''));

if ($phone === '') {
    invite_respond(['status' => 'error', 'message' => 'Student WhatsApp number is required.']);
}

$result = xander_prescreening_admin_send_invite($conn, $phone, $name);

if (!$result['sent']) {
    invite_respond([
        'status' => 'error',
        'message' => $result['error'] !== '' ? $result['error'] : 'Failed to send WhatsApp invite.',
    ]);
}

$method = (string) ($result['method'] ?? 'template');
$lang = (string) ($result['template_lang'] ?? '');
$msg = 'Pre-screening invite sent on WhatsApp (template'
    . ($lang !== '' ? ' ' . $lang : '')
    . '). The student should tap START or reply START to begin.';
if ($method === 'text') {
    $msg = 'Invite sent as plain text (template failed). Student must reply START within 24 hours if they have not messaged you recently.';
}

invite_respond([
    'status' => 'success',
    'message' => $msg,
    'to' => $result['to'],
    'method' => $method,
    'template_lang' => $lang,
]);
