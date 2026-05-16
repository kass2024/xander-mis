<?php
require_once dirname(__DIR__) . '/db.php';
foreach (['student_contracts_special', 'student_signatures_special'] as $table) {
    echo "=== $table ===\n";
    $r = $conn->query("SHOW CREATE TABLE `$table`");
    $row = $r->fetch_assoc();
    echo ($row['Create Table'] ?? 'missing') . "\n\n";
}
