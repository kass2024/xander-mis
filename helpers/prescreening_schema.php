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
        service_type VARCHAR(32) NOT NULL DEFAULT 'study_abroad',
        applicant_address TEXT NULL,
        work_country_destination VARCHAR(255) NOT NULL DEFAULT '',
        work_emergency_contact TEXT NULL,
        work_profile_json TEXT NULL,
        work_docs_checklist TEXT NULL,
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
        study_attendance_mode VARCHAR(32) NOT NULL DEFAULT '',
        ready_to_apply VARCHAR(16) NOT NULL DEFAULT '',
        doc_valid_passport VARCHAR(512) NOT NULL DEFAULT '',
        doc_degree_transcripts VARCHAR(512) NOT NULL DEFAULT '',
        doc_high_school VARCHAR(512) NOT NULL DEFAULT '',
        doc_cv_resume VARCHAR(512) NOT NULL DEFAULT '',
        doc_recommendation VARCHAR(512) NOT NULL DEFAULT '',
        doc_personal_statement VARCHAR(512) NOT NULL DEFAULT '',
        doc_english_certificate VARCHAR(512) NOT NULL DEFAULT '',
        doc_birth_certificate VARCHAR(512) NOT NULL DEFAULT '',
        doc_passport_photo VARCHAR(512) NOT NULL DEFAULT '',
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
    static $done = false;
    if ($done) {
        return;
    }

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
        'service_type' => ["VARCHAR(32) NOT NULL DEFAULT 'study_abroad'", 'invite_channel'],
        'applicant_address' => ['TEXT NULL', 'service_type'],
        'work_country_destination' => ["VARCHAR(255) NOT NULL DEFAULT ''", 'applicant_address'],
        'work_emergency_contact' => ['TEXT NULL', 'work_country_destination'],
        'work_profile_json' => ['TEXT NULL', 'work_emergency_contact'],
        'work_docs_checklist' => ['TEXT NULL', 'work_profile_json'],
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
        'study_attendance_mode' => ["VARCHAR(32) NOT NULL DEFAULT ''", 'planned_intake'],
        'ready_to_apply' => ["VARCHAR(16) NOT NULL DEFAULT ''", null],
        'doc_valid_passport' => ["VARCHAR(512) NOT NULL DEFAULT ''", null],
        'doc_degree_transcripts' => ["VARCHAR(512) NOT NULL DEFAULT ''", null],
        'doc_high_school' => ["VARCHAR(512) NOT NULL DEFAULT ''", null],
        'doc_cv_resume' => ["VARCHAR(512) NOT NULL DEFAULT ''", null],
        'doc_recommendation' => ["VARCHAR(512) NOT NULL DEFAULT ''", null],
        'doc_personal_statement' => ["VARCHAR(512) NOT NULL DEFAULT ''", null],
        'doc_english_certificate' => ["VARCHAR(512) NOT NULL DEFAULT ''", null],
        'doc_birth_certificate' => ["VARCHAR(512) NOT NULL DEFAULT ''", null],
        'doc_passport_photo' => ["VARCHAR(512) NOT NULL DEFAULT ''", null],
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

    $done = true;
}

/**
 * Full pre-screening schema: submissions + WhatsApp helper tables.
 */
/**
 * Draft invites / in-progress uploads — not shown in submissions list until final save.
 */
function xander_ensure_prescreening_invites_table(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS prescreening_invites (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id VARCHAR(64) NOT NULL,
        source VARCHAR(16) NOT NULL DEFAULT 'invite',
        student_name VARCHAR(255) NOT NULL DEFAULT '',
        student_email VARCHAR(255) NOT NULL DEFAULT '',
        whatsapp_number VARCHAR(32) NOT NULL DEFAULT '',
        invite_token VARCHAR(64) NOT NULL,
        invite_channel VARCHAR(16) NOT NULL DEFAULT '',
        doc_valid_passport VARCHAR(512) NOT NULL DEFAULT '',
        doc_degree_transcripts VARCHAR(512) NOT NULL DEFAULT '',
        doc_high_school VARCHAR(512) NOT NULL DEFAULT '',
        doc_cv_resume VARCHAR(512) NOT NULL DEFAULT '',
        doc_recommendation VARCHAR(512) NOT NULL DEFAULT '',
        doc_personal_statement VARCHAR(512) NOT NULL DEFAULT '',
        doc_english_certificate VARCHAR(512) NOT NULL DEFAULT '',
        doc_birth_certificate VARCHAR(512) NOT NULL DEFAULT '',
        doc_passport_photo VARCHAR(512) NOT NULL DEFAULT '',
        doc_payment_proof VARCHAR(512) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_prescreen_invite_user (user_id),
        UNIQUE KEY uq_prescreen_invite_token (invite_token),
        KEY idx_prescreen_invite_whatsapp (whatsapp_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!@$conn->query($sql)) {
        error_log('[prescreening_schema] CREATE TABLE prescreening_invites failed: ' . $conn->error);
    }
}

/** Move legacy pending submission rows into invites, then remove pending from submissions. */
function xander_prescreening_purge_pending_submissions(mysqli $conn): void
{
    if (!xander_prescreening_table_exists($conn, 'prescreening_submissions')) {
        return;
    }

    xander_ensure_prescreening_invites_table($conn);
    if (!xander_prescreening_table_exists($conn, 'prescreening_invites')) {
        @$conn->query('DELETE FROM prescreening_submissions WHERE submitted_at IS NULL');

        return;
    }

    $res = @$conn->query(
        "SELECT * FROM prescreening_submissions WHERE submitted_at IS NULL ORDER BY id ASC"
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $userId = (string) ($row['user_id'] ?? '');
            if ($userId === '') {
                continue;
            }
            $token = trim((string) ($row['invite_token'] ?? ''));
            if ($token === '') {
                $token = 'legacy-' . substr(md5($userId), 0, 24);
            }
            $stmt = $conn->prepare(
                'INSERT IGNORE INTO prescreening_invites (
                    user_id, source, student_name, student_email, whatsapp_number,
                    invite_token, invite_channel,
                    doc_valid_passport, doc_degree_transcripts, doc_high_school, doc_cv_resume,
                    doc_recommendation, doc_personal_statement, doc_english_certificate,
                    doc_birth_certificate, doc_passport_photo, doc_payment_proof, created_at
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            if ($stmt) {
                $source = (string) ($row['source'] ?? 'invite');
                $name = (string) ($row['student_name'] ?? '');
                $email = (string) ($row['student_email'] ?? '');
                $wa = (string) ($row['whatsapp_number'] ?? '');
                $channel = (string) ($row['invite_channel'] ?? '');
                $created = (string) ($row['created_at'] ?? date('Y-m-d H:i:s'));
                $d0 = (string) ($row['doc_valid_passport'] ?? '');
                $d1 = (string) ($row['doc_degree_transcripts'] ?? '');
                $d2 = (string) ($row['doc_high_school'] ?? '');
                $d3 = (string) ($row['doc_cv_resume'] ?? '');
                $d4 = (string) ($row['doc_recommendation'] ?? '');
                $d5 = (string) ($row['doc_personal_statement'] ?? '');
                $d6 = (string) ($row['doc_english_certificate'] ?? '');
                $d7 = (string) ($row['doc_birth_certificate'] ?? '');
                $d8 = (string) ($row['doc_passport_photo'] ?? '');
                $d9 = (string) ($row['doc_payment_proof'] ?? '');
                $stmt->bind_param(
                    'ssssssssssssssssss',
                    $userId,
                    $source,
                    $name,
                    $email,
                    $wa,
                    $token,
                    $channel,
                    $d0,
                    $d1,
                    $d2,
                    $d3,
                    $d4,
                    $d5,
                    $d6,
                    $d7,
                    $d8,
                    $d9,
                    $created
                );
                $stmt->execute();
                $stmt->close();
            }
        }
        $res->free();
    }

    @$conn->query('DELETE FROM prescreening_submissions WHERE submitted_at IS NULL');
}

function xander_ensure_prescreening_schema(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    xander_prescreening_ensure_submissions_columns($conn);
    xander_ensure_prescreening_invites_table($conn);
    xander_prescreening_purge_pending_submissions($conn);

    $whatsappSchema = __DIR__ . '/prescreening_whatsapp_schema.php';
    if (is_readable($whatsappSchema)) {
        require_once $whatsappSchema;
        xander_ensure_prescreening_whatsapp_tables_only($conn);
    }
}
