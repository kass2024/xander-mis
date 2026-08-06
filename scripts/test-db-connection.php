<?php
require_once dirname(__DIR__) . '/db.php';
echo 'OK: connected to database "' . ($conn->query('SELECT DATABASE()')->fetch_row()[0] ?? '?') . '"' . PHP_EOL;
