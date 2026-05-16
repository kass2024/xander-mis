<?php
require_once dirname(__DIR__) . '/db.php';
foreach (['fee_packages', 'fee_items'] as $t) {
    echo "=== DESCRIBE $t ===\n";
    $r = $conn->query("DESCRIBE `$t`");
    while ($row = $r->fetch_assoc()) {
        echo "{$row['Field']}\t{$row['Type']}\n";
    }
}
echo "\n=== fee_packages ===\n";
$r = $conn->query('SELECT * FROM fee_packages ORDER BY display_order ASC, id ASC');
while ($row = $r->fetch_assoc()) {
    echo json_encode($row) . "\n";
}
echo "\n=== fee_items ===\n";
$r = $conn->query('SELECT * FROM fee_items ORDER BY package_id, id');
while ($row = $r->fetch_assoc()) {
    echo json_encode($row) . "\n";
}
