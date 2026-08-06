<?php
foreach (['contract_2.pdf', 'long-test.pdf'] as $f) {
    $a = file_get_contents("C:/xampp/htdocs/Xander/uploads/contracts/{$f}");
    echo "{$f}: stream count=" . substr_count($a, 'stream') . ', len=' . strlen($a) . "\n";
}
