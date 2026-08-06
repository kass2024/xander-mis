<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';
$r = $conn->query("
  SELECT c.id, c.status, c.signed_at, sig.student_email, sig.student_name
  FROM student_contracts c
  LEFT JOIN student_signatures sig ON sig.contract_id = c.id
  ORDER BY c.id DESC
");
while ($row = $r->fetch_assoc()) {
    echo implode(' | ', $row) . "\n";
}

echo "\nList query rows:\n";
require_once __DIR__ . '/../includes/contract_admin_helpers.php';
$sql = xander_admin_signed_contracts_sql('student_contracts', 'student_signatures');
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    echo "contract_id={$row['contract_id']} email={$row['email']}\n";
}
