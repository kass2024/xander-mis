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
<style>
:root{--ink:#111827;--muted:#374151;--border:#d1d5db;--paper:#fff;--link:#1d4ed8}
body{margin:0;padding:32px 16px 48px;background:linear-gradient(180deg,#eef2f7,#e5e7eb);font-family:Inter,"Segoe UI",system-ui,sans-serif;color:var(--ink)}
.burundi-sheet{max-width:920px;margin:0 auto;background:var(--paper);box-shadow:0 10px 40px rgba(0,0,0,.08);border-radius:10px;overflow:hidden}
.burundi-letterhead,.burundi-letterfoot{width:100%;line-height:0}
.burundi-letterhead img,.burundi-letterfoot img{width:100%;height:auto;display:block}
.burundi-inner{padding:48px 64px 40px;font-family:Georgia,"Times New Roman",serif;font-size:12.2pt;line-height:1.75;color:#000}
@media(max-width:768px){.burundi-inner{padding:32px 24px}}
.bc-main-title{text-align:center;font-size:17pt;font-weight:700;text-transform:uppercase;margin:0 0 6pt;line-height:1.35}
.bc-subtitle{text-align:center;font-size:11.5pt;margin:0 0 22pt;color:var(--muted)}
.bc-h3{font-size:14pt;font-weight:700;margin:28pt 0 12pt}
.bc-intro,.burundi-contract-body p{text-align:justify;margin:0 0 12pt}
.bc-list{margin:0 0 14pt 22pt;padding:0}
.bc-list li{margin-bottom:6pt}
.bc-and{text-align:center;font-weight:700;margin:18pt 0}
.bc-client-grid{display:grid;gap:10pt}
.bc-client-grid label{display:block;font-size:12pt}
.bc-client-grid input,.bc-inline-input{border:none;border-bottom:1.6px solid #111;width:min(100%,420px);font:inherit;padding:4px 2px;background:transparent}
.bc-email-first{margin-bottom:4pt}
.bc-input-email{border-bottom-color:var(--link)!important;font-weight:600;color:var(--link);width:min(100%,480px)!important}
.bc-client-grid input[readonly]{background:#f7f9fc}
.bc-client-type{display:flex;flex-wrap:wrap;gap:12px;margin:12pt 0}
.bc-check{font-weight:600;cursor:pointer}
.bc-fee-head{margin:16pt 0 8pt;font-weight:700}
.bc-fee-divider{color:#9ca3af;margin:12pt 0}
.bc-fee-intro{margin-bottom:10pt}
.bc-selected-pkg-summary{margin-top:14pt;padding:12pt;background:#ecfdf5;border:1px solid #6ee7b7;border-radius:8px}
.package-item{margin:14px 0;padding:14px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb}
.package-label{font-weight:700;display:flex;align-items:flex-start;gap:10px;cursor:pointer}
.package-details{margin-top:8px;padding-left:28px;display:none;font-size:11.5pt}
.package-label input[type=radio]{margin-top:3px;flex-shrink:0}
.bc-sig-grid{display:grid;grid-template-columns:1fr 1fr;gap:24pt;margin-top:16pt}
@media(max-width:768px){.bc-sig-grid{grid-template-columns:1fr}}
.bc-sig-block{font-size:11.5pt}
.bc-sig-canvas-wrap{border:1px dashed #9ca3af;height:120px;margin:8pt 0}
.signature-canvas{width:100%;height:120px;display:block;cursor:crosshair}
.bc-sig-img{max-height:118px;max-width:100%}
.bc-sig-actions{margin-top:10pt;display:flex;gap:10px;flex-wrap:wrap}
.bc-sig-actions button{font-family:system-ui,sans-serif;font-size:14px;font-weight:600;padding:10px 18px;border-radius:6px;border:none;cursor:pointer}
#clearSignature{background:#f3f4f6}
#signContract{background:var(--link);color:#fff}
#signContract:disabled{background:#9ca3af}
.bc-progress{margin-top:10pt}
.bc-progress-bar{height:8px;background:#e5e7eb;border-radius:999px;overflow:hidden}
#signatureProgressBar{height:100%;width:0;background:#2563eb;transition:width .2s}
.footer-ref{text-align:center;margin-top:32pt;font-size:10.5pt;color:#6b7280}
a{color:var(--link)}
@media print{body{background:#fff;padding:0}.burundi-sheet{box-shadow:none;border-radius:0}.bc-sig-actions,.bc-progress{display:none!important}}
</style>
</head>
<body>
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
