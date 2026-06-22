<?php
require __DIR__ . '/../helpers/env_load.php';
xander_load_env_file();
require __DIR__ . '/../helpers/whatsapp_api.php';

$token = xander_env_get('WHATSAPP_ACCESS_TOKEN');
$version = xander_env_get('META_GRAPH_VERSION') ?: 'v19.0';
$phoneId = xander_env_get('WHATSAPP_PHONE_NUMBER_ID');
$waba = xander_env_get('WHATSAPP_BUSINESS_ID');

$checks = [
    'phone' => "{$version}/{$phoneId}?fields=display_phone_number,verified_name,quality_rating,account_mode,status",
    'waba_phones' => "{$version}/{$waba}/phone_numbers?fields=id,display_phone_number,verified_name,quality_rating,status",
    'waba' => "{$version}/{$waba}?fields=id,name,timezone_id,currency,account_review_status,business_verification_status,message_template_namespace",
];

foreach ($checks as $label => $path) {
    $r = xander_whatsapp_graph_get($path, $token);
    echo "=== {$label} (HTTP {$r['http']}) ===\n";
    echo $r['body'] . "\n\n";
}

$otherWaba = '1534937984407084';
$portfolioId = '1988061245394236';

$extra = [
    'other_waba' => "{$version}/{$otherWaba}?fields=id,name,timezone_id,currency,account_review_status,business_verification_status",
    'other_waba_phones' => "{$version}/{$otherWaba}/phone_numbers?fields=id,display_phone_number,status",
    'portfolio' => "{$version}/{$portfolioId}?fields=id,name,owned_whatsapp_business_accounts{id,name,currency,timezone_id,account_review_status}",
    'waba_extended' => "{$version}/{$waba}?fields=id,name,primary_funding_id,purchase_order_number,on_behalf_of_business_info,is_enabled_for_insights",
];

foreach ($extra as $label => $path) {
    $r = xander_whatsapp_graph_get($path, $token);
    echo "=== {$label} (HTTP {$r['http']}) ===\n";
    echo $r['body'] . "\n\n";
}
