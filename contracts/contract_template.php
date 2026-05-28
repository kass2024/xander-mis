<?php
/**
 * VARIABLES EXPECTED (UNCHANGED):
 * ------------------------------------------------
 * $admin
 * $tasks
 * $hasSignature
 * $signatureHtml
 * $signDate
 * $isPdf
 */
$isPdf = $isPdf ?? false;
$basePath = realpath(__DIR__);
/* =========================
   LETTERHEAD (PDF ONLY)
========================= */
$letterheadPath = realpath(__DIR__ . '/../assets/letterhead.png');

$letterheadBase64 = '';

if ($isPdf && file_exists($letterheadPath)) {
    $letterheadBase64 =
        'data:image/png;base64,' . base64_encode(
            file_get_contents($letterheadPath)
        );
}


/* Employer signature */
$employerSignaturePath = $basePath . '/employer-signature.png';
$employerSignatureBase64 = '';
if (file_exists($employerSignaturePath)) {
    $employerSignatureBase64 =
        'data:image/png;base64,' . base64_encode(file_get_contents($employerSignaturePath));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employment Agreement</title>

<style>
/* =====================================================
   FINAL PRODUCTION PDF CONTRACT – PRINT OPTIMIZED
===================================================== */

/* ---------- PAGE SETUP (A4 PRINT) ---------- */
@page {
  size: A4;
  margin: 22mm 22mm 26mm 22mm; /* balanced top/bottom */
}

/* ---------- BASE ---------- */
body {
  font-family: DejaVu Sans, Arial, sans-serif;
  font-size: 11.5pt;
  line-height: 1.6;
  color: #000;
  margin: 0;
  padding: 0;
}

/* ---------- LETTERHEAD ---------- */
.letterhead {
  width: 100%;
  margin-bottom: 6mm; /* tight, professional */
}

.letterhead img {
  width: 100%;
  height: auto;
  display: block;
}

/* ---------- CONTRACT BODY ---------- */
.contract {
  width: 100%;
  margin: 0;
  padding: 0;
}

/* ---------- TITLES ---------- */
h1 {
  font-size: 15.5pt;
  text-align: center;
  letter-spacing: 0.4px;
  margin: 0 0 3mm 0;
}

h2 {
  font-size: 11.5pt;
  text-align: center;
  font-weight: normal;
  margin: 0 0 7mm 0;
}

/* ---------- SECTIONS ---------- */
.section {
  margin-bottom: 5mm;
}

.section:last-of-type {
  margin-bottom: 3mm; /* helps signatures fit same page */
}

.section h3 {
  font-size: 10.5pt;
  text-transform: uppercase;
  margin-bottom: 2mm;
  padding-bottom: 1mm;
  border-bottom: 1px solid #000;
}

/* ---------- TEXT ---------- */
p {
  margin-bottom: 3mm;
  text-align: justify;
}

strong {
  font-weight: bold;
}

/* ---------- LISTS ---------- */
ul {
  margin-left: 6mm;
  padding-left: 2mm;
}

li {
  margin-bottom: 2mm;
}

/* ---------- DIVIDER ---------- */
hr {
  border: none;
  border-top: 1px solid #000;
  margin: 5mm 0;
}

/* ---------- SIGNATURES ---------- */
.signature-area {
  margin-top: 8mm;
  page-break-inside: avoid;   /* keep together */
  page-break-before: auto;   /* allow same page */
}

.signature-area h3 {
  margin-top: 0;
}

.signature-box {
  margin-top: 4mm;
}

.signature-box img {
  max-height: 20mm;
}

/* ---------- WEB PREVIEW ONLY ---------- */
<?php if (!$isPdf): ?>
body {
  background: #f4f6f9;
  padding: 40px 20px;
}

.contract {
  max-width: 900px;
  margin: auto;
  padding: 50px;
  background: #fff;
  box-shadow: 0 12px 30px rgba(0,0,0,0.12);
}
<?php endif; ?>
</style>


</head>

<body>
  <?php if ($isPdf && $letterheadBase64): ?>
  <div class="letterhead">
    <img src="<?= $letterheadBase64 ?>" alt="Company Letterhead">
  </div>
<?php endif; ?>


<?php
// ===============================
// HEADER (FULL WIDTH, PARENT DIR)
// ===============================
if (empty($isPdf)) {
    include __DIR__ . '/../header.php';
}
?>

<div class="page-wrapper">

  <div class="contract">
<h1>XANDER GLOBAL SCHOLARS LTD</h1>
<h2>EMPLOYMENT AGREEMENT<br>(Republic of Rwanda)</h2>

<p>
This Employment Agreement (“Agreement”) is made and entered into on
<strong><?= htmlspecialchars($signDate) ?></strong> (“Effective Date”), by and between:
</p>

<p><strong>Employer</strong><br>
Xander Global Scholars Ltd (XGS)<br>
Kigali, Rwanda<br>
Email: info@xanderglobalscholars.com | +1 270 438 7305<br>
Website: www.xanderglobalscholars.com<br>
(Hereinafter referred to as the <strong>“Company”</strong> or <strong>“Employer”</strong>)
</p>

<p><strong>AND</strong></p>

<p><strong>Employee</strong><br>
Full Name: <?= htmlspecialchars($admin['full_name']) ?><br>
National ID / Passport No: <?= htmlspecialchars($admin['national_id'] ?? 'N/A') ?><br>
(Hereinafter referred to as the <strong>“Employee”</strong>)
</p>

<p>The Employer and Employee shall collectively be referred to as the <strong>“Parties.”</strong></p>

<hr>

<div class="section">
<h3>1. POSITION & APPOINTMENT</h3>
<p>
The Employer hereby appoints the Employee to the position of:
</p>
<p><strong><?= htmlspecialchars($admin['position'] ?? 'Admission Advisor') ?></strong></p>
<p>
The Employee accepts this appointment and agrees to faithfully, diligently, and competently
perform all duties assigned by the Employer to the best of their abilities and in the best
interests of the Company.
</p>
</div>

<div class="section">
<h3>2. REPORTING STRUCTURE</h3>
<p>
The Employee shall report to:
Xander Global Scholars MIS Smart Report System, Company Board, and/or appointed supervisors.
</p>
</div>

<div class="section">
<h3>3. DUTIES & RESPONSIBILITIES</h3>
<p>The Employee shall perform duties including, but not limited to:</p>

<p><strong>A. Operations & Administration</strong></p>
<ul>
<li>Support daily office operations in Rwanda</li>
<li>Implement Company strategies and policies</li>
<li>Represent the Company professionally at all times</li>
<li>Ensure compliance with Company procedures and applicable laws</li>
</ul>

<p><strong>B. Recruitment & Student Support</strong></p>
<ul>
<li>Promote Company services ethically and transparently</li>
<li>Assist students and clients throughout application and enrollment</li>
<li>Maintain accurate records and reports</li>
<li>Immediately report any irregularities or concerns</li>
</ul>

<p><strong>C. Integrity & Confidentiality</strong></p>
<ul>
  <li>Maintain strict confidentiality of Company information</li>
  <li>Act honestly, loyally, and professionally at all times</li>
  <li>Avoid any conflict of interest</li>
</ul>

Any fraud, misrepresentation, or misuse of Company resources shall result in immediate
disciplinary action, including termination.
</p>
</div>

<div class="section">
  <h3>4. WORKING HOURS</h3>

  <p>
    The Employee shall work up to eight (8) hours per day, based on assigned duties.
    The Employee shall be entitled to one (1) full rest day per week, as scheduled
    by the Employer.
  </p>

  <p>
    <strong>Standard office hours (where applicable):</strong><br>
    Monday to Friday<br>
    9:00 AM – 5:00 PM<br>
    <strong>Total:</strong> 40 hours per week
  </p>
</div>
<div class="section">
  <h3>5. ATTENDANCE POLICY</h3>

  <p>
    The Employee shall not be absent from work without prior written approval
    from the Employer. Unauthorized absence may result in disciplinary action,
    salary deduction, or termination.
  </p>
</div>

<div class="section">
  <h3>6. TERM OF EMPLOYMENT & PROBATION</h3>

  <p>
    This Agreement shall be valid for twelve (12) months from the Effective Date.
    The first three (3) to six (6) months shall constitute a probationary period,
    during which performance, conduct, and compliance shall be evaluated.
  </p>

  <p>
    Upon successful completion of probation, employment may be confirmed,
    extended, or terminated in accordance with Rwandan labor law.
  </p>
</div>
<div class="section">
  <h3>7. SALARY & COMPENSATION</h3>

  <p>
    The Employee shall receive a gross monthly salary of:
    <strong>
      <?= htmlspecialchars($admin['salary_currency'] ?? 'RWF') ?>
      <?= number_format((float)($admin['monthly_salary'] ?? 0), 2) ?>
    </strong>.
  </p>

  <p>
    Salary shall be paid on or before the last working day of each month via
    bank transfer or mobile money. All statutory deductions shall apply as
    required by law.
  </p>

  <p>
    Performance incentives or commissions, if any, shall be determined
    separately by the Company.
  </p>
</div>

<div class="section">
  <h3>8. COMPANY SUPPORT & BENEFITS</h3>

  <p><strong>The Company shall provide:</strong></p>
  <ul>
    <li>Office internet access</li>
  </ul>

  <p><strong>The Company shall not provide:</strong></p>
  <ul>
    <li>Meals</li>
    <li>Transportation allowances (unless approved in writing)</li>
  </ul>
</div>
<div class="section">
  <h3>9. SOCIAL SECURITY (RSSB)</h3>

  <p>
    The Employer shall register the Employee with the Rwanda Social Security Board
    (RSSB). Mandatory contributions shall be deducted and remitted in accordance
    with Rwandan law.
  </p>
</div>

<div class="section">
  <h3>10. LEAVE ENTITLEMENTS (RWANDA LAW COMPLIANT)</h3>

  <ul>
    <li>Annual Leave: Minimum eighteen (18) working days after twelve (12) months of service</li>
    <li>Public Holidays: Fully paid</li>
    <li>Sick Leave: With valid medical certificate</li>
    <li>Maternity Leave: Twelve (12) weeks paid</li>
    <li>Paternity Leave: Four (4) working days paid</li>
    <li>Compassionate Leave: Subject to Company approval</li>
  </ul>
</div>

<div class="section">
  <h3>11. CONFIDENTIALITY</h3>

  <p>
    The Employee shall not disclose any confidential Company, client, student,
    or partner information during or after employment.
  </p>
</div>
<div class="section">
  <h3>12. TERMINATION</h3>

  <p>
    Either Party may terminate this Agreement with thirty (30) days’ written notice.
    Immediate termination may occur in cases of:
  </p>

  <ul>
    <li>Fraud</li>
    <li>Misconduct</li>
    <li>Negligence</li>
    <li>Breach of confidentiality</li>
    <li>Conflict of interest</li>
  </ul>

  <p>
    All Company property must be returned upon termination.
  </p>
</div>

<div class="section">
  <h3>13. INDEMNITY</h3>

  <p>
    The Employee agrees to indemnify and hold harmless the Company against any
    losses arising from misconduct, negligence, or breach of this Agreement.
  </p>
</div>

<div class="section">
  <h3>14. GOVERNING LAW</h3>

  <p>
    This Agreement shall be governed by and interpreted in accordance with the
    laws of the Republic of Rwanda.
  </p>
</div>

<div class="section">
  <h3>15. ENTIRE AGREEMENT</h3>

  <p>
    This Agreement constitutes the entire understanding between the Parties and
    supersedes all prior agreements, whether written or oral.
  </p>
</div>

<div class="signature-area">
  <h3>16. SIGNATURES</h3>

  <!-- =========================
       EMPLOYER SIGNATURE
  ========================== -->
  <div class="signature-box">
    <strong>FOR THE EMPLOYER</strong><br>
    Name: Jean de Dieu Hakizimana<br>
    Title: Managing Director<br>

    <?php if (!empty($employerSignatureBase64)): ?>
      <img src="<?= $employerSignatureBase64 ?>" alt="Employer Signature">
    <?php else: ?>
      <p class="notice">Employer signature on file.</p>
    <?php endif; ?>
  </div>

  <!-- =========================
       EMPLOYEE SIGNATURE
  ========================== -->
  <div class="signature-box">
    <strong>FOR THE EMPLOYEE</strong><br>
    Name: <?= htmlspecialchars($admin['full_name']) ?><br><br>

    <?php if ($hasSignature): ?>

      <!-- STORED SIGNATURE -->
      <?= $signatureHtml ?>

      <p class="notice">
        <strong>
          ☑ I confirm that I have read, understood, and agree to all terms of this Agreement.
        </strong>
      </p>

      <?php if (empty($isPdf)): ?>
        <!-- FINAL SIGN ACTION -->
        <form method="POST"
              action="sign-contract.php"
              onsubmit="return confirm(
                'This action will permanently sign this contract and generate a final PDF. Continue?'
              );">

          <button type="submit" class="btn btn-success">
            Finalize & Sign Contract
          </button>
        </form>
      <?php endif; ?>

    <?php else: ?>

      <?php if (empty($isPdf)): ?>
      <!-- SIGNATURE CREATION -->
      <div class="signature-card">

        <label><strong>Full Legal Name</strong></label>
        <input
          type="text"
          id="typed_name"
          value="<?= htmlspecialchars($admin['full_name']) ?>"
          style="width:100%;padding:8px;margin-bottom:10px"
        >

        <p><strong>Draw your signature</strong></p>

        <canvas
          id="signaturePad"
          style="border:2px dashed #888;width:100%;height:200px;"
        ></canvas>

        <input type="hidden" id="signature_base64">

        <div style="margin-top:10px">
          <button type="button" onclick="clearPad()" class="btn">
            Clear
          </button>
          <button
            type="button"
            onclick="saveSignatureAjax()"
            class="btn btn-primary"
          >
            Save Signature
          </button>
        </div>

        <p id="signMsg" class="notice" style="margin-top:10px"></p>

      </div>
      <?php else: ?>
        <p class="notice">Employee signature pending.</p>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</div>


<script>
(function () {
  'use strict';

  /* =========================
     ELEMENT REFERENCES
  ========================== */
  const canvas   = document.getElementById('signaturePad');
  const nameInput = document.getElementById('typed_name');
  const msgBox   = document.getElementById('signMsg');

  // If canvas does not exist (PDF mode or already signed)
  if (!canvas) return;

  const ctx = canvas.getContext('2d');

  /* =========================
     CANVAS SETUP
  ========================== */
  function resizeCanvas() {
    canvas.width  = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;
  }
  resizeCanvas();

  let drawing = false;

  function getPosition(e) {
    const rect = canvas.getBoundingClientRect();
    if (e.touches && e.touches[0]) {
      return {
        x: e.touches[0].clientX - rect.left,
        y: e.touches[0].clientY - rect.top
      };
    }
    return {
      x: e.clientX - rect.left,
      y: e.clientY - rect.top
    };
  }

  function startDraw(e) {
    e.preventDefault();
    drawing = true;
    const pos = getPosition(e);
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
  }

  function draw(e) {
    if (!drawing) return;
    e.preventDefault();
    const pos = getPosition(e);
    ctx.lineWidth   = 2;
    ctx.lineCap     = 'round';
    ctx.strokeStyle = '#000';
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
  }

  function stopDraw() {
    drawing = false;
  }

  /* =========================
     EVENT BINDINGS
  ========================== */
  canvas.addEventListener('mousedown', startDraw);
  canvas.addEventListener('mousemove', draw);
  canvas.addEventListener('mouseup', stopDraw);
  canvas.addEventListener('mouseleave', stopDraw);

  canvas.addEventListener('touchstart', startDraw, { passive: false });
  canvas.addEventListener('touchmove', draw, { passive: false });
  canvas.addEventListener('touchend', stopDraw);

  window.addEventListener('resize', resizeCanvas);

  /* =========================
     PUBLIC HELPERS
  ========================== */
  window.clearPad = function () {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
  };

  function setMessage(text, color = '#555') {
    if (!msgBox) return;
    msgBox.innerHTML = `<span style="color:${color}">${text}</span>`;
  }

  /* =========================
     AUTO SIGN FLOW
  ========================== */
  window.saveSignatureAjax = async function () {

    const fullName = nameInput ? nameInput.value.trim() : '';
    if (!fullName) {
      alert('Full legal name is required');
      return;
    }

    const signatureData = canvas.toDataURL('image/png');

    try {
      /* ---------- STEP 1: SAVE SIGNATURE ---------- */
      setMessage('Saving signature…');

      const saveResponse = await fetch('create-signature.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:
          'typed_name=' + encodeURIComponent(fullName) +
          '&signature_base64=' + encodeURIComponent(signatureData)
      });

      const saveResult = await saveResponse.json();
      if (!saveResult.success) {
        throw saveResult.message || 'Signature save failed';
      }

      /* ---------- STEP 2: SIGN CONTRACT ---------- */
      setMessage('Signing contract…', 'green');

      const signResponse = await fetch('sign-contract.php', {
        method: 'POST'
      });

      if (!signResponse.ok) {
        throw 'Contract signing failed';
      }

      /* ---------- STEP 3: OPEN PDF ---------- */
      setMessage('Contract signed. Opening PDF…', 'green');

     window.open(
  '/contracts/signed/contract_<?= (int)$admin["id"] ?>.pdf',
  '_blank'
);


      // Optional UI refresh
      setTimeout(() => location.reload(), 1500);

    } catch (err) {
      console.error(err);
      setMessage(err.toString(), 'red');
    }
  };

})();
</script>
</div> <!-- /.contract -->

</div> <!-- /.page-wrapper -->

<?php
// ===============================
// FOOTER (FULL WIDTH, PARENT DIR)
// ===============================
if (empty($isPdf)) {
    include __DIR__ . '/../footer.php';
}
?>

</body>
</html>
