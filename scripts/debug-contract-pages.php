<?php
require 'C:/xampp/htdocs/Xander/db.php';
require 'C:/xampp/htdocs/Xander/vendor/autoload.php';
require 'C:/xampp/htdocs/Xander/includes/contract_pdf_helpers.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id = 2;
$stmt = $conn->prepare('SELECT sc.*, ss.* FROM student_contracts sc LEFT JOIN student_signatures ss ON ss.contract_id = sc.id WHERE sc.id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

ob_start();
include 'C:/xampp/htdocs/Xander/generate-contract-pdf.php';
// Can't easily get HTML - call generateContractPDF and inspect via wrapper

$orig = null;
// Patch: run generate with reflection after
require 'C:/xampp/htdocs/Xander/generate-contract-pdf.php';

// Duplicate minimal render check using same function
$path = generateContractPDF($id);

$raw = file_get_contents($path);
echo "PDF size: " . strlen($raw) . "\n";

// Use reflection on a fresh render
require_once 'C:/xampp/htdocs/Xander/includes/contract_package_map.php';
require_once 'C:/xampp/htdocs/Xander/includes/contract_fee_packages.php';

// Invoke generateContractPDF internals by reading file - simpler: test page_text on 8-page html
$html = str_repeat('<p>Lorem ipsum dolor sit amet.</p>', 200) . str_repeat('<div style="page-break-before:always"><p>break</p></div>', 7);
$dompdf = new Dompdf(new Options(['isRemoteEnabled' => true]));
$dompdf->loadHtml('<html><body>' . $html . '</body></html>');
$dompdf->setPaper('A4');
$dompdf->render();
$canvas = $dompdf->getCanvas();
echo 'Long doc pages: ' . $canvas->get_page_count() . "\n";
xander_dompdf_add_page_numbers($dompdf);
file_put_contents('C:/xampp/htdocs/Xander/uploads/contracts/long-test.pdf', $dompdf->output());
