<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/helpers/phone_whatsapp_normalize.php';

$cases = [
    ['+250788123456', null, '250788123456'],
    ['+1 270 438 7305', null, '12704387305'],
    ['+447700900123', null, '447700900123'],
    ['+234 801 234 5678', null, '2348012345678'],
    ['00250788123456', null, '250788123456'],
    ['250788123456', null, '250788123456'],
    ['788123456', null, null],
    ['0788123456', '250', '250788123456'],
    ['788123456', '250', '250788123456'],
];

$fail = 0;
foreach ($cases as [$in, $cc, $exp]) {
    $got = xander_format_phone_for_whatsapp_e164($in, $cc);
    $ok = $got === $exp;
    if (!$ok) {
        $fail++;
        echo "FAIL in={$in} cc=" . ($cc ?? '') . " expected=" . ($exp ?? 'null') . " got=" . ($got ?? 'null') . PHP_EOL;
    }
}
echo $fail === 0 ? "All " . count($cases) . " cases OK\n" : "{$fail} failed\n";
exit($fail > 0 ? 1 : 0);
