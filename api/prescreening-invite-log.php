<?php
/**
 * Last pre-screening WhatsApp log lines (admin, session required).
 * https://YOUR-DOMAIN/api/prescreening-invite-log.php
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();

if (empty($_SESSION['id']) && empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/helpers/prescreening_access.php';
if (!isset($conn) || !($conn instanceof mysqli) || !xander_prescreening_has_menu_access($conn, 'prescreening.php')) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

require_once dirname(__DIR__) . '/helpers/whatsapp_track_log.php';
require_once dirname(__DIR__) . '/helpers/env_load.php';
require_once dirname(__DIR__) . '/helpers/prescreening_whatsapp_schema.php';
require_once dirname(__DIR__) . '/helpers/prescreening_whatsapp_flow.php';
require_once dirname(__DIR__) . '/helpers/whatsapp_api.php';

xander_load_env_file();

$tail = xander_whatsapp_track_read_tail(100);

$staffNumbers = xander_prescreening_staff_whatsapp_numbers();

$hasDeliveryForward = false;
$hasDeliveryWebhook = false;
foreach ($tail['lines'] as $line) {
    if (str_contains($line, 'delivery_status_forward') || str_contains($line, 'invite_delivery_')) {
        $hasDeliveryForward = true;
    }
    if (str_contains($line, 'invite_delivery_failed') || str_contains($line, 'invite_delivery_delivered')) {
        $hasDeliveryWebhook = true;
    }
}

$sessions = [];
if (is_file(dirname(__DIR__) . '/db.php')) {
    require_once dirname(__DIR__) . '/db.php';
    if (isset($conn) && $conn instanceof mysqli) {
        xander_ensure_prescreening_whatsapp_tables($conn);
        $r = @$conn->query(
            'SELECT wa_phone, current_step, last_wamid, last_delivery_status, last_delivery_error_code,
                    last_delivery_error_message, last_delivery_at, updated_at
             FROM whatsapp_prescreening_sessions ORDER BY updated_at DESC LIMIT 15'
        );
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $row['display_status'] = xander_prescreening_invite_status_message($row);
                $sessions[] = $row;
            }
            $r->free();
        }
    }
}

$diagnosis = [];
foreach ($sessions as $s) {
    $phone = (string) ($s['wa_phone'] ?? '');
    $st = strtolower((string) ($s['last_delivery_status'] ?? ''));
    $code = (int) ($s['last_delivery_error_code'] ?? 0);
    if ($phone !== '' && in_array($phone, $staffNumbers, true)) {
        $diagnosis[] = "Session …{$phone} (staff): use a student +250… number, not PRESCREENING_STAFF_WHATSAPP.";
    }
    if ($st === 'api_accepted' || $st === 'accepted') {
        $diagnosis[] = 'Session …' . substr($phone, -4) . ': API accepted — waiting for VPS webhook (sent/delivered/failed). Refresh in ~30s.';
    }
    if ($st === 'failed' && $code === 131031) {
        $diagnosis[] = 'Session …' . substr($phone, -4) . ': Meta reports 131031 (business restricted) on this delivery attempt.';
    } elseif ($st === 'failed' && $code > 0) {
        $diagnosis[] = 'Session …' . substr($phone, -4) . ": delivery failed ({$code}): " . ($s['last_delivery_error_message'] ?? '');
    }
}
if (!$hasDeliveryForward) {
    $diagnosis[] = 'No delivery_status_forward / invite_delivery_* lines in cPanel log — VPS → cPanel delivery webhook forward is missing or not deployed.';
}
if ($hasDeliveryForward && !$hasDeliveryWebhook) {
    $diagnosis[] = 'Forward reached cPanel but no delivered/failed yet — wait 30s after invite or check VPS: tail -f storage/logs/laravel.log | grep delivery_failed';
}

echo json_encode([
    'log_file' => $tail['path'],
    'log_exists' => $tail['exists'],
    'lines' => $tail['lines'],
    'whatsapp_sessions' => $sessions,
    'diagnosis' => $diagnosis,
    'delivery_forward_seen_in_cpanel_log' => $hasDeliveryForward,
    'hint' => 'graph_template_ok / api_accepted = Meta accepted send. Real delivery = sent|delivered|failed from VPS webhook. Test forward: api/prescreening-forward-test.php (admin login).',
    'vps_webhook' => 'https://xanderbot.site/api/webhook/meta',
    'vps_diagnostic' => 'https://xanderbot.site/api/webhook/diagnostic',
    'forward_test_url' => 'api/prescreening-forward-test.php',
    'staff_whatsapp_numbers' => $staffNumbers,
    'env' => [
        'WHATSAPP_PHONE_NUMBER_ID_set' => xander_env_get('WHATSAPP_PHONE_NUMBER_ID') !== '',
        'WHATSAPP_ACCESS_TOKEN_set' => xander_env_get('WHATSAPP_ACCESS_TOKEN') !== '',
        'WHATSAPP_PRESCREENING_INVITE_TEMPLATE_LANG' => xander_env_get('WHATSAPP_PRESCREENING_INVITE_TEMPLATE_LANG') ?: 'en (default)',
        'PRESCREENING_FORWARD_SECRET_set' => xander_env_get('PRESCREENING_FORWARD_SECRET') !== '',
    ],
    'api_diagnostic' => xander_whatsapp_api_diagnostic(),
    'delivery_poll_url' => 'api/prescreening-invite-delivery.php?phone=',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
