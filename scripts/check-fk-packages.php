<?php
require_once dirname(__DIR__) . '/db.php';
$r = $conn->query("
    SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND REFERENCED_TABLE_NAME IN ('fee_packages', 'fee_items')
");
while ($row = $r->fetch_assoc()) {
    print_r($row);
}
echo "\nTables with package_id:\n";
$r = $conn->query("SHOW TABLES");
while ($t = $r->fetch_array()) {
    $table = $t[0];
    $c = $conn->query("SHOW COLUMNS FROM `$table`");
    while ($col = $c->fetch_assoc()) {
        if (stripos($col['Field'], 'package') !== false || stripos($col['Field'], 'fee_item') !== false) {
            echo "$table.{$col['Field']}\n";
        }
    }
}
