<?php
require_once dirname(__DIR__) . '/db.php';
foreach (['student_signatures', 'student_signatures_special', 'student_signatures_burundi'] as $t) {
    echo "=== $t ===\n";
    $r = $conn->query("SHOW COLUMNS FROM `$t`");
    while ($row = $r->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
}
