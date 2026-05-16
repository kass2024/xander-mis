<?php
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/contract_fee_repository.php';

$p502 = getPackageDetails('p502');
$p506 = getPackageDetails('p506');
$p509 = getPackageDetails('p509');

echo "p502 (loan): " . json_encode($p502, JSON_PRETTY_PRINT) . "\n";
echo "p506 (SK/China): " . json_encode($p506, JSON_PRETTY_PRINT) . "\n";
echo "p509 (Bachelor): " . json_encode($p509, JSON_PRETTY_PRINT) . "\n";

$r = $conn->query("SELECT contract_code, title, total_amount FROM fee_packages WHERE contract_code IS NOT NULL ORDER BY display_order");
echo "\nDB packages:\n";
while ($row = $r->fetch_assoc()) {
    echo "  {$row['contract_code']}: {$row['title']} = {$row['total_amount']}\n";
}
