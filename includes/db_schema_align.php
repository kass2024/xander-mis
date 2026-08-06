<?php
declare(strict_types=1);

/**
 * Idempotent column fixes after local table repair (.frm recovery may miss migrated columns).
 */
function xander_db_column_exists(mysqli $conn, string $table, string $column): bool
{
    $table = preg_replace('/[^a-z0-9_]/', '', $table);
    $column = preg_replace('/[^a-z0-9_]/', '', $column);
    if ($table === '' || $column === '') {
        return false;
    }

    $dbRow = $conn->query('SELECT DATABASE() AS db');
    $dbName = $dbRow ? (string) ($dbRow->fetch_assoc()['db'] ?? '') : '';
    if ($dbName === '') {
        return false;
    }

    $stmt = $conn->prepare(
        'SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('sss', $dbName, $table, $column);
    $stmt->execute();
    $ok = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();

    return $ok;
}

function xander_db_table_exists(mysqli $conn, string $table): bool
{
    $table = preg_replace('/[^a-z0-9_]/', '', $table);
    if ($table === '') {
        return false;
    }
    $res = $conn->query("SHOW TABLES LIKE '{$table}'");

    return $res && $res->num_rows > 0;
}

function xander_db_add_column_if_missing(mysqli $conn, string $table, string $column, string $ddl): void
{
    if (!xander_db_table_exists($conn, $table) || xander_db_column_exists($conn, $table, $column)) {
        return;
    }
    $table = preg_replace('/[^a-z0-9_]/', '', $table);
    $column = preg_replace('/[^a-z0-9_]/', '', $column);
    $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$ddl}";
    if (!$conn->query($sql)) {
        error_log("[db_schema_align] {$table}.{$column}: " . $conn->error);
    }
}

function xander_db_align_core_schema(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (!xander_db_table_exists($conn, 'universities')) {
        return;
    }

    xander_db_add_column_if_missing($conn, 'universities', 'region_id', 'INT UNSIGNED NULL DEFAULT NULL AFTER country_id');

    if (!xander_db_table_exists($conn, 'platforms')) {
        return;
    }

    xander_db_add_column_if_missing($conn, 'platforms', 'serial_no', 'VARCHAR(50) NULL DEFAULT NULL');
    xander_db_add_column_if_missing($conn, 'platforms', 'platform_name', 'VARCHAR(255) NULL DEFAULT NULL');
    xander_db_add_column_if_missing($conn, 'platforms', 'username', 'VARCHAR(191) NULL DEFAULT NULL');
    xander_db_add_column_if_missing($conn, 'platforms', 'password', 'VARCHAR(255) NULL DEFAULT NULL');
    xander_db_add_column_if_missing($conn, 'platforms', 'person_in_charge', 'VARCHAR(191) NULL DEFAULT NULL');
    xander_db_add_column_if_missing($conn, 'platforms', 'status', "VARCHAR(50) NULL DEFAULT 'Active'");
    xander_db_add_column_if_missing($conn, 'platforms', 'platform_link', 'VARCHAR(500) NULL DEFAULT NULL');
    xander_db_add_column_if_missing($conn, 'platforms', 'created_at', 'DATETIME NULL DEFAULT CURRENT_TIMESTAMP');

    xander_db_add_column_if_missing($conn, 'platforms', 'created_at', 'DATETIME NULL DEFAULT CURRENT_TIMESTAMP');

    if (xander_db_column_exists($conn, 'platforms', 'name') && xander_db_column_exists($conn, 'platforms', 'platform_name')) {
        $conn->query(
            "UPDATE platforms SET platform_name = name WHERE (platform_name IS NULL OR platform_name = '') AND name IS NOT NULL AND name != ''"
        );
    }

    require_once __DIR__ . '/contract_tables_schema.php';
    try {
        xander_ensure_student_contract_tables($conn);
    } catch (Throwable $e) {
        error_log('[db_schema_align] contract tables: ' . $e->getMessage());
    }
}
