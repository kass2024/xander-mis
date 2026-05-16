<?php
declare(strict_types=1);

/**
 * Extra client fields on signature tables (contract snapshot — not student_applications).
 */
function xander_ensure_contract_signature_columns(mysqli $conn): void
{
    $columns = [
        'client_dob'         => 'DATE DEFAULT NULL',
        'client_nationality' => 'VARCHAR(120) DEFAULT NULL',
        'client_passport'    => 'VARCHAR(80) DEFAULT NULL',
        'client_phone'       => 'VARCHAR(50) DEFAULT NULL',
        'client_type'        => 'VARCHAR(255) DEFAULT NULL',
        'client_country'     => 'VARCHAR(120) DEFAULT NULL',
        'client_address'     => 'TEXT DEFAULT NULL',
        'effective_date'     => 'DATE DEFAULT NULL',
    ];

    require_once __DIR__ . '/contract_admin_helpers.php';

    foreach (['student_signatures', 'student_signatures_special', 'student_signatures_burundi'] as $table) {
        $existing = [];
        $res = $conn->query("SHOW COLUMNS FROM `$table`");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $existing[$row['Field']] = true;
            }
            $res->free();
        }
        foreach ($columns as $col => $def) {
            if (!isset($existing[$col])) {
                $conn->query("ALTER TABLE `$table` ADD COLUMN `$col` $def");
            }
        }
        xander_ensure_signature_unique_per_contract($conn, $table);
    }
}
