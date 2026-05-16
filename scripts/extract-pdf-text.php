<?php
/**
 * Extract text from HEERA-Xander contract PDF (requires xpdf pdftotext).
 * Run: php scripts/extract-pdf-text.php
 */
declare(strict_types=1);

$pdf = dirname(__DIR__) . '/contracts/HEERA-Xander CLIENT CONTRACT-MAY 2026.pdf';
$out = dirname(__DIR__) . '/scripts/heera-contract-may2026.txt';

$pdftotext = 'C:\\Program Files\\xpdf\\bin64\\pdftotext.exe';
if (!is_file($pdftotext)) {
    $pdftotext = trim((string) shell_exec('where pdftotext 2>nul'));
}

if ($pdftotext !== '' && is_file($pdftotext)) {
    $cmd = escapeshellarg($pdftotext) . ' -layout ' . escapeshellarg($pdf) . ' ' . escapeshellarg($out);
    passthru($cmd, $code);
    if ($code === 0 && is_file($out)) {
        echo file_get_contents($out);
        exit(0);
    }
}

echo "pdftotext not available; falling back to raw parse.\n";
$c = file_get_contents($pdf);
preg_match_all('/\(([^\)]{3,300})\)/', $c, $m);
foreach (array_unique($m[1]) as $p) {
    $p = preg_replace('/\\\\[nrt]/', ' ', $p);
    if (preg_match('/^[\x20-\x7E\xC0-\xFF]+$/u', $p) && strlen($p) > 4) {
        echo $p . "\n";
    }
}
