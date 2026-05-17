<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/prescreening_schema.php';
require_once __DIR__ . '/helpers/prescreening_invite.php';

xander_ensure_prescreening_schema($conn);

$token = trim((string) ($_GET['t'] ?? ''));
$invite = $token !== '' ? xander_prescreening_load_invite_by_token($conn, $token) : null;
$submittedRow = $token !== '' ? xander_prescreening_submission_by_invite_token($conn, $token) : null;

if (!$invite && !$submittedRow) {
    http_response_code(404);
    exit('This pre-screening link is invalid or expired.');
}

$completed = $submittedRow !== null
    || ($invite !== null && xander_prescreening_user_has_submission($conn, (string) $invite['user_id']));
$prefill = $invite ?? $submittedRow;
$asyncDocs = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pre-screening — Xander Global Scholars</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(160deg, #eef2ff 0%, #f8fafc 50%); min-height: 100vh; font-family: 'Segoe UI', system-ui, sans-serif; }
    .wrap { max-width: 820px; margin: 0 auto; padding: 1.5rem 1rem 3rem; }
    .hero { text-align: center; margin-bottom: 1.25rem; }
    .hero h1 { font-size: 1.45rem; font-weight: 700; color: #0f172a; }
    .card-panel { background: #fff; border-radius: 14px; box-shadow: 0 4px 24px rgba(15,23,42,.07); padding: 1.25rem 1.35rem; margin-bottom: 1rem; }
    .card-panel h2 { font-size: 1rem; font-weight: 700; color: #1e3a8a; margin-bottom: .85rem; }
    .q-num { color: #f59e0b; font-weight: 600; }
    .btn-submit { background: linear-gradient(135deg, #1d4ed8, #2563eb); border: none; font-weight: 600; padding: .75rem 1.5rem; }
    .done-box { text-align: center; padding: 2.5rem 1rem; }
    .prescreen-doc-status.uploading { color: #2563eb !important; }
    .prescreen-mini-spin {
      display: inline-block; width: 14px; height: 14px; margin-right: 4px;
      border: 2px solid #bfdbfe; border-top-color: #2563eb; border-radius: 50%;
      animation: ps-spin .7s linear infinite; vertical-align: -2px;
    }
    @keyframes ps-spin { to { transform: rotate(360deg); } }
  </style>
</head>
<body>
<div class="wrap">
  <div class="hero">
    <h1><i class="bi bi-clipboard2-check text-primary"></i> Pre-screening</h1>
    <p class="text-muted mb-0 small">Xander Global Scholars</p>
  </div>

  <?php if ($completed): ?>
    <div class="card-panel done-box">
      <i class="bi bi-check-circle-fill text-success" style="font-size:3rem"></i>
      <h2 class="mt-3">Thank you</h2>
      <p class="text-muted">Your pre-screening was submitted on <?= htmlspecialchars((string) ($submittedRow['submitted_at'] ?? $prefill['submitted_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>.</p>
    </div>
  <?php else: ?>
    <form id="studentForm" novalidate>
      <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) $invite['user_id'], ENT_QUOTES, 'UTF-8') ?>">

      <div class="card-panel prescreen-contact-readonly">
        <h2>Your details</h2>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Full name</label>
            <input type="text" name="student_name" class="form-control" readonly
                   value="<?= htmlspecialchars((string) ($invite['student_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Email</label>
            <input type="email" name="student_email" class="form-control" readonly
                   value="<?= htmlspecialchars((string) ($invite['student_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">WhatsApp</label>
            <input type="tel" name="whatsapp_number" class="form-control" readonly
                   value="<?= htmlspecialchars((string) ($invite['whatsapp_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>
      </div>

      <?php
      $hideContactOnWork = false;
      include __DIR__ . '/includes/prescreening_questions_form.php';
      ?>

      <button type="submit" class="btn btn-primary btn-submit w-100" id="submitBtn">
        <i class="bi bi-send me-1"></i> Submit pre-screening
      </button>
    </form>
  <?php endif; ?>
</div>

<?php if (!$completed): ?>
<?php include __DIR__ . '/includes/prescreening_submit_overlay.php'; ?>
<script>
(function () {
  const form = document.getElementById('studentForm');
  const btn = document.getElementById('submitBtn');
  const token = <?= json_encode($token) ?>;
  const uploadsInFlight = new Set();

  function setDocStatus(row, state, text) {
    const el = row.querySelector('.prescreen-doc-status');
    if (!el) return;
    el.classList.remove('text-success', 'text-muted', 'text-danger', 'uploading');
    if (state === 'uploading') {
      el.classList.add('uploading');
      el.innerHTML = '<span class="prescreen-mini-spin"></span> Uploading…';
    } else if (state === 'saved') {
      el.classList.add('text-success');
      el.innerHTML = '<i class="bi bi-check-circle-fill"></i> Saved';
    } else if (state === 'error') {
      el.classList.add('text-danger');
      el.innerHTML = '<i class="bi bi-exclamation-circle"></i> ' + (text || 'Failed');
    } else {
      el.classList.add('text-muted');
      el.innerHTML = '<span class="prescreen-doc-status-idle">Optional</span>';
    }
  }

  document.querySelectorAll('.prescreen-doc-input').forEach(input => {
    input.addEventListener('change', async function () {
      const file = input.files && input.files[0];
      const docKey = input.dataset.docKey;
      const row = input.closest('.prescreen-doc-row');
      if (!file || !docKey || !row) return;

      const fd = new FormData();
      fd.append('token', token);
      fd.append('doc_key', docKey);
      fd.append('file', file);

      uploadsInFlight.add(docKey);
      setDocStatus(row, 'uploading');
      input.disabled = true;

      try {
        const res = await fetch('upload_prescreening_document.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.status !== 'success' || !data.path) {
          throw new Error(data.message || 'Upload failed');
        }
        const hidden = row.querySelector('.prescreen-doc-path');
        if (hidden) hidden.value = data.path;
        setDocStatus(row, 'saved');
        input.value = '';
      } catch (err) {
        setDocStatus(row, 'error', err.message || 'Upload failed');
        input.disabled = false;
      } finally {
        uploadsInFlight.delete(docKey);
      }
    });
  });

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    const serviceSel = form.querySelector('select[name="service_type"]');
    if (serviceSel && !serviceSel.value) {
      serviceSel.reportValidity();
      return;
    }
    if (uploadsInFlight.size > 0) {
      alert('Please wait — documents are still uploading.');
      return;
    }

    btn.disabled = true;
    if (window.PrescreenSubmitUI) {
      PrescreenSubmitUI.start('Saving your answers…');
    }

    const fd = new FormData();
    form.querySelectorAll('input, select, textarea').forEach(el => {
      if (!el.name || el.disabled) return;
      if (el.type === 'file') return;
      if (el.type === 'checkbox' || el.type === 'radio') {
        if (el.checked) fd.append(el.name, el.value);
        return;
      }
      fd.append(el.name, el.value);
    });

    try {
      const res = await fetch('submit_prescreening_student.php', { method: 'POST', body: fd });
      const raw = await res.text();
      let data = null;
      try {
        data = raw ? JSON.parse(raw) : null;
      } catch (parseErr) {
        if (res.ok) {
          if (window.PrescreenSubmitUI) {
            PrescreenSubmitUI.success('Thank you! Your pre-screening has been submitted.', 4000);
          } else {
            alert('Submitted successfully.');
            location.reload();
          }
          return;
        }
      }
      if (data && data.status === 'success') {
        const ref = data.reference ? ' Reference: ' + data.reference + '.' : '';
        if (window.PrescreenSubmitUI) {
          PrescreenSubmitUI.success((data.message || 'Thank you! Your pre-screening has been submitted.') + ref, 4000);
        } else {
          alert((data.message || 'Submitted successfully.') + ref);
          location.reload();
        }
      } else if (res.ok) {
        if (window.PrescreenSubmitUI) {
          PrescreenSubmitUI.success('Thank you! Your pre-screening has been submitted.', 4000);
        } else {
          location.reload();
        }
      } else {
        if (window.PrescreenSubmitUI) PrescreenSubmitUI.hide();
        btn.disabled = false;
        alert((data && data.message) ? data.message : 'Submission failed.');
      }
    } catch (err) {
      if (window.PrescreenSubmitUI) PrescreenSubmitUI.hide();
      btn.disabled = false;
      alert('Network error. Please try again.');
    }
  });
})();
</script>
<?php endif; ?>
</body>
</html>
