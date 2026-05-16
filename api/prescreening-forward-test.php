<?php
/**
 * Verify VPS → cPanel delivery_status forward (admin session or shared secret).
 * GET/POST ?secret=PRESCREENING_FORWARD_SECRET — simulates Meta "failed" for latest invited session.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/helpers/env_load.php';
require_once dirname(__DIR__) . '/helpers/whatsapp_track_log.php';
require_once dirname(__DIR__) . '/helpers/prescreening_whatsapp_schema.php';
require_once dirname(__DIR__) . '/helpers/prescreening_whatsapp_flow.php';

xander_load_env_file();

$expectedSecret = xander_env_get('PRESCREENING_FORWARD_SECRET');
$givenSecret = (string) ($_GET['secret'] ?? $_POST['secret'] ?? $_SERVER['HTTP_X_XANDER_FORWARD_SECRET'] ?? '');

session_start();
$adminOk = !empty($_SESSION['id']) || !empty($_SESSION['admin_id']);
$secretOk = $expectedSecret !== '' && hash_equals($expectedSecret, $givenSecret);

if (!$adminOk && !$secretOk) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden — log in as admin or pass ?secret=PRESCREENING_FORWARD_SECRET']);
    exit;
}

if (!is_file(dirname(__DIR__) . '/db.php')) {
    http_response_code(500);
    echo json_encode(['error' => 'db.php missing']);
    exit;
}

require_once dirname(__DIR__) . '/db.php';
xander_ensure_prescreening_whatsapp_tables($conn);

$row = null;
$r = @$conn->query(
    "SELECT wa_phone, last_wamid FROM whatsapp_prescreening_sessions
     WHERE current_step = 'invited' ORDER BY updated_at DESC LIMIT 1"
);
if ($r && ($row = $r->fetch_assoc())) {
    $r->free();
}

if (!$row) {
    echo json_encode(['ok' => false, 'error' => 'No invited session in DB — send an invite first']);
    exit;
}

$wamid = trim((string) ($row['last_wamid'] ?? ''));
if ($wamid === '') {
    $wamid = 'test-wamid-forward-ping';
}

$recorded = xander_prescreening_apply_delivery_status(
    $conn,
    $wamid,
    'failed',
    131031,
    'TEST ONLY — forward path works (not a real Meta error)',
    (string) $row['wa_phone']
);

echo json_encode([
    'ok' => $recorded,
    'message' => $recorded
        ? 'DB updated — cPanel can receive delivery_status. Deploy xanderbot forwardDeliveryStatus on VPS if real invites stay at api_accepted.'
        : 'DB update failed — check whatsapp_prescreening_sessions columns',
    'wa_phone' => $row['wa_phone'],
    'wamid' => $wamid,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
