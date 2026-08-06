<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/db.php';

$res = $conn->query('SHOW TABLES');
if (!$res) {
    fwrite(STDERR, $conn->error . PHP_EOL);
    exit(1);
}

$broken = [];
$ok = 0;

while ($row = $res->fetch_row()) {
    $table = (string) $row[0];
    try {
        $conn->query("SELECT 1 FROM `{$table}` LIMIT 1");
        $ok++;
    } catch (Throwable $e) {
        $broken[$table] = $e->getMessage();
    }
}

echo "OK tables: {$ok}\n";
echo 'Broken tables: ' . count($broken) . "\n\n";
foreach ($broken as $table => $msg) {
    echo "{$table}\n  {$msg}\n";
}
