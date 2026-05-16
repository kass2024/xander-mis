<?php
declare(strict_types=1);

/**
 * MySQLi compat helpers for hosts without mysqlnd.
 * - Avoids mysqli_stmt::get_result() which may be unavailable.
 */

function pcvc_stmt_fetch_assoc(mysqli_stmt $stmt): ?array
{
    if (method_exists($stmt, 'get_result')) {
        $res = @$stmt->get_result();
        if ($res instanceof mysqli_result) {
            $row = $res->fetch_assoc();
            $res->free();
            return $row ?: null;
        }
    }

    $meta = $stmt->result_metadata();
    if (!$meta) return null;

    $row = [];
    $bind = [];
    while ($field = $meta->fetch_field()) {
        $row[$field->name] = null;
        $bind[] = &$row[$field->name];
    }
    $meta->free();

    if (empty($bind)) return null;
    call_user_func_array([$stmt, 'bind_result'], $bind);

    if (!$stmt->fetch()) return null;

    $out = [];
    foreach ($row as $k => $v) $out[$k] = $v;
    return $out;
}

function pcvc_stmt_fetch_all_assoc(mysqli_stmt $stmt): array
{
    if (method_exists($stmt, 'get_result')) {
        $res = @$stmt->get_result();
        if ($res instanceof mysqli_result) {
            $rows = $res->fetch_all(MYSQLI_ASSOC);
            $res->free();
            return is_array($rows) ? $rows : [];
        }
    }

    $meta = $stmt->result_metadata();
    if (!$meta) return [];

    $row = [];
    $bind = [];
    while ($field = $meta->fetch_field()) {
        $row[$field->name] = null;
        $bind[] = &$row[$field->name];
    }
    $meta->free();

    if (empty($bind)) return [];
    call_user_func_array([$stmt, 'bind_result'], $bind);

    $rows = [];
    while ($stmt->fetch()) {
        $out = [];
        foreach ($row as $k => $v) $out[$k] = $v;
        $rows[] = $out;
    }
    return $rows;
}

