<?php
require_once dirname(__DIR__) . '/db.php';
foreach (['application_payments', 'payment_receipts'] as $table) {
    echo "=== $table ===\n";
    $r = $conn->query("SHOW COLUMNS FROM `$table`");
    while ($row = $r->fetch_assoc()) {
        echo $row['Field'] . ' (' . $row['Type'] . ")\n";
    }
}
