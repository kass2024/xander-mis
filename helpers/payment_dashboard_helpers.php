<?php
declare(strict_types=1);

/**
 * Build unified student source SQL for payment dashboards (only existing tables).
 */
function xander_payment_students_source_sql(mysqli $conn): string
{
    $parts = [
        'SELECT id, email, first_name, last_name FROM student_applications',
    ];

    $optional = [
        'malta_applications' => 'SELECT id, email, name AS first_name, surname AS last_name FROM malta_applications',
        'turkey_applications' => 'SELECT id, email, first_name, last_name FROM turkey_applications',
    ];

    foreach ($optional as $table => $sql) {
        $safe = preg_replace('/[^a-z_]/', '', $table);
        $check = $conn->query("SHOW TABLES LIKE '{$safe}'");
        if ($check && $check->num_rows > 0) {
            $parts[] = $sql;
        }
        if ($check) {
            $check->free();
        }
    }

    return '(' . implode("\n    UNION ALL\n    ", $parts) . "\n) sa";
}

function xander_payment_table_exists(mysqli $conn, string $table): bool
{
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $check = $conn->query("SHOW TABLES LIKE '{$safe}'");
    $ok = $check && $check->num_rows > 0;
    if ($check) {
        $check->free();
    }
    return $ok;
}
