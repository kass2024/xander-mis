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

require_once __DIR__ . '/helpers/whatsapp_track_log.php';
require_once __DIR__ . '/helpers/env_load.php';
xander_load_env_file();

try {
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/helpers/prescreening_whatsapp_schema.php';
    require_once __DIR__ . '/helpers/prescreening_whatsapp_flow.php';

    xander_ensure_prescreening_whatsapp_tables($conn);

    $phone = trim((string) ($_POST['whatsapp_number'] ?? ''));
    $name = trim((string) ($_POST['student_name'] ?? ''));

    xander_whatsapp_track('invite_request', [
        'phone_raw' => $phone,
        'student_name' => $name !== '' ? $name : '(empty)',
    ]);

    if ($phone === '') {
        invite_respond(['status' => 'error', 'message' => 'Student WhatsApp number is required.']);
    }

    if (! function_exists('curl_init')) {
        xander_whatsapp_track('invite_error', ['reason' => 'curl_missing']);
        invite_respond(['status' => 'error', 'message' => 'Server cannot call WhatsApp API (PHP cURL missing).']);
    }

    $result = xander_prescreening_admin_send_invite($conn, $phone, $name);

    if (!$result['sent']) {
        xander_whatsapp_track('invite_api_failed', [
            'to' => $result['to'] ?? '',
            'error' => $result['error'] ?? '',
        ]);
        invite_respond([
            'status' => 'error',
            'message' => $result['error'] !== '' ? $result['error'] : 'Failed to send WhatsApp invite.',
            'to' => $result['to'] ?? '',
            'log_url' => 'api/prescreening-invite-log.php',
        ]);
    }

    $method = (string) ($result['method'] ?? 'template');
    $lang = (string) ($result['template_lang'] ?? '');
    $msg = 'Pre-screening invite accepted by Meta (template'
        . ($lang !== '' ? ' ' . $lang : '')
        . '). Student must tap START on WhatsApp. Delivery can still fail — check Meta message logs if they see nothing.';
    invite_respond([
        'status' => 'success',
        'message' => $msg,
        'to' => $result['to'],
        'method' => $method,
        'template_lang' => $lang,
        'log_url' => 'api/prescreening-invite-log.php',
    ]);
} catch (Throwable $e) {
    xander_whatsapp_track('invite_exception', ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
    error_log('[send_prescreening_invite] ' . $e->getMessage());
    invite_respond([
        'status' => 'error',
        'message' => 'Server error while sending invite. Check api/prescreening-invite-log.php or cPanel error log.',
    ], 500);
}
