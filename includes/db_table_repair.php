<?php
declare(strict_types=1);

/**
 * Detect and repair corrupted InnoDB tables (error 1932 / missing engine).
 */
function xander_db_table_is_usable(mysqli $conn, string $table): bool
{
    $table = preg_replace('/[^a-z0-9_]/', '', $table);
    if ($table === '') {
        return false;
    }
    try {
        $conn->query("SELECT 1 FROM `{$table}` LIMIT 1");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/** @return list<string> */
function xander_db_inno_db_data_dirs(): array
{
    $dirs = [
        'C:/xampp/mysql/data',
        'C:/xampp7/mysql/data',
    ];
    $out = [];
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            $out[] = rtrim(str_replace('\\', '/', $dir), '/');
        }
    }
    return $out;
}

function xander_db_remove_orphan_inno_db_file(string $dbName, string $table, bool $allowDelete): bool
{
    if (!$allowDelete) {
        return false;
    }
    $dbName = preg_replace('/[^a-z0-9_]/', '', $dbName);
    $table = preg_replace('/[^a-z0-9_]/', '', $table);
    foreach (xander_db_inno_db_data_dirs() as $base) {
        $ibd = "{$base}/{$dbName}/{$table}.ibd";
        if (is_file($ibd) && @unlink($ibd)) {
            error_log("[db_table_repair] removed orphan tablespace {$ibd}");
            return true;
        }
    }
    return false;
}

function xander_db_drop_broken_table(mysqli $conn, string $table, bool $allowDeleteIbd): void
{
    $table = preg_replace('/[^a-z0-9_]/', '', $table);
    if ($table === '') {
        return;
    }
    try {
        $conn->query("DROP TABLE IF EXISTS `{$table}`");
    } catch (Throwable $e) {
        error_log("[db_table_repair] DROP {$table}: " . $e->getMessage());
    }

    $dbRow = $conn->query('SELECT DATABASE()');
    $dbName = $dbRow ? (string) ($dbRow->fetch_row()[0] ?? '') : '';
    if ($dbName !== '') {
        xander_db_remove_orphan_inno_db_file($dbName, $table, $allowDeleteIbd);
    }
}

function xander_db_admins_create_sql(): string
{
    return <<<'SQL'
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `first_name` VARCHAR(100) DEFAULT NULL,
  `last_name` VARCHAR(100) DEFAULT NULL,
  `full_name` VARCHAR(200) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `phone_number` VARCHAR(50) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'standard',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `position` VARCHAR(100) DEFAULT NULL,
  `employment_type` VARCHAR(50) DEFAULT NULL,
  `employment_start_date` DATE DEFAULT NULL,
  `national_id` VARCHAR(50) DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `marital_status` VARCHAR(30) DEFAULT NULL,
  `nationality` VARCHAR(80) DEFAULT NULL,
  `place_of_birth` VARCHAR(120) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `salary_per_minute` DECIMAL(10,4) DEFAULT NULL,
  `monthly_salary` DECIMAL(12,2) DEFAULT NULL,
  `salary_currency` VARCHAR(10) DEFAULT 'RWF',
  `allowed_break_minutes` INT DEFAULT NULL,
  `work_days_per_week` INT DEFAULT NULL,
  `sheet_id` VARCHAR(100) DEFAULT NULL,
  `sheet_link` VARCHAR(500) DEFAULT NULL,
  `office_id` INT UNSIGNED DEFAULT NULL,
  `profile_photo` VARCHAR(255) DEFAULT NULL,
  `password_reset_token` VARCHAR(64) DEFAULT NULL,
  `password_reset_expires` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admins_username` (`username`),
  KEY `idx_admins_email` (`email`),
  KEY `idx_admins_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
}

function xander_db_repair_admins_table(mysqli $conn, bool $allowDeleteIbd = false): bool
{
    if (xander_db_table_is_usable($conn, 'admins')) {
        return true;
    }

    xander_db_drop_broken_table($conn, 'admins', $allowDeleteIbd);

    try {
        if (!$conn->query(xander_db_admins_create_sql())) {
            error_log('[db_table_repair] admins CREATE failed: ' . $conn->error);
            return false;
        }
        error_log('[db_table_repair] admins table recreated');
        return xander_db_table_is_usable($conn, 'admins');
    } catch (Throwable $e) {
        error_log('[db_table_repair] admins repair failed: ' . $e->getMessage());
        return false;
    }
}

function xander_db_repair_prescreening_tables(mysqli $conn, bool $allowDeleteIbd = false): void
{
    $tables = [
        'prescreening_submissions',
        'prescreening_invites',
        'whatsapp_prescreening_sessions',
        'whatsapp_inbound_dedup',
    ];
    $broken = [];
    foreach ($tables as $table) {
        if (!xander_db_table_is_usable($conn, $table)) {
            $broken[] = $table;
        }
    }
    if ($broken === []) {
        return;
    }
    foreach ($broken as $table) {
        xander_db_drop_broken_table($conn, $table, $allowDeleteIbd);
        error_log("[db_table_repair] dropped broken prescreening table {$table}");
    }
}

function xander_db_repair_contract_tables(mysqli $conn, bool $allowDeleteIbd = false): void
{
    $tables = [
        'student_signatures',
        'student_signatures_special',
        'student_signatures_burundi',
    ];
    foreach ($tables as $table) {
        if (!xander_db_table_is_usable($conn, $table)) {
            xander_db_drop_broken_table($conn, $table, $allowDeleteIbd);
            error_log("[db_table_repair] dropped broken contract table {$table}");
        }
    }
}

function xander_db_repair_critical_tables(mysqli $conn, bool $isLocal = false): void
{
    xander_db_repair_admins_table($conn, $isLocal);
    xander_db_repair_prescreening_tables($conn, $isLocal);
    if ($isLocal) {
        xander_db_repair_contract_tables($conn, true);

        require_once __DIR__ . '/db_full_repair.php';
        try {
            xander_db_repair_all_broken_tables($conn, true);
        } catch (Throwable $e) {
            error_log('[db_table_repair] full repair: ' . $e->getMessage());
        }
    }
}
