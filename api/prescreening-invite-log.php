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

require_once dirname(__DIR__) . '/helpers/whatsapp_track_log.php';
require_once dirname(__DIR__) . '/helpers/env_load.php';
require_once dirname(__DIR__) . '/helpers/prescreening_whatsapp_schema.php';

xander_load_env_file();

$tail = xander_whatsapp_track_read_tail(100);

$sessions = [];
if (is_file(dirname(__DIR__) . '/db.php')) {
    require_once dirname(__DIR__) . '/db.php';
    if (isset($conn) && $conn instanceof mysqli) {
        xander_ensure_prescreening_whatsapp_tables($conn);
        $r = @$conn->query(
            'SELECT wa_phone, current_step, updated_at FROM whatsapp_prescreening_sessions ORDER BY updated_at DESC LIMIT 15'
        );
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $sessions[] = $row;
            }
            $r->free();
        }
    }
}

echo json_encode([
    'log_file' => $tail['path'],
    'log_exists' => $tail['exists'],
    'lines' => $tail['lines'],
    'whatsapp_sessions' => $sessions,
    'hint' => 'invite_send_ok = Meta API accepted. If phone gets nothing, check Meta Business → Message logs. Student START goes to xanderbot.site webhook, not cPanel.',
    'vps_webhook' => 'https://xanderbot.site/api/webhook/meta',
    'env' => [
        'WHATSAPP_PHONE_NUMBER_ID_set' => xander_env_get('WHATSAPP_PHONE_NUMBER_ID') !== '',
        'WHATSAPP_ACCESS_TOKEN_set' => xander_env_get('WHATSAPP_ACCESS_TOKEN') !== '',
        'WHATSAPP_PRESCREENING_INVITE_TEMPLATE_LANG' => xander_env_get('WHATSAPP_PRESCREENING_INVITE_TEMPLATE_LANG') ?: 'en (default)',
        'PRESCREENING_FORWARD_SECRET_set' => xander_env_get('PRESCREENING_FORWARD_SECRET') !== '',
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
