<?php
declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/burundi_contract_assets.php';
require_once __DIR__ . '/includes/contract_pdf_helpers.php';

function generateBurundiContractPDF(int $contractId): string
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT c.contract_token, c.selected_package_code, c.selected_package_label, c.signed_at,
               sig.student_name AS full_name, sig.student_email AS email,
               sig.client_dob AS dob, sig.client_nationality AS nationality,
               sig.client_passport AS passport_number, sig.client_phone AS phone_number,
               sig.signed_date, sig.signature_image, sig.client_residence, sig.client_address,
               sig.client_type, sig.effective_date
        FROM student_contracts_burundi c
        INNER JOIN student_signatures_burundi sig ON sig.contract_id = c.id
        WHERE c.id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $contractId);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$data) {
        throw new RuntimeException('Contract not found.');
    }

    $assets = xander_burundi_contract_paths();
    $headerB64 = xander_burundi_img_src($assets['header'], true);
    $footerB64 = xander_burundi_img_src($assets['footer'], true);

    $burundiContractPdf = true;
    $isSigned = true;
    $student = [
        'first_name' => (string) ($data['full_name'] ?? ''),
        'last_name' => '',
        'email' => (string) ($data['email'] ?? ''),
        'dob' => (string) ($data['dob'] ?? ''),
        'nationality' => (string) ($data['nationality'] ?? ''),
        'passport_number' => (string) ($data['passport_number'] ?? ''),
        'phone_number' => (string) ($data['phone_number'] ?? ''),
    ];
    $clientResidence = (string) ($data['client_residence'] ?? '');
    $clientAddress = (string) ($data['client_address'] ?? '');
    $clientType = (string) ($data['client_type'] ?? '');
    $selectedPackageCode = (string) ($data['selected_package_code'] ?? '');
    $studentSignatureImg = (string) ($data['signature_image'] ?? '');
    $contractToken = (string) $data['contract_token'];
    $clientName = trim((string) ($data['full_name'] ?? ''));
    $clientSignedDate = (string) ($data['signed_date'] ?? '');
    $effectiveDate = !empty($data['effective_date'])
        ? (string) $data['effective_date']
        : (!empty($data['signed_date']) ? $data['signed_date'] : date('Y-m-d', strtotime((string) $data['signed_at'])));
    $contract = ['selected_package_code' => $selectedPackageCode];
    $burundiFeesPdfOnly = true;

    ob_start();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
@page { size: A4 portrait; margin: 12mm 14mm 16mm 14mm; }
* { box-sizing: border-box; }
body {
  font-family: DejaVu Sans, Arial, sans-serif;
  font-size: 10.5pt;
  line-height: 1.5;
  color: #000;
  margin: 0;
  padding: 0;
}
.letterhead { width: 100%; margin: 0 0 6mm; text-align: center; }
.letterhead img { width: 100%; max-height: 28mm; height: auto; }
.bc-main-title {
  text-align: center;
  font-size: 12.5pt;
  font-weight: bold;
  text-transform: uppercase;
  margin: 0 0 3pt;
  line-height: 1.35;
}
.bc-subtitle { text-align: center; font-size: 9.5pt; margin: 0 0 10pt; }
.bc-h3 { font-size: 11pt; font-weight: bold; margin: 12pt 0 6pt; page-break-after: avoid; }
p { text-align: justify; margin: 0 0 6pt; }
.bc-intro { margin-bottom: 8pt; }
.bc-list { margin: 0 0 8pt 16pt; padding: 0; }
.bc-list li { margin-bottom: 3pt; }
.bc-and { text-align: center; font-weight: bold; margin: 8pt 0; }
.pdf-client-table {
  width: 100%;
  border-collapse: collapse;
  margin: 6pt 0 10pt;
  font-size: 10pt;
}
.pdf-client-table td {
  border: 1px solid #bbb;
  padding: 5pt 7pt;
  vertical-align: top;
}
.pdf-label {
  width: 38%;
  font-weight: bold;
  background: #f3f4f6;
}
.pdf-value { width: 62%; }
.pdf-sig-table { width: 100%; border-collapse: collapse; margin-top: 6pt; page-break-inside: avoid; }
.pdf-sig-cell {
  width: 50%;
  border: 1px solid #bbb;
  padding: 6pt 8pt;
  vertical-align: top;
  font-size: 9pt;
  line-height: 1.45;
}
.pdf-sig-title { font-weight: bold; margin: 0 0 4pt; font-size: 9.5pt; }
.pdf-sig-cell p { margin: 0 0 3pt; text-align: left; }
.pdf-sig-img { max-width: 140px; max-height: 42px; display: block; margin: 4pt 0; }
.pdf-sig-line { margin: 6pt 0; }
.pdf-contract-ref {
  text-align: center;
  font-size: 8pt;
  color: #444;
  margin: 8pt 0 4pt;
  page-break-inside: avoid;
}
.pdf-closing {
  page-break-inside: avoid;
  margin-top: 4pt;
}
.pdf-footer-wrap {
  margin-top: 4pt;
  page-break-inside: avoid;
  page-break-before: avoid;
}
.letterfoot { width: 100%; text-align: center; }
.letterfoot img { width: 100%; max-height: 16mm; height: auto; }
.package-item, .bc-sig-actions, .bc-progress, input, button { display: none !important; }
.bc-selected-pkg-summary { margin: 8pt 0; padding: 8pt; background: #f0fdf4; border: 1px solid #86efac; }
</style>
</head>
<body>
<?php if ($headerB64): ?><div class="letterhead"><img src="<?= $headerB64 ?>" alt=""></div><?php endif; ?>
<h1 class="bc-main-title">Xander Global Scholars Master International Employment, Education &amp; Immigration Services Agreement</h1>
<p class="bc-subtitle">(Africa, EU, UK, USA, Canada &amp; Asia)</p>
<?php include __DIR__ . '/contracts/burundi_contract_body.php'; ?>
<div class="pdf-closing">
<?php include __DIR__ . '/includes/burundi_contract_signatures_pdf.php'; ?>
<?php if ($footerB64): ?>
<div class="pdf-footer-wrap">
  <div class="letterfoot"><img src="<?= $footerB64 ?>" alt=""></div>
</div>
<?php endif; ?>
</div>
</body>
</html>
    <?php
    $html = ob_get_clean();
    $html = str_replace(['<' . 'mo' . 'tion ', '</' . 'mo' . 'tion>'], ['<' . 'di' . 'v ', '</' . 'di' . 'v>'], $html);

    $dir = __DIR__ . '/uploads/contracts/burundi';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = 'burundi-contract-' . $contractId . '-' . date('Ymd-His') . '.pdf';
    $path = $dir . '/' . $filename;

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    file_put_contents($path, $dompdf->output());

    return $path;
}
