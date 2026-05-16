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

xander_load_env_file();

$tail = xander_whatsapp_track_read_tail(100);

echo json_encode([
    'log_file' => $tail['path'],
    'log_exists' => $tail['exists'],
    'lines' => $tail['lines'],
    'hint' => 'Also check cPanel → Errors for lines starting with [xander-wa]',
    'env' => [
        'WHATSAPP_PHONE_NUMBER_ID_set' => xander_env_get('WHATSAPP_PHONE_NUMBER_ID') !== '',
        'WHATSAPP_ACCESS_TOKEN_set' => xander_env_get('WHATSAPP_ACCESS_TOKEN') !== '',
        'WHATSAPP_PRESCREENING_INVITE_TEMPLATE_LANG' => xander_env_get('WHATSAPP_PRESCREENING_INVITE_TEMPLATE_LANG') ?: 'en (default)',
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
