<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';

$code = 'p503';
$stmt = $conn->prepare('SELECT id, title, total_amount, contract_code FROM fee_packages WHERE contract_code = ?');
$stmt->bind_param('s', $code);
$stmt->execute();
$pkg = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo "Package p503:\n";
print_r($pkg);

if ($pkg) {
    $pid = (int) $pkg['id'];
    $items = $conn->query("SELECT name, amount, payable_stage FROM fee_items WHERE package_id = {$pid} ORDER BY id");
    echo "Items:\n";
    while ($row = $items->fetch_assoc()) {
        echo "  - {$row['name']}: {$row['amount']} ({$row['payable_stage']})\n";
    }
}
