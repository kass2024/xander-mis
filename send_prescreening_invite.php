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

if (empty($_SESSION['admin_id'])) {
    invite_respond(['status' => 'error', 'message' => 'Unauthorized'], 401);
}
require_once __DIR__ . '/helpers/role.php';
if (!pcvc_is_superadmin_role($_SESSION['role'] ?? '')) {
    invite_respond(['status' => 'error', 'message' => 'Superadmin only'], 403);
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
    require_once __DIR__ . '/helpers/prescreening_invite.php';

    xander_ensure_prescreening_whatsapp_tables($conn);

    $channel = strtolower(trim((string) ($_POST['send_via'] ?? 'whatsapp')));
    if (!in_array($channel, ['email', 'whatsapp', 'both'], true)) {
        invite_respond(['status' => 'error', 'message' => 'Invalid delivery channel.']);
    }

    $phone = trim((string) ($_POST['whatsapp_number'] ?? ''));
    $name = trim((string) ($_POST['student_name'] ?? ''));
    $email = trim((string) ($_POST['student_email'] ?? ''));
    $sendEmailNow = ($_POST['send_email_now'] ?? '1') !== '0';

    if ($name === '') {
        invite_respond(['status' => 'error', 'message' => 'Student name is required.']);
    }
    if ($phone === '') {
        invite_respond(['status' => 'error', 'message' => 'Student WhatsApp number is required.']);
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        invite_respond(['status' => 'error', 'message' => 'Valid student email is required.']);
    }

    $invite = xander_prescreening_create_invite($conn, $name, $email, $phone, $channel);

    $out = [
        'status' => 'success',
        'message' => 'Invite created.',
        'channel' => $channel,
        'link' => $invite['url'],
        'token' => $invite['token'],
        'user_id' => $invite['user_id'],
        'whatsapp' => null,
        'email' => null,
    ];

    $errors = [];

    if ($channel === 'whatsapp' || $channel === 'both') {
        if (!function_exists('curl_init')) {
            $errors[] = 'WhatsApp: PHP cURL missing.';
        } else {
            xander_whatsapp_track('invite_request', [
                'phone_raw' => $phone,
                'student_name' => $name,
                'channel' => $channel,
            ]);
            $result = xander_prescreening_admin_send_invite($conn, $phone, $name);
            if (!$result['sent']) {
                $errors[] = $result['error'] !== '' ? $result['error'] : 'WhatsApp invite failed.';
                $out['whatsapp'] = ['sent' => false, 'error' => end($errors)];
            } else {
                $session = xander_prescreening_load_session($conn, $result['to']);
                $out['whatsapp'] = [
                    'sent' => true,
                    'to' => $result['to'],
                    'message_id' => $result['message_id'] ?? '',
                    'delivery_status' => $session['last_delivery_status'] ?? 'api_accepted',
                ];
                $out['message'] = 'WhatsApp template sent.';
            }
        }
    }

    if ($channel === 'email' || $channel === 'both') {
        if ($sendEmailNow) {
            $mailRes = xander_prescreening_send_invite_email($email, $name, $invite['url']);
            $out['email'] = ['sent' => $mailRes['ok'], 'error' => $mailRes['error']];
            if (!$mailRes['ok']) {
                $errors[] = 'Email: ' . $mailRes['error'];
            } elseif ($out['message'] === 'Invite created.') {
                $out['message'] = 'Email sent with pre-screening link.';
            } else {
                $out['message'] .= ' Email sent.';
            }
        } else {
            $out['email'] = ['sent' => false, 'skipped' => true];
            if ($channel === 'email') {
                $out['message'] = 'Link ready — copy or send email when ready.';
            }
        }
    }

    if ($errors !== []) {
        $out['status'] = ($out['whatsapp']['sent'] ?? false) || ($out['email']['sent'] ?? false) ? 'partial' : 'error';
        $out['message'] = implode(' ', $errors);
        if ($out['status'] === 'partial') {
            $out['message'] .= ' Link: ' . $invite['url'];
        }
    }

    $out['log_url'] = 'api/prescreening-invite-log.php';
    invite_respond($out, $out['status'] === 'error' ? 400 : 200);
} catch (Throwable $e) {
    xander_whatsapp_track('invite_exception', ['error' => $e->getMessage()]);
    error_log('[send_prescreening_invite] ' . $e->getMessage());
    invite_respond([
        'status' => 'error',
        'message' => 'Server error while sending invite.',
    ], 500);
}
