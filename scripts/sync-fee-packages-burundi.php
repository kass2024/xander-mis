<?php
/**
 * Sync fee_packages + fee_items from HEERA-Xander CLIENT CONTRACT-MAY 2026.pdf (Section 5).
 * Packages are applied in contract order via display_order (1–16).
 * fee_items are updated in-place by package_id so existing payment FKs stay valid.
 *
 * Run: php scripts/sync-fee-packages-burundi.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/contract_fee_catalog.php';

if (!isset($conn) || $conn->connect_error) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

function ensure_column(mysqli $conn, string $table, string $column, string $definition): void
{
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($res && $res->num_rows === 0) {
        if (!$conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition")) {
            throw new RuntimeException("ALTER $table.$column failed: " . $conn->error);
        }
        echo "Added column $table.$column\n";
    }
}

echo "=== Sync fee_packages + fee_items (HEERA-Xander contract May 2026) ===\n\n";

ensure_column($conn, 'fee_packages', 'contract_code', 'VARCHAR(20) DEFAULT NULL');
ensure_column($conn, 'fee_packages', 'display_order', 'INT NOT NULL DEFAULT 0');

$catalog = xander_contract_fee_catalog();
$order = 0;

$conn->begin_transaction();

try {
    foreach ($catalog as $pkg) {
        $order++;
        $pid = (int) $pkg['package_id'];
        $title = trim($pkg['title']);
        $total = (float) $pkg['total'];
        $currency = $pkg['currency'];
        $contractCode = $pkg['contract_code'];
        $dbCode = $pkg['db_code'];

        $stmt = $conn->prepare(
            'UPDATE fee_packages SET code = ?, title = ?, currency = ?, total_amount = ?, total_expected = ?, contract_code = ?, display_order = ? WHERE id = ?'
        );
        $displayOrder = $pid;
        $stmt->bind_param('sssddsii', $dbCode, $title, $currency, $total, $total, $contractCode, $displayOrder, $pid);
        $stmt->execute();
        if ($stmt->affected_rows === 0 && $stmt->errno === 0) {
            $chk = $conn->query("SELECT id FROM fee_packages WHERE id = {$pid} LIMIT 1");
            if (!$chk || $chk->num_rows === 0) {
                throw new RuntimeException("fee_packages id {$pid} not found.");
            }
        }
        $stmt->close();

        $itemRes = $conn->query("SELECT id FROM fee_items WHERE package_id = {$pid} ORDER BY id ASC");
        $itemIds = [];
        while ($row = $itemRes->fetch_assoc()) {
            $itemIds[] = (int) $row['id'];
        }

        $items = $pkg['items'];
        if (count($itemIds) < count($items)) {
            throw new RuntimeException("Package {$contractCode} (id {$pid}): need " . count($items) . " fee_items, found " . count($itemIds));
        }

        $upd = $conn->prepare(
            'UPDATE fee_items SET name = ?, amount = ?, currency = ?, payable_stage = ? WHERE id = ? AND package_id = ?'
        );
        foreach ($items as $i => $item) {
            $itemId = $itemIds[$i];
            $name = $item['name'];
            $amount = (float) $item['amount'];
            $stage = $item['payable_stage'];
            $upd->bind_param('sdssii', $name, $amount, $currency, $stage, $itemId, $pid);
            $upd->execute();
        }
        $upd->close();

        if (count($itemIds) > count($items)) {
            foreach (array_slice($itemIds, count($items)) as $eid) {
                $conn->query("DELETE FROM fee_items WHERE id = {$eid} AND package_id = {$pid}");
            }
        }

        echo sprintf(
            "[%2d] %s  package_id=%d  %s  total %s %s\n",
            $order,
            $contractCode,
            $pid,
            $title,
            number_format($total, 2),
            $currency
        );
        foreach ($items as $item) {
            echo sprintf("      · %s: %s %s\n", $item['name'], number_format((float) $item['amount'], 2), $currency);
        }
    }

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "\n--- Verification (contract order) ---\n";
$res = $conn->query("
    SELECT fp.display_order, fp.contract_code, fp.id AS package_id, fp.title, fp.total_amount,
           fi.id AS item_id, fi.name AS item_name, fi.amount
    FROM fee_packages fp
    LEFT JOIN fee_items fi ON fi.package_id = fp.id
    WHERE fp.contract_code IS NOT NULL
    ORDER BY fp.display_order ASC, fi.id ASC
");
while ($row = $res->fetch_assoc()) {
    echo sprintf(
        "order=%s %s pkg#%s item#%s %s | %s = %s\n",
        $row['display_order'],
        $row['contract_code'],
        $row['package_id'],
        $row['item_id'] ?? '-',
        $row['title'],
        $row['item_name'] ?? '',
        $row['amount'] ?? ''
    );
}

echo "\nDone. " . count($catalog) . " packages synced (id 1–16 = contract order).\n";
