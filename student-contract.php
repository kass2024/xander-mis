<?php
declare(strict_types=1);

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/site_session_bootstrap.php";

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
if (!isset($_GET['token']) || trim($_GET['token']) === '') {
    http_response_code(400);
    exit("Invalid contract link.");
}

$token = trim($_GET['token']);

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
if (!$contract) {
    http_response_code(404);
    exit("This contract link is invalid or expired.");
}

/**
 * 4. Contract state flag (DO NOT EXIT)
 */
$isSigned = ($contract['status'] === 'signed');
$selectedPackageCode = (string) ($contract['selected_package_code'] ?? '');

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
<title>Xander Global Scholars – Service Contract</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
/* =========================
   MODERN RESET
========================= */
*,
*::before,
*::after {
  box-sizing: border-box;
}

html {
  scroll-behavior: smooth;
}

body {
  margin: 0;
  background: #f4f6fb;
  color: #111827;
  font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  line-height: 1.7;
}

/* =========================
   PAGE WRAPPER
========================= */
.page-section {
  padding: 24px 12px;
}

/* =========================
   CONTRACT CARD
========================= */
.contract {
  max-width: 900px;
  margin: 0 auto;
  background: #ffffff;
  padding: 32px 28px;
  border-radius: 14px;
  box-shadow: 0 15px 40px rgba(15, 23, 42, 0.08);
  font-size: 15px;
}

/* =========================
   HEADERS
========================= */
.contract-title {
  font-size: 20px;
  font-weight: 800;
  text-align: center;
  margin-bottom: 6px;
}

.contract-subtitle {
  font-size: 14px;
  text-align: center;
  color: #475569;
  margin-bottom: 24px;
}

h2 {
  font-size: 16px;
  margin: 28px 0 10px;
  font-weight: 700;
}

/* =========================
   DIVIDERS
========================= */
.hr {
  height: 1px;
  background: linear-gradient(to right, transparent, #cbd5e1, transparent);
  margin: 24px 0;
}

/* =========================
   PARAGRAPHS
========================= */
p {
  margin: 8px 0;
}

/* =========================
   FORM GRID (RESPONSIVE)
========================= */
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px 20px;
  margin-top: 16px;
}

.form-grid.full {
  grid-template-columns: 1fr;
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}

/* =========================
   INPUTS
========================= */
input[type="text"],
input[type="email"],
input[type="date"],
input[type="tel"] {
  width: 100%;
  padding: 10px 12px;
  border-radius: 8px;
  border: 1.5px solid #cbd5e1;
  font-size: 14px;
  outline: none;
  background: #fff;
}

input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}

/* =========================
   CHECKBOX & RADIO
========================= */
label {
  cursor: pointer;
}

input[type="checkbox"],
input[type="radio"] {
  transform: scale(1.1);
  margin-right: 6px;
}

/* =========================
   PACKAGE BOXES
========================= */
.package-item {
  margin: 14px 0;
  padding: 14px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #f9fafb;
}

.package-label {
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 10px;
}

.package-details {
  margin-top: 8px;
  padding-left: 24px;
  display: none;
  color: #334155;
}

/* =========================
   SIGNATURE BLOCK
========================= */
.signature {
  margin-top: 32px;
}

.signature canvas {
  width: 100%;
  height: 140px;
}

/* =========================
   ACTION BUTTONS
========================= */
button {
  appearance: none;
  border: none;
  padding: 10px 18px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

#signContract {
  background: #2563eb;
  color: white;
}

#clearSignature {
  background: #e5e7eb;
}

/* =========================
   WARNING
========================= */
.contract-warning {
  margin-top: 14px;
  padding: 12px;
  background: #fff7ed;
  border-left: 4px solid #fb923c;
  border-radius: 6px;
}

/* =========================
   PRINT MODE (PDF SAFE)
========================= */
@media print {
  body {
    background: white;
  }

  .contract {
    box-shadow: none;
    border-radius: 0;
    padding: 0;
  }

  button {
    display: none;
  }
}
</style>


</head>

<body>

<?php include 'header.php'; ?>

<section class="page-section">


<div class="contract">

<div class="contract-title">
  XANDER GLOBAL SCHOLARS LTD Master International Employment, Education &<br>
  Immigration Services Agreement
</div>

<div class="contract-subtitle">
  (Africa, EU, UK, USA, Canada & Asia)
</div>


<p>
This Agreement (“Agreement”) is made and entered into on
<span class="line-sm"></span> (“Effective Date”), by and between:
</p>
<div class="hr"></div>
<h2>1. COMPANY</h2>

<p>
<strong>Xander Global Scholars Ltd</strong>, Rwanda registered company<br>
A platform of <strong>Xander Tech LLC</strong>, an Arizona-registered company<br>
Phone: +1 270 438 7305<br>
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
  <p style="margin-top:14px;">
    Client Type:
    <label style="margin-left:10px;">
      <input type="checkbox" name="client_type[]" value="Student"> Student
    </label>
    <label style="margin-left:10px;">
      <input type="checkbox" name="client_type[]" value="Professional"> Professional
    </label>
    <label style="margin-left:10px;">
      <input type="checkbox" name="client_type[]" value="Job Seeker"> Job Seeker
    </label>
    <label style="margin-left:10px;">
      <input type="checkbox" name="client_type[]" value="Visitor Visa Applicant">
      Visitor Visa Applicant
    </label>
  </p>

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

<?php
require_once __DIR__ . '/includes/contract_fee_packages.php';
renderContractFeePackagesSection($isSigned, $selectedPackageCode);
?>
<!-- PACKAGES_END -->
<div class="hr"></div>
<h2>6. PROCESSING TIMELINE</h2>

<p>
Estimated processing time is 2–4 months, depending on:
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
If the Job Seeker visa application is refused, the Client shall be entitled to a <strong>30% refund</strong> of the total amount paid. The refund will be processed within 2–4 months from the date of the official refusal decision.
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
the sole responsibility of the Client.
</p>
<div class="hr"></div>
<h2>9. DATA COLLECTION & CONSENT</h2>

<p>The Client authorizes the Company to collect, store, process, and use personal data for:</p>
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
Fraud may result in civil, administrative, or criminal penalties under
U.S., EU, UK, Canadian, and international laws.
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
  <p>Title: <strong>Owner / Managing Director</strong></p>

  <p>Signature:</p>
  <div style="border-bottom:1.5px solid #000; height:48px; position:relative;">
    <img
      src="assets/signatures/xander-signature.png"
      alt="Authorized Signature"
      style="max-height:42px; position:absolute; bottom:2px; left:0;"
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

  <div style="border:1.5px dashed #7a7a7a; height:140px; padding:6px;">
    <canvas class="signature-canvas"></canvas>
  </div>

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
    <button type="button" id="clearSignature">Clear</button>
    <button type="button" id="signContract">Sign & Submit</button>
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


</div>
</section>
<?php include __DIR__ . '/includes/contract_signing_overlay.php'; ?>

<?php include 'footer.php'; ?>

<script>
(() => {
  /* ==========================
     CONFIG & ELEMENTS
  ========================== */
  const canvas = document.querySelector('.signature-canvas');
  const ctx = canvas.getContext('2d');

  const btnClear = document.getElementById('clearSignature');
  const btnSubmit = document.getElementById('signContract');

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

  /* ==========================
     2. PACKAGE SELECTION (ARTICLE 7)
  ========================== */
  const selectedRadio = document.querySelector('input[name="package"]:checked');

  if (!selectedRadio) {
    alert("Please select one service package under Article 7 before signing.");
    return;
  }

  const selectedPackageLabel = selectedRadio
    .closest('label')
    ?.textContent
    ?.trim();

  if (!selectedPackageLabel) {
    alert("Invalid package selection. Please reselect your package.");
    return;
  }

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
    selectedPackageLabel
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
function submitSignature(signature, name, date, selectedPackage) {
  // 🚀 START SMART PROGRESS BAR
  startSubmitProgress();

  /* ==========================
     1. HARD SAFETY CHECKS
  ========================== */
  if (!signature || !name || !date || !selectedPackage) {
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
  selected_package_code: document.getElementById('selected_package_code')?.value || null,

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
  alert("Student email is required.");
  emailInput.focus();
  return;
}

if (!payload.selected_package_label) {
  alert("Selected package is missing. Please reselect a package under Article 7.");
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
 * =====================================================
 * PACKAGE SELECTION CONTROLLER (UNIVERSAL)
 * =====================================================
 * ✔ Works with onclick="showPkg('pxxx')"
 * ✔ Ensures ONLY ONE package is visible at a time
 * ✔ ID-agnostic (p501, p71, future-safe)
 * ✔ Backend-safe
 * ✔ No UI conflicts
 * =====================================================
 */

(function () {
  'use strict';

  /**
   * Hide ALL package detail blocks
   */
  function hideAllPackages() {
    document.querySelectorAll('.package-details').forEach(el => {
      el.style.display = 'none';
    });
  }

  /**
   * Show selected package + store selection
   * @param {string} id
   */
  window.showPkg = function (id) {
    hideAllPackages();

    const selected = document.getElementById(id);
    if (selected) {
      selected.style.display = 'block';
    }

    // Save selected package code for backend
    const holder = document.getElementById('selected_package_code');
    if (holder) {
      holder.value = id;
    }
  };

  /**
   * Optional helper: return selected package label
   */
  window.getSelectedPackage = function () {
    const radio = document.querySelector('input[name="package"]:checked');
    if (!radio) return null;

    const label = radio.closest('label');
    return label ? label.textContent.trim() : null;
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
  const formatted = today.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });

  // Static dates
  const xanderDate = document.getElementById('xander_date');
  const notaryDate = document.getElementById('notary_date');
  const studentDate = document.getElementById('sig_signed_date');

  if (xanderDate) xanderDate.textContent = formatted;
  if (notaryDate) notaryDate.textContent = formatted;
  if (studentDate) studentDate.value = formatted;

})();
</script>

</body>
</html>
