<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';

$r = $conn->query("SELECT id, contract_token, status, selected_package_code FROM student_contracts ORDER BY id DESC LIMIT 5");
while ($row = $r->fetch_assoc()) {
    echo "{$row['id']} | {$row['status']} | {$row['contract_token']} | pkg={$row['selected_package_code']}\n";
}
