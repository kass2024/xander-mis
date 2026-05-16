<?php
declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';

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
   ARTICLE 5 – PACKAGE MAP (SINGLE SOURCE OF TRUTH)
   ALIGNED WITH FINAL HTML CONTRACT (EXACT MATCH)
===================================================== */
function getPackageDetails(string $code): array
{
    $packages = [

        /* =========================
           5. FEES & PAYMENT TERMS - Study Services
        ========================== */

        /* -----------------------------------------
           🎓 Study Services 
        ----------------------------------------- */
        'p501' => [
            'title' => 'USA & Canada (Without Loan) – €1,500',
            'lines' => [
                '€350 – Pre-admission',
                '€1150 – After visa approval',
            ],
            'total' => '€1,500',
        ],

        'p502' => [
            'title' => 'Education Loan Processing (USA & Canada) – €1,500',
            'lines' => [
                '€550 – Pre-admission',
                '€950 – After visa approval',
            ],
            'total' => '€1,500',
        ],

        'p503' => [
            'title' => 'Europe Study – €1,500',
            'lines' => [
                '€350 – Pre-admission',
                '€1150 – After visa approval',
            ],
            'total' => '€1,500',
        ],

        'p504' => [
            'title' => 'Europe Study Full scholarships – €1,500',
            'lines' => [
                '€600 – Pre-admission',
                '€900 – After visa approval',
            ],
            'total' => '€1,500',
        ],

        'p505' => [
            'title' => 'High School Placement (USA, Canada & Europe) – €4,000',
            'lines' => [
                '€2500 – Pre-admission',
                '€1500 – After visa approval',
            ],
            'total' => '€4,000',
        ],

        'p506' => [
            'title' => 'South Korea and China Study – €3,000',
            'lines' => [
                '€1500 – Pre-admission',
                '€1500 – After visa approval',
            ],
            'total' => '€3,000',
        ],

        /* =========================
           🌍 Visit Visa Services
        ========================== */
        'p507' => [
            'title' => 'USA & Canada Visit Visa – €4,000',
            'lines' => [
                '€2,600 Pre-admission',
                '€1,400 After visa approval',
            ],
            'total' => '€4,000',
        ],

        'p508' => [
            'title' => 'Europe Visit Visa – €2,500',
            'lines' => [
                '€1,625 Pre-admission',
                '€875 After visa approval',
            ],
            'total' => '€2,500',
        ],

        /* =========================
           🔁 Credit Transfer Services
        ========================== */
        'p509' => [
            'title' => 'Bachelor’s Degree – €1,500',
            'lines' => [
                '€975 Pre-admission',
                '€525 After visa approval',
            ],
            'total' => '€1,500',
        ],

        'p510' => [
            'title' => 'Master’s Degree – €1,700',
            'lines' => [
                '65%: €1,105',
                '35%: €595',
            ],
            'total' => '€1,700',
        ],

        'p511' => [
            'title' => 'PhD Level – €2,400',
            'lines' => [
                '65%: €1,560',
                '35%: €840',
            ],
            'total' => '€2,400',
        ],

        /* =========================
           🌏 Asia Visit Visa Services
        ========================== */
        'p512' => [
            'title' => 'Documentation Support Only – €1,200',
            'lines' => [
                '65%: €780',
                '35%: €420',
            ],
            'total' => '€1,200',
        ],

        'p513' => [
            'title' => 'Application Processing Only – €800',
            'lines' => [
                '65%: €520',
                '35%: €280',
            ],
            'total' => '€800',
        ],

        'p514' => [
            'title' => 'Full Service Package – €2,000',
            'lines' => [
                '65%: €1,300',
                '35%: €700',
            ],
            'total' => '€2,000',
        ],

        /* =========================
           💼 Job Seeker Services
        ========================== */
        'p515' => [
            'title' => 'Expedited Processing (1-2 months) – €2,500',
            'lines' => [
                '50% (€1,250) before application',
                '50% (€1,250) after visa approval',
            ],
            'total' => '€2,500',
        ],

        'p516' => [
            'title' => 'Standard Processing (2-5months) – €1,500',
            'lines' => [
                '50% (€750) before application',
                '50% (€750) before embassy appointment',
            ],
            'total' => '€1,500',
        ],
    ];

    return $packages[$code] ?? [];
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

    TRIM(s.first_name) AS full_name,
    s.email,
    s.dob,
    s.nationality,
    s.passport_number,
    s.phone_number,
    s.country,
    s.address,
    s.client_type,

    sig.signed_date,
    sig.signature_image

    FROM student_contracts c
    INNER JOIN student_applications s ON s.id = c.student_id
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

    $studentSignature = $data['signature_image'];

    $consultantSigPath = __DIR__ . '/admin/employer-signature.png';
    if (!file_exists($consultantSigPath)) {
        throw new RuntimeException('Consultant signature missing.');
    }

    $consultantSignature =
        'data:image/png;base64,' . base64_encode(file_get_contents($consultantSigPath));

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
    margin: 0cm 2.54cm 2.54cm 2.54cm;
}

/* =====================================================
   BASE DOCUMENT STYLE
===================================================== */
body {
    font-family: "Times New Roman", Times, serif;
    font-size: 12pt;
    line-height: 1.6;
    color: #000;
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
}

h2 {
    font-size: 14pt;
    font-weight: bold;
    text-transform: uppercase;
    margin: 22pt 0 10pt 0;
}

h3 {
    font-size: 12pt;
    font-weight: bold;
    margin: 16pt 0 6pt 0;
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

//* LABEL COLUMN — SMALLER */
.client-label {
    width: 26%;
    white-space: nowrap;
    padding-right: 12pt;
}

/* VALUE COLUMN — PUSH FAR RIGHT */
.client-value {
    width: 74%;
    font-weight: bold;
    border-bottom: 1px solid #000;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: clip;

    padding-left: 18pt;   /* <<< KEY FIX */
    padding-right: 6pt;
    box-sizing: border-box;
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
    width: 20% !important;
}

.client-type-row .client-value {
    width: 80% !important;
}

.client-type-value {
    border-bottom: none !important;
    white-space: nowrap !important;
    font-size: 9pt;          /* slightly smaller */
    word-spacing: 5pt;       /* reduced spacing */
    padding-left: 18pt;
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

/* =====================================================
   FOOTER
===================================================== */
.footer {
    margin-top: 24pt;
    text-align: center;
    font-size: 10pt;
    color: #444;
}

</style>


</head>
<body>
<!-- =========================
     LETTERHEAD
========================= -->
<div class="letterhead">
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
    <strong>Xander Global Scholars Ltd</strong>, Rwanda registered company<br>
    A platform of <strong>Xander Tech LLC</strong>, an Arizona-registered company<br>
    Phone: +1 270 438 7305<br>
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
<?= checkbox(in_array('Professional', $clientTypes)) ?> Professional&nbsp;
<?= checkbox(in_array('Job Seeker', $clientTypes)) ?> Job&nbsp;Seeker&nbsp;
<?= checkbox(in_array('Visitor Visa Applicant', $clientTypes)) ?> Visitor&nbsp;Visa 
 
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

<p>
    All fees cover professional consulting, documentation support,
    administrative processing, and coordination services.
</p>

<p>
    <strong>
        Government fees, embassy charges, biometric fees, tuition deposits,
        courier fees, legal fees, and third-party costs are paid separately
        and are non-refundable.
    </strong>
</p>

<p>
    The Client shall select <strong>one (1)</strong> applicable service package
    from the options below.
</p>

<p><strong><?= esc($package['title']) ?></strong></p>

<ul>
<?php foreach ($package['lines'] as $line): ?>
    <li><?= esc($line) ?></li>
<?php endforeach; ?>
</ul>

<?php if (!empty($package['total'])): ?>
<p><strong>Total Package Fee: <?= esc($package['total']) ?></strong></p>
<?php endif; ?>

<p>
    <strong>Failure to pay may result in suspension or termination of services.</strong>
</p>
<hr>
<!-- =========================
     ARTICLE 6 – PROCESSING TIMELINE
========================= -->
<h2>6. PROCESSING TIMELINE</h2>

<p>
    Estimated processing time is <strong>2–4 months</strong>, depending on:
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
If the Job Seeker visa application is refused, the Client shall be entitled to a <strong>30% refund</strong> of the total amount paid. The refund will be processed within <strong>2–4 months</strong> from the date of the official refusal decision.
</p>

<p>
The remaining <strong>70% is non-refundable</strong> as it covers services already rendered, including:
</p>

<ul>
    <li>Administrative processing</li>
    <li>Documentation handling</li>
    <li>Application support</li>
    <li>Government-related procedures</li>
    <li>Professional time and services</li>
</ul>

<p>
<strong>N.B:</strong> All other services and fees paid are strictly non-refundable.
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
    shall be the sole responsibility of the Client.
</p>
<hr>
<!-- =========================
     ARTICLE 9 – DATA COLLECTION & CONSENT
========================= -->
<h2>9. DATA COLLECTION &amp; CONSENT</h2>

<p>
    The Client authorizes the Company to collect, store, process,
    and use personal data for:
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
    Fraud may result in civil, administrative, or criminal penalties under
    U.S., EU, UK, Canadian, and international laws.
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
                Title: <strong>Owner / Managing Director</strong>
            </p>

            <div style="
                width:7cm;
                height:3cm;
                border-bottom:1px solid #000;
                margin-bottom:4pt;
            ">
                <img src="<?= $consultantSignature ?>"
                     alt="Authorized Signature"
                     style="width:100%; height:100%; object-fit:contain;">
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

            <div style="
                width:7cm;
                height:3cm;
                border-bottom:1px solid #000;
                margin-bottom:4pt;
            ">
                <img src="<?= $studentSignature ?>"
                     alt="Client Signature"
                     style="width:100%; height:100%; object-fit:contain;">
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

<div class="footer">
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
