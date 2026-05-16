<?php
$wrong = '</' . 'mo' . 'tion' . '>';
$right = '</' . 'di' . 'v' . '>';
foreach (glob(__DIR__ . '/../contracts/*.php') as $f) {
    $c = file_get_contents($f);
    $n = str_replace($wrong, $right, $c);
    if ($n !== $c) {
        file_put_contents($f, $n);
        echo "Fixed $f\n";
    }
}
