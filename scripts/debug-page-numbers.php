<?php
require 'C:/xampp/htdocs/Xander/vendor/autoload.php';
require 'C:/xampp/htdocs/Xander/includes/contract_pdf_helpers.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$html = '<html><body><p>Page one</p><div style="page-break-before:always"><p>Page two</p></div></body></html>';
$dompdf = new Dompdf(new Options(['isRemoteEnabled' => true]));
$dompdf->loadHtml($html);
$dompdf->setPaper('A4');
$dompdf->render();

$fm = $dompdf->getFontMetrics();
foreach (['helvetica', 'times', 'dejavu sans', 'DejaVu Sans'] as $f) {
    $font = $fm->getFont($f, 'normal');
    echo "$f => " . ($font ?: 'NULL') . "\n";
}
$default = $fm->getFont(null, 'normal');
echo "default => " . ($default ?: 'NULL') . "\n";

xander_dompdf_add_page_numbers($dompdf);
file_put_contents('C:/xampp/htdocs/Xander/uploads/contracts/page-test.pdf', $dompdf->output());
echo "wrote page-test.pdf\n";
