<?php
declare(strict_types=1);

session_start();
if (empty($_SESSION['id']) && empty($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/prescreening_schema.php';

xander_ensure_prescreening_table($conn);

$userId = 'user-' . time() . '-' . random_int(1000, 9999);
if (!empty($_GET['id']) && preg_match('/^user-[0-9]+-[0-9]+$/', (string) $_GET['id'])) {
    $userId = (string) $_GET['id'];
}

$docLabels = [
    'doc_valid_passport' => 'Valid Passport',
    'doc_degree_transcripts' => 'Degree / Academic Transcripts',
    'doc_high_school' => 'High School Certificate',
    'doc_cv_resume' => 'CV / Resume',
    'doc_recommendation' => 'Recommendation Letter(s)',
    'doc_personal_statement' => 'Personal Statement / Motivation Letter',
    'doc_english_certificate' => 'English Proficiency Certificate',
    'doc_birth_certificate' => 'Birth Certificate',
    'doc_payment_proof' => 'Application / Payment Proof',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pre-screening</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f4f6fb; font-family: 'Segoe UI', system-ui, sans-serif; }
    .wrap { max-width: 920px; margin: 0 auto; padding: 1.5rem 1rem 3rem; }
    .card-panel { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(15,23,42,.08); padding: 1.5rem; margin-bottom: 1.25rem; }
    .card-panel h2 { font-size: 1.15rem; color: #0b1f3f; margin-bottom: 1rem; border-bottom: 2px solid #f0a500; padding-bottom: .5rem; }
    .q-num { color: #f0a500; font-weight: 600; margin-right: .35rem; }
    .btn-whatsapp { background: #25d366; border: none; color: #fff; font-weight: 600; }
    .btn-whatsapp:hover { background: #1da851; color: #fff; }
    #statusBox { display: none; }
    details summary { list-style: none; }
    details summary::-webkit-details-marker { display: none; }
  </style>
</head>
<body>
<div class="wrap">
  <div class="mb-3">
    <h1 class="h3 text-primary fw-bold"><i class="bi bi-clipboard-check me-2"></i>Quick Pre-screening</h1>
    <p class="text-muted mb-0">Start from the sidebar: send a WhatsApp <strong>template invite</strong>. The student replies <strong>START</strong>, answers 15 questions, and uploads 9 documents (same as web). Results go to staff WhatsApp <strong>+1 270 438 7305</strong> and <strong>+254 711 807 646</strong>.</p>
    <p class="text-muted small mt-2 mb-0">Meta webhook stays on <strong>xanderbot.site</strong>; pre-screening data is saved on this server (cPanel). Setup: <a href="api/webhook-health.php" target="_blank">webhook-health</a> · <a href="api/prescreening-invite-log.php" target="_blank">invite log</a></p>
  </div>

  <div id="statusBox" class="alert" role="alert"></div>

  <div class="card-panel border border-success border-2">
    <h2><i class="bi bi-whatsapp text-success me-1"></i> Step 1 — Send WhatsApp template invite</h2>
    <p class="text-muted small">Approve template <code>xander_prescreening_invite</code> in Meta. Student replies <strong>START</strong> to begin. WhatsApp number must include <strong>country code</strong> (e.g. +250…, +1…, +44…, +234…).</p>
    <form id="inviteForm" class="row g-3 align-items-end">
      <div class="col-md-5">
        <label class="form-label">Student name (template greeting)</label>
        <input type="text" name="student_name" class="form-control" placeholder="Full name">
      </div>
      <div class="col-md-5">
        <label class="form-label">Student WhatsApp <span class="text-danger">*</span></label>
        <input type="tel" name="whatsapp_number" class="form-control" placeholder="Student +250… (not staff +254711…)" required pattern="^\+[0-9\s\-().]{10,20}$" title="Student personal WhatsApp with +country code">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-whatsapp w-100" id="inviteBtn"><i class="bi bi-send me-1"></i>Send</button>
      </div>
    </form>
  </div>

  <details class="card-panel">
    <summary class="h2 mb-0" style="cursor:pointer"><i class="bi bi-ui-checks me-1"></i> Step 2 — Web form (admin fills)</summary>

  <form id="prescreenForm" enctype="multipart/form-data" novalidate class="mt-3">
    <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId, ENT_QUOTES, 'UTF-8') ?>">

    <div class="card-panel">
      <h2>Student contact</h2>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Student full name <span class="text-danger">*</span></label>
          <input type="text" name="student_name" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Student email</label>
          <input type="email" name="student_email" class="form-control" placeholder="student@example.com">
        </div>
        <div class="col-md-4">
          <label class="form-label">Student WhatsApp number <span class="text-danger">*</span></label>
          <input type="tel" name="whatsapp_number" class="form-control" placeholder="+country code & number" required pattern="^\+[0-9\s\-().]{10,20}$" title="Include + and country code">
          <small class="text-muted">International format with + and country code (any country).</small>
        </div>
      </div>
    </div>

    <div class="card-panel">
      <h2>Pre-screening questions</h2>
      <div class="mb-3">
        <label class="form-label"><span class="q-num">1.</span> Highest level of education? <span class="text-danger">*</span></label>
        <input type="text" name="education_level" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label"><span class="q-num">2.</span> Course or program? <span class="text-danger">*</span></label>
        <input type="text" name="course_program" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label"><span class="q-num">3.</span> Country of interest? <span class="text-danger">*</span></label>
        <input type="text" name="country_interest" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label"><span class="q-num">4.</span> Open to India, Cyprus, Malta (under $15k/year)?</label>
        <textarea name="open_other_countries" class="form-control" rows="2"></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label"><span class="q-num">5.</span> Tuition budget per year? <span class="text-danger">*</span></label>
        <input type="text" name="budget_tuition" class="form-control" required>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label"><span class="q-num">6.</span> Funds for application/visa fees? <span class="text-danger">*</span></label>
          <select name="funds_application_visa" class="form-select" required>
            <option value="">— Select —</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label"><span class="q-num">7.</span> Sponsor? <span class="text-danger">*</span></label>
          <select name="sponsor" class="form-select" required>
            <option value="">— Select —</option>
            <option value="Self">Self</option>
            <option value="Parent">Parent</option>
            <option value="Sponsor">Sponsor</option>
          </select>
        </div>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label"><span class="q-num">8.</span> Afford deposit and accommodation? <span class="text-danger">*</span></label>
          <select name="afford_deposit" class="form-select" required>
            <option value="">— Select —</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label"><span class="q-num">9.</span> Valid passport? <span class="text-danger">*</span></label>
          <select name="has_valid_passport" class="form-select" required>
            <option value="">— Select —</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
          </select>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label"><span class="q-num">10.</span> Academic documents ready? <span class="text-danger">*</span></label>
        <select name="academic_docs_ready" class="form-select" required>
          <option value="">— Select —</option>
          <option value="Yes">Yes</option>
          <option value="No">No</option>
          <option value="Partially">Partially</option>
        </select>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label"><span class="q-num">11.</span> English level? <span class="text-danger">*</span></label>
          <select name="english_level" class="form-select" required>
            <option value="">— Select —</option>
            <option value="Basic">Basic</option>
            <option value="Good">Good</option>
            <option value="Test done">Test done</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label"><span class="q-num">12.</span> IELTS/TOEFL/Duolingo?</label>
          <input type="text" name="english_test_taken" class="form-control">
        </div>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label"><span class="q-num">13.</span> Ever denied a visa? <span class="text-danger">*</span></label>
          <select name="visa_denied" class="form-select" required>
            <option value="">— Select —</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label"><span class="q-num">14.</span> Planned intake? <span class="text-danger">*</span></label>
          <input type="text" name="planned_intake" class="form-control" required>
        </div>
      </div>
      <div class="mb-0">
        <label class="form-label"><span class="q-num">15.</span> Ready to apply now? <span class="text-danger">*</span></label>
        <select name="ready_to_apply" class="form-select" required>
          <option value="">— Select —</option>
          <option value="Yes">Yes</option>
          <option value="No">No</option>
        </select>
      </div>
    </div>

    <div class="card-panel">
      <h2>Documents (email and WhatsApp)</h2>
      <?php foreach ($docLabels as $key => $label): ?>
        <div class="mb-3">
          <label class="form-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
          <input type="file" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
        </div>
      <?php endforeach; ?>
    </div>

    <div class="d-flex flex-wrap gap-2">
      <button type="submit" class="btn btn-whatsapp btn-lg" id="submitBtn">
        <i class="bi bi-whatsapp me-1"></i> Send to WhatsApp and Email
      </button>
      <a href="prescreening-report.php" class="btn btn-outline-secondary" target="_top">View submissions</a>
    </div>
  </form>
  </details>
</div>

<script>
(function () {
  const statusBox = document.getElementById('statusBox');
  function showStatus(kind, msg) {
    statusBox.style.display = 'block';
    statusBox.className = 'alert alert-' + kind;
    statusBox.textContent = msg;
  }

  const inviteForm = document.getElementById('inviteForm');
  const inviteBtn = document.getElementById('inviteBtn');
  if (inviteForm && inviteBtn) {
    inviteForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      if (!inviteForm.checkValidity()) { inviteForm.reportValidity(); return; }
      inviteBtn.disabled = true;
      try {
        const res = await fetch('send_prescreening_invite.php', { method: 'POST', body: new FormData(inviteForm), credentials: 'same-origin' });
        const raw = await res.text();
        let data;
        try { data = JSON.parse(raw); } catch (e) {
          showStatus('danger', 'Server error (not JSON). Check cPanel error log or api/prescreening-invite-log.php');
          console.error(raw);
          return;
        }
        let msg = data.message || 'Done.';
        if (data.status === 'success' && data.to) {
          msg += ' → +' + String(data.to).replace(/^\+/, '');
        }
        if (data.status === 'error' && data.log_url) {
          msg += ' See invite log.';
        }
        showStatus(data.status === 'success' ? 'success' : 'danger', msg);
        if (data.status === 'success') inviteForm.reset();
      } catch (err) {
        showStatus('danger', 'Network error: ' + (err.message || ''));
      } finally {
        inviteBtn.disabled = false;
      }
    });
  }

  const form = document.getElementById('prescreenForm');
  const submitBtn = document.getElementById('submitBtn');
  if (!form || !submitBtn) return;

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    if (!form.checkValidity()) { form.reportValidity(); return; }
    submitBtn.disabled = true;
    try {
      const res = await fetch('save_prescreening.php', { method: 'POST', body: new FormData(form), credentials: 'same-origin' });
      const data = await res.json();
      let msg = data.message || 'Done.';
      if (data.reference) msg += ' Reference: ' + data.reference;
      showStatus(data.status === 'success' ? 'success' : (data.status === 'partial' ? 'warning' : 'danger'), msg);
      if (data.status === 'success') {
        form.reset();
        document.querySelector('input[name="user_id"]').value = 'user-' + Date.now() + '-' + Math.floor(1000 + Math.random() * 9000);
      }
    } catch (err) {
      showStatus('danger', 'Network error.');
    } finally {
      submitBtn.disabled = false;
    }
  });
})();
</script>
</body>
</html>
