<?php
/**
 * Pre-screening webhook diagnostics (cPanel). No secrets exposed.
 * Open: https://YOUR-CPANEL-DOMAIN/api/webhook-health.php
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$root = dirname(__DIR__);
$checks = [];
$ok = true;

function wh_add(array &$checks, string $name, bool $pass, string $detail = ''): void
{
    $checks[] = ['name' => $name, 'ok' => $pass, 'detail' => $detail];
}

wh_add($checks, 'php_version', true, PHP_VERSION);

$helpers = [
    'helpers/env_load.php',
    'helpers/prescreening_whatsapp_flow.php',
    'helpers/prescreening_whatsapp_schema.php',
    'api/whatsapp-webhook.php',
];
foreach ($helpers as $rel) {
    $path = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    wh_add($checks, 'file:' . $rel, is_file($path), $path);
    if (!is_file($path)) {
        $ok = false;
    }
}

require_once $root . '/helpers/env_load.php';
xander_load_env_file();

wh_add($checks, 'WHATSAPP_VERIFY_TOKEN', xander_env_get('WHATSAPP_VERIFY_TOKEN') !== '', 'set in .env');
wh_add($checks, 'WHATSAPP_APP_SECRET', xander_env_get('WHATSAPP_APP_SECRET') !== '', 'set in .env (POST signature)');
wh_add($checks, 'WHATSAPP_ACCESS_TOKEN', xander_env_get('WHATSAPP_ACCESS_TOKEN') !== '', 'set in .env');
wh_add($checks, 'WHATSAPP_PHONE_NUMBER_ID', xander_env_get('WHATSAPP_PHONE_NUMBER_ID') !== '', 'set in .env');
$inviteLang = xander_env_get('WHATSAPP_PRESCREENING_INVITE_TEMPLATE_LANG');
wh_add(
    $checks,
    'WHATSAPP_PRESCREENING_INVITE_TEMPLATE_LANG',
    true,
    $inviteLang !== '' ? $inviteLang : 'default en (xander_prescreening_invite)'
);
wh_add($checks, 'PRESCREENING_FORWARD_SECRET', xander_env_get('PRESCREENING_FORWARD_SECRET') !== '', 'must match xanderbot VPS .env');

if (!xander_env_get('WHATSAPP_VERIFY_TOKEN') || !xander_env_get('WHATSAPP_ACCESS_TOKEN')) {
    $ok = false;
}

require_once $root . '/helpers/prescreening_whatsapp_schema.php';

$dbOk = false;
$dbDetail = '';
$tables = [];
if (is_file($root . '/db.php')) {
    require_once $root . '/db.php';
    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        $dbOk = true;
        $dbDetail = 'connected';
        xander_ensure_prescreening_whatsapp_tables($conn);
        foreach (['prescreening_submissions', 'whatsapp_prescreening_sessions', 'whatsapp_inbound_dedup'] as $t) {
            $r = @$conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($t) . "'");
            $tables[$t] = ($r && $r->num_rows > 0);
        }
    } else {
        $dbDetail = isset($conn) ? ($conn->connect_error ?? 'connect failed') : 'no $conn';
        $ok = false;
    }
} else {
    $dbDetail = 'db.php missing';
    $ok = false;
}
wh_add($checks, 'database', $dbOk, $dbDetail);

foreach ($tables as $t => $exists) {
    wh_add($checks, 'table:' . $t, $exists, $exists ? 'ok' : 'missing');
    if (!$exists) {
        $ok = false;
    }
}

$uploadDir = $root . '/uploads/prescreening';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}
wh_add($checks, 'uploads/prescreening writable', is_dir($uploadDir) && is_writable($uploadDir), $uploadDir);

wh_add(
    $checks,
    'function:verify_signature',
    function_exists('xander_whatsapp_verify_webhook_signature'),
    ''
);
wh_add(
    $checks,
    'function:handle_inbound',
    function_exists('xander_prescreening_handle_inbound'),
    ''
);

$host = (string) ($_SERVER['HTTP_HOST'] ?? 'your-domain.com');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$webhookUrl = $scheme . '://' . $host . '/api/whatsapp-webhook.php';

echo json_encode([
    'status' => $ok ? 'ready' : 'fix_required',
    'webhook_callback_url' => $webhookUrl,
    'meta_verify_token_name' => 'WHATSAPP_VERIFY_TOKEN',
    'checks' => $checks,
    'forward_inbound_url' => $scheme . '://' . $host . '/api/prescreening-inbound.php',
    'meta_setup' => [
        'meta_webhook' => 'Keep https://xanderbot.site/api/webhook/meta (unchanged)',
        'cpanel_forward' => 'VPS forwards prescreening to /api/prescreening-inbound.php',
        'env' => 'PRESCREENING_FORWARD_SECRET must match xanderbot .env',
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
