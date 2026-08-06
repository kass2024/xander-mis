<?php
declare(strict_types=1);

require_once __DIR__ . '/contract_fee_catalog.php';

function xander_fee_packages_ensure_columns(mysqli $conn): void
{
    $res = $conn->query("SHOW COLUMNS FROM fee_packages LIKE 'contract_code'");
    if (!$res || $res->num_rows === 0) {
        $conn->query("ALTER TABLE fee_packages ADD COLUMN contract_code VARCHAR(20) DEFAULT NULL");
    }
    $res = $conn->query("SHOW COLUMNS FROM fee_packages LIKE 'display_order'");
    if (!$res || $res->num_rows === 0) {
        $conn->query('ALTER TABLE fee_packages ADD COLUMN display_order INT NOT NULL DEFAULT 0');
    }
}

function xander_fee_packages_find_id_by_db_code(mysqli $conn, string $dbCode): ?int
{
    $stmt = $conn->prepare('SELECT id FROM fee_packages WHERE code = ? LIMIT 1');
    $stmt->bind_param('s', $dbCode);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int) $row['id'] : null;
}

function xander_fee_packages_find_id_by_code(mysqli $conn, string $contractCode): ?int
{
    $stmt = $conn->prepare('SELECT id FROM fee_packages WHERE contract_code = ? LIMIT 1');
    $stmt->bind_param('s', $contractCode);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int) $row['id'] : null;
}

function xander_fee_packages_resolve_code_conflict(mysqli $conn, string $dbCode, int $keepId): void
{
    if ($dbCode === '') {
        return;
    }
    $stmt = $conn->prepare('UPDATE fee_packages SET code = CONCAT(code, "_", id) WHERE code = ? AND id <> ?');
    $stmt->bind_param('si', $dbCode, $keepId);
    $stmt->execute();
    $stmt->close();
}

function xander_fee_packages_sync_items(mysqli $conn, int $packageId, array $items, string $currency): void
{
    $res = $conn->query("SELECT id FROM fee_items WHERE package_id = {$packageId} ORDER BY id ASC");
    $itemIds = [];
    while ($row = $res->fetch_assoc()) {
        $itemIds[] = (int) $row['id'];
    }

    $upd = $conn->prepare(
        'UPDATE fee_items SET name = ?, amount = ?, currency = ?, payable_stage = ? WHERE id = ? AND package_id = ?'
    );
    $ins = $conn->prepare(
        'INSERT INTO fee_items (package_id, name, amount, currency, payable_stage) VALUES (?, ?, ?, ?, ?)'
    );

    foreach ($items as $i => $item) {
        $name = $item['name'];
        $amount = (float) $item['amount'];
        $stage = $item['payable_stage'];
        if (isset($itemIds[$i])) {
            $itemId = $itemIds[$i];
            $upd->bind_param('sdssii', $name, $amount, $currency, $stage, $itemId, $packageId);
            $upd->execute();
        } else {
            $ins->bind_param('isdss', $packageId, $name, $amount, $currency, $stage);
            $ins->execute();
        }
    }
    if ($upd) {
        $upd->close();
    }
    if ($ins) {
        $ins->close();
    }
    if (count($itemIds) > count($items)) {
        foreach (array_slice($itemIds, count($items)) as $extraId) {
            $conn->query("DELETE FROM fee_items WHERE id = {$extraId} AND package_id = {$packageId}");
        }
    }
}

function xander_fee_packages_needs_sync(mysqli $conn): bool
{
    $catalog = xander_contract_fee_catalog();
    $expected = count($catalog);
    if ($expected === 0) {
        return false;
    }

    $res = $conn->query("SELECT COUNT(*) AS c FROM fee_packages WHERE contract_code IS NOT NULL AND contract_code <> ''");
    if (!$res) {
        return true;
    }
    $count = (int) ($res->fetch_assoc()['c'] ?? 0);
    if ($count < $expected) {
        return true;
    }

    foreach ($catalog as $pkg) {
        $id = xander_fee_packages_find_id_by_code($conn, $pkg['contract_code']);
        if ($id === null) {
            return true;
        }

        $stmt = $conn->prepare(
            'SELECT title, total_amount FROM fee_packages WHERE id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return true;
        }

        if (abs((float) $row['total_amount'] - (float) $pkg['total']) > 0.009) {
            return true;
        }

        if (trim((string) $row['title']) !== trim($pkg['title'])) {
            return true;
        }

        $itemStmt = $conn->prepare(
            'SELECT name, amount, payable_stage FROM fee_items WHERE package_id = ? ORDER BY id ASC'
        );
        $itemStmt->bind_param('i', $id);
        $itemStmt->execute();
        $dbItems = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $itemStmt->close();

        if (count($dbItems) !== count($pkg['items'])) {
            return true;
        }

        foreach ($pkg['items'] as $i => $item) {
            if (!isset($dbItems[$i])) {
                return true;
            }
            if (trim((string) $dbItems[$i]['name']) !== trim((string) $item['name'])) {
                return true;
            }
            if (abs((float) $dbItems[$i]['amount'] - (float) $item['amount']) > 0.009) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Sync fee_packages + fee_items from contract catalog (idempotent).
 */
function xander_sync_fee_packages_from_catalog(mysqli $conn): bool
{
    static $synced = false;
    if ($synced) {
        return true;
    }

    if (!xander_fee_packages_needs_sync($conn)) {
        $synced = true;
        return true;
    }

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
                $packageId = xander_fee_packages_find_id_by_db_code($conn, $dbCode);
            }
            if ($packageId === null) {
                $slotId = $displayOrder;
                $slotCheck = $conn->query("SELECT id, contract_code FROM fee_packages WHERE id = {$slotId} LIMIT 1");
                if ($slotCheck && ($slotRow = $slotCheck->fetch_assoc()) && empty($slotRow['contract_code'])) {
                    $packageId = $slotId;
                }
            }

            if ($packageId !== null) {
                xander_fee_packages_resolve_code_conflict($conn, $dbCode, $packageId);
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
        }

        if ($activeCodes !== []) {
            $escaped = array_map(static fn(string $c): string => "'" . $conn->real_escape_string($c) . "'", $activeCodes);
            $conn->query(
                'UPDATE fee_packages SET contract_code = NULL WHERE contract_code IS NOT NULL AND contract_code NOT IN (' . implode(',', $escaped) . ')'
            );
        }

        $conn->commit();
        $synced = true;
        error_log('[fee_packages_sync] synced ' . count($catalog) . ' packages from catalog');
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('[fee_packages_sync] failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * After contract signing: sync catalog → fee_packages/fee_items, then assign package to application.
 */
function xander_assign_application_package_from_contract(
    mysqli $conn,
    int $applicationId,
    string $contractCode,
    string $sourceTable = 'student_applications'
): bool {
    if ($applicationId <= 0 || trim($contractCode) === '') {
        return false;
    }

    xander_sync_fee_packages_from_catalog($conn);

    $packageId = xander_fee_packages_find_id_by_code($conn, trim($contractCode));
    if ($packageId === null) {
        error_log('[fee_packages_sync] missing fee_packages row for ' . $contractCode);
        return false;
    }

    $check = $conn->query("SHOW TABLES LIKE 'application_packages'");
    if (!$check || $check->num_rows === 0) {
        return false;
    }

    $stmt = $conn->prepare(
        'DELETE FROM application_packages WHERE application_id = ? AND source_table = ?'
    );
    $stmt->bind_param('is', $applicationId, $sourceTable);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare(
        'INSERT INTO application_packages (application_id, source_table, package_id, assigned_at)
         VALUES (?, ?, ?, NOW())'
    );
    $stmt->bind_param('isi', $applicationId, $sourceTable, $packageId);
    $stmt->execute();
    $stmt->close();

    error_log('[fee_packages_sync] assigned package ' . $packageId . ' to application ' . $applicationId);
    return true;
}
