<?php
declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/contract_fee_packages.php';
require_once __DIR__ . '/includes/contract_package_map.php';
require_once __DIR__ . '/includes/contract_pdf_helpers.php';

/* =====================================================
   SAFE ESCAPE
===================================================== */
function esc(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* =====================================================
   PDF CHECKBOX HELPER (DOMPDF SAFE)
===================================================== */
function checkbox(bool $checked): string
{
    $symbol = $checked ? '☑' : '☐';
    return '<span class="checkbox">' . $symbol . '</span>';
}

/* =====================================================
   GENERATE FINAL SIGNED CONTRACT PDF
===================================================== */
function generateContractPDF(int $contractId): string
{
    global $conn;

    /* =====================================================
       1. LOAD FULL CONTRACT + STUDENT + SIGNATURE
    ===================================================== */
$stmt = $conn->prepare("
   SELECT
    c.contract_token,
    c.selected_package_code,

    sig.student_name AS full_name,
    sig.student_email AS email,
    sig.client_dob AS dob,
    sig.client_nationality AS nationality,
    sig.client_passport AS passport_number,
    sig.client_phone AS phone_number,
    sig.client_country AS country,
    sig.client_address AS address,
    sig.client_type,

    sig.signed_date,
    sig.signature_image

    FROM student_contracts c
    INNER JOIN student_signatures sig ON sig.contract_id = c.id
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

    /* =====================================================
       2. SIGNATURE SOURCES
    ===================================================== */
    if (
        empty($data['signature_image']) ||
        !str_starts_with($data['signature_image'], 'data:image')
    ) {
        throw new RuntimeException('Invalid student signature.');
    }

    $studentSignature = xander_pdf_signature_on_white($data['signature_image']);

    require_once __DIR__ . '/includes/contract_branding.php';
    xander_contract_ensure_branding_assets();

    $consultantStampPath = xander_contract_stamp_asset_path();
    if (!file_exists($consultantStampPath)) {
        throw new RuntimeException('Company stamp missing.');
    }

    $consultantSignaturePath = xander_contract_signature_asset_path();
    if (!file_exists($consultantSignaturePath)) {
        throw new RuntimeException('Authorized signature missing.');
    }

    $consultantStamp = xander_contract_branding_data_uri($consultantStampPath);
    $consultantHandSignature = xander_contract_branding_data_uri($consultantSignaturePath);
    if ($consultantStamp === '' || $consultantHandSignature === '') {
        throw new RuntimeException('Company stamp or signature could not be loaded.');
    }

    /* =====================================================
       3. ARTICLE 7 – SELECTED PACKAGE
    ===================================================== */
    $package = getPackageDetails($data['selected_package_code']);
    if (!$package) {
        throw new RuntimeException('Selected package not defined.');
    }
$clientTypes = array_map('trim', explode(',', (string)$data['client_type']));

    /* =====================================================
       4. BUILD HTML (ALL ARTICLES INCLUDED)
    ===================================================== */
    $letterheadPath = __DIR__ . '/assets/letterhead.png';
if (!file_exists($letterheadPath)) {
    throw new RuntimeException('Letterhead image missing.');
}
$letterheadBase64 =
    'data:image/png;base64,' . base64_encode(file_get_contents($letterheadPath));

    ob_start();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>

/* =====================================================
   PAGE SETUP (A4 – WORD STANDARD)
===================================================== */
@page {
    size: A4;
    margin: 2.2cm 2.54cm 2.6cm 2.54cm;
}

@page :first {
    margin-top: 0.6cm;
}

/* =====================================================
   LETTERHEAD (first page only — in document flow)
===================================================== */
.letterhead-first {
    width: 100%;
    margin: 0 0 14pt 0;
    page-break-after: avoid;
    page-break-inside: avoid;
}

.letterhead-first img {
    width: 100%;
    max-height: 3.2cm;
    height: auto;
    display: block;
    object-fit: contain;
}

/* =====================================================
   BASE DOCUMENT STYLE
===================================================== */
body {
    font-family: "Times New Roman", Times, serif;
    font-size: 12pt;
    line-height: 1.65;
    color: #111;
}

/* =====================================================
   HEADINGS
===================================================== */
h1 {
    text-align: center;
    font-size: 20pt;
    font-weight: bold;
    text-transform: uppercase;
    margin: 0 0 18pt 0;
    color: #0f172a;
    letter-spacing: 0.5pt;
}

h2 {
    font-size: 14pt;
    font-weight: bold;
    text-transform: uppercase;
    margin: 22pt 0 10pt 0;
    color: #1e3a8a;
    border-bottom: 1.5pt solid #1d4ed8;
    padding-bottom: 4pt;
}

h3 {
    font-size: 12pt;
    font-weight: bold;
    margin: 16pt 0 6pt 0;
    color: #1e3a8a;
}

/* =====================================================
   PARAGRAPHS & LISTS
===================================================== */
p {
    text-align: justify;
    margin: 0 0 10pt 0;
}

ul,
ol {
    margin: 0 0 12pt 32pt;
    padding: 0;
}

li {
    margin-bottom: 6pt;
}

/* =====================================================
   GLOBAL TABLE DEFAULTS
===================================================== */
table {
    width: 100%;
    border-collapse: collapse;
}

td {
    vertical-align: top;
    padding: 10pt;
    font-size: 11.5pt;
}

/* =====================================================
   LINKS (WORD DEFAULT)
===================================================== */
a {
    color: #0000EE;
    text-decoration: underline;
}

/* =====================================================
   ARTICLE 2 – CLIENT INFORMATION TABLE
===================================================== */
.client-table {
    table-layout: fixed;
    width: 100%;
    margin-top: 4pt;
}

.client-table tr {
    height: 18pt;
}

.client-table td {
    padding: 3pt 2pt;
    font-size: 10.8pt;
    line-height: 1.25;
    vertical-align: bottom;
}

/* LABEL COLUMN */
.client-label {
    width: 44%;
    white-space: nowrap;
    padding-right: 10pt;
    font-size: 10.5pt;
}

/* VALUE COLUMN — aligned right of label, no overlap */
.client-value {
    width: 56%;
    font-weight: bold;
    border-bottom: 1px solid #000;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: clip;
    padding-left: 6pt;
    padding-right: 2pt;
    box-sizing: border-box;
    text-align: left;
}

/* =====================================================
   CLIENT TYPE (NO WRAP, NO UNDERLINE)
===================================================== */
.client-type-row {
    page-break-inside: avoid;
}
/* =====================================================
   CLIENT TYPE ROW – EXTRA WIDTH OVERRIDE
===================================================== */
.client-type-row .client-label {
    width: 44% !important;
}

.client-type-row .client-value {
    width: 56% !important;
}

.client-type-value {
    border-bottom: none !important;
    white-space: nowrap !important;
    font-size: 9pt;
    word-spacing: 5pt;
    padding-left: 6pt;
    overflow: hidden;
}


/* =====================================================
   CHECKBOX SYMBOL (DOMPDF SAFE)
===================================================== */
.checkbox {
    font-family: "DejaVu Sans", sans-serif;
    font-size: 11pt;
}

/* =====================================================
   KEEP ARTICLE 2 TOGETHER
===================================================== */
h2 + table.client-table {
    page-break-before: avoid;
    margin-top: 4pt;
}

/* =====================================================
   SIGNATURE SECTIONS
===================================================== */
.signature-box {
    width: 7cm;
    height: 4cm;
    margin-top: 8pt;
    margin-bottom: 6pt;
    border-bottom: 1px solid #000;
}

.signature-box img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.signature-section {
    margin-top: 28pt;
}

.signature-title {
    font-weight: bold;
    margin-bottom: 6pt;
}

.signature-name {
    margin-top: 6pt;
}

.signature-line {
    width: 7cm;
    height: 3.5cm;
    border-bottom: 1px solid #000;
    margin: 6pt 0;
}

.signature-date {
    margin-top: 4pt;
}

/* =====================================================
   NOTARY BLOCK
===================================================== */
.notary-section {
    margin-top: 36pt;
}

.notary-line {
    width: 9cm;
    border-bottom: 1px solid #000;
    margin: 6pt 0 14pt 0;
}

/* =====================================================
   KEEP ALL SIGNATURES ON ONE PAGE
===================================================== */
.signature-wrapper {
    page-break-inside: avoid;
    page-break-before: avoid;
}

/* =====================================================
   LETTERHEAD
===================================================== */
.letterhead {
    width: 100%;
    margin-bottom: 18pt;
}

.letterhead img {
    width: 100%;
    height: auto;
    display: block;
}

.pdf-page-footer {
    margin-top: 18pt;
    text-align: center;
    font-size: 9pt;
    color: #666;
}

</style>


</head>
<body>
<!-- =========================
     LETTERHEAD
========================= -->
<div class="letterhead-first">
    <img src="<?= $letterheadBase64 ?>" alt="Xander Global Scholars Letterhead">
</div>

<!-- =========================
     CONTRACT HEADER (MATCH HTML)
========================= -->
<h1 style="font-size:15pt; text-align:left; font-weight:bold; text-transform:none;">
    XANDER GLOBAL SCHOLARS LTD Master International Employment, Education &amp;<br>
    Immigration Services Agreement
</h1>

<p style="font-size:12pt; margin-bottom:18pt;">
    (Africa, EU, UK, USA, Canada &amp; Asia)
</p>

<p>
    This Agreement (“<strong>Agreement</strong>”) is made and entered into on
    <strong><?= esc($data['signed_date']) ?></strong> (“Effective Date”),
    by and between:
</p>

<hr>

<!-- =========================
     ARTICLE 1 – COMPANY
========================= -->
<h2>1. COMPANY</h2>

<p>
    <strong>Xander Global Scholars Ltd</strong>, a Rwanda-registered company<br>
    In partnership with <strong>Xander Tech LLC</strong>, an Arizona-registered company<br>
    Phone: +1 450 390 8614<br>
    Email: <a href="mailto:info@xanderglobalscholars.com">info@xanderglobalscholars.com</a>
</p>

<p>
    (Hereinafter referred to as the
    “<strong>Company</strong>,” “<strong>Consultant</strong>,”
    “<strong>we</strong>,” “<strong>us</strong>,” or “<strong>our</strong>”)
</p>

<hr>

<!-- =========================
     ARTICLE 2 – CLIENT
========================= -->
<h2 style="margin-bottom:6pt;">2. CLIENT</h2>

<table class="client-table">
    <tr>
        <td class="client-label">Full Name:</td>
        <td class="client-value"><?= esc($data['full_name']) ?></td>
    </tr>

    <tr>
        <td class="client-label">Date of Birth:</td>
        <td class="client-value"><?= esc($data['dob']) ?></td>
    </tr>

    <tr>
        <td class="client-label">Passport / National ID Number:</td>
        <td class="client-value"><?= esc($data['passport_number']) ?></td>
    </tr>

    <tr>
        <td class="client-label">Nationality:</td>
        <td class="client-value"><?= esc($data['nationality']) ?></td>
    </tr>

    <tr>
        <td class="client-label">Country of Residence:</td>
        <td class="client-value"><?= esc($data['country']) ?></td>
    </tr>

    <tr>
        <td class="client-label">Current Address:</td>
        <td class="client-value"><?= esc($data['address']) ?></td>
    </tr>

    <tr>
        <td class="client-label">Email:</td>
        <td class="client-value"><?= esc($data['email']) ?></td>
    </tr>

    <tr>
        <td class="client-label">Phone:</td>
        <td class="client-value"><?= esc($data['phone_number']) ?></td>
    </tr>

    <tr class="client-type-row">
    <td class="client-label">Client Type:</td>
   <td class="client-value client-type-value">
   <?= checkbox(in_array('Student', $clientTypes)) ?> Student&nbsp;
<?= checkbox(in_array('Job Applicant', $clientTypes) || in_array('Job Seeker', $clientTypes)) ?> Job&nbsp;Applicant&nbsp;
<?= checkbox(in_array('Visitor Visa Applicant', $clientTypes)) ?> Visitor&nbsp;Visa&nbsp;Applicant
</td>

</tr>

</table>

<p style="margin-top:6pt;">
    (Hereinafter referred to as the
    “<strong>Client</strong>,” “<strong>Student</strong>,” or
    “<strong>Applicant</strong>”)
</p>

<p>
    The Company and the Client shall collectively be referred to as the
    “<strong>Parties</strong>.”
</p>
<hr>
<!-- =========================
     ARTICLE 3 – PURPOSE
========================= -->
<h2>3. PURPOSE OF THIS AGREEMENT</h2>

<p>
    This Agreement governs the provision of international education consulting,
    employment placement assistance, immigration and visa support, admissions guidance,
    documentation support, relocation advisory, and related professional services
    provided by Xander Global Scholars.
</p>

<p>
    This Agreement applies to Clients globally, including but not limited to Africa
    (Rwanda, Uganda, Kenya, Tanzania, Burundi, Ghana, Nigeria), Europe, the United States,
    Canada, Asia, and other jurisdictions.
</p>
<hr>
<!-- =========================
     ARTICLE 4 – SCOPE OF SERVICES
========================= -->
<h2>4. SCOPE OF SERVICES</h2>

<p>
    Subject to the service package selected by the Client, services may include,
    but are not limited to:
</p>

<p><strong>A. Education &amp; Career Services</strong></p>
<ul>
    <li>University, college, and professional program applications</li>
    <li>Scholarships, fee waivers, and financial aid guidance</li>
    <li>Education loan facilitation (where applicable)</li>
    <li>Admission documentation support</li>
    <li>Interview preparation</li>
    <li>Pre-departure orientation</li>
    <li>Accommodation guidance</li>
    <li>Credit transfer assistance</li>
</ul>

<p><strong>B. Employment &amp; Immigration Services</strong></p>
<ul>
    <li>Job opportunity referrals (EU &amp; international)</li>
    <li>Employer connection facilitation</li>
    <li>Work permit application support</li>
    <li>Visa documentation preparation</li>
    <li>Embassy application guidance</li>
    <li>Immigration process coordination</li>
</ul>

<p><strong>No Guarantee Disclaimer</strong></p>
<p>The Client acknowledges and agrees that Xander Global Scholars does not guarantee:</p>
<ul>
    <li>Visa approval</li>
    <li>Admission</li>
    <li>Employment placement</li>
    <li>Loan approval</li>
    <li>Processing timelines</li>
</ul>

<p>
    All final decisions rest solely with universities, employers, embassies,
    immigration authorities, lenders, and other third-party entities.
</p>
<hr>
<!-- =========================
     ARTICLE 5 – FEES & PAYMENT TERMS
========================= -->
<h2>5. FEES &amp; PAYMENT TERMS</h2>

<?php renderContractFeePackagesPdf($data['selected_package_code']); ?>
<hr>
<!-- =========================
     ARTICLE 6 – PROCESSING TIMELINE
========================= -->
<h2>6. PROCESSING TIMELINE</h2>

<p>
    Estimated <strong>Standard</strong> processing time is <strong>2–9 months</strong>, depending on:
</p>

<ul>
    <li>Embassy workload</li>
    <li>Employer response</li>
    <li>Immigration authorities</li>
    <li>Third-party institutions</li>
</ul>

<p>
    The Company shall not be responsible for delays beyond its control.
</p>
<hr>
<!-- =========================
     ARTICLE 7 – REFUND POLICY
========================= -->
<h2>7. REFUND POLICY</h2>

<p>
If a Job Seeker visa application is refused, the client shall be entitled to a <strong>17% refund of the total amount paid at the second installment</strong>. Refunds will be processed within <strong>1–2 months</strong> of the official refusal decision date.
</p>

<p>
The remaining <strong>83% is non-refundable</strong> as it covers services already rendered, including:
</p>

<ul>
    <li>Administrative processing</li>
    <li>Documentation handling</li>
    <li>Application support</li>
    <li>Government-related procedures</li>
    <li>Professional time and consultation services</li>
    <li>Work permit application</li>
</ul>

<p>
<strong>FINAL NOTICE:</strong> All other services and fees paid are strictly non-refundable, unless otherwise stated under an official promotion or written agreement from Xander Global Scholars.
</p>
<!-- =========================
     ARTICLE 8 – CLIENT RESPONSIBILITIES
========================= -->
<h2>8. CLIENT RESPONSIBILITIES</h2>

<p>The Client agrees to:</p>

<ul>
    <li>Provide true, accurate, and complete information</li>
    <li>Submit only genuine and authentic documents</li>
    <li>Respond promptly to Company requests</li>
    <li>Attend all required interviews and appointments</li>
    <li>Comply with all immigration and employment laws</li>
</ul>

<p>
    Any failure resulting from false, misleading, or delayed information
    shall be the sole responsibility of the Client, and there will be no refund.
</p>
<hr>
<!-- =========================
     ARTICLE 9 – DATA COLLECTION & CONSENT
========================= -->
<h2>9. DATA COLLECTION &amp; CONSENT</h2>

<p>
    The Client authorizes the Company to collect, store temporarily for no longer than 12 months,
    process, and use personal data for:
</p>

<ul>
    <li>Applications</li>
    <li>Admissions</li>
    <li>Employment placement</li>
    <li>Visa processing</li>
    <li>Loan facilitation</li>
    <li>Embassy communication</li>
    <li>Compliance and audits</li>
</ul>
<hr>
<!-- =========================
     ARTICLE 10 – CROSS-BORDER DATA TRANSFER
========================= -->
<h2>10. CROSS-BORDER DATA TRANSFER</h2>

<p>
    The Client expressly consents that their data may be transferred,
    stored, and processed internationally, including but not limited to
    the USA, Europe, Canada, Asia, and partner countries.
</p>
<hr>
<!-- =========================
     ARTICLE 11 – CONFIDENTIALITY & DATA PROTECTION
========================= -->
<h2>11. CONFIDENTIALITY &amp; DATA PROTECTION</h2>

<p>
    Xander Global Scholars applies reasonable safeguards to protect
    Client information. However, no system guarantees absolute security.
</p>
<hr>
<!-- =========================
     ARTICLE 12 – FRAUD & MISREPRESENTATION
========================= -->
<h2>12. FRAUD, MISREPRESENTATION &amp; LEGAL RESPONSIBILITY</h2>

<p>
    All documents and information submitted must be genuine,
    accurate, and lawful.
</p>

<p>If the Client submits false, forged, altered, or misleading documents:</p>

<ul>
    <li>The Company bears no liability</li>
    <li>The Client assumes full legal responsibility</li>
    <li>Services may be terminated immediately</li>
    <li>No refund shall be issued</li>
</ul>

<p>
    Fraud may result in civil, administrative, or criminal penalties under your local legal
    administration, Africa, U.S., EU, UK, Canadian, and international laws.
</p>
<hr>
<!-- =========================
     ARTICLE 13 – LIMITATION OF LIABILITY
========================= -->
<h2>13. LIMITATION OF LIABILITY</h2>

<p>The Company shall not be liable for:</p>

<ul>
    <li>Visa refusals</li>
    <li>Embassy decisions</li>
    <li>Employer withdrawal</li>
    <li>Admission rejection</li>
    <li>Loan refusal</li>
    <li>Delays by third parties</li>
    <li>Policy changes</li>
    <li>Deportation or bans</li>
    <li>Financial losses</li>
</ul>
<hr>
<!-- =========================
     ARTICLE 14 – TERMINATION
========================= -->
<h2>14. TERMINATION</h2>

<p>
    Either Party may terminate this Agreement in writing.
    If the Client terminates after processing has begun,
    no refund shall apply except as stated in Article 7.
</p>
<hr>
<!-- =========================
     ARTICLE 15 – TESTIMONIAL & MEDIA CONSENT
========================= -->
<h2>15. TESTIMONIAL &amp; MEDIA CONSENT</h2>

<p>
    The Client voluntarily consents to the use of testimonials
    (video, text, images) for educational and marketing purposes.
</p>

<p>
    No compensation shall be owed unless separately agreed in writing.
</p>
<hr>
<!-- =========================
     ARTICLE 16 – WITHDRAWAL OF CONSENT
========================= -->
<h2>16. WITHDRAWAL OF CONSENT</h2>

<p>The Client may withdraw consent in writing. However:</p>

<ul>
    <li>Already-rendered services remain payable</li>
    <li>Previously published media may not be retractable</li>
</ul>
<hr>
<!-- =========================
     ARTICLE 17 – GOVERNING LAW
========================= -->
<h2>17. GOVERNING LAW</h2>

<p>
    This Agreement shall be governed by the laws of the
    <strong>United States of America</strong>,
    with due consideration to international immigration
    and data protection principles.
</p>
<hr>
<!-- =========================
     ARTICLE 18 – ENTIRE AGREEMENT
========================= -->
<h2>18. ENTIRE AGREEMENT</h2>

<p>
    This Agreement constitutes the entire understanding between the Parties
    and supersedes all prior agreements, representations, or understandings,
    whether written or oral.
</p>

<!-- =========================
     ARTICLE 19 – SIGNATURES
========================= -->
<div style="page-break-inside:avoid;">

<h2 style="margin-top:16pt; margin-bottom:6pt;">19. SIGNATURES</h2>

<p style="margin-bottom:8pt;">
    IN WITNESS WHEREOF, the Parties have executed this Agreement
    as of the date written below.
</p>

<table style="width:100%; border-collapse:collapse; margin-top:8pt;">
    <tr>
        <!-- COMPANY -->
        <td style="width:50%; vertical-align:top; padding-right:12pt;">
            <p style="font-weight:bold; margin:0 0 4pt 0;">
                For Xander Global Scholars Ltd / Xander Tech LLC
            </p>

            <p style="margin:0 0 6pt 0;">
                Name: <strong>Jean de Dieu Hakizimana</strong><br>
                Title: <strong>Chief of Operation</strong>
            </p>

            <div style="
                width:8.5cm;
                height:4.2cm;
                border-bottom:1.2px solid #000;
                margin-bottom:6pt;
                padding:4pt 0;
                display:flex;
                align-items:flex-end;
                gap:8pt;
            ">
                <img src="<?= $consultantHandSignature ?>"
                     alt="Authorized Signature"
                     style="max-height:3.6cm; max-width:3.8cm; display:block;">
                <img src="<?= $consultantStamp ?>"
                     alt="Authorized Stamp"
                     style="max-height:3.8cm; max-width:3.8cm; display:block;">
            </div>

            <p style="margin:0;">
                Date: <strong><?= date('Y-m-d') ?></strong>
            </p>
        </td>

        <!-- CLIENT -->
        <td style="width:50%; vertical-align:top; padding-left:12pt;">
            <p style="font-weight:bold; margin:0 0 4pt 0;">
                Client (Student / Applicant)
            </p>

            <p style="margin:0 0 6pt 0;">
                Name: <strong><?= esc($data['full_name']) ?></strong>
            </p>

            <div class="client-signature-box" style="
                width:7cm;
                height:3cm;
                border-bottom:1px solid #000;
                margin-bottom:4pt;
                background:#ffffff;
                padding:4pt;
            ">
                <img src="<?= $studentSignature ?>"
                     alt="Client Signature"
                     style="width:100%; height:100%; object-fit:contain; background:#ffffff; display:block;">
            </div>

            <p style="margin:0;">
                Date: <strong><?= date('Y-m-d') ?></strong>
            </p>
        </td>
    </tr>
</table>

<!-- NOTARY (COMPACT – SAME PAGE) -->
<h3 style="margin:12pt 0 4pt 0;">For the Notary</h3>

<p style="margin:0 0 2pt 0;">Full Name:</p>
<div style="border-bottom:1px solid #000; width:9cm; height:12pt; margin-bottom:6pt;"></div>

<p style="margin:0 0 2pt 0;">Signature:</p>
<div style="border-bottom:1px solid #000; width:9cm; height:16pt; margin-bottom:6pt;"></div>

<p style="margin:0 0 2pt 0;">Date:</p>
<div style="border-bottom:1px solid #000; width:6cm; height:12pt;"></div>

</div>

<div class="pdf-page-footer">
Contract Reference: <?= esc($data['contract_token']) ?>
</div>

</body>
</html>
<?php
    $html = ob_get_clean();

    /* =====================================================
       5. RENDER PDF
    ===================================================== */
    $dompdf = new Dompdf(new Options(['isRemoteEnabled' => true]));
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    xander_dompdf_add_page_numbers($dompdf);

if (!$dompdf->getCanvas()) {
    throw new RuntimeException('DOMPDF failed to render (canvas is null)');
}


    $dir = __DIR__ . '/uploads/contracts';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $path = $dir . "/contract_{$contractId}.pdf";
    file_put_contents($path, $dompdf->output());

    return $path;
}
