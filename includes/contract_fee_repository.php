<?php
declare(strict_types=1);

require_once __DIR__ . '/contract_fee_catalog.php';

/**
 * @return array<string, array{title:string, lines:string[], total:string, label?:string, package_id?:int}>
 */
function xander_get_all_contract_packages(?mysqli $conn = null): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $conn = $conn ?? ($GLOBALS['conn'] ?? null);
    if ($conn instanceof mysqli) {
        $fromDb = xander_load_contract_packages_from_db($conn);
        if ($fromDb !== []) {
            $cache = $fromDb;
            return $cache;
        }
    }

    $cache = xander_packages_from_catalog();
    return $cache;
}

/**
 * @return array<string, array{title:string, lines:string[], total:string}>
 */
function xander_packages_from_catalog(): array
{
    $out = [];
    foreach (xander_contract_fee_catalog() as $pkg) {
        $code = $pkg['contract_code'];
        $out[$code] = [
            'title'       => $pkg['label'],
            'lines'       => $pkg['lines'],
            'total'       => $pkg['total_fmt'],
            'label'       => $pkg['label'],
            'package_id'  => $pkg['package_id'],
        ];
    }
    return $out;
}

/**
 * @return array<string, array{title:string, lines:string[], total:string}>
 */
function xander_load_contract_packages_from_db(mysqli $conn): array
{
    $cols = $conn->query("SHOW COLUMNS FROM fee_packages LIKE 'contract_code'");
    if (!$cols || $cols->num_rows === 0) {
        return [];
    }

    $sql = "
        SELECT fp.id, fp.contract_code, fp.title, fp.total_amount, fp.currency,
               fi.id AS item_id, fi.name AS item_name, fi.amount AS item_amount, fi.payable_stage
        FROM fee_packages fp
        LEFT JOIN fee_items fi ON fi.package_id = fp.id
        WHERE fp.contract_code IS NOT NULL AND fp.contract_code <> ''
        ORDER BY fp.display_order ASC, fp.id ASC, fi.id ASC
    ";
    $res = $conn->query($sql);
    if (!$res) {
        return [];
    }

    $sym = '€';
    $byCode = [];
    while ($row = $res->fetch_assoc()) {
        $code = (string) $row['contract_code'];
        if (!isset($byCode[$code])) {
            $total = (float) $row['total_amount'];
            $totalFmt = $sym . number_format($total, 0, '.', ',');
            $title = trim((string) $row['title']);
            $byCode[$code] = [
                'package_id' => (int) $row['id'],
                'title'      => $title,
                'total'      => $totalFmt,
                'lines'      => [],
                'label'      => $title . ' – ' . $totalFmt,
            ];
        }
        if ($row['item_name'] !== null && $row['item_name'] !== '') {
            $amt = number_format((float) $row['item_amount'], 0, '.', ',');
            $byCode[$code]['lines'][] = $sym . $amt . ' – ' . trim((string) $row['item_name']);
        }
    }

    foreach ($byCode as $code => &$pkg) {
        $catalog = xander_contract_fee_catalog_by_code();
        if (isset($catalog[$code])) {
            $pkg['label'] = $catalog[$code]['label'];
            $pkg['title'] = $catalog[$code]['label'];
        }
    }
    unset($pkg);

    return $byCode;
}

/** @return array<string, array> */
function xander_contract_fee_catalog_by_code(): array
{
    static $map = null;
    if ($map === null) {
        $map = [];
        foreach (xander_contract_fee_catalog() as $pkg) {
            $map[$pkg['contract_code']] = $pkg;
        }
    }
    return $map;
}

/** @return list<array> */
function xander_contract_fee_catalog_list(): array
{
    return xander_contract_fee_catalog();
}

function getPackageDetails(string $code): array
{
    $all = xander_get_all_contract_packages();
    return $all[$code] ?? [];
}
