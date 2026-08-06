<?php
foreach (['page-test.pdf', 'contract_2.pdf'] as $file) {
    $path = "C:/xampp/htdocs/Xander/uploads/contracts/{$file}";
    echo "=== {$file} ===\n";
    $raw = file_get_contents($path);
    $found = false;
    if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $m)) {
        foreach ($m[1] as $i => $s) {
            $d = @gzuncompress($s);
            if ($d === false) {
                $d = @gzdecode($s);
            }
            if ($d === false) {
                continue;
            }
            if (preg_match('/Page\s+\d+\s+of\s+\d+/i', $d, $match)) {
                echo "  Stream {$i}: {$match[0]}\n";
                $found = true;
            }
        }
    }
    echo $found ? "  OK\n" : "  NOT FOUND\n";
}
