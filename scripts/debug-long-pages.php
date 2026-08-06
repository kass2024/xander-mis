<?php
require 'C:/xampp/htdocs/Xander/vendor/autoload.php';
require 'C:/xampp/htdocs/Xander/includes/contract_pdf_helpers.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$html = str_repeat('<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>', 200);
for ($i = 0; $i < 7; $i++) {
    $html .= '<div style="page-break-before:always"><p>Page break ' . ($i + 2) . '</p></div>';
}

$dompdf = new Dompdf(new Options(['isRemoteEnabled' => true]));
$dompdf->loadHtml('<html><body>' . $html . '</body></html>');
$dompdf->setPaper('A4');
$dompdf->render();
echo 'Long doc pages: ' . $dompdf->getCanvas()->get_page_count() . "\n";
xander_dompdf_add_page_numbers($dompdf);
$out = 'C:/xampp/htdocs/Xander/uploads/contracts/long-test.pdf';
file_put_contents($out, $dompdf->output());

$raw = file_get_contents($out);
$hits = 0;
if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $m)) {
    foreach ($m[1] as $s) {
        $d = @gzuncompress($s) ?: @gzdecode($s) ?: $s;
        if (preg_match_all('/Page\s+\d+\s+of\s+\d+/i', $d, $mm)) {
            $hits += count($mm[0]);
        }
    }
}
echo "Page number hits: {$hits}\n";
