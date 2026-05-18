<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/burundi_contract_db.php';
require_once __DIR__ . '/includes/burundi_contract_assets.php';

xander_ensure_burundi_contract_tables($conn);

if (!isset($_GET['token']) || trim($_GET['token']) === '') {
    http_response_code(400);
    exit('Invalid contract link.');
}

$token = trim($_GET['token']);
$stmt = $conn->prepare('SELECT * FROM student_contracts_burundi WHERE contract_token = ? LIMIT 1');
$stmt->bind_param('s', $token);
$stmt->execute();
$contract = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$contract) {
    http_response_code(404);
    exit('This contract link is invalid or expired.');
}

$isSigned = ($contract['status'] === 'signed');
$student = null;
$prefillEmail = '';
$clientResidence = '';
$clientAddress = '';
$clientType = '';
$selectedPackageCode = (string) ($contract['selected_package_code'] ?? '');
$studentSignatureImg = null;
$sig = null;

if ($isSigned) {
    $stmt = $conn->prepare('SELECT student_name, student_email, signed_date, signature_image, client_residence, client_address, client_dob, client_nationality, client_passport, client_phone, client_type, effective_date FROM student_signatures_burundi WHERE contract_id = ? ORDER BY id DESC LIMIT 1');
    $cid = (int) $contract['id'];
    $stmt->bind_param('i', $cid);
    $stmt->execute();
    $sig = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($sig) {
        $studentSignatureImg = $sig['signature_image'];
        $clientResidence = (string) ($sig['client_residence'] ?? '');
        $clientAddress = (string) ($sig['client_address'] ?? '');
        $clientType = (string) ($sig['client_type'] ?? '');
        $sigStudentName = trim((string) ($sig['student_name'] ?? ''));
        $sigSignedDate = (string) ($sig['signed_date'] ?? '');
        $student = [
            'first_name' => $sigStudentName,
            'last_name' => '',
            'email' => (string) ($sig['student_email'] ?? ''),
            'dob' => (string) ($sig['client_dob'] ?? ''),
            'nationality' => (string) ($sig['client_nationality'] ?? ''),
            'passport_number' => (string) ($sig['client_passport'] ?? ''),
            'phone_number' => (string) ($sig['client_phone'] ?? ''),
        ];
    }
} elseif (!empty($contract['student_id'])) {
    $sid = (int) $contract['student_id'];
    $stmt = $conn->prepare('SELECT email FROM student_applications WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $sid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $prefillEmail = trim((string) ($row['email'] ?? ''));
}

$assets = xander_burundi_contract_paths();
$headerSrc = xander_burundi_img_src($assets['header'], false);
$footerSrc = xander_burundi_img_src($assets['footer'], false);
$effectiveDate = date('Y-m-d');
if ($isSigned && !empty($sig['effective_date'] ?? '')) {
    $effectiveDate = (string) $sig['effective_date'];
} elseif ($isSigned && !empty($contract['signed_at'])) {
    $effectiveDate = date('Y-m-d', strtotime((string) $contract['signed_at']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HEERA–Xander Client Contract | Xander Global Scholars</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Source+Serif+Pro:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/contract-modern.css">
<style>
:root{
  --ink:#0f172a; --muted:#475569; --border:#e2e8f0;
  --paper:#fff; --link:#1d4ed8; --soft:#f8fafc;
}
body{
  margin:0;
  padding:32px 16px 64px;
  background:linear-gradient(180deg,#eef2f7 0%,#e2e8f0 100%);
  font-family:Inter,"Segoe UI",system-ui,sans-serif;
  color:var(--ink);
  -webkit-font-smoothing:antialiased;
}
.burundi-sheet{
  max-width:980px; margin:0 auto;
  background:var(--paper);
  box-shadow:0 16px 48px rgba(15,23,42,.10);
  border-radius:16px;
  overflow:hidden;
}
.burundi-letterhead,.burundi-letterfoot{width:100%;line-height:0}
.burundi-letterhead img,.burundi-letterfoot img{width:100%;height:auto;display:block}
.burundi-inner{
  padding:56px 64px 48px;
  font-family:"Source Serif Pro",Georgia,"Times New Roman",serif;
  font-size:12.2pt; line-height:1.75; color:#000;
}
@media(max-width:768px){ .burundi-inner{padding:32px 24px} }
.bc-main-title{
  text-align:center; font-size:17pt; font-weight:700;
  text-transform:uppercase; margin:0 0 6pt;
  line-height:1.35; letter-spacing:0.02em;
}
.bc-subtitle{text-align:center;font-size:11.5pt;margin:0 0 22pt;color:var(--muted)}
.bc-h3{
  font-size:14pt; font-weight:700;
  margin:28pt 0 12pt;
  padding-bottom:6pt;
  border-bottom:2px solid var(--border);
  display:flex; align-items:center; gap:10pt;
}
.bc-h3::before{
  content:""; display:inline-block; width:4px; height:18px;
  background:linear-gradient(180deg,#1d4ed8,#2563eb); border-radius:2px; flex-shrink:0;
}
.bc-intro,.burundi-contract-body p{text-align:justify;margin:0 0 12pt}
.bc-list{margin:0 0 14pt 22pt;padding:0}
.bc-list li{margin-bottom:6pt}
.bc-and{text-align:center;font-weight:700;margin:18pt 0;color:var(--muted);letter-spacing:.08em}
.bc-client-grid{display:grid;gap:12pt}
.bc-client-grid label{display:block;font-size:12pt}
.bc-client-grid input,.bc-inline-input{
  border:none; border-bottom:1.6px solid #94a3b8;
  width:min(100%,440px); font:inherit; padding:6px 2px;
  background:transparent; transition:border-color .15s;
}
.bc-client-grid input:focus,.bc-inline-input:focus{
  outline:none; border-bottom-color:var(--link);
}
.bc-email-first{margin-bottom:4pt}
.bc-input-email{
  border-bottom-color:var(--link)!important;
  font-weight:600; color:var(--link);
  width:min(100%,480px)!important;
}
.bc-client-grid input[readonly]{background:#f7f9fc;color:#64748b}
.bc-client-type{display:flex;flex-wrap:wrap;gap:10px;margin:14pt 0}
.bc-check{
  font-weight:500; cursor:pointer;
  padding:8px 14px; border:1.5px solid #cbd5e1;
  border-radius:999px; background:#fff;
  font-size:11pt; transition:all .15s;
  display:inline-flex; align-items:center; gap:6px;
}
.bc-check:hover{ border-color:var(--link); background:#eef2ff; }
.bc-check:has(input:checked){
  border-color:var(--link); background:#eef2ff;
  color:#1e3a8a; font-weight:600;
}
.bc-fee-head{margin:16pt 0 8pt;font-weight:700}
.bc-fee-divider{color:#cbd5e1;margin:12pt 0}
.bc-fee-intro{margin-bottom:10pt}
.bc-selected-pkg-summary{
  margin-top:14pt; padding:14pt 16pt;
  background:#dcfce7; border:1.5px solid #86efac;
  border-radius:10px; color:#14532d;
}
.package-item{
  margin:14px 0; padding:16px 18px;
  border:1.5px solid #e2e8f0; border-radius:12px;
  background:#f8fafc; transition:all .15s;
}
.package-item:hover{border-color:#2563eb; background:#fff; box-shadow:0 4px 16px rgba(15,23,42,.08);}
.package-item:has(input[type=radio]:checked){
  border-color:#2563eb;
  background:linear-gradient(180deg,#fff,#eef2ff);
  box-shadow:0 4px 16px rgba(15,23,42,.08);
}
.package-label{font-weight:700;display:flex;align-items:flex-start;gap:10px;cursor:pointer;color:#0f172a}
.package-details{margin-top:10px;padding:10px 0 0 28px;display:none;font-size:11.5pt;border-top:1px dashed #e2e8f0}
.package-label input[type=radio]{margin-top:3px;flex-shrink:0;accent-color:#2563eb}
.bc-sig-grid{display:grid;grid-template-columns:1fr 1fr;gap:24pt;margin-top:16pt}
@media(max-width:768px){.bc-sig-grid{grid-template-columns:1fr}}
.bc-sig-block{
  font-size:11.5pt; padding:18pt;
  background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px;
}
.bc-xander-sig-wrap{
  border-bottom:1.5px solid #000;
  min-height:110px;
  position:relative;
  max-width:340px;
  margin:6pt 0 10pt;
  display:flex;
  align-items:flex-end;
  padding:6px 0;
}
.bc-xander-sig-img{
  max-height:105px;
  max-width:100%;
  height:auto;
  display:block;
  position:relative;
  bottom:auto; left:auto;
  filter:contrast(1.15) saturate(1.1);
  -webkit-filter:contrast(1.15) saturate(1.1);
}
.bc-sig-canvas-wrap{
  border:2px dashed #cbd5e1; height:130px;
  margin:8pt 0; border-radius:8px; background:#fff;
}
.signature-canvas{width:100%;height:130px;display:block;cursor:crosshair}
.bc-sig-img{max-height:128px;max-width:100%}
.bc-sig-actions{margin-top:12pt;display:flex;gap:10px;flex-wrap:wrap}
.bc-sig-actions button{
  font-family:Inter,system-ui,sans-serif;
  font-size:14px; font-weight:600;
  padding:11px 22px; border-radius:8px; border:none;
  cursor:pointer; transition:all .15s;
  display:inline-flex; align-items:center; gap:8px;
}
#clearSignature{background:#fff;color:#1e293b;border:1.5px solid #cbd5e1}
#clearSignature:hover{background:#f8fafc;border-color:#94a3b8}
#signContract{
  background:linear-gradient(135deg,#1d4ed8,#2563eb);
  color:#fff;
  box-shadow:0 4px 12px rgba(37,99,235,.28);
}
#signContract:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(37,99,235,.36)}
#signContract:disabled{background:#94a3b8;cursor:not-allowed;transform:none;box-shadow:none}
.bc-progress{margin-top:12pt}
.bc-progress-bar{height:8px;background:#e2e8f0;border-radius:999px;overflow:hidden}
#signatureProgressBar{height:100%;width:0;background:linear-gradient(90deg,#1d4ed8,#2563eb);transition:width .2s}
.footer-ref{text-align:center;margin-top:32pt;font-size:10.5pt;color:#94a3b8}
a{color:var(--link)}
@media print{body{background:#fff;padding:0}.burundi-sheet{box-shadow:none;border-radius:0}.bc-sig-actions,.bc-progress{display:none!important}}
</style>
</head>
<body class="xgs-contract-body">

<div class="xgs-contract-hero">
  <span class="xgs-hero-eyebrow">HEERA – Xander Client Contract</span>
  <h1 class="xgs-hero-title">Master International Employment, Education &amp; Immigration Services Agreement</h1>
  <p class="xgs-hero-sub">Burundi-specific edition – Africa, EU, UK, USA, Canada &amp; Asia coverage</p>
  <div class="xgs-hero-meta">
    <span>🤝 HEERA 10 (SURL) &amp; Xander Global Scholars</span>
    <span>🔒 E-Signed Agreement</span>
    <?php if ($isSigned): ?><span style="background:rgba(255,255,255,.18); color:#fff; border:1px solid rgba(255,255,255,.30); padding:4px 12px; border-radius:999px; font-weight:700;">✓ Signed</span><?php endif; ?>
  </div>
</div>

<div class="burundi-sheet">
  <?php if ($headerSrc): ?>
  <div class="burundi-letterhead"><img src="<?= htmlspecialchars($headerSrc, ENT_QUOTES, 'UTF-8') ?>" alt="Xander Global Scholars and HEERA 10 (SURL)"></div>
  <?php endif; ?>

  <div class="burundi-inner">
    <h1 class="bc-main-title">Xander Global Scholars Master International Employment, Education &amp; Immigration Services Agreement</h1>
    <p class="bc-subtitle">(Africa, EU, UK, USA, Canada &amp; Asia)</p>

    <?php include __DIR__ . '/contracts/burundi_contract_body.php'; ?>

    <?php
    $contractToken = $token;
    include __DIR__ . '/includes/burundi_contract_signatures.php';
    ?>
  </div>

  <?php if ($footerSrc): ?>
  <div class="burundi-letterfoot"><img src="<?= htmlspecialchars($footerSrc, ENT_QUOTES, 'UTF-8') ?>" alt="Contact footer"></div>
  <?php endif; ?>
</div>

<?php if (!$isSigned): ?>
<script>
(() => {
  'use strict';
  const fields = {
    email: document.getElementById('student_email'),
    name: document.getElementById('student_name'),
    dob: document.getElementById('student_dob'),
    nationality: document.getElementById('student_nationality'),
    passportNumber: document.getElementById('student_passport'),
    residence: document.getElementById('client_residence'),
    address: document.getElementById('client_address'),
    phone: document.getElementById('student_phone'),
  };
  if (fields.email) {
    const DEBOUNCE_DELAY = 500;
    let debounceTimer = null;
    let emailConfirmed = false;
    let autofilled = false;

    function resetStudentFields() {
      autofilled = false;
      emailConfirmed = false;
      Object.entries(fields).forEach(([key, input]) => {
        if (!input || key === 'email') return;
        input.value = '';
        input.readOnly = false;
        input.style.backgroundColor = '';
      });
    }

    function lockFields() {
      Object.entries(fields).forEach(([key, input]) => {
        if (!input || key === 'email') return;
        if (!input.value || input.value.trim() === '') {
          input.readOnly = false;
          input.style.backgroundColor = '';
          return;
        }
        input.readOnly = true;
        input.style.backgroundColor = '#f7f9fc';
      });
    }

    function autofillStudent(student) {
      if (!student || autofilled) return;
      if (student.email && fields.email) {
        fields.email.value = student.email;
      }
      if (fields.name && (student.first_name || student.last_name)) {
        fields.name.value = [student.first_name, student.last_name].filter(Boolean).join(' ');
        fields.name.dispatchEvent(new Event('input', { bubbles: true }));
      }
      if (fields.dob && student.dob) fields.dob.value = student.dob;
      if (fields.nationality && student.nationality) fields.nationality.value = student.nationality;
      if (fields.passportNumber && student.passport_number) fields.passportNumber.value = student.passport_number;
      if (fields.phone && student.phone_number) fields.phone.value = student.phone_number;
      if (fields.residence && student.country_residence) fields.residence.value = student.country_residence;
      if (fields.address && student.address) fields.address.value = student.address;
      autofilled = true;
      emailConfirmed = true;
      lockFields();
    }

    function searchByEmail(email) {
      fetch('student-autofill.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (!data || !data.possible_match || !data.student) return;
          autofillStudent(data.student);
        })
        .catch((err) => console.error('Student autofill error:', err));
    }

    fields.email.addEventListener('input', () => {
      const email = fields.email.value.trim();
      resetStudentFields();
      clearTimeout(debounceTimer);
      if (email.length < 3) return;
      debounceTimer = setTimeout(() => searchByEmail(email), DEBOUNCE_DELAY);
    });

    const initialEmail = fields.email.value.trim();
    if (initialEmail.length >= 3) {
      searchByEmail(initialEmail);
    }

    window.isStudentConfirmed = () => emailConfirmed;
  }
})();
</script>
<script>
(() => {
  const canvas = document.querySelector('.signature-canvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const btnClear = document.getElementById('clearSignature');
  const btnSubmit = document.getElementById('signContract');
  const inputName = document.getElementById('sig_student_name');
  const inputDate = document.getElementById('sig_signed_date');
  const hiddenSignature = document.getElementById('signatureData');
  const nameField = document.getElementById('student_name');
  let drawing = false;

  function resizeCanvas() {
    const ratio = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#000';
  }
  resizeCanvas();
  window.addEventListener('resize', resizeCanvas);

  function getPos(e) {
    const rect = canvas.getBoundingClientRect();
    if (e.touches) {
      return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
    }
    return { x: e.offsetX, y: e.offsetY };
  }
  function startDraw(e) { e.preventDefault(); drawing = true; const p = getPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); }
  function draw(e) {
    if (!drawing) return;
    e.preventDefault();
    const p = getPos(e);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
  }
  function stopDraw() { drawing = false; }

  canvas.addEventListener('mousedown', startDraw);
  canvas.addEventListener('mousemove', draw);
  canvas.addEventListener('mouseup', stopDraw);
  canvas.addEventListener('mouseleave', stopDraw);
  canvas.addEventListener('touchstart', startDraw, { passive: false });
  canvas.addEventListener('touchmove', draw, { passive: false });
  canvas.addEventListener('touchend', stopDraw);

  if (nameField && inputName) {
    inputName.value = nameField.value;
    nameField.addEventListener('input', () => { inputName.value = nameField.value; });
  }
  if (!inputDate.value) inputDate.value = new Date().toISOString().slice(0, 10);

  btnClear.addEventListener('click', () => ctx.clearRect(0, 0, canvas.width, canvas.height));

  function hasSignature() {
    const pixels = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
    return pixels.some(c => c !== 0);
  }

  window.showPkg = function (id) {
    document.querySelectorAll('.package-details').forEach(el => { el.style.display = 'none'; });
    const block = document.getElementById(id);
    if (block) block.style.display = 'block';
    const holder = document.getElementById('selected_package_code');
    if (holder) holder.value = id;
  };
  document.querySelectorAll('input[name="package"]').forEach(r => {
    if (r.checked) showPkg(r.value);
  });

  btnSubmit.addEventListener('click', async () => {
    const clientType = document.querySelector('input[name="client_type"]:checked');
    if (!clientType) { alert('Please select a Client Type.'); return; }
    const pkgRadio = document.querySelector('input[name="package"]:checked');
    if (!pkgRadio) { alert('Please select one fee package in Section 5.'); return; }
    const studentName = (inputName.value || nameField?.value || '').trim();
    if (!studentName) { alert('Please enter your full name.'); return; }
    const signedDate = inputDate.value;
    if (!signedDate) { alert('Please select the signing date.'); return; }
    if (!hasSignature()) { alert('Please draw your signature.'); return; }

    const email = document.getElementById('student_email')?.value?.trim() || '';
    const dob = document.getElementById('student_dob')?.value || '';
    const nationality = document.getElementById('student_nationality')?.value?.trim() || '';
    const passport = document.getElementById('student_passport')?.value?.trim() || '';
    const phone = document.getElementById('student_phone')?.value?.trim() || '';
    const residence = document.getElementById('client_residence')?.value?.trim() || '';
    const address = document.getElementById('client_address')?.value?.trim() || '';
    const effective = document.getElementById('effective_date')?.value || '';

    if (!email || !dob || !nationality || !phone || !residence || !address) {
      alert('Please complete all required client fields.');
      return;
    }

    const signature = canvas.toDataURL('image/png');
    hiddenSignature.value = signature;

    if (window.ContractSigningUI) {
      ContractSigningUI.start({ submitBtn: btnSubmit, message: 'Securing your signature…' });
    } else {
      btnSubmit.disabled = true;
    }

    const payload = {
      token: <?= json_encode($token) ?>,
      student_name: studentName,
      signed_date: signedDate,
      signature,
      student_email: email,
      student_dob: dob,
      student_nationality: nationality,
      student_passport: passport,
      student_phone: phone,
      client_residence: residence,
      client_address: address,
      client_type: clientType.value,
      effective_date: effective,
      selected_package_code: pkgRadio.value,
      selected_package_label: (pkgRadio.closest('label')?.textContent || '').trim()
    };

    try {
      if (window.ContractSigningUI) ContractSigningUI.setMessage('Saving contract & generating PDF…');
      const res = await fetch('submit-signature-burundi.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!res.ok || !data.success) {
        throw new Error(data.error || 'Signing failed');
      }
      if (window.ContractSigningUI) {
        ContractSigningUI.finishAndReload(
          data.message || 'Contract signed successfully. You can download your signed PDF below.',
          3000
        );
      } else {
        alert(data.message || 'Contract signed successfully.');
        location.reload();
      }
    } catch (err) {
      if (window.ContractSigningUI) ContractSigningUI.hide({ submitBtn: btnSubmit });
      else btnSubmit.disabled = false;
      alert(err.message || 'Signing failed. Please try again.');
    }
  });
})();
</script>
<?php endif; ?>

<?php if (!$isSigned): include __DIR__ . '/includes/contract_signing_overlay.php'; endif; ?>
</body>
</html>
