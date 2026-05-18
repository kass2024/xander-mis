<?php
declare(strict_types=1);

/**
 * Canonical status priority (highest wins). SQL/helpers may use a DB subset when columns are missing.
 *
 * @return list<string>
 */
function pcvc_application_status_priority(): array
{
    return [
        'deny',
        'enrolled',
        'visa_approved',
        'visa_scheduled',
        'sevis_paid',
        'i20_sent',
        'admit',
        'app_paid',
        'sent_to_platform',
        'submitted',
        'addn_doc',
        'incomplete_app',
        'app_start',
    ];
}

/**
 * Human-readable labels for admin filters (aligned with students-manage status dropdown).
 *
 * @return array<string, string>
 */
function pcvc_application_status_labels(): array
{
    return [
        'incomplete_app' => 'Incomplete App',
        'submitted' => 'Submitted',
        'sent_to_platform' => 'Sent to Platform',
        'app_paid' => 'App Paid',
        'admit' => 'Admit',
        'i20_sent' => 'I-20 Sent',
        'sevis_paid' => 'Sevis Paid',
        'visa_scheduled' => 'Visa Sch.',
        'visa_approved' => 'Visa OK',
        'enrolled' => 'Enrolled',
        'addn_doc' => 'Add Doc',
        'deny' => 'Rejected',
        'app_start' => 'App Start',
    ];
}

/**
 * Status flag columns that exist on student_applications (cached per request).
 *
 * @return list<string>
 */
function pcvc_application_status_columns_for_db(mysqli $conn): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $existing = [];
    $res = $conn->query("SHOW COLUMNS FROM student_applications");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $name = strtolower((string) ($row['Field'] ?? ''));
            if ($name !== '') {
                $existing[$name] = true;
            }
        }
        $res->free();
    }

    $out = [];
    foreach (pcvc_application_status_priority() as $key) {
        if (isset($existing[strtolower($key)])) {
            $out[] = $key;
        }
    }

    $cache = $out;

    return $out;
}

/**
 * Effective status key for one application row (assoc from DB).
 */
function pcvc_application_effective_status(array $row): ?string
{
    foreach (pcvc_application_status_priority() as $key) {
        if (!array_key_exists($key, $row)) {
            continue;
        }
        if (!empty($row[$key]) && (int) $row[$key] === 1) {
            return $key;
        }
    }

    return null;
}

/**
 * SQL CASE expression for one row (use in WHERE before GROUP BY).
 */
function pcvc_sql_case_effective_status(string $tableAlias = 'sa', ?mysqli $conn = null): string
{
    $a = preg_replace('/[^a-zA-Z0-9_]/', '', $tableAlias);
    if ($a === '') {
        $a = 'sa';
    }

    $keys = $conn instanceof mysqli
        ? pcvc_application_status_columns_for_db($conn)
        : pcvc_application_status_priority();

    if ($keys === []) {
        return 'NULL';
    }

    $parts = [];
    foreach ($keys as $key) {
        $parts[] = "WHEN IFNULL(`{$a}`.`{$key}`,0)=1 THEN '{$key}'";
    }

    return 'CASE ' . implode(' ', $parts) . ' ELSE NULL END';
}

/**
 * MAX(CASE…) for SELECT … GROUP BY sa.id (MySQL ONLY_FULL_GROUP_BY safe).
 */
function pcvc_sql_max_effective_status(string $tableAlias = 'sa', ?mysqli $conn = null): string
{
    $inner = pcvc_sql_case_effective_status($tableAlias, $conn);
    if ($inner === 'NULL') {
        return 'NULL';
    }

    return 'MAX(' . $inner . ')';
}

/**
 * Sidebar list visibility: show submitted applications and drafts with real uploads.
 */
function pcvc_sql_application_visible_in_list(string $tableAlias = 'sa'): string
{
    $a = preg_replace('/[^a-zA-Z0-9_]/', '', $tableAlias) ?: 'sa';
    $cols = [
        'degree_transcripts',
        'high_school_degree',
        'valid_passport',
        'cv_resume',
        'personal_statement',
        'recommendation_letters',
        'english_certificate',
        'birth_certificate',
        'payment_proof',
    ];
    $parts = [];
    foreach ($cols as $col) {
        $parts[] = "({$a}.{$col} IS NOT NULL AND TRIM({$a}.{$col}) <> '' AND TRIM({$a}.{$col}) <> '[]')";
    }

    $hasDocs = implode(' OR ', $parts);
    $hasIdentity = "TRIM(COALESCE({$a}.first_name, '')) <> ''"
        . " OR TRIM(COALESCE({$a}.last_name, '')) <> ''"
        . " OR TRIM(COALESCE({$a}.email, '')) <> ''";

    return '(((' . $a . '.submitted = 1) OR ' . $hasDocs . ') OR (' . $hasIdentity . '))';
}
