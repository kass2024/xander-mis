<?php
/**
 * Remap fee_packages.id 1–16 to match contract order (PDF Section 5).
 * Europe Study Full scholarships moves from id 16 → id 4; ids 4–15 shift +1.
 *
 * Run once: php scripts/reorder-fee-package-ids.php
 * Then:    php scripts/sync-fee-packages-burundi.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/db.php';

if (!isset($conn) || $conn->connect_error) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

/** @var array<int,int> old_id => new_id */
$oldToNew = [
    1  => 1,
    2  => 2,
    3  => 3,
    16 => 4,
    4  => 5,
    5  => 6,
    6  => 7,
    7  => 8,
    8  => 9,
    9  => 10,
    10 => 11,
    11 => 12,
    12 => 13,
    13 => 14,
    14 => 15,
    15 => 16,
];

const TEMP_OFFSET = 9000;

echo "=== Reorder fee_packages.id to contract sequence ===\n\n";

$conn->begin_transaction();

try {
    $conn->query('SET FOREIGN_KEY_CHECKS = 0');

    $tablesPackageId = ['fee_items', 'application_packages', 'payment_receipts'];

    echo "Step 1: Move ids to temporary range " . TEMP_OFFSET . "+...\n";
    $conn->query('UPDATE fee_packages SET id = id + ' . TEMP_OFFSET . ' WHERE id BETWEEN 1 AND 16');
    foreach ($tablesPackageId as $table) {
        $conn->query("UPDATE `$table` SET package_id = package_id + " . TEMP_OFFSET . ' WHERE package_id BETWEEN 1 AND 16');
    }

    echo "Step 2: Assign final ids 1–16\n";
    foreach ($oldToNew as $oldId => $newId) {
        $tempId = $oldId + TEMP_OFFSET;
        $conn->query("UPDATE fee_packages SET id = {$newId} WHERE id = {$tempId}");
        foreach ($tablesPackageId as $table) {
            $conn->query("UPDATE `$table` SET package_id = {$newId} WHERE package_id = {$tempId}");
        }
        echo "  package {$oldId} → id {$newId}\n";
    }

    $conn->query('SET FOREIGN_KEY_CHECKS = 1');
    $conn->commit();

    echo "\nStep 3: Align display_order with id\n";
    $conn->query('UPDATE fee_packages SET display_order = id WHERE id BETWEEN 1 AND 16');

    echo "\n--- fee_packages (by id) ---\n";
    $res = $conn->query('SELECT id, contract_code, title, total_amount, display_order FROM fee_packages WHERE id BETWEEN 1 AND 16 ORDER BY id');
    while ($row = $res->fetch_assoc()) {
        echo sprintf(
            "id=%2d  %s  %-45s  €%s  display_order=%s\n",
            $row['id'],
            $row['contract_code'],
            $row['title'],
            $row['total_amount'],
            $row['display_order']
        );
    }

    echo "\nDone. Run: php scripts/sync-fee-packages-burundi.php\n";
} catch (Throwable $e) {
    $conn->query('SET FOREIGN_KEY_CHECKS = 1');
    $conn->rollback();
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}
