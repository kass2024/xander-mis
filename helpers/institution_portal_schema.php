<?php
declare(strict_types=1);

/**
 * Institution portal tables + universities profile columns (idempotent).
 */
function xander_institution_portal_ensure_schema(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $dbRow = $conn->query('SELECT DATABASE() AS db')->fetch_assoc();
    $dbName = (string) ($dbRow['db'] ?? '');

    $hasColumn = static function (string $table, string $column) use ($conn, $dbName): bool {
        if ($dbName === '') {
            return false;
        }
        $sql = 'SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1';
        $st = $conn->prepare($sql);
        if (!$st) {
            return false;
        }
        $st->bind_param('sss', $dbName, $table, $column);
        $st->execute();
        $ok = (bool) $st->get_result()->fetch_assoc();
        $st->close();

        return $ok;
    };

    $addColumn = static function (string $table, string $ddl) use ($conn): void {
        if (!$conn->query($ddl)) {
            $err = (string) $conn->error;
            if (stripos($err, 'Duplicate column') === false) {
                error_log("[institution_portal_schema] {$table}: {$err}");
            }
        }
    };

    if ($conn->query("SHOW TABLES LIKE 'universities'")->num_rows > 0) {
        if (!$hasColumn('universities', 'website')) {
            $addColumn('universities', 'ALTER TABLE universities ADD COLUMN website VARCHAR(500) NULL DEFAULT NULL AFTER country_id');
        }
        if (!$hasColumn('universities', 'city')) {
            $addColumn('universities', 'ALTER TABLE universities ADD COLUMN city VARCHAR(191) NULL DEFAULT NULL AFTER website');
        }
        if (!$hasColumn('universities', 'institution_phone')) {
            $addColumn('universities', 'ALTER TABLE universities ADD COLUMN institution_phone VARCHAR(64) NULL DEFAULT NULL AFTER city');
        }
        if (!$hasColumn('universities', 'institution_kind')) {
            $addColumn('universities', 'ALTER TABLE universities ADD COLUMN institution_kind VARCHAR(64) NULL DEFAULT NULL AFTER institution_phone');
        }
    }

    $sqlAccounts = "
        CREATE TABLE IF NOT EXISTS institution_portal_accounts (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            university_id INT UNSIGNED NOT NULL,
            contact_name VARCHAR(191) NOT NULL,
            contact_title VARCHAR(120) NULL DEFAULT NULL,
            email VARCHAR(190) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            phone VARCHAR(64) NULL DEFAULT NULL,
            status ENUM('active','disabled') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_institution_portal_email (email),
            UNIQUE KEY uq_institution_portal_university (university_id),
            KEY idx_institution_portal_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    if (!$conn->query($sqlAccounts)) {
        throw new RuntimeException('Failed creating institution_portal_accounts: ' . $conn->error);
    }

    $sqlProfiles = "
        CREATE TABLE IF NOT EXISTS institution_university_profiles (
            university_id INT UNSIGNED NOT NULL,
            scholarship_program_name VARCHAR(255) NULL DEFAULT NULL,
            scholarship_tagline VARCHAR(500) NULL DEFAULT NULL,
            scholarship_summary TEXT NULL,
            scholarship_eligibility TEXT NULL,
            scholarship_benefits TEXT NULL,
            scholarship_amount_notes VARCHAR(500) NULL DEFAULT NULL,
            scholarship_deadline DATE NULL DEFAULT NULL,
            scholarship_apply_url VARCHAR(500) NULL DEFAULT NULL,
            loan_program_name VARCHAR(255) NULL DEFAULT NULL,
            loan_institution_name VARCHAR(255) NULL DEFAULT NULL,
            loan_summary TEXT NULL,
            loan_coverage TEXT NULL,
            loan_eligibility TEXT NULL,
            loan_rates_notes TEXT NULL,
            loan_contact_email VARCHAR(190) NULL DEFAULT NULL,
            loan_apply_url VARCHAR(500) NULL DEFAULT NULL,
            profile_complete_scholarship TINYINT(1) NOT NULL DEFAULT 0,
            profile_complete_loan TINYINT(1) NOT NULL DEFAULT 0,
            homepage_published TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (university_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    if (!$conn->query($sqlProfiles)) {
        throw new RuntimeException('Failed creating institution_university_profiles: ' . $conn->error);
    }

    $sqlDocs = "
        CREATE TABLE IF NOT EXISTS institution_profile_documents (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            university_id INT UNSIGNED NOT NULL,
            section ENUM('scholarship','loan','general') NOT NULL DEFAULT 'general',
            label VARCHAR(191) NULL DEFAULT NULL,
            original_name VARCHAR(255) NOT NULL,
            stored_path VARCHAR(500) NOT NULL,
            mime_type VARCHAR(120) NULL DEFAULT NULL,
            size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
            uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_inst_profile_docs_university (university_id),
            KEY idx_inst_profile_docs_section (section)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    if (!$conn->query($sqlDocs)) {
        throw new RuntimeException('Failed creating institution_profile_documents: ' . $conn->error);
    }
}
