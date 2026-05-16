<?php
declare(strict_types=1);

/**
 * Pre-screening DB schema (auto-created / upgraded on first use or deploy).
 *
 * Manual migration: sql/prescreening_schema.sql
 * Deploy CLI:       php scripts/ensure-prescreening-schema.php
 * Auto on connect:  set XANDER_AUTO_SCHEMA=1 in .env
 */

function xander_prescreening_table_exists(mysqli $conn, string $table): bool
{
    $table = preg_replace('/[^a-z_]/', '', $table);
    $stmt = $conn->prepare(
        'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $ok = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();

    return $ok;
}

function xander_prescreening_column_exists(mysqli $conn, string $table, string $column): bool
{
    $table = preg_replace('/[^a-z_]/', '', $table);
    $column = preg_replace('/[^a-z_]/', '', $column);
    $stmt = $conn->prepare(
        'SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $ok = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();

    return $ok;
}

function xander_prescreening_index_exists(mysqli $conn, string $table, string $indexName): bool
{
    $table = preg_replace('/[^a-z_]/', '', $table);
    $indexName = preg_replace('/[^a-z_]/', '', $indexName);
    $r = @$conn->query(
        "SHOW INDEX FROM `{$table}` WHERE Key_name = '" . $conn->real_escape_string($indexName) . "'"
    );

    return $r && $r->num_rows > 0;
}

/**
 * @param string|null $after Column name to place new column AFTER (MySQL)
 */
function xander_prescreening_add_column_if_missing(
    mysqli $conn,
    string $table,
    string $column,
    string $definition,
    ?string $after = null
): void {
    $table = preg_replace('/[^a-z_]/', '', $table);
    $column = preg_replace('/[^a-z_]/', '', $column);
    if ($column === '' || xander_prescreening_column_exists($conn, $table, $column)) {
        return;
    }
    $sql = 'ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition;
    if ($after !== null && $after !== '') {
        $after = preg_replace('/[^a-z_]/', '', $after);
        if ($after !== '' && xander_prescreening_column_exists($conn, $table, $after)) {
            $sql .= ' AFTER `' . $after . '`';
        }
    }
    if (!@$conn->query($sql)) {
        error_log('[prescreening_schema] ADD COLUMN ' . $table . '.' . $column . ': ' . $conn->error);
    }
}

function xander_prescreening_add_index_if_missing(
    mysqli $conn,
    string $table,
    string $indexName,
    string $indexSql
): void {
    if (xander_prescreening_index_exists($conn, $table, $indexName)) {
        return;
    }
    if (!@$conn->query($indexSql)) {
        error_log('[prescreening_schema] INDEX ' . $indexName . ' on ' . $table . ': ' . $conn->error);
    }
}

/**
 * Core submissions table (new installs get full column set).
 */
function xander_ensure_prescreening_table(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS prescreening_submissions (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id VARCHAR(64) NOT NULL,
        source VARCHAR(16) NOT NULL DEFAULT 'admin',
        student_name VARCHAR(255) NOT NULL DEFAULT '',
        student_email VARCHAR(255) NOT NULL DEFAULT '',
        whatsapp_number VARCHAR(32) NOT NULL DEFAULT '',
        invite_token VARCHAR(64) NULL DEFAULT NULL,
        invite_channel VARCHAR(16) NOT NULL DEFAULT '',
        education_level VARCHAR(255) NOT NULL DEFAULT '',
        course_program VARCHAR(500) NOT NULL DEFAULT '',
        country_interest VARCHAR(255) NOT NULL DEFAULT '',
        open_other_countries TEXT NULL,
        budget_tuition VARCHAR(255) NOT NULL DEFAULT '',
        funds_application_visa VARCHAR(16) NOT NULL DEFAULT '',
        sponsor VARCHAR(64) NOT NULL DEFAULT '',
        afford_deposit VARCHAR(16) NOT NULL DEFAULT '',
        has_valid_passport VARCHAR(16) NOT NULL DEFAULT '',
        academic_docs_ready VARCHAR(64) NOT NULL DEFAULT '',
        english_level VARCHAR(64) NOT NULL DEFAULT '',
        english_test_taken VARCHAR(255) NOT NULL DEFAULT '',
        visa_denied VARCHAR(16) NOT NULL DEFAULT '',
        planned_intake VARCHAR(255) NOT NULL DEFAULT '',
        ready_to_apply VARCHAR(16) NOT NULL DEFAULT '',
        doc_valid_passport VARCHAR(512) NOT NULL DEFAULT '',
        doc_degree_transcripts VARCHAR(512) NOT NULL DEFAULT '',
        doc_high_school VARCHAR(512) NOT NULL DEFAULT '',
        doc_cv_resume VARCHAR(512) NOT NULL DEFAULT '',
        doc_recommendation VARCHAR(512) NOT NULL DEFAULT '',
        doc_personal_statement VARCHAR(512) NOT NULL DEFAULT '',
        doc_english_certificate VARCHAR(512) NOT NULL DEFAULT '',
        doc_birth_certificate VARCHAR(512) NOT NULL DEFAULT '',
        doc_payment_proof VARCHAR(512) NOT NULL DEFAULT '',
        submitted_by_admin_id INT UNSIGNED NULL DEFAULT NULL,
        email_sent TINYINT(1) NOT NULL DEFAULT 0,
        whatsapp_sent TINYINT(1) NOT NULL DEFAULT 0,
        notify_errors TEXT NULL,
        submitted_at DATETIME NULL DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_prescreen_user (user_id),
        UNIQUE KEY uq_prescreen_invite_token (invite_token),
        KEY idx_prescreen_submitted (submitted_at),
        KEY idx_prescreen_email (student_email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!@$conn->query($sql)) {
        error_log('[prescreening_schema] CREATE TABLE prescreening_submissions failed: ' . $conn->error);
    }
}

/**
 * Upgrade existing prescreening_submissions (safe to run repeatedly).
 */
function xander_prescreening_ensure_submissions_columns(mysqli $conn): void
{
    xander_ensure_prescreening_table($conn);

    if (!xander_prescreening_table_exists($conn, 'prescreening_submissions')) {
        return;
    }

    $columns = [
        'source' => ["VARCHAR(16) NOT NULL DEFAULT 'admin'", 'user_id'],
        'student_name' => ["VARCHAR(255) NOT NULL DEFAULT ''", 'source'],
        'student_email' => ["VARCHAR(255) NOT NULL DEFAULT ''", 'student_name'],
        'whatsapp_number' => ["VARCHAR(32) NOT NULL DEFAULT ''", 'student_email'],
        'invite_token' => ['VARCHAR(64) NULL DEFAULT NULL', 'whatsapp_number'],
        'invite_channel' => ["VARCHAR(16) NOT NULL DEFAULT ''", 'invite_token'],
        'education_level' => ["VARCHAR(255) NOT NULL DEFAULT ''", null],
        'course_program' => ["VARCHAR(500) NOT NULL DEFAULT ''", null],
        'country_interest' => ["VARCHAR(255) NOT NULL DEFAULT ''", null],
        'open_other_countries' => ['TEXT NULL', null],
        'budget_tuition' => ["VARCHAR(255) NOT NULL DEFAULT ''", null],
        'funds_application_visa' => ["VARCHAR(16) NOT NULL DEFAULT ''", null],
        'sponsor' => ["VARCHAR(64) NOT NULL DEFAULT ''", null],
        'afford_deposit' => ["VARCHAR(16) NOT NULL DEFAULT ''", null],
        'has_valid_passport' => ["VARCHAR(16) NOT NULL DEFAULT ''", null],
        'academic_docs_ready' => ["VARCHAR(64) NOT NULL DEFAULT ''", null],
        'english_level' => ["VARCHAR(64) NOT NULL DEFAULT ''", null],
        'english_test_taken' => ["VARCHAR(255) NOT NULL DEFAULT ''", null],
        'visa_denied' => ["VARCHAR(16) NOT NULL DEFAULT ''", null],
        'planned_intake' => ["VARCHAR(255) NOT NULL DEFAULT ''", null],
        'ready_to_apply' => ["VARCHAR(16) NOT NULL DEFAULT ''", null],
        'doc_valid_passport' => ["VARCHAR(512) NOT NULL DEFAULT ''", null],
        'doc_degree_transcripts' => ["VARCHAR(512) NOT NULL DEFAULT ''", null],
        'doc_high_school' => ["VARCHAR(512) NOT NULL DEFAULT ''", null],
        'doc_cv_resume' => ["VARCHAR(512) NOT NULL DEFAULT ''", null],
        'doc_recommendation' => ["VARCHAR(512) NOT NULL DEFAULT ''", null],
        'doc_personal_statement' => ["VARCHAR(512) NOT NULL DEFAULT ''", null],
        'doc_english_certificate' => ["VARCHAR(512) NOT NULL DEFAULT ''", null],
        'doc_birth_certificate' => ["VARCHAR(512) NOT NULL DEFAULT ''", null],
        'doc_payment_proof' => ["VARCHAR(512) NOT NULL DEFAULT ''", null],
        'submitted_by_admin_id' => ['INT UNSIGNED NULL DEFAULT NULL', null],
        'email_sent' => ['TINYINT(1) NOT NULL DEFAULT 0', null],
        'whatsapp_sent' => ['TINYINT(1) NOT NULL DEFAULT 0', null],
        'notify_errors' => ['TEXT NULL', null],
        'submitted_at' => ['DATETIME NULL DEFAULT NULL', null],
        'created_at' => ['DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', null],
    ];

    foreach ($columns as $name => [$def, $after]) {
        xander_prescreening_add_column_if_missing($conn, 'prescreening_submissions', $name, $def, $after);
    }

    xander_prescreening_add_index_if_missing(
        $conn,
        'prescreening_submissions',
        'uq_prescreen_invite_token',
        'CREATE UNIQUE INDEX uq_prescreen_invite_token ON prescreening_submissions (invite_token)'
    );
    xander_prescreening_add_index_if_missing(
        $conn,
        'prescreening_submissions',
        'idx_prescreen_submitted',
        'CREATE INDEX idx_prescreen_submitted ON prescreening_submissions (submitted_at)'
    );
    xander_prescreening_add_index_if_missing(
        $conn,
        'prescreening_submissions',
        'idx_prescreen_email',
        'CREATE INDEX idx_prescreen_email ON prescreening_submissions (student_email)'
    );
}

/**
 * Full pre-screening schema: submissions + WhatsApp helper tables.
 */
function xander_ensure_prescreening_schema(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    xander_prescreening_ensure_submissions_columns($conn);

    $whatsappSchema = __DIR__ . '/prescreening_whatsapp_schema.php';
    if (is_readable($whatsappSchema)) {
        require_once $whatsappSchema;
        xander_ensure_prescreening_whatsapp_tables_only($conn);
    }
}
