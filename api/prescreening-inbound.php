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
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden', 'handled' => false]);
    exit;
}

$action = (string) ($body['action'] ?? 'handle');
$from = preg_replace('/\D+/', '', (string) ($body['from'] ?? ''));
if ($from === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Missing from', 'handled' => false]);
    exit;
}

if ($action === 'active_session') {
    $session = xander_prescreening_load_session($conn, $from);
    $step = $session ? (string) ($session['current_step'] ?? 'idle') : 'idle';
    echo json_encode([
        'active' => $step !== 'idle',
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
    $handled = (bool) xander_prescreening_handle_inbound($conn, $from, $message);
    echo json_encode(['handled' => $handled, 'duplicate' => false]);
} catch (Throwable $e) {
    error_log('[prescreening-inbound] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['handled' => false, 'error' => 'internal']);
}
