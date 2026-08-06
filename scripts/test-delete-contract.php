<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';
if (!function_exists('xander_admin_delete_contract')) {
    require __DIR__ . '/../includes/contract_admin_helpers.php';
}

echo "Signed contracts:\n";
$r = $conn->query("SELECT c.id, c.pdf_path, sig.student_email FROM student_contracts c JOIN student_signatures sig ON sig.contract_id=c.id WHERE c.status='signed'");
while ($row = $r->fetch_assoc()) {
    echo " id={$row['id']} email={$row['student_email']} pdf={$row['pdf_path']}\n";
}

$id = (int)($argv[1] ?? 0);
if ($id > 0) {
    echo "\nAttempting delete id={$id}...\n";
    $ok = xander_admin_delete_contract($conn, 'student_contracts', 'student_signatures', $id);
    echo $ok ? "DELETE OK\n" : "DELETE FAILED\n";
    echo "conn error: " . $conn->error . "\n";
}

// FK checks
$fk = $conn->query("SELECT TABLE_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IN ('student_contracts','student_signatures')");
echo "\nForeign keys referencing contracts:\n";
while ($row = $fk->fetch_assoc()) {
    print_r($row);
}
