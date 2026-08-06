<?php
declare(strict_types=1);

/**
 * Repair all broken InnoDB/MyISAM tables in rwanda_xander (local XAMPP).
 * Browser: http://localhost/xander/scripts/repair-all-tables-web.php
 */
require_once dirname(__DIR__) . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

if (!defined('XANDER_IS_LOCAL_XAMPP') || !XANDER_IS_LOCAL_XAMPP) {
    echo "This repair tool runs on local XAMPP only.\n";
    exit(1);
}

require_once dirname(__DIR__) . '/includes/db_full_repair.php';
require_once dirname(__DIR__) . '/includes/db_schema_align.php';
xander_db_align_core_schema($conn);

$before = xander_db_list_broken_tables($conn);
echo 'Broken before: ' . count($before) . "\n\n";

$summary = xander_db_repair_all_broken_tables($conn, true);

echo 'Repaired (' . count($summary['repaired']) . "):\n";
foreach ($summary['repaired'] as $t) {
    echo "  + {$t}\n";
}

if ($summary['skipped'] !== []) {
    echo "\nSkipped (" . count($summary['skipped']) . " — no DDL available):\n";
    foreach ($summary['skipped'] as $t) {
        echo "  ? {$t}\n";
    }
}

if ($summary['failed'] !== []) {
    echo "\nFailed (" . count($summary['failed']) . "):\n";
    foreach ($summary['failed'] as $t => $msg) {
        echo "  ! {$t}: {$msg}\n";
    }
}

// Run schema helpers on recreated tables
require_once dirname(__DIR__) . '/helpers/student_applications_schema.php';
require_once dirname(__DIR__) . '/helpers/student_portal_schema.php';
require_once dirname(__DIR__) . '/helpers/institution_portal_schema.php';
require_once dirname(__DIR__) . '/helpers/admin_menu_permissions.php';
require_once dirname(__DIR__) . '/includes/contract_signature_schema.php';
require_once dirname(__DIR__) . '/includes/db_local_admin_seed.php';

try {
    pcvc_student_applications_ensure_schema($conn);
} catch (Throwable $e) {
    echo "\nstudent_applications schema: " . $e->getMessage() . "\n";
}
try {
    pcvc_student_portal_ensure_schema($conn);
} catch (Throwable $e) {
    echo "student_portal schema: " . $e->getMessage() . "\n";
}
try {
    xander_institution_portal_ensure_schema($conn);
} catch (Throwable $e) {
    echo "institution portal schema: " . $e->getMessage() . "\n";
}
try {
    xander_admin_menu_ensure_table($conn);
} catch (Throwable $e) {
    echo "admin menu schema: " . $e->getMessage() . "\n";
}
try {
    xander_ensure_contract_signature_columns($conn);
} catch (Throwable $e) {
    echo "contract signature schema: " . $e->getMessage() . "\n";
}
xander_db_seed_local_admin_if_empty($conn);

$after = xander_db_list_broken_tables($conn);
echo "\nBroken after: " . count($after) . "\n";
if ($after !== []) {
    foreach ($after as $t) {
        echo "  - {$t}\n";
    }
} else {
    echo "All tables usable.\n";
}
