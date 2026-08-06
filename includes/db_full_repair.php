<?php
declare(strict_types=1);

require_once __DIR__ . '/db_table_repair.php';
require_once __DIR__ . '/db_frm_recovery.php';

/**
 * Known CREATE TABLE statements for tables without recoverable .frm or where we want exact DDL.
 *
 * @return array<string, string>
 */
function xander_db_known_table_schemas(): array
{
    return [
        'admin_menu_permissions' => <<<'SQL'
CREATE TABLE IF NOT EXISTS `admin_menu_permissions` (
  `admin_id` INT UNSIGNED NOT NULL,
  `permissions` JSON NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` INT UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        'application_study_choices' => <<<'SQL'
CREATE TABLE IF NOT EXISTS `application_study_choices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` INT UNSIGNED NOT NULL,
  `region_id` INT UNSIGNED NOT NULL,
  `university_id` INT UNSIGNED NOT NULL,
  `program_level_id` INT UNSIGNED NOT NULL,
  `program_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_app_study_application` (`application_id`),
  KEY `idx_app_study_region` (`region_id`),
  KEY `idx_app_study_university` (`university_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        'countries' => <<<'SQL'
CREATE TABLE IF NOT EXISTS `countries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(8) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_countries_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        'regions' => <<<'SQL'
CREATE TABLE IF NOT EXISTS `regions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `country_id` INT UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_regions_country` (`country_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        'universities' => <<<'SQL'
CREATE TABLE IF NOT EXISTS `universities` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `region_id` INT UNSIGNED NULL DEFAULT NULL,
  `country_id` INT UNSIGNED NULL DEFAULT NULL,
  `website` VARCHAR(500) NULL DEFAULT NULL,
  `city` VARCHAR(191) NULL DEFAULT NULL,
  `institution_phone` VARCHAR(64) NULL DEFAULT NULL,
  `institution_kind` VARCHAR(64) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_universities_country` (`country_id`),
  KEY `idx_universities_region` (`region_id`),
  KEY `idx_universities_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        'platforms' => <<<'SQL'
CREATE TABLE IF NOT EXISTS `platforms` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `serial_no` VARCHAR(50) NULL DEFAULT NULL,
  `platform_name` VARCHAR(255) NOT NULL,
  `username` VARCHAR(191) NULL DEFAULT NULL,
  `password` VARCHAR(255) NULL DEFAULT NULL,
  `person_in_charge` VARCHAR(191) NULL DEFAULT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Active',
  `platform_link` VARCHAR(500) NULL DEFAULT NULL,
  `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_platforms_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        'offices' => <<<'SQL'
CREATE TABLE IF NOT EXISTS `offices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `country` VARCHAR(120) NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        'payments' => <<<'SQL'
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_application_id` INT UNSIGNED NULL DEFAULT NULL,
  `email` VARCHAR(190) NULL DEFAULT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(10) NULL DEFAULT 'USD',
  `status` VARCHAR(50) NULL DEFAULT 'pending',
  `payment_method` VARCHAR(50) NULL DEFAULT NULL,
  `reference` VARCHAR(191) NULL DEFAULT NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_application` (`student_application_id`),
  KEY `idx_payments_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        'notifications' => <<<'SQL'
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NULL DEFAULT NULL,
  `title` VARCHAR(255) NULL DEFAULT NULL,
  `message` TEXT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        'student_contracts' => <<<'SQL'
CREATE TABLE IF NOT EXISTS `student_contracts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) DEFAULT NULL,
  `contract_token` CHAR(64) NOT NULL,
  `status` ENUM('draft','signed') NOT NULL DEFAULT 'draft',
  `selected_package_code` VARCHAR(32) DEFAULT NULL,
  `selected_package_label` VARCHAR(255) DEFAULT NULL,
  `signed_at` DATETIME DEFAULT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `pdf_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contract_token` (`contract_token`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        'student_contracts_special' => <<<'SQL'
CREATE TABLE IF NOT EXISTS `student_contracts_special` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) DEFAULT NULL,
  `contract_token` CHAR(64) NOT NULL,
  `status` ENUM('draft','signed') NOT NULL DEFAULT 'draft',
  `selected_package_code` VARCHAR(32) DEFAULT NULL,
  `selected_package_label` VARCHAR(255) DEFAULT NULL,
  `signed_at` DATETIME DEFAULT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `pdf_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contract_token` (`contract_token`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        'student_contracts_burundi' => <<<'SQL'
CREATE TABLE IF NOT EXISTS `student_contracts_burundi` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_application_id` INT UNSIGNED NULL DEFAULT NULL,
  `student_email` VARCHAR(190) NULL DEFAULT NULL,
  `student_name` VARCHAR(255) NULL DEFAULT NULL,
  `contract_code` VARCHAR(64) NULL DEFAULT NULL,
  `contract_html` LONGTEXT NULL,
  `status` VARCHAR(50) NULL DEFAULT 'draft',
  `signed_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
    ];
}

/** @return list<string> */
function xander_db_repair_cache_file(mysqli $conn): string
{
    $dbRow = $conn->query('SELECT DATABASE()');
    $dbName = preg_replace('/[^a-z0-9_]/', '', (string) ($dbRow ? ($dbRow->fetch_row()[0] ?? '') : ''));

    return rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'xander_db_ok_' . ($dbName ?: 'default') . '.stamp';
}

function xander_db_repair_cache_clear(mysqli $conn): void
{
    $file = xander_db_repair_cache_file($conn);
    if (is_file($file)) {
        @unlink($file);
    }
}

/** @return list<string> */
function xander_db_list_broken_tables(mysqli $conn, bool $useCache = true): array
{
    if ($useCache) {
        $cache = xander_db_repair_cache_file($conn);
        if (is_file($cache) && (time() - (int) filemtime($cache)) < 300) {
            return [];
        }
    }

    $res = $conn->query('SHOW TABLES');
    if (!$res) {
        return [];
    }

    $broken = [];
    while ($row = $res->fetch_row()) {
        $table = (string) $row[0];
        if (!xander_db_table_is_usable($conn, $table)) {
            $broken[] = $table;
        }
    }

    if ($broken === [] && $useCache) {
        @touch(xander_db_repair_cache_file($conn));
    }

    return $broken;
}

function xander_db_resolve_create_sql(mysqli $conn, string $table, bool $isLocal): ?string
{
    $table = preg_replace('/[^a-z0-9_]/', '', $table);
    if ($table === '') {
        return null;
    }

    $known = xander_db_known_table_schemas();
    if (isset($known[$table])) {
        return $known[$table];
    }

    if ($isLocal) {
        $dbRow = $conn->query('SELECT DATABASE()');
        $dbName = $dbRow ? (string) ($dbRow->fetch_row()[0] ?? '') : '';
        if ($dbName !== '') {
            foreach (xander_db_inno_db_data_dirs() as $base) {
                $frm = "{$base}/{$dbName}/{$table}.frm";
                $ddl = xander_db_build_create_table_from_frm($table, $frm);
                if ($ddl !== null) {
                    return $ddl;
                }
            }
        }
    }

    return null;
}

/**
 * Drop broken table, remove orphan tablespace, recreate from known or .frm-derived DDL.
 *
 * @return array{repaired: list<string>, skipped: list<string>, failed: array<string, string>}
 */
function xander_db_repair_broken_table(mysqli $conn, string $table, bool $isLocal): array
{
    $result = ['repaired' => [], 'skipped' => [], 'failed' => []];
    $table = preg_replace('/[^a-z0-9_]/', '', $table);
    if ($table === '') {
        return $result;
    }

    if (xander_db_table_is_usable($conn, $table)) {
        return $result;
    }

    $ddl = xander_db_resolve_create_sql($conn, $table, $isLocal);
    if ($ddl === null) {
        // MyISAM repair attempt (e.g. ad_subtopics)
        try {
            $conn->query("REPAIR TABLE `{$table}`");
            if (xander_db_table_is_usable($conn, $table)) {
                $result['repaired'][] = $table;
                return $result;
            }
        } catch (Throwable $e) {
            // continue to drop/recreate if we have no DDL
        }
        $result['skipped'][] = $table;
        return $result;
    }

    xander_db_drop_broken_table($conn, $table, $isLocal);

    try {
        if (!$conn->query($ddl)) {
            $result['failed'][$table] = (string) $conn->error;
            return $result;
        }
    } catch (Throwable $e) {
        $result['failed'][$table] = $e->getMessage();
        return $result;
    }

    if (xander_db_table_is_usable($conn, $table)) {
        $result['repaired'][] = $table;
        error_log("[db_table_repair] recreated table {$table}");
    } else {
        $result['failed'][$table] = 'Table still unusable after recreate';
    }

    xander_db_repair_cache_clear($conn);

    return $result;
}

/**
 * Repair every broken table in the current database (local XAMPP only by default).
 *
 * @return array{repaired: list<string>, skipped: list<string>, failed: array<string, string>}
 */
function xander_db_repair_all_broken_tables(mysqli $conn, bool $isLocal = false): array
{
    $summary = ['repaired' => [], 'skipped' => [], 'failed' => []];
    if (!$isLocal) {
        return $summary;
    }

    foreach (xander_db_list_broken_tables($conn) as $table) {
        $part = xander_db_repair_broken_table($conn, $table, true);
        $summary['repaired'] = array_merge($summary['repaired'], $part['repaired']);
        $summary['skipped'] = array_merge($summary['skipped'], $part['skipped']);
        $summary['failed'] = array_merge($summary['failed'], $part['failed']);
    }

    return $summary;
}
