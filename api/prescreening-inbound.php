<?php
/**
 * Server-to-server pre-screening handler (called from xanderbot VPS webhook).
 * Meta webhook stays on xanderbot.site — this uses cPanel DB only.
 *
 * Set same secret in both .env files: PRESCREENING_FORWARD_SECRET
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/helpers/env_load.php';
require_once dirname(__DIR__) . '/helpers/prescreening_whatsapp_schema.php';
require_once dirname(__DIR__) . '/helpers/prescreening_whatsapp_flow.php';
require_once dirname(__DIR__) . '/helpers/whatsapp_track_log.php';

xander_load_env_file();
xander_ensure_prescreening_whatsapp_tables($conn);

$expectedSecret = xander_env_get('PRESCREENING_FORWARD_SECRET');
$givenSecret = (string) ($_SERVER['HTTP_X_XANDER_FORWARD_SECRET'] ?? '');
// Also accept in JSON body for hosts that strip custom headers
$raw = file_get_contents('php://input') ?: '';
$body = json_decode($raw, true);
if (!is_array($body)) {
    $body = [];
}
if ($givenSecret === '' && isset($body['secret'])) {
    $givenSecret = (string) $body['secret'];
}

if ($expectedSecret === '' || !hash_equals($expectedSecret, $givenSecret)) {
    xander_whatsapp_track('inbound_forbidden', ['reason' => 'secret_mismatch']);
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden', 'handled' => false]);
    exit;
}

$action = (string) ($body['action'] ?? 'handle');

// Delivery webhooks use recipient_id (no "from") — must run before generic from check
if ($action === 'delivery_status') {
    $wamid = trim((string) ($body['wamid'] ?? $body['external_id'] ?? ''));
    $delivery = strtolower(trim((string) ($body['status'] ?? '')));
    $recipient = preg_replace('/\D+/', '', (string) ($body['recipient_id'] ?? $body['from'] ?? '')) ?? '';
    if ($recipient === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Missing recipient_id', 'recorded' => false]);
        exit;
    }
    $errors = $body['errors'] ?? [];
    $errorCode = null;
    $errorMessage = '';
    if (is_array($errors) && isset($errors[0]) && is_array($errors[0])) {
        $first = $errors[0];
        $errorCode = isset($first['code']) ? (int) $first['code'] : null;
        $errorMessage = trim((string) ($first['message'] ?? $first['title'] ?? ''));
        $details = $first['error_data']['details'] ?? '';
        if (is_string($details) && $details !== '') {
            $errorMessage = $errorMessage !== '' ? $errorMessage . ' — ' . $details : $details;
        }
    }
    $recorded = xander_prescreening_apply_delivery_status(
        $conn,
        $wamid,
        $delivery,
        $errorCode,
        $errorMessage,
        $recipient
    );
    xander_whatsapp_track('delivery_status_forward', [
        'wamid' => $wamid,
        'status' => $delivery,
        'recipient' => $recipient,
        'error_code' => $errorCode,
        'recorded' => $recorded,
    ]);
    echo json_encode(['recorded' => $recorded, 'status' => $delivery, 'recipient' => $recipient]);
    exit;
}

$from = preg_replace('/\D+/', '', (string) ($body['from'] ?? ''));
if ($from === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Missing from', 'handled' => false]);
    exit;
}

if ($action === 'active_session') {
    $session = xander_prescreening_load_session($conn, $from);
    $step = $session ? (string) ($session['current_step'] ?? 'idle') : 'idle';
    $active = $step !== 'idle';
    xander_whatsapp_track('active_session', ['from' => $from, 'active' => $active, 'step' => $step]);
    echo json_encode([
        'active' => $active,
        'step' => $step,
    ]);
    exit;
}

$message = $body['message'] ?? null;
if (!is_array($message)) {
    http_response_code(422);
    echo json_encode(['error' => 'Missing message', 'handled' => false]);
    exit;
}

$messageId = (string) ($message['id'] ?? '');
if ($messageId !== '' && xander_prescreening_wa_dedup_seen($conn, $messageId)) {
    echo json_encode(['handled' => true, 'duplicate' => true]);
    exit;
}

try {
    xander_whatsapp_track('handle_start', [
        'from' => $from,
        'message_id' => $messageId,
        'type' => $message['type'] ?? null,
    ]);
    $handled = (bool) xander_prescreening_handle_inbound($conn, $from, $message);
    xander_whatsapp_track($handled ? 'handle_ok' : 'handle_noop', ['from' => $from, 'message_id' => $messageId]);
    echo json_encode(['handled' => $handled, 'duplicate' => false]);
} catch (Throwable $e) {
    xander_whatsapp_track('handle_error', ['from' => $from, 'error' => $e->getMessage()]);
    error_log('[prescreening-inbound] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['handled' => false, 'error' => 'internal']);
}
