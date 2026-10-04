<?php
declare(strict_types=1);

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/site_session_bootstrap.php";
require_once __DIR__ . '/includes/contract_tables_schema.php';
require_once __DIR__ . '/includes/service_contract_lib.php';
require_once __DIR__ . '/includes/service_contract_render.php';
xander_ensure_student_contract_tables($conn);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$isStaffViewer = !empty($_SESSION['admin_id']) || !empty($_SESSION['id']);
$isPreview = isset($_GET['preview']) && (string) $_GET['preview'] === '1' && $isStaffViewer;

/**
 * 0. Safety check: DB connection
 */
if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    exit("Database connection error.");
}

/**
 * 1. Validate token presence
 */
if (!$isPreview && (!isset($_GET['token']) || trim($_GET['token']) === '')) {
    http_response_code(400);
    exit("Invalid contract link.");
}

$token = $isPreview ? '' : trim((string) ($_GET['token'] ?? ''));

/**
 * 2. Load contract session
 */
$sql = "
    SELECT *
    FROM student_contracts
    WHERE contract_token = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit("Query preparation failed.");
}

$stmt->bind_param("s", $token);
$stmt->execute();
$result   = $stmt->get_result();
$contract = $result->fetch_assoc();
$stmt->close();

/**
 * 3. Token not found
 */
$isDynamicContract = false;
$serviceContractRow = null;
$signBlockReason = null;

if ($isPreview) {
    $previewService = (string) ($_GET['service'] ?? 'study');
    if (!in_array($previewService, ['study', 'work', 'visit'], true)) {
        $previewService = 'study';
    }
    $previewOffering = [
        'title' => (string) ($_GET['offering'] ?? ''),
        'description' => (string) ($_GET['details'] ?? ''),
        'school_name' => $previewService === 'study' ? (string) ($_GET['offering'] ?? '') : '',
        'program_name' => $previewService === 'study' ? (string) ($_GET['details'] ?? '') : '',
        'job_title' => $previewService === 'work' ? (string) ($_GET['offering'] ?? '') : '',
        'package_name' => $previewService === 'visit' ? (string) ($_GET['offering'] ?? '') : '',
        'country_name' => (string) ($_GET['country'] ?? ''),
    ];
    $serviceContractRow = [
        'status' => 'pending_signature',
        'service_type' => $previewService,
        'reference' => 'PREVIEW',
        'created_at' => date('Y-m-d H:i:s'),
        'country_name' => (string) ($_GET['country'] ?? ''),
        'country' => ['name' => (string) ($_GET['country'] ?? '')],
        'offering' => $previewOffering,
        'customer' => [
            'full_name' => (string) ($_GET['customer'] ?? ''),
            'email' => (string) ($_GET['email'] ?? ''),
            'phone' => (string) ($_GET['phone'] ?? ''),
        ],
        'fee' => [
            'fee_label' => (string) ($_GET['fee_type'] ?? ''),
            'formatted' => trim((string) ($_GET['currency'] ?? '') . ' ' . (string) ($_GET['amount'] ?? '')),
            'amount' => (float) ($_GET['amount'] ?? 0),
            'remaining_formatted' => trim((string) ($_GET['remaining_currency'] ?? '') . ' ' . (string) ($_GET['remaining'] ?? '')),
        ],
        'staff' => ['prepared_by' => (string) ($_GET['prepared_by'] ?? '')],
        'amount' => (float) ($_GET['amount'] ?? 0),
        'upfront_paid_at' => '',
    ];
    $isDynamicContract = true;
    $isSigned = false;
    $signBlockReason = 'Preview only. The customer signs from the contract link.';
    $selectedPackageCode = '';
    $contract = [
        'status' => 'draft',
        'student_id' => null,
        'contract_token' => '',
    ];
} elseif (!$contract) {
    $serviceContractRow = xander_sc_load_by_token($conn, $token, true);
    if (!$serviceContractRow) {
        http_response_code(404);
        exit("This contract link is invalid or expired.");
    }
    $isDynamicContract = true;
    $signBlockReason = xander_sc_sign_block_reason($serviceContractRow, date('Y-m-d H:i:s'));
    $isSigned = (($serviceContractRow['status'] ?? '') === 'signed');
    $selectedPackageCode = '';
    $contract = [
        'status' => $isSigned ? 'signed' : 'draft',
        'student_id' => $serviceContractRow['customer_id'] ?? null,
        'contract_token' => $token,
    ];
} else {
    /**
     * 4. Contract state flag (DO NOT EXIT)
     */
    $isSigned = ($contract['status'] === 'signed');
    $selectedPackageCode = $isSigned ? (string) ($contract['selected_package_code'] ?? '') : '';
}

$dynCustomer = $isDynamicContract ? (array) ($serviceContractRow['customer'] ?? []) : [];
$dynLock = $isDynamicContract ? ' readonly' : '';
$dynClientType = $isDynamicContract ? xander_sc_client_type_for_service((string) ($serviceContractRow['service_type'] ?? '')) : '';
$canSignDynamic = $isDynamicContract && !$isSigned && $signBlockReason === null && !$isStaffViewer && !$isPreview;
$upfrontAmount = $isDynamicContract ? (float) ($serviceContractRow['amount'] ?? ($serviceContractRow['fee']['amount'] ?? 0)) : 0;
$upfrontPaid = $isDynamicContract && trim((string) ($serviceContractRow['upfront_paid_at'] ?? '')) !== '';
$studySkipsPayHere = $isDynamicContract && (string) ($serviceContractRow['service_type'] ?? '') === 'study';
$needsUpfrontPayment = $isDynamicContract && !$isSigned && !$isStaffViewer && !$isPreview && $upfrontAmount > 0 && !$upfrontPaid && !$studySkipsPayHere;
$preparedByName = $isDynamicContract ? trim((string) ($serviceContractRow['staff']['prepared_by'] ?? $serviceContractRow['staff']['name'] ?? '')) : '';
$showSignaturePad = !$isSigned && !$isStaffViewer && !$isPreview && !$needsUpfrontPayment && ($canSignDynamic || !$isDynamicContract);

require_once __DIR__ . '/helpers/payment_config.php';
$payStudentId = !empty($contract['student_id']) ? (int) $contract['student_id'] : 0;
$payHereUrl = $payStudentId > 0
    ? xander_payment_public_url('/payment.php?student_id=' . $payStudentId)
    : xander_payment_public_url('/payment.php');

/* =====================================================
   LOAD STUDENT DATA FOR SERVER-SIDE RENDERING (SAFE)
===================================================== */
$student = null;

if (!empty($contract['student_id']) && is_numeric($contract['student_id'])) {

    $studentId = (int) $contract['student_id'];

    $stmt = $conn->prepare("
        SELECT
            first_name,
            last_name,
            email,
            dob,
            nationality,
            passport_number,
            phone_number
        FROM student_applications
        WHERE id = ?
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("i", $studentId);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $student = $result->fetch_assoc();
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<?php if (str_contains(str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '')), '/contract/')): ?>
<base href="<?= htmlspecialchars((xander_sc_app_base_path() === '' ? '/' : xander_sc_app_base_path() . '/'), ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<title>Xander Global Scholars – Service Contract</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/contract-modern.css?v=20261004b">
<style>
/* Page-specific tweaks for the main student contract */
.contract-letterhead { width:100%; margin:0 0 24px; }
.contract-letterhead img { width:100%; height:auto; display:block; border-radius:8px; }
.contract            { /* legacy alias mapped onto modern card */ }
.page-section        { padding: 32px 16px 64px; }
.contract            { max-width: 980px; margin: 0 auto; background:#fff; padding:40px 44px; border-radius:16px; box-shadow:0 16px 48px rgba(15,23,42,.10); font-size:17.5px; line-height:1.8; color:#1e293b; }
.contract h2         { font-size:20px; font-weight:700; color:#0f172a; margin:32px 0 12px; padding-bottom:8px; border-bottom:2px solid #e2e8f0; display:flex; align-items:center; gap:10px; }
.contract h2::before { content:""; display:inline-block; width:4px; height:18px; background:linear-gradient(180deg,#1d4ed8,#2563eb); border-radius:2px; }
.contract-title      { font-size:clamp(24px,2.4vw,30px); font-weight:800; text-align:center; margin:0 0 8px; color:#0f172a; letter-spacing:-0.02em; }
.contract-subtitle   { font-size:16px; text-align:center; color:#64748b; margin:0 0 28px; }
.hr                  { height:1px; border:none; background:linear-gradient(to right,transparent,#cbd5e1,transparent); margin:28px 0; }
.line-sm             { display:inline-block; min-width:140px; border-bottom:1.5px solid #94a3b8; }
.line                { display:inline-block; min-width:220px; border-bottom:1.5px solid #94a3b8; }
@media (max-width:768px){ .contract { padding:28px 20px; border-radius:12px; } }

/* Override legacy underline-style inputs inside paragraphs */
.contract p input[type="text"],
.contract p input[type="email"],
.contract p input[type="date"],
.contract p input[type="tel"] {
  border:none !important;
  border-bottom:1.5px solid #cbd5e1 !important;
  border-radius:0 !important;
  padding:4px 2px !important;
  background:transparent !important;
  box-shadow:none !important;
  transition:border-color .15s;
}
.contract p input[type="text"]:focus,
.contract p input[type="email"]:focus,
.contract p input[type="date"]:focus,
.contract p input[type="tel"]:focus {
  border-bottom-color:#2563eb !important;
  box-shadow:none !important;
}
.contract p input[readonly] { background:#f8fafc !important; color:#64748b; }

/* Signature panel */
.signature { margin-top:28px; padding:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; }
.signature canvas { width:100%; height:140px; background:#fff; border:2px dashed #cbd5e1; border-radius:8px; cursor:crosshair; display:block; }
.signature p { margin:6px 0; }

/* Action buttons */
#signContract  { background:linear-gradient(135deg,#1d4ed8,#2563eb); color:#fff; padding:12px 24px; border-radius:8px; font-weight:600; border:none; box-shadow:0 4px 12px rgba(37,99,235,.28); cursor:pointer; transition:all .15s; }
#signContract:hover  { transform:translateY(-1px); box-shadow:0 8px 20px rgba(37,99,235,.36); }
#signContract:disabled{ background:#94a3b8; cursor:not-allowed; transform:none; box-shadow:none; }
#clearSignature{ background:#fff; color:#1e293b; padding:12px 24px; border-radius:8px; font-weight:600; border:1.5px solid #cbd5e1; cursor:pointer; transition:all .15s; }
#clearSignature:hover { background:#f8fafc; }

.contract-warning { margin:18px 0; padding:14px 16px; background:#fef3c7; border-left:4px solid #d97706; border-radius:8px; color:#7c2d12; }

.contract { padding-bottom: 72px; }
.contract-prepared-by {
  position: fixed;
  right: 18px;
  bottom: 10px;
  z-index: 30;
  margin: 0;
  padding: 8px 14px;
  background: #fff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 800;
  color: #0f172a;
}
@media print {
  .contract-prepared-by {
    position: fixed;
    right: 12mm;
    bottom: 8mm;
    border: none;
    background: transparent;
    font-size: 12pt;
    padding: 0;
  }
  body { background:#fff; }
  .contract { box-shadow:none; border-radius:0; padding:0 0 16mm; }
  button, #signContract, #clearSignature { display:none; }
}
</style>


</head>

<body class="xgs-contract-body">

<section class="page-section">

<div class="xgs-contract-hero">
  <span class="xgs-hero-eyebrow">Service Agreement</span>
  <h1 class="xgs-hero-title">Xander Global Scholars – Master International Services Agreement</h1>
  <p class="xgs-hero-sub">Education, Employment &amp; Immigration Services for Africa, EU, UK, USA, Canada &amp; Asia</p>
  <div class="xgs-hero-meta">
    <span>📄 Service Contract</span>
    <span>🔒 Securely Signed Digitally</span>
    <span>🌍 International Coverage</span>
    <?php if ($isSigned): ?><span class="xgs-signed-stamp" style="background:rgba(255,255,255,.18); color:#fff; border-color:rgba(255,255,255,.30);">✓ Signed</span><?php endif; ?>
  </div>
</div>

<?php
$contractPayUrl = $payHereUrl;
if ($isDynamicContract && $token !== '') {
    $contractPayUrl = xander_payment_public_url('/payment.php?contract_token=' . rawurlencode($token) . '&amount=' . rawurlencode((string) $upfrontAmount) . '&currency=' . rawurlencode((string) ($serviceContractRow['currency'] ?? $serviceContractRow['fee']['currency'] ?? '')));
}
$customerMaySign = !$isSigned && !$isStaffViewer && !$isPreview && ($canSignDynamic || (!$isDynamicContract && !$needsUpfrontPayment));
?>
<?php if ($needsUpfrontPayment): ?>
<div class="xgs-pay-here-banner" style="max-width:980px;margin:0 auto 24px;padding:18px 22px;background:linear-gradient(135deg,#1d4ed8,#2563eb);border-radius:12px;color:#fff;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px;box-shadow:0 8px 24px rgba(37,99,235,.25);">
  <div>
    <strong style="font-size:16px;">Pay Here is required before you sign</strong>
    <div style="font-size:14px;opacity:.92;margin-top:4px;">Pay the upfront fee of <?= htmlspecialchars(xander_sc_format_money($upfrontAmount, (string) ($serviceContractRow['currency'] ?? $serviceContractRow['fee']['currency'] ?? '')), ENT_QUOTES, 'UTF-8') ?>. The signature stays closed until this payment is confirmed. An upfront fee of 0 does not need Pay Here.</div>
  </div>
  <a href="<?= htmlspecialchars($contractPayUrl, ENT_QUOTES, 'UTF-8') ?>" style="display:inline-flex;align-items:center;gap:8px;background:#fff;color:#1d4ed8;padding:12px 22px;border-radius:8px;font-weight:700;text-decoration:none;white-space:nowrap;">Pay Here →</a>
</div>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/contract_branding.php';
xander_contract_ensure_branding_assets();
$contractSignatureSrc = xander_contract_web_signature_src();
$contractHandSignatureSrc = xander_contract_hand_signature_web_src();
$contractLetterheadSrc = xander_contract_letterhead_web_src();
?>

<div class="contract xgs-contract bc-fee-host">

<?php if ($contractLetterheadSrc !== ''): ?>
<div class="contract-letterhead">
  <img src="<?= htmlspecialchars($contractLetterheadSrc, ENT_QUOTES, 'UTF-8') ?>" alt="Xander Global Scholars Letterhead">
</div>
<?php endif; ?>

<div class="contract-title">
  XANDER GLOBAL SCHOLARS LTD Master International Employment, Education &<br>
  Immigration Services Agreement
</div>

<div class="contract-subtitle">
  (Africa, EU, UK, USA, Canada & Asia)
</div>


<p>
This Agreement (“Agreement”) is made and entered into on
<span class="line-sm"><?php if ($isDynamicContract && !empty($serviceContractRow['created_at'])) { echo htmlspecialchars(date('F j, Y', strtotime((string) $serviceContractRow['created_at'])), ENT_QUOTES, 'UTF-8'); } ?></span> (“Effective Date”), by and between:
</p>
<div class="hr"></div>
<h2>1. COMPANY</h2>

<p>
<strong>Xander Global Scholars Ltd</strong>, a Rwanda-registered company<br>
In partnership with <strong>Xander Tech LLC</strong>, an Arizona-registered company<br>
Phone: +1 450 390 8614<br>
Email: info@xanderglobalscholars.com
</p>

<p>
(Hereinafter referred to as the “Company,” “Consultant,” “we,” “us,” or “our”)
</p>

<h2>AND</h2>
<div class="hr"></div>
<h2>2. CLIENT</h2>

<!-- CLIENT DETAILS (DYNAMIC & BACKEND-SAFE) -->
<div style="max-width:720px; font-size:13pt;">
<p>
    Email:
    <input
      type="email"
      id="student_email"
      name="student_email"
      autocomplete="email"
      required
      <?= $dynLock ?>
      value="<?= htmlspecialchars((string) ($dynCustomer['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
      style="
        width:60%;
        border:none;
        border-bottom:1.5px solid #1d4ed8;
        font-family:inherit;
        font-size:inherit;
        outline:none;
        background:transparent;
        font-weight:600;
        color:#1d4ed8;
      ">
  </p>
  <p>
    Full Name:
    <input
      type="text"
      id="student_name"
      name="student_name"
      autocomplete="name"
      required
      <?= $dynLock ?>
      value="<?= htmlspecialchars((string) ($dynCustomer['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
      style="
        width:65%;
        border:none;
        border-bottom:1.5px solid #000;
        font-family:inherit;
        font-size:inherit;
        outline:none;
        background:transparent;
      ">
  </p>

  <p>
    Date of Birth:
    <input
      type="date"
      id="student_dob"
      name="student_dob"
      autocomplete="bday"
      required
      <?= $dynLock ?>
      value="<?= htmlspecialchars((string) ($dynCustomer['dob'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
      style="
        width:40%;
        border:none;
        border-bottom:1.5px solid #000;
        font-family:inherit;
        font-size:inherit;
        outline:none;
        background:transparent;
      ">
  </p>

  <p>
    Passport / National ID Number:
    <input
      type="text"
      id="student_passport"
      name="student_passport"
      autocomplete="off"
      <?= $dynLock ?>
      value="<?= htmlspecialchars((string) ($dynCustomer['passport'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
      style="
        width:55%;
        border:none;
        border-bottom:1.5px solid #000;
        font-family:inherit;
        font-size:inherit;
        outline:none;
        background:transparent;
      ">
  </p>

  <p>
    Nationality:
    <input
      type="text"
      id="student_nationality"
      name="student_nationality"
      autocomplete="country-name"
      required
      <?= $dynLock ?>
      value="<?= htmlspecialchars((string) ($dynCustomer['nationality'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
      style="
        width:45%;
        border:none;
        border-bottom:1.5px solid #000;
        font-family:inherit;
        font-size:inherit;
        outline:none;
        background:transparent;
      ">
  </p>

  <p>
    Country of Residence:
    <input
      type="text"
      id="student_country"
      name="student_country"
      autocomplete="country"
      <?= $dynLock ?>
      value="<?= htmlspecialchars((string) ($dynCustomer['residence_country'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
      style="
        width:45%;
        border:none;
        border-bottom:1.5px solid #000;
        font-family:inherit;
        font-size:inherit;
        outline:none;
        background:transparent;
      ">
  </p>

  <p>
    Current Address:
    <input
      type="text"
      id="student_address"
      name="student_address"
      autocomplete="street-address"
      <?= $dynLock ?>
      value="<?= htmlspecialchars((string) ($dynCustomer['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
      style="
        width:70%;
        border:none;
        border-bottom:1.5px solid #000;
        font-family:inherit;
        font-size:inherit;
        outline:none;
        background:transparent;
      ">
  </p>

  

  <p>
    Phone:
    <input
      type="tel"
      id="student_phone"
      name="student_phone"
      autocomplete="tel"
      required
      <?= $dynLock ?>
      value="<?= htmlspecialchars((string) ($dynCustomer['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
      style="
        width:45%;
        border:none;
        border-bottom:1.5px solid #000;
        font-family:inherit;
        font-size:inherit;
        outline:none;
        background:transparent;
      ">
  </p>

  <!-- CLIENT TYPE (ACTIVE CHECKBOXES) -->
  <div style="margin-top:18px;">
    <div style="font-weight:600; margin-bottom:8px; color:#0f172a;">Client Type:</div>
    <div class="xgs-checkgroup">
      <label><input type="checkbox" name="client_type[]" value="Student" <?= $dynClientType === 'Student' ? 'checked' : '' ?> <?= $isDynamicContract ? 'disabled' : '' ?>> Student</label>
      <label><input type="checkbox" name="client_type[]" value="Job Applicant" <?= $dynClientType === 'Job Applicant' ? 'checked' : '' ?> <?= $isDynamicContract ? 'disabled' : '' ?>> Job Applicant</label>
      <label><input type="checkbox" name="client_type[]" value="Visitor Visa Applicant" <?= $dynClientType === 'Visitor Visa Applicant' ? 'checked' : '' ?> <?= $isDynamicContract ? 'disabled' : '' ?>> Visitor Visa Applicant</label>
    </div>
  </div>

<?php if ($isDynamicContract && is_array($serviceContractRow)): ?>
<?php xander_sc_render_service_facts($serviceContractRow); ?>
<?php endif; ?>

</div>

<p style="margin-top:12px;">
  (Hereinafter referred to as the <strong>“Client,” “Student,” or “Applicant”</strong>)
</p>

<p>
  The Company and the Client shall collectively be referred to as the
  <strong>“Parties.”</strong>
</p>
<div class="hr"></div>
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
<div class="hr"></div>
<h2>4. SCOPE OF SERVICES</h2>

<p>
Subject to the service package selected by the Client, services may include, but are
not limited to:
</p>

<p><strong>A. Education & Career Services</strong></p>
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

<p><strong>B. Employment & Immigration Services</strong></p>
<ul>
<li>Job opportunity referrals (EU & international)</li>
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
<div class="hr"></div>
<!-- ============================
     ARTICLE 5 – FEES & PAYMENT TERMS
============================ -->

<h2>5. FEES & PAYMENT TERMS</h2>

<?php if ($isDynamicContract && is_array($serviceContractRow)): ?>
<?php xander_sc_render_locked_fee($serviceContractRow); ?>
<?php else: ?>
<?php
require_once __DIR__ . '/includes/contract_fee_packages.php';
renderContractFeePackagesSection($isSigned, $selectedPackageCode, (string) ($contract['selected_package_label'] ?? ''));
?>
<?php endif; ?>
<!-- PACKAGES_END -->
<div class="hr"></div>
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
<div class="hr"></div>
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
Any failure resulting from false, misleading, or delayed information shall be
the sole responsibility of the Client, and there will be no refund.
</p>
<div class="hr"></div>
<h2>9. DATA COLLECTION & CONSENT</h2>

<p>The Client authorizes the Company to collect, store temporarily for no longer than 12 months, process, and use personal data for:</p>
<ul>
  <li>Applications</li>
  <li>Admissions</li>
  <li>Employment placement</li>
  <li>Visa processing</li>
  <li>Loan facilitation</li>
  <li>Embassy communication</li>
  <li>Compliance and audits</li>
</ul>
<div class="hr"></div>
<h2>10. CROSS-BORDER DATA TRANSFER</h2>

<p>
The Client expressly consents that their data may be transferred, stored, and processed
internationally, including but not limited to the USA, Europe, Canada, Asia,
and partner countries.
</p>
<div class="hr"></div>
<h2>11. CONFIDENTIALITY & DATA PROTECTION</h2>

<p>
Xander Global Scholars applies reasonable safeguards to protect Client information.
However, no system guarantees absolute security.
</p>
<div class="hr"></div>
<h2>12. FRAUD, MISREPRESENTATION & LEGAL RESPONSIBILITY</h2>

<p>
All documents and information submitted must be genuine, accurate, and lawful.
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
<div class="hr"></div>
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
<div class="hr"></div>
<h2>14. TERMINATION</h2>

<p>
Either Party may terminate this Agreement in writing.
If the Client terminates after processing has begun,
no refund shall apply except as stated in Section 7.
</p>
<div class="hr"></div>
<h2>15. TESTIMONIAL & MEDIA CONSENT</h2>

<p>
The Client voluntarily consents to the use of testimonials (video, text, images)
for educational and marketing purposes.
</p>

<p>
No compensation shall be owed unless separately agreed in writing.
</p>
<div class="hr"></div>
<h2>16. WITHDRAWAL OF CONSENT</h2>

<p>The Client may withdraw consent in writing. However:</p>
<ul>
  <li>Already-rendered services remain payable</li>
  <li>Previously published media may not be retractable</li>
</ul>
<div class="hr"></div>
<h2>17. GOVERNING LAW</h2>

<p>
This Agreement shall be governed by the laws of the
<strong>United States of America</strong>,
with due consideration to international immigration and data protection principles.
</p>
<div class="hr"></div>
<h2>18. ENTIRE AGREEMENT</h2>

<p>
This Agreement constitutes the entire understanding between the Parties
and supersedes all prior agreements.
</p>
<div class="hr"></div>
<h2>19. SIGNATURES</h2>

<!-- ============================
     XANDER (STATIC SIGNATURE + AUTO DATE)
============================ -->
<div class="signature">

  <p><strong>For Xander Global Scholars Ltd / Xander Tech LLC</strong></p>

  <p>Name: <strong>Jean de Dieu Hakizimana</strong></p>
  <p>Title: <strong>Chief of Operation</strong></p>

  <p>Signature &amp; Stamp:</p>
  <div style="border-bottom:1.5px solid #000; min-height:120px; max-width:320px; display:flex; align-items:flex-end; gap:12px; padding:6px 0; margin:6px 0 10px;">
    <img
      src="<?= htmlspecialchars($contractHandSignatureSrc, ENT_QUOTES, 'UTF-8') ?>"
      alt="Authorized Signature"
      style="max-height:100px; max-width:140px; width:auto; height:auto; display:block;"
    >
    <img
      src="<?= htmlspecialchars($contractSignatureSrc, ENT_QUOTES, 'UTF-8') ?>"
      alt="Xander Global Scholars Official Stamp"
      style="max-height:115px; max-width:140px; width:auto; height:auto; display:block;"
    >
  </div>

  <p>
    Date:
    <span class="line-sm" id="xander_date"></span>
  </p>

</div>

<!-- ============================
     STUDENT (DRAWN SIGNATURE + AUTO DATE)
============================ -->
<?php if ($needsUpfrontPayment): ?>
<div class="xgs-pay-here-banner" style="margin:0 0 18px;padding:18px 22px;background:linear-gradient(135deg,#1d4ed8,#2563eb);border-radius:12px;color:#fff;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px;">
  <div>
    <strong style="font-size:18px;">Pay Here is required before you sign</strong>
    <div style="font-size:15px;opacity:.92;margin-top:4px;">Pay the upfront fee of <?= htmlspecialchars(xander_sc_format_money($upfrontAmount, (string) ($serviceContractRow['currency'] ?? $serviceContractRow['fee']['currency'] ?? '')), ENT_QUOTES, 'UTF-8') ?>. After payment is confirmed, return here to sign. A zero upfront fee does not need this step.</div>
  </div>
  <a href="<?= htmlspecialchars($contractPayUrl, ENT_QUOTES, 'UTF-8') ?>" style="display:inline-flex;align-items:center;gap:8px;background:#fff;color:#1d4ed8;padding:12px 22px;border-radius:8px;font-weight:700;text-decoration:none;white-space:nowrap;">Pay Here →</a>
</div>
<?php elseif ($studySkipsPayHere && !$isSigned && !$isStaffViewer && !$isPreview): ?>
<p class="contract-warning">Study contracts do not use Pay Here. You can sign this contract.</p>
<?php elseif ($isDynamicContract && !$isSigned && !$isStaffViewer && !$isPreview && $upfrontAmount <= 0): ?>
<p class="contract-warning">The upfront fee is 0, so Pay Here is not required. You can sign this contract.</p>
<?php endif; ?>

<div class="signature">

  <p><strong>Client (Student / Applicant)</strong></p>

  <p>
    Full Name:
    <input
      type="text"
      id="sig_student_name"
      readonly
      style="
        width:60%;
        border:none;
        border-bottom:1.5px solid #000;
        font-family:inherit;
        font-size:inherit;
        background:#f7f9fc;
        outline:none;
      "
    >
  </p>

  <p>Signature:</p>

  <?php if ($isDynamicContract && $isSigned && !empty($serviceContractRow['signature_image'])): ?>
  <div style="border-bottom:1.5px solid #000; min-height:80px; max-width:320px;">
    <img src="<?= htmlspecialchars((string) $serviceContractRow['signature_image'], ENT_QUOTES, 'UTF-8') ?>" alt="Client signature" style="max-height:100px; max-width:280px;">
  </div>
  <?php elseif ($showSignaturePad): ?>
  <div style="border:1.5px dashed #7a7a7a; height:140px; padding:6px;">
    <canvas class="signature-canvas"></canvas>
  </div>
  <?php endif; ?>

  <p>
    Date:
    <input
      type="text"
      id="sig_signed_date"
      readonly
      style="
        width:40%;
        border:none;
        border-bottom:1.5px solid #000;
        font-family:inherit;
        font-size:inherit;
        background:#f7f9fc;
        outline:none;
      "
    >
  </p>

  <div style="margin-top:10px;">
    <?php if ($isSigned): ?>
    <p class="contract-warning" style="margin:0;">This contract has already been signed.</p>
    <?php elseif ($isStaffViewer || $isPreview): ?>
    <p class="contract-warning" style="margin:0;"><?= $studySkipsPayHere ? 'Staff cannot sign a study contract. ' : '' ?>Signing is closed here. Only the customer can sign from their contract link.</p>
    <?php elseif ($needsUpfrontPayment): ?>
    <p class="contract-warning" style="margin:0;">Use Pay Here above before signing. The signature stays closed until that payment is confirmed.</p>
    <?php elseif ($customerMaySign && $isDynamicContract): ?>
    <label style="display:flex; gap:8px; align-items:flex-start; margin:0 0 12px; font-weight:600;">
      <input type="checkbox" id="contract_agree" style="margin-top:4px;">
      <span>I have reviewed this contract and agree to its terms.</span>
    </label>
    <button type="button" id="clearSignature">Clear</button>
    <button type="button" id="signContract">Sign & Submit</button>
    <?php elseif ($customerMaySign): ?>
    <button type="button" id="clearSignature">Clear</button>
    <button type="button" id="signContract">Sign & Submit</button>
    <?php elseif ($signBlockReason): ?>
    <p class="contract-warning" style="margin:0;"><?= htmlspecialchars($signBlockReason, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <input type="hidden" id="signatureData">
  </div>

</div>

<!-- ============================
     NOTARY (STATIC + AUTO DATE)
============================ -->
<div class="signature">

  <p><strong>For the Notary</strong></p>

  <p>Full Name: <span class="line"></span></p>
  <p>Signature: <span class="line"></span></p>

  <p>
    Date:
    <span class="line-sm" id="notary_date"></span>
  </p>

</div>

<?php if ($preparedByName !== ''): ?>
<p class="contract-prepared-by">Prepared by: <?= htmlspecialchars($preparedByName, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

</div>
</section>
<?php include __DIR__ . '/includes/contract_signing_overlay.php'; ?>

<?php include 'footer.php'; ?>

<script>
(() => {
  const isSigned = <?= $isSigned ? 'true' : 'false' ?>;
  const isDynamic = <?= $isDynamicContract ? 'true' : 'false' ?>;
  window.XGS_DYNAMIC_CONTRACT = isDynamic;
  if (isSigned) return;

  /* ==========================
     CONFIG & ELEMENTS
  ========================== */
  const canvas = document.querySelector('.signature-canvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');

  const btnClear = document.getElementById('clearSignature');
  const btnSubmit = document.getElementById('signContract');
  if (!btnSubmit) return;

  const inputName = document.getElementById('sig_student_name');
  const inputDate = document.getElementById('sig_signed_date');
  const hiddenSignature = document.getElementById('signatureData');

  let drawing = false;
let points = [];


  /* ==========================
     CANVAS SETUP (RETINA SAFE)
  ========================== */
  function resizeCanvas() {
    const ratio = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();

    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;

    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    ctx.lineWidth = 2;
    ctx.lineCap = "round";
    ctx.strokeStyle = "#000";
  }

  resizeCanvas();
  window.addEventListener('resize', resizeCanvas);

  /* ==========================
     DRAWING HELPERS
  ========================== */
  function getPos(e) {
    const rect = canvas.getBoundingClientRect();

    if (e.touches) {
      return {
        x: e.touches[0].clientX - rect.left,
        y: e.touches[0].clientY - rect.top
      };
    }
    return { x: e.offsetX, y: e.offsetY };
  }

  function startDraw(e) {
  e.preventDefault();
  drawing = true;
  points = [];

  const pos = getPos(e);
  points.push(pos);

  ctx.beginPath();
  ctx.moveTo(pos.x, pos.y);
}

function draw(e) {
  if (!drawing) return;
  e.preventDefault();

  const pos = getPos(e);
  points.push(pos);

  // First points: draw simple line
  if (points.length < 3) {
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    return;
  }

  // Take last 3 points
  const p0 = points[points.length - 3];
  const p1 = points[points.length - 2];
  const p2 = points[points.length - 1];

  // Midpoint between p1 and p2
  const midX = (p1.x + p2.x) / 2;
  const midY = (p1.y + p2.y) / 2;

  ctx.beginPath();
  ctx.moveTo(p0.x, p0.y);
  ctx.quadraticCurveTo(p1.x, p1.y, midX, midY);
  ctx.stroke();
}

function stopDraw() {
  drawing = false;
  points = [];
}

  /* ==========================
     EVENT LISTENERS
  ========================== */
  canvas.addEventListener('mousedown', startDraw);
  canvas.addEventListener('mousemove', draw);
  canvas.addEventListener('mouseup', stopDraw);
  canvas.addEventListener('mouseleave', stopDraw);

  canvas.addEventListener('touchstart', startDraw, { passive: false });
  canvas.addEventListener('touchmove', draw, { passive: false });
  canvas.addEventListener('touchend', stopDraw);

  /* ==========================
     CLEAR SIGNATURE
  ========================== */
  btnClear.addEventListener('click', () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
  });

  /* ==========================
     VALIDATION HELPERS
  ========================== */
  function hasSignature() {
    const pixels = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
    return pixels.some(channel => channel !== 0);
  }
function getClientTypes() {
  return Array.from(
    document.querySelectorAll('input[name="client_type[]"]:checked')
  ).map(cb => cb.value);
}

  /* ==========================
     SUBMIT SIGNATURE
  ========================== */
 btnSubmit.addEventListener('click', () => {

  /* ==========================
     1. SAFETY CHECKS
  ========================== */
  if (!inputName || !inputDate || !canvas) {
    alert("Required signature fields are missing. Please reload the page.");
    return;
  }

  if (isDynamic) {
    const agree = document.getElementById('contract_agree');
    if (!agree || !agree.checked) {
      alert("Please confirm that you have reviewed this contract and agree to its terms.");
      return;
    }
    const studentName = inputName.value.trim();
    const signedDate = inputDate.value;
    if (!studentName || !signedDate) {
      alert("Please complete the signature name and date.");
      return;
    }
    if (!hasSignature()) {
      alert("Please draw your signature before submitting.");
      return;
    }
    submitDynamicSignature(canvas.toDataURL("image/png"), studentName, signedDate);
    return;
  }

  /* ==========================
     2. PACKAGE SELECTION (ARTICLE 7)
  ========================== */
  const selectedRadio = document.querySelector('input[name="package"]:checked');

  if (!selectedRadio) {
    alert("Please select one service package under Section 5 (Fees & Payment) before signing.");
    return;
  }

  const selectedPackageCode = selectedRadio.value.trim()
    || document.getElementById('selected_package_code')?.value?.trim()
    || selectedRadio.getAttribute('data-package-code')
    || '';
  const selectedPackageLabel = selectedRadio.getAttribute('data-package-label')
    || selectedRadio.closest('.package-item')?.getAttribute('data-package-label')
    || selectedRadio.closest('.package-item')?.querySelector('.package-label-text')?.textContent?.trim()
    || document.getElementById('selected_package_label')?.value?.trim()
    || '';

  if (!selectedPackageCode || !selectedPackageLabel) {
    alert("Invalid package selection. Please reselect your package.");
    return;
  }

  const holder = document.getElementById('selected_package_code');
  if (holder) holder.value = selectedPackageCode;
  const labelHolder = document.getElementById('selected_package_label');
  if (labelHolder) labelHolder.value = selectedPackageLabel;

  /* ==========================
     3. STUDENT NAME VALIDATION
  ========================== */
  const studentName = inputName.value.trim();
  if (!studentName) {
    alert("Please enter your full name before signing.");
    inputName.focus();
    return;
  }

  /* ==========================
     4. SIGNING DATE VALIDATION
  ========================== */
  const signedDate = inputDate.value;
  if (!signedDate) {
    alert("Please select the signing date.");
    inputDate.focus();
    return;
  }

  /* ==========================
     5. SIGNATURE VALIDATION
  ========================== */
  if (!hasSignature()) {
    alert("Please draw your signature before submitting.");
    return;
  }

  /* ==========================
     6. CAPTURE SIGNATURE
  ========================== */
  const signature = canvas.toDataURL("image/png");
  hiddenSignature.value = signature;

  /* ==========================
     7. SUBMIT (FINAL)
  ========================== */
  submitSignature(
    signature,
    studentName,
    signedDate,
    selectedPackageLabel,
    selectedPackageCode
  );
});

/* ==========================
   SUBMIT PROGRESS CONTROLLER
========================== */
const submitBtnUI = document.getElementById('signContract');

function startSubmitProgress() {
  if (window.ContractSigningUI) {
    ContractSigningUI.start({ submitBtn: submitBtnUI, message: 'Securing your signature…' });
  } else if (submitBtnUI) {
    submitBtnUI.disabled = true;
  }
}

function finishSubmitProgress() {
  if (window.ContractSigningUI) {
    ContractSigningUI.finish();
  }
}

  /* ==========================
     SEND TO BACKEND
  ========================== */
function submitDynamicSignature(signature, name, date) {
  startSubmitProgress();
  const endpoint = <?= json_encode(rtrim(xander_sc_app_base_path(), '/') . '/submit-service-contract-signature.php') ?>;
  fetch(endpoint, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      token: "<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>",
      signature: signature,
      student_name: name,
      full_name: name,
      signed_date: date,
      agreement: true
    })
  })
  .then(async res => {
    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.error || "Server error");
    }
    return data;
  })
  .then(data => {
    if (window.ContractSigningUI) {
      ContractSigningUI.finishAndReload(data.message || "Contract signed successfully.", 3000);
    } else {
      alert("Contract signed successfully.");
      window.location.reload();
    }
  })
  .catch(err => {
    if (window.ContractSigningUI) ContractSigningUI.hide({ submitBtn: submitBtnUI });
    else if (submitBtnUI) submitBtnUI.disabled = false;
    alert(err.message || "Unable to submit at this time.\nPlease check your connection and try again.");
  });
}

function submitSignature(signature, name, date, selectedPackage, selectedPackageCode) {
  startSubmitProgress();

  if (!signature || !name || !date || !selectedPackage || !selectedPackageCode) {
    finishSubmitProgress();
    alert("Missing required data. Please review the form and try again.");
    return;
  }

  /* ==========================
     2. STUDENT FIELD REFERENCES
  ========================== */
  const emailInput       = document.getElementById('student_email');
  const dobInput         = document.getElementById('student_dob');
  const nationalityInput = document.getElementById('student_nationality');
  const passportInput    = document.getElementById('student_passport');
  const phoneInput       = document.getElementById('student_phone');
  const fullNameInput  = document.getElementById('student_name');
const countryInput   = document.getElementById('student_country');
const addressInput   = document.getElementById('student_address');

  if (!emailInput || !dobInput || !nationalityInput || !passportInput || !phoneInput) {
    finishSubmitProgress();
    alert("Student information fields are missing. Please reload the page.");
    return;
  }

  /* ==========================
   BUILD PAYLOAD
========================== */
const payload = {
  token: "<?= htmlspecialchars($token) ?>",

  /* ==========================
     📦 ARTICLE 7 – PACKAGE (LOCKED)
  ========================== */
  selected_package_label: selectedPackage,
  selected_package_code: selectedPackageCode,

  /* ==========================
     ✍️ SIGNATURE DATA
  ========================== */
  student_name: name,          // name used in signature section
  signed_date: date,
  signature: signature,

  /* ==========================
     👤 CLIENT / STUDENT DATA
  ========================== */
  full_name: document.getElementById('student_name')?.value.trim() || '',
  student_email: emailInput.value.trim(),
  student_dob: dobInput.value,
  student_passport: passportInput.value.trim(),
  student_nationality: nationalityInput.value.trim(),
  student_phone: phoneInput.value.trim(),
  student_country: document.getElementById('student_country')?.value.trim() || '',
  student_address: document.getElementById('student_address')?.value.trim() || '',

  /* ==========================
     ✅ CLIENT TYPE (CHECKBOXES)
  ========================== */
  client_type: Array.from(
    document.querySelectorAll('input[name="client_type[]"]:checked')
  ).map(cb => cb.value)
};


/* ==========================
   FINAL VALIDATION
========================== */
if (!payload.student_email) {
  finishSubmitProgress();
  alert("Student email is required.");
  emailInput.focus();
  return;
}

if (!payload.selected_package_code) {
  finishSubmitProgress();
  alert("Selected package is missing. Please reselect a package under Section 5.");
  return;
}

if (!payload.client_type || payload.client_type.length === 0) {
  finishSubmitProgress();
  alert("Please select at least one Client Type (Student, Job Applicant, or Visitor Visa Applicant).");
  return;
}

if (window.ContractSigningUI) {
  ContractSigningUI.setMessage('Saving contract & generating PDF…');
}

/* ==========================
   SUBMIT TO BACKEND
========================== */
fetch("submit-signature.php", {
  method: "POST",
  headers: {
    "Content-Type": "application/json"
  },
  body: JSON.stringify(payload)
})
.then(async res => {
  let data;

  try {
    data = await res.json();
  } catch (e) {
    throw new Error("Invalid JSON response from server");
  }

  // HTTP-level error but JSON returned
  if (!res.ok) {
    throw new Error(data.error || "Server error");
  }

  return data;
})
.then(data => {
  if (data.success) {
    if (window.ContractSigningUI) {
      ContractSigningUI.finishAndReload(
        data.message || "Contract signed successfully.\nYou can download or view your signed agreement.",
        3000
      );
    } else {
      alert("Contract signed successfully.\n\nYou can now download or view the signed agreement.");
      window.location.reload();
    }
    return;
  }

  if (data.error && data.error.toLowerCase().includes("already signed")) {
    if (window.ContractSigningUI) {
      ContractSigningUI.finishAndReload("This contract was already signed.", 2500);
    } else {
      alert("This contract has already been signed.\n\nYou can now download or view the signed agreement.");
      window.location.reload();
    }
    return;
  }

  if (window.ContractSigningUI) ContractSigningUI.hide({ submitBtn: submitBtnUI });
  else if (submitBtnUI) submitBtnUI.disabled = false;
  alert(data.error || "Submission failed.");
})
.catch(err => {
  console.error("Signature submission error:", err);
  if (window.ContractSigningUI) ContractSigningUI.hide({ submitBtn: submitBtnUI });
  else if (submitBtnUI) submitBtnUI.disabled = false;
  alert("Unable to submit at this time.\nPlease check your connection and try again.");
});


}

})();
</script>

<script>
(() => {
  'use strict';
  if (window.XGS_DYNAMIC_CONTRACT) return;

  /* =====================================================
     FIELD REFERENCES (REAL INPUTS ONLY)
  ===================================================== */
  const fields = {
    email: document.getElementById('student_email'),
    name: document.getElementById('student_name'),
    dob: document.getElementById('student_dob'),
    nationality: document.getElementById('student_nationality'),
    passportNumber: document.getElementById('student_passport'), // ✅ REAL TEXTBOX
    phone: document.getElementById('student_phone')
  };

  /* =====================================================
     SAFETY CHECK
  ===================================================== */
  if (!fields.email) {
    console.warn('Student autofill: email field not found');
    return;
  }

  const DEBOUNCE_DELAY = 500;
  let debounceTimer   = null;
  let emailConfirmed = false;
  let autofilled     = false;

  /* =====================================================
     EMAIL-ONLY LIVE SEARCH
  ===================================================== */
/* =====================================================
   EMAIL INPUT LISTENER (RESET + SEARCH)
===================================================== */
fields.email.addEventListener('input', () => {
  const email = fields.email.value.trim();

  // ⛔ Reset everything immediately on email change
  resetStudentFields();

  clearTimeout(debounceTimer);

  // Too short → do nothing, manual entry allowed
  if (email.length < 3) {
    return;
  }

  // ⏳ Debounced search
  debounceTimer = setTimeout(() => {
    searchByEmail(email);
  }, DEBOUNCE_DELAY);
});
function resetStudentFields() {
  autofilled = false;
  emailConfirmed = false;

  Object.entries(fields).forEach(([key, input]) => {
    if (!input) return;

    // Clear all except email
    if (key !== 'email') {
      input.value = '';
    }

    input.readOnly = false;
    input.style.backgroundColor = '#fff';
  });

  console.log('Student fields reset due to email change');
}

  /* =====================================================
     FETCH STUDENT BY EMAIL
  ===================================================== */
  function searchByEmail(email) {
    fetch('student-autofill.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email })
    })
      .then(res => res.json())
      .then(data => {
        if (!data || !data.possible_match || !data.student) return;
        autofillStudent(data.student);
      })
      .catch(err => console.error('Student autofill error:', err));
  }

  /* =====================================================
     AUTOFILL (SAFE & CLEAN)
  ===================================================== */
  function autofillStudent(student) {
    if (!student || autofilled) return;

    // Always overwrite email with full DB email
    if (student.email) {
      fields.email.value = student.email;
    }

   if (fields.name && (student.first_name || student.last_name)) {
  fields.name.value = [student.first_name, student.last_name]
    .filter(Boolean)
    .join(' ');

  // 🔔 FORCE SYNC EVENT
  fields.name.dispatchEvent(new Event('input', { bubbles: true }));
}

    if (fields.dob && student.dob) {
      fields.dob.value = student.dob;
    }

    if (fields.nationality && student.nationality) {
      fields.nationality.value = student.nationality;
    }

    if (fields.phone && student.phone_number) {
      fields.phone.value = student.phone_number;
    }

    // ✅ REAL PASSPORT NUMBER (TEXT FIELD)
    if (fields.passportNumber && student.passport_number) {
      fields.passportNumber.value = student.passport_number;
    }

    autofilled = true;
    confirmStudent();
  }

  /* =====================================================
     CONFIRM & LOCK
  ===================================================== */
  function confirmStudent() {
    if (emailConfirmed) return;

    emailConfirmed = true;
    lockFields();
    console.log('Student confirmed via email autofill');
  }

  /* =====================================================
     LOCK FIELDS (EXCEPT EMAIL)
  ===================================================== */
function lockFields() {
  Object.entries(fields).forEach(([key, input]) => {
    if (!input || key === 'email') return;

    // 🔓 If value is empty, user must type it
    if (!input.value || input.value.trim() === '') {
      input.readOnly = false;
      input.style.backgroundColor = '#fff';
      return;
    }

    // 🔒 Lock only autofilled fields
    input.readOnly = true;
    input.style.backgroundColor = '#f7f9fc';
  });
}


  /* =====================================================
     PUBLIC HELPER
  ===================================================== */
  window.isStudentConfirmed = () => emailConfirmed;

})();
</script>
<script>
/**
 * Package selection — draft uses <label> for native radio clicks; signed is view-only.
 */
(function () {
  'use strict';

  const wrap = document.getElementById('bcFeeWrap');
  if (!wrap) return;

  const readOnly = wrap.dataset.readonly === '1';

  function showDetailsFor(code) {
    document.querySelectorAll('.package-details').forEach(el => {
      el.style.display = 'none';
    });
    document.querySelectorAll('.package-item').forEach(el => {
      el.classList.remove('is-selected');
    });

    if (!code) return;

    const details = document.getElementById(code);
    const item = document.querySelector('.package-item[data-package-code="' + code + '"]');
    if (details) details.style.display = 'block';
    if (item) item.classList.add('is-selected');
  }

  function syncHiddenFields(radio) {
    if (!radio || readOnly) return;

    const codeHolder = document.getElementById('selected_package_code');
    const labelHolder = document.getElementById('selected_package_label');
    const label = radio.getAttribute('data-package-label')
      || radio.closest('.package-item')?.getAttribute('data-package-label')
      || '';

    if (codeHolder) codeHolder.value = radio.value;
    if (labelHolder) labelHolder.value = label;
  }

  function onSelected(radio) {
    if (!radio) return;
    showDetailsFor(radio.value);
    syncHiddenFields(radio);
  }

  window.showPkg = function (id) {
    showDetailsFor(id);
    if (!readOnly) {
      const radio = document.querySelector('input[name="package"][value="' + id + '"]');
      if (radio && !radio.disabled) {
        radio.checked = true;
        syncHiddenFields(radio);
      }
    }
  };

  document.querySelectorAll('input[name="package"]').forEach((radio) => {
    radio.addEventListener('change', () => {
      if (radio.checked) onSelected(radio);
    });

    if (radio.checked) {
      onSelected(radio);
    }
  });

  if (readOnly) {
    document.querySelectorAll('.package-item').forEach((item) => {
      item.addEventListener('click', () => {
        const code = item.getAttribute('data-package-code');
        if (code) showDetailsFor(code);
      });
    });
  }

  window.getSelectedPackage = function () {
    const radio = document.querySelector('input[name="package"]:checked');
    if (!radio) return null;

    return radio.getAttribute('data-package-label')
      || radio.closest('.package-item')?.getAttribute('data-package-label')
      || radio.closest('.package-item')?.querySelector('.package-label-text')?.textContent?.trim()
      || null;
  };
})();
</script>

<script>
(function () {
  'use strict';

  const source = document.getElementById('student_name');
  const target = document.getElementById('sig_student_name');

  if (!source || !target) return;

  const sync = () => {
    const val = source.value.trim();
    if (val && target.value !== val) {
      target.value = val;
    }
  };

  /* 1️⃣ Manual typing */
  source.addEventListener('input', sync);
  source.addEventListener('change', sync);

  /* 2️⃣ Programmatic autofill (MutationObserver) */
  const observer = new MutationObserver(sync);
  observer.observe(source, {
    attributes: true,
    attributeFilter: ['value']
  });

  /* 3️⃣ Initial page load / delayed autofill */
  setTimeout(sync, 200);
  setTimeout(sync, 600);
  setTimeout(sync, 1200);

})();
</script>
<script>
(function () {
  'use strict';

  const today = new Date();
  const isoDate = today.toISOString().slice(0, 10);
  const formatted = today.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });

  const xanderDate = document.getElementById('xander_date');
  const notaryDate = document.getElementById('notary_date');
  const studentDate = document.getElementById('sig_signed_date');

  if (xanderDate) xanderDate.textContent = formatted;
  if (notaryDate) notaryDate.textContent = formatted;
  if (studentDate) studentDate.value = isoDate;

})();
</script>

</body>
</html>
