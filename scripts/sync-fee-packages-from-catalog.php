<?php
/**
 * CLI wrapper — sync runs automatically on db.php connect; use this to force sync manually.
 * Run: php scripts/sync-fee-packages-from-catalog.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/fee_packages_sync.php';

echo "=== Sync fee_packages + fee_items (Client Service Contract catalog) ===\n\n";

// Force sync by clearing one package code temporarily is overkill; call internals directly.
xander_fee_packages_ensure_columns($conn);

$catalog = xander_contract_fee_catalog();
$activeCodes = [];
$conn->begin_transaction();
try {
    foreach ($catalog as $pkg) {
        $contractCode = $pkg['contract_code'];
        $activeCodes[] = $contractCode;
        $title = trim($pkg['title']);
        $total = (float) $pkg['total'];
        $currency = $pkg['currency'];
        $dbCode = $pkg['db_code'];
        $displayOrder = (int) $pkg['package_id'];

        $packageId = xander_fee_packages_find_id_by_code($conn, $contractCode);
        if ($packageId === null) {
            $slotId = $displayOrder;
            $slotCheck = $conn->query("SELECT id, contract_code FROM fee_packages WHERE id = {$slotId} LIMIT 1");
            if ($slotCheck && ($slotRow = $slotCheck->fetch_assoc()) && empty($slotRow['contract_code'])) {
                $packageId = $slotId;
            }
        }

        if ($packageId !== null) {
            $stmt = $conn->prepare(
                'UPDATE fee_packages SET code = ?, title = ?, currency = ?, total_amount = ?, total_expected = ?, contract_code = ?, display_order = ? WHERE id = ?'
            );
            $stmt->bind_param('sssddsii', $dbCode, $title, $currency, $total, $total, $contractCode, $displayOrder, $packageId);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO fee_packages (code, title, currency, total_amount, total_expected, contract_code, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('sssddsi', $dbCode, $title, $currency, $total, $total, $contractCode, $displayOrder);
            $stmt->execute();
            $packageId = (int) $conn->insert_id;
            $stmt->close();
        }

        xander_fee_packages_sync_items($conn, $packageId, $pkg['items'], $currency);
        echo sprintf("[%2d] %s package_id=%d %s\n", $displayOrder, $contractCode, $packageId, $title);
    }

    if ($activeCodes !== []) {
        $escaped = array_map(static fn(string $c): string => "'" . $conn->real_escape_string($c) . "'", $activeCodes);
        $conn->query(
            'UPDATE fee_packages SET contract_code = NULL WHERE contract_code IS NOT NULL AND contract_code NOT IN (' . implode(',', $escaped) . ')'
        );
    }
    $conn->commit();
    echo "\nDone. " . count($catalog) . " packages synced.\n";
} catch (Throwable $e) {
    $conn->rollback();
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}
