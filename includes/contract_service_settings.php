<?php
declare(strict_types=1);

require_once __DIR__ . '/contract_fee_catalog.php';
require_once __DIR__ . '/db_schema_align.php';

function xander_contract_service_sections(): array
{
    return [
        'study'  => 'Study Services',
        'credit' => 'Credit Transfer Services',
        'visit'  => 'Visit Visa Services',
        'job'    => 'Job Seeker Services',
    ];
}

function xander_contract_services_ensure(mysqli $conn): void
{
    static $done = false;
    if ($done || !xander_db_table_exists($conn, 'fee_packages')) {
        return;
    }
    $done = true;

    xander_db_add_column_if_missing($conn, 'fee_packages', 'service_section', "VARCHAR(16) NULL DEFAULT NULL");
    xander_db_add_column_if_missing($conn, 'fee_packages', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1');
    if (xander_db_table_exists($conn, 'fee_items')) {
        xander_db_add_column_if_missing($conn, 'fee_items', 'note', 'VARCHAR(255) NULL DEFAULT NULL');
    }

    $catalog = xander_contract_fee_catalog();
    $missingSection = $conn->query("SELECT 1 FROM fee_packages WHERE contract_code IS NOT NULL AND contract_code <> '' AND (service_section IS NULL OR service_section = '') LIMIT 1");
    if ($missingSection && $missingSection->num_rows > 0) {
        foreach ($catalog as $pkg) {
            $code = $pkg['contract_code'];
            $section = $pkg['section'];
            $stmt = $conn->prepare(
                "UPDATE fee_packages
                 SET service_section = ?
                 WHERE contract_code = ? AND (service_section IS NULL OR service_section = '')"
            );
            if ($stmt) {
                $stmt->bind_param('ss', $section, $code);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    xander_contract_jobs_apply_revision($conn);

    if (!xander_db_column_exists($conn, 'fee_items', 'note')) {
        return;
    }
    $missingNote = $conn->query('SELECT 1 FROM fee_items WHERE note IS NULL LIMIT 1');
    if (!$missingNote || $missingNote->num_rows === 0) {
        return;
    }

    foreach ($catalog as $pkg) {
        $find = $conn->prepare('SELECT id FROM fee_packages WHERE contract_code = ? LIMIT 1');
        if (!$find) {
            continue;
        }
        $code = $pkg['contract_code'];
        $find->bind_param('s', $code);
        $find->execute();
        $row = $find->get_result()->fetch_assoc();
        $find->close();
        if (!$row) {
            continue;
        }
        $packageId = (int) $row['id'];
        $items = $conn->query('SELECT id, note FROM fee_items WHERE package_id = ' . $packageId . ' ORDER BY id ASC');
        if (!$items) {
            continue;
        }
        $ids = [];
        while ($item = $items->fetch_assoc()) {
            $ids[] = $item;
        }
        foreach ($pkg['items'] as $i => $catalogItem) {
            if (!isset($ids[$i])) {
                break;
            }
            if (trim((string) ($ids[$i]['note'] ?? '')) !== '') {
                continue;
            }
            $note = trim((string) ($catalogItem['note'] ?? ''));
            if ($note === '') {
                continue;
            }
            $itemId = (int) $ids[$i]['id'];
            $upd = $conn->prepare('UPDATE fee_items SET note = ? WHERE id = ? AND (note IS NULL OR note = \'\')');
            if ($upd) {
                $upd->bind_param('si', $note, $itemId);
                $upd->execute();
                $upd->close();
            }
        }
    }
    $conn->query("UPDATE fee_items SET note = '' WHERE note IS NULL");
}

function xander_contract_jobs_revision(): string
{
    return '2026-09-18';
}

/**
 * Replace job-service rows with the 18 Sept 2026 fee schedule.
 * Study, credit, and visit prices are left as saved in settings.
 */
function xander_contract_jobs_apply_revision(mysqli $conn): void
{
    static $applied = false;
    if ($applied || !xander_db_table_exists($conn, 'fee_packages') || !xander_db_table_exists($conn, 'fee_items')) {
        return;
    }
    $applied = true;

    xander_db_add_column_if_missing($conn, 'fee_packages', 'catalog_revision', 'VARCHAR(32) NULL DEFAULT NULL');
    if (!xander_db_column_exists($conn, 'fee_packages', 'catalog_revision')) {
        return;
    }

    $revision = xander_contract_jobs_revision();
    $jobs = [];
    foreach (xander_contract_fee_catalog() as $pkg) {
        if (($pkg['section'] ?? '') === 'job') {
            $jobs[] = $pkg;
        }
    }
    if ($jobs === []) {
        return;
    }

    $needs = false;
    $find = $conn->prepare('SELECT id, catalog_revision FROM fee_packages WHERE contract_code = ? LIMIT 1');
    if (!$find) {
        return;
    }
    foreach ($jobs as $pkg) {
        $code = $pkg['contract_code'];
        $find->bind_param('s', $code);
        $find->execute();
        $row = $find->get_result()->fetch_assoc();
        if (!$row || (string) ($row['catalog_revision'] ?? '') !== $revision) {
            $needs = true;
            break;
        }
    }
    $find->close();
    if (!$needs) {
        return;
    }

    $conn->begin_transaction();
    try {
        $lookup = $conn->prepare('SELECT id FROM fee_packages WHERE contract_code = ? LIMIT 1');
        $update = $conn->prepare(
            'UPDATE fee_packages
             SET title = ?, currency = ?, total_amount = ?, total_expected = ?, display_order = ?, service_section = ?, is_active = 1, catalog_revision = ?, code = ?
             WHERE id = ?'
        );
        $insert = $conn->prepare(
            'INSERT INTO fee_packages (code, title, currency, total_amount, total_expected, contract_code, display_order, service_section, is_active, catalog_revision)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)'
        );
        $wipe = $conn->prepare('DELETE FROM fee_items WHERE package_id = ?');
        $item = $conn->prepare(
            'INSERT INTO fee_items (package_id, name, amount, currency, payable_stage, note) VALUES (?, ?, ?, ?, ?, ?)'
        );
        if (!$lookup || !$update || !$insert || !$wipe || !$item) {
            throw new RuntimeException('Could not prepare the job fee update.');
        }

        foreach ($jobs as $pkg) {
            $code = (string) $pkg['contract_code'];
            $title = (string) $pkg['title'];
            $currency = (string) $pkg['currency'];
            $total = (float) $pkg['total'];
            $order = (int) $pkg['package_id'];
            $section = 'job';
            $dbCode = (string) $pkg['db_code'];

            $lookup->bind_param('s', $code);
            $lookup->execute();
            $existing = $lookup->get_result()->fetch_assoc();
            if ($existing) {
                $packageId = (int) $existing['id'];
                $update->bind_param('ssddisssi', $title, $currency, $total, $total, $order, $section, $revision, $dbCode, $packageId);
                $update->execute();
            } else {
                $insert->bind_param('sssddsiss', $dbCode, $title, $currency, $total, $total, $code, $order, $section, $revision);
                $insert->execute();
                $packageId = (int) $insert->insert_id;
            }

            $wipe->bind_param('i', $packageId);
            $wipe->execute();
            foreach ($pkg['items'] as $line) {
                $name = (string) $line['name'];
                $amount = (float) $line['amount'];
                $stage = (string) ($line['payable_stage'] ?? 'Installment');
                $note = (string) ($line['note'] ?? '');
                $item->bind_param('isdsss', $packageId, $name, $amount, $currency, $stage, $note);
                $item->execute();
            }
        }

        $lookup->close();
        $update->close();
        $insert->close();
        $wipe->close();
        $item->close();
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('[contract_jobs] ' . $e->getMessage());
    }
}

function xander_contract_services_money(float $amount, string $currency): string
{
    $currency = strtoupper(trim($currency));
    $decimals = abs($amount - round($amount)) < 0.001 ? 0 : 2;
    $num = number_format($amount, $decimals, '.', ',');

    return match ($currency) {
        'EUR' => '€' . $num,
        'USD' => '$' . $num,
        'GBP' => '£' . $num,
        'CAD' => 'CA$' . $num,
        default => ($currency !== '' ? $currency . ' ' : '') . $num,
    };
}

/**
 * Packages for the student contract service list.
 * Active rows come from settings. A signed selection stays visible.
 *
 * @return list<array<string, mixed>>
 */
function xander_contract_services_catalog(mysqli $conn, string $includeCode = ''): array
{
    xander_contract_services_ensure($conn);
    if (!xander_db_table_exists($conn, 'fee_packages')) {
        return xander_contract_fee_catalog();
    }

    $sql = "
        SELECT fp.id, fp.contract_code, fp.title, fp.total_amount, fp.currency, fp.service_section, fp.is_active, fp.display_order,
               fi.name AS item_name, fi.amount AS item_amount, fi.note AS item_note, fi.payable_stage
        FROM fee_packages fp
        LEFT JOIN fee_items fi ON fi.package_id = fp.id
        WHERE fp.contract_code IS NOT NULL AND fp.contract_code <> ''
          AND (fp.is_active = 1 OR fp.contract_code = ?)
        ORDER BY fp.display_order ASC, fp.id ASC, fi.id ASC
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return xander_contract_fee_catalog();
    }
    $stmt->bind_param('s', $includeCode);
    $stmt->execute();
    $res = $stmt->get_result();

    $byCode = [];
    while ($row = $res->fetch_assoc()) {
        $code = (string) $row['contract_code'];
        if (!isset($byCode[$code])) {
            $currency = strtoupper((string) ($row['currency'] ?: 'EUR'));
            $total = (float) $row['total_amount'];
            $totalFmt = xander_contract_services_money($total, $currency);
            $title = trim((string) $row['title']);
            $section = (string) ($row['service_section'] ?: 'study');
            if (!isset(xander_contract_service_sections()[$section])) {
                $section = 'study';
            }
            $byCode[$code] = [
                'contract_code' => $code,
                'section'       => $section,
                'title'         => $title,
                'currency'      => $currency,
                'total'         => $total,
                'total_fmt'     => $totalFmt,
                'label'         => $title . ' – ' . $totalFmt,
                'lines'         => [],
                'items'         => [],
                'is_active'     => (int) $row['is_active'] === 1,
            ];
        }
        $name = trim((string) ($row['item_name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $currency = $byCode[$code]['currency'];
        $amount = (float) $row['item_amount'];
        $note = trim((string) ($row['item_note'] ?? ''));
        $line = xander_contract_services_money($amount, $currency) . ' – ' . $name;
        if ($note !== '') {
            $line .= ' (' . $note . ')';
        }
        $byCode[$code]['lines'][] = $line;
        $byCode[$code]['items'][] = [
            'name' => $name,
            'amount' => $amount,
            'note' => $note,
            'payable_stage' => (string) ($row['payable_stage'] ?? ''),
        ];
    }
    $stmt->close();

    if ($byCode === []) {
        return xander_contract_fee_catalog();
    }

    foreach (xander_contract_fee_catalog() as $source) {
        $code = (string) ($source['contract_code'] ?? '');
        $profile = $source['profile'] ?? [];
        if ($code !== '' && isset($byCode[$code]) && is_array($profile) && $profile !== []) {
            $byCode[$code]['profile'] = $profile;
        }
    }

    return array_values($byCode);
}

/** @return list<array<string, mixed>> */
function xander_contract_services_admin_list(mysqli $conn): array
{
    xander_contract_services_ensure($conn);
    $sql = "
        SELECT fp.id, fp.contract_code, fp.title, fp.currency, fp.total_amount, fp.service_section, fp.is_active, fp.display_order,
               fi.id AS item_id, fi.name AS item_name, fi.amount AS item_amount, fi.note AS item_note
        FROM fee_packages fp
        LEFT JOIN fee_items fi ON fi.package_id = fp.id
        WHERE fp.contract_code IS NOT NULL AND fp.contract_code <> ''
        ORDER BY fp.display_order ASC, fp.id ASC, fi.id ASC
    ";
    $res = $conn->query($sql);
    if (!$res) {
        return [];
    }
    $byId = [];
    while ($row = $res->fetch_assoc()) {
        $id = (int) $row['id'];
        if (!isset($byId[$id])) {
            $byId[$id] = [
                'id' => $id,
                'contract_code' => (string) $row['contract_code'],
                'title' => (string) $row['title'],
                'currency' => strtoupper((string) ($row['currency'] ?: 'EUR')),
                'total_amount' => (float) $row['total_amount'],
                'service_section' => (string) ($row['service_section'] ?: 'study'),
                'is_active' => (int) $row['is_active'] === 1,
                'items' => [],
            ];
        }
        if ($row['item_id'] !== null) {
            $byId[$id]['items'][] = [
                'id' => (int) $row['item_id'],
                'name' => (string) $row['item_name'],
                'amount' => (float) $row['item_amount'],
                'note' => (string) ($row['item_note'] ?? ''),
            ];
        }
    }

    return array_values($byId);
}

/**
 * @param list<array{name:string, amount:float, note:string}> $items
 * @return array{ok:bool, error?:string}
 */
function xander_contract_services_save(
    mysqli $conn,
    int $packageId,
    string $title,
    string $section,
    string $currency,
    bool $active,
    array $items
): array {
    xander_contract_services_ensure($conn);
    $title = trim($title);
    $currency = strtoupper(trim($currency));
    if ($packageId <= 0) {
        return ['ok' => false, 'error' => 'Service not found.'];
    }
    if ($title === '') {
        return ['ok' => false, 'error' => 'Service name is required.'];
    }
    if (!isset(xander_contract_service_sections()[$section])) {
        return ['ok' => false, 'error' => 'Choose a service group.'];
    }
    require_once dirname(__DIR__) . '/helpers/currencies.php';
    if (!xander_is_valid_currency($currency)) {
        return ['ok' => false, 'error' => 'Choose a valid currency.'];
    }

    $clean = [];
    foreach ($items as $item) {
        $name = trim((string) ($item['name'] ?? ''));
        $note = trim((string) ($item['note'] ?? ''));
        $amount = $item['amount'] ?? null;
        if ($name === '' && ($amount === null || $amount === '')) {
            continue;
        }
        if ($name === '' || !is_numeric($amount) || (float) $amount < 0) {
            return ['ok' => false, 'error' => 'Each price line needs a name and an amount of zero or more.'];
        }
        $clean[] = [
            'name' => mb_substr($name, 0, 255),
            'amount' => round((float) $amount, 2),
            'note' => mb_substr($note, 0, 255),
        ];
    }
    if ($clean === []) {
        return ['ok' => false, 'error' => 'Add at least one price.'];
    }

    $total = 0.0;
    foreach ($clean as $item) {
        $total += $item['amount'];
    }
    $total = round($total, 2);
    if ($total <= 0) {
        return ['ok' => false, 'error' => 'The service total must be greater than zero.'];
    }
    $activeFlag = $active ? 1 : 0;

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            'UPDATE fee_packages
             SET title = ?, currency = ?, total_amount = ?, total_expected = ?, service_section = ?, is_active = ?
             WHERE id = ? AND contract_code IS NOT NULL'
        );
        if (!$stmt) {
            throw new RuntimeException('Could not save the service.');
        }
        $stmt->bind_param('ssddsii', $title, $currency, $total, $total, $section, $activeFlag, $packageId);
        $stmt->execute();
        if ($stmt->affected_rows < 0) {
            $stmt->close();
            throw new RuntimeException('Could not save the service.');
        }
        $stmt->close();

        $existing = [];
        $res = $conn->query('SELECT id FROM fee_items WHERE package_id = ' . $packageId . ' ORDER BY id ASC');
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $existing[] = (int) $row['id'];
            }
        }
        $upd = $conn->prepare('UPDATE fee_items SET name = ?, amount = ?, currency = ?, note = ? WHERE id = ? AND package_id = ?');
        $ins = $conn->prepare('INSERT INTO fee_items (package_id, name, amount, currency, payable_stage, note) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($clean as $i => $item) {
            $stage = 'Custom';
            if (isset($existing[$i])) {
                $itemId = $existing[$i];
                $upd->bind_param('sdssii', $item['name'], $item['amount'], $currency, $item['note'], $itemId, $packageId);
                $upd->execute();
            } else {
                $ins->bind_param('isdsss', $packageId, $item['name'], $item['amount'], $currency, $stage, $item['note']);
                $ins->execute();
            }
        }
        if ($upd) {
            $upd->close();
        }
        if ($ins) {
            $ins->close();
        }
        if (count($existing) > count($clean)) {
            foreach (array_slice($existing, count($clean)) as $extraId) {
                $conn->query('DELETE FROM fee_items WHERE id = ' . (int) $extraId . ' AND package_id = ' . $packageId);
            }
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        return ['ok' => false, 'error' => 'Could not save the service.'];
    }

    return ['ok' => true];
}

/**
 * @param list<array{name:string, amount:float, note:string}> $items
 * @return array{ok:bool, error?:string, id?:int}
 */
function xander_contract_services_create(
    mysqli $conn,
    string $title,
    string $section,
    string $currency,
    array $items
): array {
    xander_contract_services_ensure($conn);
    $title = trim($title);
    $currency = strtoupper(trim($currency));
    if ($title === '' || !isset(xander_contract_service_sections()[$section])) {
        return ['ok' => false, 'error' => 'Service name and group are required.'];
    }
    require_once dirname(__DIR__) . '/helpers/currencies.php';
    if (!xander_is_valid_currency($currency)) {
        return ['ok' => false, 'error' => 'Choose a valid currency.'];
    }

    $clean = [];
    foreach ($items as $item) {
        $name = trim((string) ($item['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        if (!is_numeric($item['amount'] ?? null) || (float) $item['amount'] < 0) {
            return ['ok' => false, 'error' => 'Each price line needs an amount of zero or more.'];
        }
        $clean[] = [
            'name' => mb_substr($name, 0, 255),
            'amount' => round((float) $item['amount'], 2),
            'note' => mb_substr(trim((string) ($item['note'] ?? '')), 0, 255),
        ];
    }
    if ($clean === []) {
        return ['ok' => false, 'error' => 'Add at least one price.'];
    }

    $max = $conn->query("SELECT contract_code FROM fee_packages WHERE contract_code REGEXP '^p[0-9]+$'");
    $next = 600;
    if ($max) {
        while ($row = $max->fetch_assoc()) {
            $n = (int) substr((string) $row['contract_code'], 1);
            if ($n >= $next) {
                $next = $n + 1;
            }
        }
    }
    $contractCode = 'p' . $next;
    $dbCode = 'SVC-' . $contractCode;
    $total = 0.0;
    foreach ($clean as $item) {
        $total += $item['amount'];
    }
    $total = round($total, 2);
    if ($total <= 0) {
        return ['ok' => false, 'error' => 'The service total must be greater than zero.'];
    }
    $order = $next;

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            'INSERT INTO fee_packages (code, title, currency, total_amount, total_expected, contract_code, display_order, service_section, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)'
        );
        if (!$stmt) {
            throw new RuntimeException('insert failed');
        }
        $stmt->bind_param('sssddsis', $dbCode, $title, $currency, $total, $total, $contractCode, $order, $section);
        $stmt->execute();
        $packageId = (int) $stmt->insert_id;
        $stmt->close();
        $ins = $conn->prepare('INSERT INTO fee_items (package_id, name, amount, currency, payable_stage, note) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($clean as $item) {
            $stage = 'Custom';
            $ins->bind_param('isdsss', $packageId, $item['name'], $item['amount'], $currency, $stage, $item['note']);
            $ins->execute();
        }
        $ins->close();
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        return ['ok' => false, 'error' => 'Could not add the service.'];
    }

    return ['ok' => true, 'id' => $packageId];
}
