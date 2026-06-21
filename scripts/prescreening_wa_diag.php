<?php
declare(strict_types=1);
/**
 * CLI: diagnose pre-screening WhatsApp invite (no secrets printed).
 * Usage: php scripts/prescreening_wa_diag.php [phone] [student name]
 */
$root = dirname(__DIR__);
require_once $root . '/helpers/env_load.php';
require_once $root . '/helpers/prescreening_whatsapp_flow.php';
require_once $root . '/helpers/whatsapp_api.php';

xander_load_env_file();

$phone = $argv[1] ?? '+250788000000';
$name = $argv[2] ?? 'Diag Test';

$checks = [
    'api_diagnostic' => xander_whatsapp_api_diagnostic(),
    'WHATSAPP_ACCESS_TOKEN' => strlen(xander_env_get('WHATSAPP_ACCESS_TOKEN')),
    'WHATSAPP_PHONE_NUMBER_ID' => strlen(xander_env_get('WHATSAPP_PHONE_NUMBER_ID')),
    'WHATSAPP_DEFAULT_COUNTRY_CODE' => xander_env_get('WHATSAPP_DEFAULT_COUNTRY_CODE'),
    'META_GRAPH_VERSION' => xander_env_get('META_GRAPH_VERSION') ?: 'v19.0 (default)',
    'template' => XANDER_WHATSAPP_PRESCREENING_INVITE_TEMPLATE,
    'template_lang' => xander_prescreening_invite_template_lang(),
    'template_lang_candidates' => xander_prescreening_invite_template_lang_candidates(),
];

$normalized = xander_prescreening_normalize_wa_phone($phone);
$checks['input_phone'] = $phone;
$checks['normalized_to'] = $normalized ?? '(invalid)';

echo json_encode($checks, JSON_PRETTY_PRINT) . PHP_EOL;

if ($normalized === null) {
    fwrite(STDERR, "Phone normalization failed.\n");
    exit(1);
}

$api = xander_whatsapp_api_messages_url();
if ($api === null) {
    fwrite(STDERR, "WhatsApp API not configured.\n");
    exit(1);
}

// Dry-run: only call Graph if second arg is --send
if (!in_array('--send', $argv, true)) {
    echo "Add --send to actually call Meta API.\n";
    exit(0);
}

require_once $root . '/db.php';
require_once $root . '/helpers/prescreening_whatsapp_schema.php';
xander_ensure_prescreening_whatsapp_tables($conn);

$result = xander_prescreening_admin_send_invite($conn, $phone, $name);
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
exit($result['sent'] ? 0 : 1);
