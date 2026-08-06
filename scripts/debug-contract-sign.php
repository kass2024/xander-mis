<?php
declare(strict_types=1);
require 'C:/xampp/htdocs/Xander/db.php';
require 'C:/xampp/htdocs/Xander/includes/contract_fee_repository.php';

$codes = array_slice(array_keys(xander_get_all_contract_packages()), 0, 3);
echo "Sample codes: " . implode(', ', $codes) . PHP_EOL;
$test = $codes[0] ?? 'p501';
$pkg = getPackageDetails($test);
echo "getPackageDetails({$test}): " . ($pkg ? 'OK - ' . ($pkg['title'] ?? '') : 'FAIL') . PHP_EOL;

$tokenRes = $conn->query("SELECT contract_token, id, status FROM student_contracts WHERE status='draft' ORDER BY id DESC LIMIT 1");
$row = $tokenRes ? $tokenRes->fetch_assoc() : null;
echo 'Draft contract: ' . json_encode($row) . PHP_EOL;
