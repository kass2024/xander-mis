<?php
$path = $argv[1] ?? 'C:/xampp/htdocs/Xander/uploads/contracts/contract_2.pdf';
$raw = file_get_contents($path);
$hits = 0;
if (preg_match_all('/stream[\x0d\x0a]+(.+?)endstream/s', $raw, $m)) {
    echo basename($path) . ': ' . count($m[1]) . " streams\n";
    foreach ($m[1] as $i => $s) {
        $d = @gzuncompress($s);
        if ($d === false) {
            $d = @gzdecode($s);
        }
        if ($d === false) {
            $d = $s;
        }
        if (preg_match_all('/Page\s+\d+\s+of\s+\d+/i', $d, $mm)) {
            foreach ($mm[0] as $t) {
                echo "  stream {$i}: {$t}\n";
                $hits++;
            }
        }
    }
}
echo "Total: {$hits}\n";
