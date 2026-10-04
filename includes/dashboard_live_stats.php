<?php
declare(strict_types=1);

function xander_dashboard_application_tables(mysqli $conn): array
{
    $res = $conn->query('SHOW TABLES');
    if (!$res) {
        return ['student_applications'];
    }
    $tables = [];
    while ($row = $res->fetch_row()) {
        $name = (string) ($row[0] ?? '');
        if (preg_match('/^[A-Za-z0-9_]+applications$/', $name)) {
            $tables[] = $name;
        }
    }
    $res->free();

    return $tables === [] ? ['student_applications'] : $tables;
}

function xander_dashboard_columns(mysqli $conn, string $table): array
{
    $table = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    $res = $conn->query("SHOW COLUMNS FROM `{$table}`");
    $cols = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cols[strtolower((string) $row['Field'])] = true;
        }
        $res->free();
    }

    return $cols;
}

/** @return array{total:int, flags:array<string, int>} */
function xander_dashboard_stats(mysqli $conn): array
{
    $flags = ['incomplete_app', 'submitted', 'admit', 'i20_sent', 'sevis_paid', 'visa_scheduled', 'visa_approved', 'enrolled', 'addn_doc', 'deny', 'app_start'];
    $total = 0;
    $counts = array_fill_keys($flags, 0);

    foreach (xander_dashboard_application_tables($conn) as $table) {
        $safe = preg_replace('/[^A-Za-z0-9_]/', '', $table);
        $columns = xander_dashboard_columns($conn, $safe);
        if (!isset($columns['email'])) {
            continue;
        }
        $res = $conn->query("SELECT COUNT(*) AS c FROM `{$safe}` WHERE email IS NOT NULL AND email <> ''");
        if ($res) {
            $total += (int) (($res->fetch_assoc()['c'] ?? 0));
            $res->free();
        }
        $present = array_values(array_filter($flags, static fn (string $flag): bool => isset($columns[$flag])));
        if ($present === []) {
            continue;
        }
        $select = implode(', ', array_map(static fn (string $flag): string => "COALESCE(SUM(`{$flag}`),0) AS `{$flag}`", $present));
        $sum = $conn->query("SELECT {$select} FROM `{$safe}`");
        if (!$sum) {
            continue;
        }
        $row = $sum->fetch_assoc() ?: [];
        $sum->free();
        foreach ($present as $flag) {
            $counts[$flag] += (int) ($row[$flag] ?? 0);
        }
    }

    return ['total' => $total, 'flags' => $counts];
}
