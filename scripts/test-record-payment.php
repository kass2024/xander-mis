<?php
require_once dirname(__DIR__) . '/db.php';

$tables = ['application_payments', 'application_packages', 'payment_receipts', 'fee_items', 'fee_packages'];
foreach ($tables as $t) {
    $r = $conn->query("SHOW TABLES LIKE '{$t}'");
    echo $t . ': ' . ($r && $r->num_rows ? 'yes' : 'MISSING') . PHP_EOL;
    if ($r && $r->num_rows) {
        $c = $conn->query("SHOW COLUMNS FROM `{$t}`");
        while ($row = $c->fetch_assoc()) {
            echo '  - ' . $row['Field'] . ' (' . $row['Type'] . ')' . PHP_EOL;
        }
    }
}

// Latest student application
$r = $conn->query('SELECT id, first_name, last_name, email FROM student_applications ORDER BY id DESC LIMIT 1');
if ($row = $r->fetch_assoc()) {
    echo PHP_EOL . 'Latest app: ' . json_encode($row) . PHP_EOL;
    $appId = (int) $row['id'];
    $pkg = $conn->query('SELECT id, title FROM fee_packages LIMIT 1');
    if ($p = $pkg->fetch_assoc()) {
        $pkgId = (int) $p['id'];
        echo 'Package: ' . json_encode($p) . PHP_EOL;
        $fi = $conn->query("SELECT id, title, amount FROM fee_items WHERE package_id = {$pkgId} LIMIT 3");
        $items = [];
        while ($f = $fi->fetch_assoc()) {
            $items[$f['id']] = min(50, (float) $f['amount']);
            echo '  fee_item: ' . json_encode($f) . PHP_EOL;
        }
        if ($items) {
            $payload = json_encode([
                'student_id' => $appId,
                'table' => 'student_applications',
                'package_id' => $pkgId,
                'payment_method' => 'Bank Transfer',
                'comment' => 'test',
                'items' => $items,
            ]);
            echo PHP_EOL . 'Simulating record-payment.php...' . PHP_EOL;
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $rawInput = $payload;
            // inline test
            chdir(dirname(__DIR__));
            ob_start();
            $GLOBALS['test_raw_input'] = $payload;
            // can't easily include record-payment without modifying it
        }
    }
}
