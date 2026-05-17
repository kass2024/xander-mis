<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/prescreening_schema.php';
require_once __DIR__ . '/helpers/prescreening_access.php';

xander_prescreening_require_superadmin();

xander_ensure_prescreening_schema($conn);
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
    :root { --brand: #1d4ed8; --wa: #25d366; --ink: #0f172a; }
    body { background: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; }
    .wrap { max-width: 720px; margin: 0 auto; padding: 1.25rem 1rem 2.5rem; }
    .top { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
    .top h1 { font-size: 1.35rem; font-weight: 700; color: var(--ink); margin: 0; }
    .card { background: #fff; border-radius: 14px; box-shadow: 0 2px 16px rgba(15,23,42,.06); padding: 1.25rem; border: 1px solid #e2e8f0; }
    .channel-tabs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 1rem; }
    .channel-tabs input { position: absolute; opacity: 0; pointer-events: none; }
    .channel-tabs label {
      display: flex; align-items: center; justify-content: center; gap: 6px;
      padding: 10px 8px; border-radius: 10px; border: 2px solid #e2e8f0;
      font-size: .85rem; font-weight: 600; cursor: pointer; transition: .15s;
    }
    .channel-tabs input:checked + label { border-color: var(--brand); background: #eff6ff; color: var(--brand); }
    .channel-tabs input[value="whatsapp"]:checked + label { border-color: var(--wa); background: #ecfdf5; color: #15803d; }
    .channel-tabs input[value="both"]:checked + label { border-color: #7c3aed; background: #f5f3ff; color: #6d28d9; }
    .link-box {
      display: none; margin-top: 1rem; padding: 12px; background: #f8fafc;
      border-radius: 10px; border: 1px dashed #cbd5e1;
    }
    .link-box.show { display: block; }
    .link-box input { font-size: .8rem; font-family: ui-monospace, monospace; }
    .btn-send { font-weight: 600; padding: .65rem 1.25rem; }
    .btn-wa { background: var(--wa); border: none; color: #fff; }
    .btn-wa:hover { background: #1da851; color: #fff; }
    .btn-email { background: var(--brand); border: none; color: #fff; }
    .btn-email:hover { background: #1e40af; color: #fff; }
    .btn-both { background: #6d28d9; border: none; color: #fff; }
    .btn-both:hover { background: #5b21b6; color: #fff; }
    #statusBox { display: none; margin-bottom: 1rem; }
    .foot-links { margin-top: 1rem; display: flex; flex-wrap: wrap; gap: 10px; font-size: .85rem; }
    .foot-links a { color: #64748b; text-decoration: none; }
    .foot-links a:hover { color: var(--brand); }
    details.admin-form { margin-top: 1rem; }
    details.admin-form summary { cursor: pointer; font-weight: 600; color: #475569; font-size: .9rem; }
  </style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <h1><i class="bi bi-lightning-charge-fill text-warning"></i> Pre-screening</h1>
    <a href="prescreening-report.php" class="btn btn-sm btn-outline-primary" target="_top">Submissions</a>
  </div>

  <div id="statusBox" class="alert" role="alert"></div>

  <div class="card">
    <form id="inviteForm">
      <div class="channel-tabs">
        <div>
          <input type="radio" name="send_via" id="ch_email" value="email">
          <label for="ch_email"><i class="bi bi-envelope"></i> Email</label>
        </div>
        <div>
          <input type="radio" name="send_via" id="ch_wa" value="whatsapp" checked>
          <label for="ch_wa"><i class="bi bi-whatsapp"></i> WhatsApp</label>
        </div>
        <div>
          <input type="radio" name="send_via" id="ch_both" value="both">
          <label for="ch_both"><i class="bi bi-send"></i> Both</label>
        </div>
      </div>

      <div class="row g-2 mb-2">
        <div class="col-12">
          <input type="text" name="student_name" class="form-control" placeholder="Student full name *" required>
        </div>
        <div class="col-md-6">
          <input type="email" name="student_email" class="form-control" placeholder="Email *" required>
        </div>
        <div class="col-md-6">
          <input type="tel" name="whatsapp_number" class="form-control" placeholder="WhatsApp +250… *" required
                 pattern="^\+[0-9\s\-().]{10,20}$" title="+country code">
        </div>
      </div>

      <div class="form-check mb-2" id="emailNowWrap" style="display:none">
        <input class="form-check-input" type="checkbox" name="send_email_now" id="sendEmailNow" value="1" checked>
        <label class="form-check-label small" for="sendEmailNow">Send link by email now</label>
      </div>

      <button type="submit" class="btn btn-send btn-wa w-100" id="inviteBtn">
        <i class="bi bi-send me-1"></i> Send invite
      </button>

      <div class="link-box" id="linkBox">
        <label class="form-label small text-muted mb-1">Direct link — share via email, WhatsApp, or any channel</label>
        <div class="input-group input-group-sm mb-2">
          <input type="text" class="form-control" id="inviteLink" readonly>
          <button type="button" class="btn btn-outline-secondary" id="copyLinkBtn" title="Copy link"><i class="bi bi-clipboard"></i></button>
          <button type="button" class="btn btn-outline-primary" id="emailLinkBtn" title="Send email"><i class="bi bi-envelope"></i></button>
          <button type="button" class="btn btn-outline-success" id="waShareBtn" title="Share on WhatsApp"><i class="bi bi-whatsapp"></i></button>
        </div>
        <p class="small text-muted mb-0">Applicants choose <strong>Study Abroad</strong> or <strong>Work Abroad</strong> on the form.</p>
      </div>
    </form>

    <div class="foot-links">
      <a href="api/webhook-health.php" target="_blank">Webhook</a>
      <a href="api/prescreening-invite-log.php" target="_blank">Invite log</a>
    </div>
  </div>

  <details class="admin-form card mt-3">
    <summary class="p-3">Admin: fill form manually</summary>
    <div class="p-3 pt-0 border-top">
      <p class="small text-muted mb-2">Staff completes the form on behalf of a student.</p>
      <a href="prescreening-admin-form.php" class="btn btn-sm btn-outline-secondary" target="_top">Open admin form</a>
    </div>
  </details>
</div>

<script>
(function () {
  const form = document.getElementById('inviteForm');
  const btn = document.getElementById('inviteBtn');
  const statusBox = document.getElementById('statusBox');
  const linkBox = document.getElementById('linkBox');
  const linkInput = document.getElementById('inviteLink');
  const emailWrap = document.getElementById('emailNowWrap');
  const channelInputs = form.querySelectorAll('input[name="send_via"]');
  let lastToken = '';

  function channel() {
    return form.querySelector('input[name="send_via"]:checked')?.value || 'whatsapp';
  }

  function syncUi() {
    const ch = channel();
    btn.className = 'btn btn-send w-100 ' + (ch === 'email' ? 'btn-email' : ch === 'both' ? 'btn-both' : 'btn-wa');
    btn.innerHTML = '<i class="bi bi-send me-1"></i> ' + (ch === 'email' ? 'Create link & send email' : ch === 'both' ? 'Send WhatsApp + email' : 'Send WhatsApp template');
    emailWrap.style.display = (ch === 'email' || ch === 'both') ? 'block' : 'none';
  }
  channelInputs.forEach(r => r.addEventListener('change', syncUi));
  syncUi();

  function showStatus(kind, msg) {
    statusBox.style.display = 'block';
    statusBox.className = 'alert alert-' + kind;
    statusBox.textContent = msg;
  }

  document.getElementById('copyLinkBtn')?.addEventListener('click', function () {
    if (!linkInput.value) return;
    navigator.clipboard.writeText(linkInput.value).then(() => showStatus('success', 'Link copied.'));
  });

  document.getElementById('waShareBtn')?.addEventListener('click', function () {
    if (!linkInput.value) {
      showStatus('warning', 'Send an invite first to generate a link.');
      return;
    }
    const text = encodeURIComponent('Complete your Xander Global Scholars pre-screening here: ' + linkInput.value);
    window.open('https://wa.me/?text=' + text, '_blank', 'noopener');
  });

  document.getElementById('emailLinkBtn')?.addEventListener('click', async function () {
    if (!lastToken) { showStatus('warning', 'Send an invite first to generate a link.'); return; }
    btn.disabled = true;
    try {
      const fd = new FormData();
      fd.set('token', lastToken);
      const res = await fetch('send_prescreening_link_email.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const data = await res.json();
      showStatus(data.status === 'success' ? 'success' : 'danger', data.message || 'Done');
    } catch (e) {
      showStatus('danger', 'Network error');
    } finally {
      btn.disabled = false;
    }
  });

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    if (!form.checkValidity()) { form.reportValidity(); return; }
    btn.disabled = true;
    const fd = new FormData(form);
    if (!fd.has('send_email_now')) fd.set('send_email_now', '0');
    try {
      const res = await fetch('send_prescreening_invite.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const raw = await res.text();
      let data;
      try { data = JSON.parse(raw); } catch (err) {
        showStatus('danger', 'Server error (not JSON).');
        return;
      }
      if (data.link) {
        linkInput.value = data.link;
        linkBox.classList.add('show');
      }
      if (data.token) lastToken = data.token;
      const kind = data.status === 'success' ? 'success' : (data.status === 'partial' ? 'warning' : 'danger');
      showStatus(kind, data.message || 'Done');
    } catch (err) {
      showStatus('danger', 'Network error.');
    } finally {
      btn.disabled = false;
    }
  });
})();
</script>
</body>
</html>
