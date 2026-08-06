<?php
declare(strict_types=1);
/**
 * CLI smoke test for admin-dashboard bootstrap (production host simulation).
 */
$_SERVER['HTTP_HOST'] = 'xanderglobalscholars.com';
$_SERVER['SERVER_NAME'] = 'xanderglobalscholars.com';

require __DIR__ . '/../db.php';
require __DIR__ . '/../database.php';

echo "DB bootstrap OK\n";
echo 'conn: ' . ($conn instanceof mysqli ? 'yes' : 'no') . "\n";
echo 'conn2: ' . ($conn2 instanceof mysqli ? 'yes' : 'null') . "\n";
