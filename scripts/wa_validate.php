<?php
declare(strict_types=1);
/**
 * CLI: validate WhatsApp Cloud API credentials from .env (no secrets printed).
 */
$root = dirname(__DIR__);
require_once $root . '/helpers/whatsapp_api.php';

xander_load_env_file();

$diag = xander_whatsapp_api_diagnostic();
echo json_encode($diag, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

if (!($diag['preflight_ok'] ?? false)) {
    fwrite(STDERR, "\nFIX: " . ($diag['fix_hint'] ?? '') . "\n");
    exit(1);
}

echo "\nWhatsApp API credentials OK.\n";
echo 'Sending from: ' . ($diag['display_phone'] ?? '') . ' (' . ($diag['verified_name'] ?? '') . ")\n";
exit(0);
