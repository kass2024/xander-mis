<?php
/**
 * CLI wrapper — repair runs automatically on local db.php connect.
 * Run: php scripts/repair-admins-table.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/db_table_repair.php';

echo "=== Repair admins table ===\n";
$ok = xander_db_repair_admins_table($conn, true);
echo $ok ? "admins table OK.\n" : "admins repair failed — check PHP error log.\n";
exit($ok ? 0 : 1);
