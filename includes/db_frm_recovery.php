<?php
declare(strict_types=1);

/**
 * Best-effort column name extraction from legacy MySQL .frm files.
 * Used only for local table repair when InnoDB tablespace is orphaned.
 *
 * @return list<string>
 */
function xander_db_frm_extract_column_names(string $frmPath): array
{
    if (!is_readable($frmPath)) {
        return [];
    }

    $raw = (string) file_get_contents($frmPath);
    if ($raw === '') {
        return [];
    }

    preg_match_all('/[\x20-\x7E]{2,64}/', $raw, $matches);
    $skip = [
        'mysql', 'InnoDB', 'utf8', 'utf8mb4', 'latin1', 'general', 'unicode', 'ci',
        'PRIMARY', 'UNIQUE', 'KEY', 'BTREE', 'HASH', 'NULL', 'DEFAULT', 'CURRENT_TIMESTAMP',
        'AUTO_INCREMENT', 'ENGINE', 'CHARSET', 'COLLATE', 'COMMENT', 'ROW_FORMAT',
        'DYNAMIC', 'COMPACT', 'REDUNDANT', 'COMPRESSED', 'FIXED', 'TABLE', 'CREATE',
    ];
    $skipLookup = array_fill_keys(array_map('strtolower', $skip), true);

    $cols = [];
    foreach ($matches[0] as $token) {
        $token = trim($token);
        if ($token === '' || strlen($token) > 64) {
            continue;
        }
        if (!preg_match('/^[a-z][a-z0-9_]*$/i', $token)) {
            continue;
        }
        // Drop short binary noise (U3, Cj, etc.) — keep id, dob
        $lower = strtolower($token);
        if (strlen($token) < 3 && !in_array($lower, ['id'], true)) {
            continue;
        }
        if (preg_match('/^[A-Z][0-9a-z]{0,2}$/', $token) && strlen($token) <= 3) {
            continue;
        }
        if (isset($skipLookup[strtolower($token)])) {
            continue;
        }
        // Skip common engine/type tokens that look like identifiers
        if (preg_match('/^(int|varchar|text|tinyint|datetime|timestamp|decimal|enum|blob|longtext|mediumtext|date|char|double|float|bigint|smallint)$/i', $token)) {
            continue;
        }
        $cols[strtolower($token)] = $token;
    }

    return array_values($cols);
}

function xander_db_guess_column_ddl(string $column): string
{
    $c = strtolower($column);

    if ($c === 'id') {
        return 'INT UNSIGNED NOT NULL AUTO_INCREMENT';
    }
    if (preg_match('/^(incomplete_app|submitted|sent_to_platform|app_paid|admit|i20_sent|sevis_paid|visa_scheduled|visa_approved|enrolled|addn_doc|deny|app_start|is_read|criminal_history|disability|study_gap|post_secondary|visa_rejection|emergency_same_address)$/', $c)) {
        return 'TINYINT(1) NOT NULL DEFAULT 0';
    }
    if (preg_match('/_id$/', $c) && $c !== 'user_id' && $c !== 'session_id' && $c !== 'application_id') {
        return 'INT UNSIGNED NULL DEFAULT NULL';
    }
    if ($c === 'user_id' || $c === 'session_id') {
        return 'VARCHAR(64) NULL DEFAULT NULL';
    }
    if (preg_match('/(email|e_mail)/', $c)) {
        return 'VARCHAR(190) NULL DEFAULT NULL';
    }
    if (preg_match('/(date|dob|expiry|birth|graduation|start|created_at|updated_at)/', $c)) {
        return 'DATE NULL DEFAULT NULL';
    }
    if (preg_match('/(created_at|updated_at|signed_at|reviewed_at|last_login)/', $c)) {
        return 'DATETIME NULL DEFAULT NULL';
    }
    if (preg_match('/(amount|salary|price|fee|total|rate)/', $c)) {
        return 'DECIMAL(12,2) NULL DEFAULT NULL';
    }
    if (preg_match('/(comment|statement|summary|notes|address|description|content|body|message|proof|transcript|passport|signature|image|path|url|html|text|details|eligibility|benefits|requirements)/', $c)) {
        return 'LONGTEXT NULL';
    }
    if (preg_match('/(name|title|slug|status|role|type|kind|gender|nationality|country|city|state|phone|code|currency|token|hash|mime|label|field|section|program|destination|relationship|position|level|intake|visa|passport|agent|office|platform|airline|airport|school|region|university|job|loan|scholarship|contract|form|file|stored|original|doc|certificate|degree|diploma|certificate|marital|employment|national|place|sheet|link|tagline|coverage|rates|award|brochure|intended|field_of|gpa|applicant|contact|website|kind|section|document|storage|stored|original|slug|tagline|award|brochure)/', $c)) {
        return 'VARCHAR(255) NULL DEFAULT NULL';
    }

    return 'TEXT NULL';
}

function xander_db_build_create_table_from_frm(string $table, string $frmPath): ?string
{
    $table = preg_replace('/[^a-z0-9_]/', '', strtolower($table));
    if ($table === '') {
        return null;
    }

    $columns = xander_db_frm_extract_column_names($frmPath);
    if ($columns === []) {
        return null;
    }

    // Prefer id first when present
    usort($columns, static function (string $a, string $b): int {
        if (strtolower($a) === 'id') {
            return -1;
        }
        if (strtolower($b) === 'id') {
            return 1;
        }
        return strcasecmp($a, $b);
    });

    $lines = [];
    $hasId = false;
    foreach ($columns as $col) {
        $safe = preg_replace('/[^a-z0-9_]/', '', $col);
        if ($safe === '') {
            continue;
        }
        if (strtolower($safe) === 'id') {
            $hasId = true;
        }
        $lines[] = '  `' . $safe . '` ' . xander_db_guess_column_ddl($safe);
    }

    if ($lines === []) {
        return null;
    }

    $ddl = "CREATE TABLE IF NOT EXISTS `{$table}` (\n" . implode(",\n", $lines);
    if ($hasId) {
        $ddl .= ",\n  PRIMARY KEY (`id`)";
    }
    $ddl .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    return $ddl;
}
