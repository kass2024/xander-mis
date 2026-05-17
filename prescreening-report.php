<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/prescreening_schema.php';
require_once __DIR__ . '/helpers/prescreening_notify.php';
require_once __DIR__ . '/helpers/prescreening_options.php';
require_once __DIR__ . '/helpers/prescreening_access.php';
require_once __DIR__ . '/helpers/prescreening_apply.php';
require_once __DIR__ . '/helpers/prescreening_work_profile.php';

xander_prescreening_require_superadmin();
xander_ensure_prescreening_schema($conn);

$prescreenCsrf = xander_prescreening_csrf_token();

$rows = [];
$res = $conn->query(
    'SELECT * FROM prescreening_submissions
     WHERE submitted_at IS NOT NULL
     ORDER BY submitted_at DESC, id DESC
     LIMIT 500'
);
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $r['_meta'] = xander_prescreening_row_list_meta($conn, $r);
        if ((string) ($r['service_type'] ?? '') === 'work_abroad') {
            $r['_work_profile_lines'] = xander_prescreening_work_profile_email_lines($r);
        }
        $rows[] = $r;
    }
    $res->free();
}

$studyQLabels = xander_prescreening_question_labels_for_row(['service_type' => 'study_abroad']);
$workQLabels = xander_prescreening_question_labels_for_row(['service_type' => 'work_abroad']);
$studyDocLabels = xander_prescreening_document_labels();
$workDocLabels = xander_prescreening_work_document_labels();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pre-screening submissions</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f7f8fa; font-family: Roboto, system-ui, sans-serif; margin: 0; }
    .layout { display: flex; min-height: 100vh; }
    .side { width: 320px; background: #fff; border-right: 1px solid #ddd; padding: 1rem; overflow-y: auto; display: flex; flex-direction: column; }
    .main { flex: 1; padding: 1.5rem; overflow-y: auto; }
    .side input[type=search] { width: 100%; margin-bottom: 10px; padding: 8px 12px; border-radius: 8px; border: 1px solid #ccc; }
    .filter-tabs { display: flex; gap: 6px; margin-bottom: 10px; flex-wrap: wrap; }
    .filter-tabs button {
      flex: 1; min-width: 70px; border: 1px solid #cbd5e1; background: #f8fafc; border-radius: 8px;
      padding: 6px 8px; font-size: 12px; font-weight: 600; cursor: pointer; color: #475569;
    }
    .filter-tabs button.active { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
    .list-scroll { flex: 1; overflow-y: auto; margin: 0 -4px; }
    .list-item { padding: 10px 12px; border-bottom: 1px solid #eee; cursor: pointer; border-radius: 8px; margin-bottom: 4px; }
    .list-item:hover, .list-item.active { background: #eef4ff; }
    .badge-ok { background: #d1e7dd; color: #0f5132; font-size: 11px; }
    .badge-warn { background: #fff3cd; color: #664d03; font-size: 11px; }
    .badge-pending { background: #e2e8f0; color: #475569; font-size: 11px; }
    .badge-study { background: #dbeafe; color: #1e40af; font-size: 11px; }
    .badge-work { background: #ffedd5; color: #9a3412; font-size: 11px; }
    .badge-app { background: #ede9fe; color: #5b21b6; font-size: 11px; }
    .action-bar { display: flex; flex-wrap: wrap; gap: 10px; margin: 1rem 0; }
    .action-bar .btn-apply { background: #1d4ed8; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .action-bar .btn-apply:hover { background: #1e40af; color: #fff; }
    .action-bar .btn-applied { background: #e2e8f0; color: #64748b; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: not-allowed; display: inline-flex; align-items: center; gap: 6px; pointer-events: none; }
    .action-bar .btn-delete { background: #dc3545; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; }
    .flash-ok { background: #d1e7dd; color: #0f5132; padding: 10px 14px; border-radius: 8px; margin-bottom: 12px; }
    .flash-err { background: #f8d7da; color: #842029; padding: 10px 14px; border-radius: 8px; margin-bottom: 12px; }
    .list-empty { padding: 1rem; color: #64748b; font-size: 14px; text-align: center; }
  </style>
</head>
<body>
<div class="layout">
  <aside class="side">
    <h5 class="mb-2">Pre-screening</h5>
    <input type="search" id="searchBox" placeholder="Search name, email, phone…">
    <div class="filter-tabs" id="filterTabs">
      <button type="button" class="active" data-filter="all">All</button>
      <button type="button" data-filter="study_abroad">Study</button>
      <button type="button" data-filter="work_abroad">Work</button>
    </div>
    <a href="prescreening.php" class="btn btn-sm btn-primary w-100 mb-2">+ New pre-screening</a>
    <div class="list-scroll" id="list"></div>
  </aside>
  <main class="main">
    <?php if (!empty($_GET['deleted'])): ?>
    <div class="flash-ok">Pre-screening record deleted.</div>
    <?php elseif (!empty($_GET['error']) && $_GET['error'] === 'delete_failed'): ?>
    <div class="flash-err">Could not delete record.</div>
    <?php endif; ?>
    <div id="detail">
      <p class="text-muted">Select a submission from the list.</p>
    </div>
  </main>
</div>

<script>
const prescreenCsrf = <?= json_encode($prescreenCsrf, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const rows = <?= json_encode($rows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const studyQLabels = <?= json_encode($studyQLabels, JSON_UNESCAPED_UNICODE) ?>;
const workQLabels = <?= json_encode($workQLabels, JSON_UNESCAPED_UNICODE) ?>;
const studyDocLabels = <?= json_encode($studyDocLabels, JSON_UNESCAPED_UNICODE) ?>;
const workDocLabels = <?= json_encode($workDocLabels, JSON_UNESCAPED_UNICODE) ?>;

let activeFilter = 'all';
let activeIdx = -1;

function esc(s) {
  const d = document.createElement('div');
  d.textContent = s == null ? '' : String(s);
  return d.innerHTML;
}

function rowServiceType(r) {
  const m = r._meta || {};
  return m.service_type || r.service_type || 'study_abroad';
}

function badgeHtml(cls, text) {
  return '<span class="badge ' + cls + ' me-1 mb-1">' + esc(text) + '</span>';
}

function renderList(filter, search) {
  const q = (search || '').toLowerCase();
  const list = document.getElementById('list');
  list.innerHTML = '';
  let shown = 0;

  rows.forEach((r, i) => {
    const svc = rowServiceType(r);
    if (filter !== 'all' && svc !== filter) return;

    const hay = [r.student_name, r.student_email, r.whatsapp_number, r.user_id].join(' ').toLowerCase();
    if (q && hay.indexOf(q) === -1) return;

    shown++;
    const m = r._meta || {};
    const el = document.createElement('div');
    el.className = 'list-item' + (i === activeIdx ? ' active' : '');
    el.dataset.idx = String(i);

    const svcBadge = svc === 'work_abroad'
      ? badgeHtml('badge-work', m.service_label || 'Work Abroad')
      : badgeHtml('badge-study', m.service_label || 'Study Abroad');

    const prescreenBadge = m.prescreen_status === 'submitted'
      ? badgeHtml('badge-ok', m.prescreen_status_label || 'Submitted')
      : badgeHtml('badge-pending', m.prescreen_status_label || 'Pending');

    let appBadge = '';
    if (m.has_application && m.application_status_label) {
      appBadge = badgeHtml('badge-app', 'App: ' + m.application_status_label);
    }

    const ch = esc(r.invite_channel || r.source || '');
    const channelBadge = ch ? badgeHtml('badge-warn', ch) : '';

    el.innerHTML = '<strong>' + esc(r.student_name || '—') + '</strong><br>'
      + '<small class="text-muted">' + esc(r.submitted_at || r.created_at || '') + '</small><br>'
      + '<div class="mt-1">' + svcBadge + prescreenBadge + appBadge + channelBadge + '</div>';

    el.onclick = () => showDetail(i, el);
    list.appendChild(el);
  });

  if (!shown) {
    list.innerHTML = '<p class="list-empty">No submissions in this category.</p>';
  }
}

function showDetail(idx, el) {
  activeIdx = idx;
  document.querySelectorAll('.list-item').forEach(n => n.classList.remove('active'));
  if (el) el.classList.add('active');

  const r = rows[idx];
  if (!r) return;

  const m = r._meta || {};
  const svc = rowServiceType(r);
  const qLabels = svc === 'work_abroad' ? workQLabels : studyQLabels;
  const docLabels = svc === 'work_abroad' ? workDocLabels : studyDocLabels;

  let qHtml = '<table class="table table-sm"><tbody>';
  Object.keys(qLabels).forEach(k => {
    let val = r[k] || '';
    if (k === 'service_type') {
      val = val === 'work_abroad' ? 'Work Abroad' : (val === 'study_abroad' ? 'Study Abroad' : val);
    }
    if (k === 'work_docs_checklist' && String(val).startsWith('[')) {
      try {
        const arr = JSON.parse(val);
        if (Array.isArray(arr)) val = arr.join('; ');
      } catch (e) { /* ignore */ }
    }
    qHtml += '<tr><th style="width:40%">' + esc(qLabels[k]) + '</th><td>' + esc(val || '—') + '</td></tr>';
  });
  if (svc === 'work_abroad' && r._work_profile_lines && typeof r._work_profile_lines === 'object') {
    Object.keys(r._work_profile_lines).forEach(label => {
      qHtml += '<tr><th style="width:40%">' + esc(label) + '</th><td>' + esc(r._work_profile_lines[label] || '—') + '</td></tr>';
    });
  }
  qHtml += '</tbody></table>';

  let dHtml = '<ul>';
  Object.keys(docLabels).forEach(k => {
    const p = r[k];
    if (p) {
      dHtml += '<li><a href="' + esc(p) + '" target="_blank" rel="noopener">' + esc(docLabels[k]) + '</a></li>';
    } else {
      dHtml += '<li class="text-muted">' + esc(docLabels[k]) + ' — not uploaded</li>';
    }
  });
  dHtml += '</ul>';

  const pending = !r.submitted_at;
  const hasDocs = Object.keys(docLabels).some(k => !!(r[k] && String(r[k]).trim()));
  let actions = '';
  if (!pending) {
    const alreadyApplied = !!(m.has_application);
    const applyBtn = alreadyApplied
      ? '<span class="btn-applied" title="An application is already linked to this pre-screening">Applied</span>'
      : '<a class="btn-apply" href="prescreening-apply.php?id=' + encodeURIComponent(r.id) + '" target="_top">Apply now</a>';
    actions = '<div class="action-bar">'
      + applyBtn
      + '<form method="post" action="delete_prescreening.php" style="display:inline" onsubmit="return confirm(\'Delete this pre-screening record and its files?\');">'
      + '<input type="hidden" name="id" value="' + esc(String(r.id)) + '">'
      + '<input type="hidden" name="csrf" value="' + esc(prescreenCsrf) + '">'
      + '<button type="submit" class="btn-delete">Delete</button></form>';
    if (!hasDocs) {
      actions += '<span class="text-muted small align-self-center">No documents — complete the application manually.</span>';
    }
    actions += '</div>';
  }

  const applyHint = m.has_application
    ? 'This pre-screening is already linked to an application (' + esc(m.application_status_label || 'submitted') + ').'
    : (svc === 'work_abroad'
      ? 'Apply now opens the job application with pre-screening documents queued; Smart AI runs automatically.'
      : 'Apply now opens the study application with documents queued; pick study choice, then Start analysis.');

  const hints = [];
  if (svc === 'work_abroad') {
    if (r.work_country_destination) hints.push('<strong>Work country:</strong> ' + esc(r.work_country_destination));
    if (r.applicant_address) hints.push('<strong>Address:</strong> ' + esc(r.applicant_address));
  } else {
    if (r.country_interest) hints.push('<strong>Country:</strong> ' + esc(r.country_interest));
    if (r.course_program) hints.push('<strong>Program:</strong> ' + esc(r.course_program));
  }
  const hintHtml = '<p class="alert alert-info py-2">' + (hints.length ? hints.join(' · ') + '<br>' : '') + '<small>' + applyHint + '</small></p>';

  const appLine = m.has_application
    ? '<strong>Application status:</strong> ' + esc(m.application_status_label || m.application_status || '—') + '<br>'
    : '<strong>Application:</strong> <span class="text-muted">Not started yet</span><br>';

  document.getElementById('detail').innerHTML = `
    <h4>${esc(r.student_name || '—')}</h4>
    ${actions}
    <p>
      ${badgeHtml(svc === 'work_abroad' ? 'badge-work' : 'badge-study', m.service_label || (svc === 'work_abroad' ? 'Work Abroad' : 'Study Abroad'))}
      ${badgeHtml(m.prescreen_status === 'submitted' ? 'badge-ok' : 'badge-pending', 'Pre-screen: ' + (m.prescreen_status_label || 'Pending'))}
      ${m.has_application ? badgeHtml('badge-app', m.application_status_label || 'Application') : ''}
    </p>
    <p>
      <strong>Source:</strong> ${esc(r.source || '—')}<br>
      <strong>Channel:</strong> ${esc(r.invite_channel || '—')}<br>
      <strong>Email:</strong> ${esc(r.student_email || '—')}<br>
      <strong>WhatsApp:</strong> ${esc(r.whatsapp_number || '—')}<br>
      <strong>Reference:</strong> ${esc(r.user_id)}<br>
      <strong>Pre-screen submitted:</strong> ${esc(r.submitted_at || '—')}<br>
      ${appLine}
    </p>
    ${hintHtml}
    ${pending ? '<p class="text-muted">Waiting for the applicant to complete the form via email link or WhatsApp.</p>' : ''}
    <h5>Answers</h5>${qHtml}
    <h5>Documents</h5>${dHtml}
  `;
}

document.getElementById('filterTabs').addEventListener('click', e => {
  const btn = e.target.closest('button[data-filter]');
  if (!btn) return;
  activeFilter = btn.dataset.filter;
  document.querySelectorAll('#filterTabs button').forEach(b => b.classList.toggle('active', b === btn));
  renderList(activeFilter, document.getElementById('searchBox').value);
});

document.getElementById('searchBox').addEventListener('input', e => renderList(activeFilter, e.target.value));

renderList('all', '');
if (rows.length) {
  const firstIdx = rows.findIndex(r => activeFilter === 'all' || rowServiceType(r) === activeFilter);
  if (firstIdx >= 0) {
    activeIdx = firstIdx;
    const el = document.querySelector('.list-item');
    showDetail(firstIdx, el);
  }
}
</script>
</body>
</html>
