<?php
/**
 * List contract signature / stamp / letterhead asset locations.
 * Run: php scripts/show-contract-branding.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/contract_branding.php';

$catalog = xander_contract_branding_catalog();
echo "=== Xander contract branding assets ===\n\n";
foreach ($catalog as $key => $item) {
    $flag = $item['exists'] ? 'OK' : 'MISSING';
    echo "[{$flag}] {$key}\n";
    echo "  Path: {$item['path']}\n";
    echo "  Role: {$item['role']}\n";
    echo "  Used by: {$item['used_by']}\n\n";
}

echo "PDF employer signature resolved to:\n  " . xander_contract_employer_signature_path() . "\n";
echo "Web contract signature src:\n  " . xander_contract_web_signature_src() . "\n";
