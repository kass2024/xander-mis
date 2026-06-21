<?php
/**
 * Meta WhatsApp webhook on cPanel (legacy / backup).
 * Primary Meta callback may point at the webhook app — pre-screening runs here via
 * api/prescreening-inbound.php (messages + delivery status from the webhook relay).
 *
 * Health: /api/webhook-health.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/env_load.php';
require_once dirname(__DIR__) . '/helpers/whatsapp_track_log.php';
xander_load_env_file();

require_once dirname(__DIR__) . '/helpers/webhook_forward_xanderbot.php';

if (xander_bot_webhook_forward_request()) {
    exit;
}

require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/helpers/prescreening_whatsapp_schema.php';
require_once dirname(__DIR__) . '/helpers/prescreening_whatsapp_flow.php';

xander_ensure_prescreening_whatsapp_tables($conn);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $mode = (string) ($_GET['hub_mode'] ?? '');
    $token = (string) ($_GET['hub_verify_token'] ?? '');
    $challenge = (string) ($_GET['hub_challenge'] ?? '');
    $expected = xander_env_get('WHATSAPP_VERIFY_TOKEN');
    if ($mode === 'subscribe' && $expected !== '' && hash_equals($expected, $token)) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $challenge;
        exit;
    }
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$sig = (string) ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');
if (!xander_whatsapp_verify_webhook_signature($raw, $sig)) {
    error_log('[whatsapp-webhook] invalid signature — set WHATSAPP_APP_SECRET to Meta App Secret');
    http_response_code(403);
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(200);
    echo json_encode(['status' => 'ignored']);
    exit;
}

foreach ($payload['entry'] ?? [] as $entry) {
    if (!is_array($entry)) {
        continue;
    }
    foreach ($entry['changes'] ?? [] as $change) {
        if (!is_array($change)) {
            continue;
        }
        $value = $change['value'] ?? [];
        if (!is_array($value)) {
            continue;
        }

        foreach ($value['statuses'] ?? [] as $status) {
            if (!is_array($status)) {
                continue;
            }
            $wamid = trim((string) ($status['id'] ?? ''));
            $delivery = strtolower(trim((string) ($status['status'] ?? '')));
            $recipient = preg_replace('/\D+/', '', (string) ($status['recipient_id'] ?? '')) ?? '';
            if ($delivery === '' || $recipient === '') {
                continue;
            }
            $errorCode = null;
            $errorMessage = '';
            $errors = $status['errors'] ?? [];
            if (is_array($errors) && isset($errors[0]) && is_array($errors[0])) {
                $first = $errors[0];
                $errorCode = isset($first['code']) ? (int) $first['code'] : null;
                $errorMessage = trim((string) ($first['message'] ?? $first['title'] ?? ''));
            }
            xander_prescreening_apply_delivery_status(
                $conn,
                $wamid,
                $delivery,
                $errorCode,
                $errorMessage,
                $recipient
            );
            xander_whatsapp_track('invite_delivery_' . $delivery, [
                'wamid' => $wamid,
                'recipient' => $recipient,
                'source' => 'cpanel_webhook',
            ]);
        }

        foreach ($value['messages'] ?? [] as $message) {
            if (!is_array($message)) {
                continue;
            }
            $messageId = (string) ($message['id'] ?? '');
            if ($messageId !== '' && xander_prescreening_wa_dedup_seen($conn, $messageId)) {
                continue;
            }
            $from = preg_replace('/\D+/', '', (string) ($message['from'] ?? ''));
            if ($from === '') {
                continue;
            }
            try {
                xander_prescreening_handle_inbound($conn, $from, $message);
            } catch (Throwable $e) {
                error_log('[whatsapp-webhook] from=' . $from . ' ' . $e->getMessage());
            }
        }
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => 'ok', 'note' => 'cPanel pre-screening webhook']);
