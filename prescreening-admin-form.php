<?php
declare(strict_types=1);

session_start();
if (empty($_SESSION['id']) && empty($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/prescreening_schema.php';
require_once __DIR__ . '/helpers/prescreening_notify.php';

xander_ensure_prescreening_schema($conn);
$userId = 'user-' . time() . '-' . random_int(1000, 9999);
$stmt = $conn->prepare(
    "INSERT INTO prescreening_submissions (user_id, source, student_name, student_email, whatsapp_number, created_at)
     VALUES (?, 'admin', '', '', '', NOW())
     ON DUPLICATE KEY UPDATE user_id = user_id"
);
$stmt->bind_param('s', $userId);
$stmt->execute();
$stmt->close();
$docLabels = xander_prescreening_document_labels();
$prefill = [];
$asyncDocs = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin pre-screening form</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f4f6fb; }
    .wrap { max-width: 920px; margin: 0 auto; padding: 1.5rem 1rem 3rem; }
    .card-panel { background: #fff; border-radius: 12px; padding: 1.25rem; margin-bottom: 1rem; box-shadow: 0 2px 12px rgba(15,23,42,.06); }
    .card-panel h2 { font-size: 1rem; font-weight: 700; color: #1e3a8a; margin-bottom: .75rem; }
    .q-num { color: #f59e0b; font-weight: 600; }
  </style>
</head>
<body>
<div class="wrap">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Admin: complete pre-screening</h1>
    <a href="prescreening.php" class="btn btn-sm btn-outline-secondary">← Back</a>
  </div>
  <div id="statusBox" class="alert d-none"></div>
  <form id="prescreenForm" novalidate>
    <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId, ENT_QUOTES, 'UTF-8') ?>">
    <div class="card-panel">
      <h2>Student contact</h2>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Full name *</label>
          <input type="text" name="student_name" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Email</label>
          <input type="email" name="student_email" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">WhatsApp *</label>
          <input type="tel" name="whatsapp_number" class="form-control" required pattern="^\+[0-9\s\-().]{10,20}$">
        </div>
      </div>
    </div>
    <?php include __DIR__ . '/includes/prescreening_questions_form.php'; ?>
    <button type="submit" class="btn btn-primary btn-lg w-100" id="submitBtn">
      <i class="bi bi-check2-circle me-1"></i> Save & notify staff
    </button>
  </form>
</div>
<?php include __DIR__ . '/includes/prescreening_submit_overlay.php'; ?>
<script>
(function () {
  const form = document.getElementById('prescreenForm');
  const btn = document.getElementById('submitBtn');
  const userId = <?= json_encode($userId) ?>;
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
    }
  }
  document.querySelectorAll('.prescreen-doc-input').forEach(input => {
    input.addEventListener('change', async function () {
      const file = input.files && input.files[0];
      const docKey = input.dataset.docKey;
      const row = input.closest('.prescreen-doc-row');
      if (!file || !docKey || !row) return;
      const fd = new FormData();
      fd.append('user_id', userId);
      fd.append('doc_key', docKey);
      fd.append('file', file);
      uploadsInFlight.add(docKey);
      setDocStatus(row, 'uploading');
      input.disabled = true;
      try {
        const res = await fetch('upload_prescreening_document.php', { method: 'POST', body: fd, credentials: 'same-origin' });
        const data = await res.json();
        if (data.status !== 'success') throw new Error(data.message || 'Upload failed');
        row.querySelector('.prescreen-doc-path').value = data.path;
        setDocStatus(row, 'saved');
        input.value = '';
      } catch (err) {
        setDocStatus(row, 'error', err.message);
        input.disabled = false;
      } finally {
        uploadsInFlight.delete(docKey);
      }
    });
  });
  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    if (!form.checkValidity()) { form.reportValidity(); return; }
    if (uploadsInFlight.size > 0) { alert('Please wait — documents are still uploading.'); return; }
    btn.disabled = true;
    if (window.PrescreenSubmitUI) PrescreenSubmitUI.start('Saving pre-screening…');
    const fd = new FormData();
    form.querySelectorAll('input, select, textarea').forEach(el => {
      if (!el.name || el.disabled || el.type === 'file') return;
      if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
      fd.append(el.name, el.value);
    });
    try {
      const res = await fetch('save_prescreening.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const data = await res.json();
      if (data.status === 'success' || data.status === 'partial') {
        if (window.PrescreenSubmitUI) PrescreenSubmitUI.success(data.message || 'Saved.', 2500);
        else alert(data.message || 'Done');
      } else {
        if (window.PrescreenSubmitUI) PrescreenSubmitUI.hide();
        btn.disabled = false;
        alert(data.message || 'Failed');
      }
    } catch (err) {
      if (window.PrescreenSubmitUI) PrescreenSubmitUI.hide();
      btn.disabled = false;
      alert('Network error.');
    }
  });
})();
</script>
<style>
.prescreen-mini-spin{display:inline-block;width:14px;height:14px;margin-right:4px;border:2px solid #bfdbfe;border-top-color:#2563eb;border-radius:50%;animation:ps-spin .7s linear infinite;vertical-align:-2px}
@keyframes ps-spin{to{transform:rotate(360deg)}}
</style>
</body>
</html>
