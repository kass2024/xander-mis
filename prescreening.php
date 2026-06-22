<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/prescreening_schema.php';
require_once __DIR__ . '/helpers/prescreening_access.php';
require_once __DIR__ . '/helpers/prescreening_invite.php';

xander_prescreening_require_menu_access('prescreening.php');

xander_ensure_prescreening_schema($conn);

$publicLink = xander_prescreening_public_url();
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
    .link-box {
      margin-top: .5rem; padding: 12px; background: #f8fafc;
      border-radius: 10px; border: 1px dashed #cbd5e1;
    }
    .link-box input { font-size: .8rem; font-family: ui-monospace, monospace; }
    #statusBox { display: none; margin-bottom: 1rem; }
    .foot-links { margin-top: 1rem; display: flex; flex-wrap: wrap; gap: 10px; font-size: .85rem; }
    .foot-links a { color: #64748b; text-decoration: none; }
    .foot-links a:hover { color: var(--brand); }
    details.admin-form { margin-top: 1rem; }
    details.admin-form summary { cursor: pointer; font-weight: 600; color: #475569; font-size: .9rem; }
    .env-hint { font-size: .8rem; color: #64748b; }
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
    <h2 class="h6 fw-bold mb-2">Public pre-screening link</h2>
    <p class="small text-muted mb-3">
      Share this link with applicants. They fill in their name, email, and WhatsApp on the form.
      The URL works on <strong>localhost</strong> and <strong>cPanel</strong> automatically.
    </p>

    <div class="link-box">
      <label class="form-label small text-muted mb-1">Direct link — copy or share via email, WhatsApp, or any channel</label>
      <div class="input-group input-group-sm mb-2">
        <input type="text" class="form-control" id="publicLink" readonly value="<?= htmlspecialchars($publicLink, ENT_QUOTES, 'UTF-8') ?>">
        <button type="button" class="btn btn-outline-secondary" id="copyLinkBtn" title="Copy link"><i class="bi bi-clipboard"></i></button>
        <button type="button" class="btn btn-outline-primary" id="emailShareBtn" title="Share via email"><i class="bi bi-envelope"></i></button>
        <button type="button" class="btn btn-outline-success" id="waShareBtn" title="Share on WhatsApp"><i class="bi bi-whatsapp"></i></button>
      </div>
      <p class="small text-muted mb-1">Applicants choose <strong>Study Abroad</strong> or <strong>Work Abroad</strong> on the form.</p>
      <p class="env-hint mb-0">Current base: <?= htmlspecialchars($publicLink, ENT_QUOTES, 'UTF-8') ?></p>
    </div>

    <div class="mt-3">
      <a href="<?= htmlspecialchars($publicLink, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm" target="_blank" rel="noopener">
        <i class="bi bi-box-arrow-up-right me-1"></i> Open form
      </a>
    </div>

    <div class="foot-links">
      <a href="api/webhook-health.php" target="_blank">Webhook</a>
      <a href="api/prescreening-invite-log.php" target="_blank">Invite log</a>
      <a href="api/prescreening-invite-delivery.php?phone=" target="_blank" title="Append digits e.g. 14503675329">Delivery</a>
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
  const linkInput = document.getElementById('publicLink');
  const statusBox = document.getElementById('statusBox');

  function showStatus(kind, msg) {
    statusBox.style.display = 'block';
    statusBox.className = 'alert alert-' + kind;
    statusBox.textContent = msg;
  }

  document.getElementById('copyLinkBtn')?.addEventListener('click', function () {
    if (!linkInput.value) return;
    navigator.clipboard.writeText(linkInput.value).then(() => showStatus('success', 'Link copied to clipboard.'));
  });

  document.getElementById('waShareBtn')?.addEventListener('click', function () {
    const text = encodeURIComponent('Complete your Xander Global Scholars pre-screening here: ' + linkInput.value);
    window.open('https://wa.me/?text=' + text, '_blank', 'noopener');
  });

  document.getElementById('emailShareBtn')?.addEventListener('click', function () {
    const subject = encodeURIComponent('Xander Global Scholars — Pre-screening form');
    const body = encodeURIComponent(
      'Hello,\n\nPlease complete your pre-screening using this link:\n\n' + linkInput.value + '\n\n— Xander Global Scholars'
    );
    window.location.href = 'mailto:?subject=' + subject + '&body=' + body;
  });
})();
</script>
</body>
</html>
