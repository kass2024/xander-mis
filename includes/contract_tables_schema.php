<?php
declare(strict_types=1);

require_once __DIR__ . '/db_schema_align.php';

/**
 * student_contracts / student_contracts_special — columns used by issue, sign, PDF, admin list.
 */
function xander_student_contracts_create_sql(string $table): string
{
    $table = preg_replace('/[^a-z_]/', '', $table);
    if ($table === '') {
        return '';
    }

    return "
        CREATE TABLE IF NOT EXISTS `{$table}` (
            id INT(11) NOT NULL AUTO_INCREMENT,
            student_id INT(11) DEFAULT NULL,
            contract_token CHAR(64) NOT NULL,
            status ENUM('draft','signed') NOT NULL DEFAULT 'draft',
            selected_package_code VARCHAR(32) DEFAULT NULL,
            selected_package_label VARCHAR(255) DEFAULT NULL,
            signed_at DATETIME DEFAULT NULL,
            sent_at DATETIME DEFAULT NULL,
            pdf_path VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY contract_token (contract_token),
            KEY student_id (student_id),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
}

function xander_ensure_student_contract_table(mysqli $conn, string $table): void
{
    $table = preg_replace('/[^a-z_]/', '', $table);
    if ($table === '') {
        return;
    }

    $exists = xander_db_table_exists($conn, $table);
    if (!$exists) {
        $sql = xander_student_contracts_create_sql($table);
        if ($sql !== '' && !$conn->query($sql)) {
            error_log("[contract_tables_schema] CREATE {$table}: " . $conn->error);
        }
        return;
    }

    $columns = [
        'student_id'             => 'INT(11) NULL DEFAULT NULL',
        'contract_token'         => 'CHAR(64) NULL DEFAULT NULL',
        'selected_package_code'  => 'VARCHAR(32) NULL DEFAULT NULL',
        'selected_package_label' => 'VARCHAR(255) NULL DEFAULT NULL',
        'signed_at'              => 'DATETIME NULL DEFAULT NULL',
        'sent_at'                => 'DATETIME NULL DEFAULT NULL',
        'pdf_path'               => 'VARCHAR(255) NULL DEFAULT NULL',
    ];

    foreach ($columns as $column => $ddl) {
        xander_db_add_column_if_missing($conn, $table, $column, $ddl);
    }

    // Legacy repair schema used student_application_id — mirror into student_id when empty.
    if (xander_db_column_exists($conn, $table, 'student_application_id')
        && xander_db_column_exists($conn, $table, 'student_id')) {
        $conn->query(
            "UPDATE `{$table}`
             SET student_id = student_application_id
             WHERE student_id IS NULL AND student_application_id IS NOT NULL"
        );
    }

    $idx = @$conn->query("SHOW INDEX FROM `{$table}` WHERE Key_name = 'contract_token'");
    if ($idx && $idx->num_rows === 0) {
        @$conn->query("ALTER TABLE `{$table}` ADD UNIQUE KEY contract_token (contract_token)");
    }
    if ($idx) {
        $idx->free();
    }
}

function xander_ensure_student_contract_tables(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    xander_ensure_student_contract_table($conn, 'student_contracts');
    xander_ensure_student_contract_table($conn, 'student_contracts_special');

    require_once __DIR__ . '/burundi_contract_db.php';
    xander_ensure_burundi_contract_tables($conn);
}
