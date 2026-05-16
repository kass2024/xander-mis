<?php
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/receipt_branding.php';

$row = $conn->query(
    "SELECT a.id, o.office_name FROM admins a
     JOIN offices o ON o.id = a.office_id
     WHERE o.office_name LIKE 'Bujumbura%' LIMIT 1"
)->fetch_assoc();

if ($row) {
    $b = xander_get_receipt_branding($conn, (int) $row['id']);
    echo "Admin {$row['id']} office={$row['office_name']} dual=" . ($b['dual'] ? 'yes' : 'no') . PHP_EOL;
} else {
    echo "No Bujumbura admin found — testing name match only\n";
    echo xander_receipt_is_bujumbura('Bujumbura') ? "Bujumbura match OK\n" : "Bujumbura match FAIL\n";
}

$bDefault = xander_get_receipt_branding($conn, 0);
$_SESSION['office_name'] = 'Kigali';
$b2 = xander_get_receipt_branding($conn, 0);
echo "Non-Bujumbura dual=" . ($b2['dual'] ? 'yes' : 'no') . PHP_EOL;
