<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/prescreening_schema.php';
require_once __DIR__ . '/helpers/prescreening_notify.php';

xander_ensure_prescreening_table($conn);

$rows = [];
$res = $conn->query('SELECT * FROM prescreening_submissions ORDER BY submitted_at DESC, id DESC LIMIT 500');
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    $res->free();
}

$qLabels = xander_prescreening_question_labels();
$docLabels = xander_prescreening_document_labels();
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
    .side { width: 300px; background: #fff; border-right: 1px solid #ddd; padding: 1rem; overflow-y: auto; }
    .main { flex: 1; padding: 1.5rem; overflow-y: auto; }
    .side input { width: 100%; margin-bottom: 10px; padding: 8px 12px; border-radius: 8px; border: 1px solid #ccc; }
    .list-item { padding: 10px 12px; border-bottom: 1px solid #eee; cursor: pointer; }
    .list-item:hover, .list-item.active { background: #eef4ff; }
    .badge-ok { background: #d1e7dd; color: #0f5132; }
    .badge-warn { background: #fff3cd; color: #664d03; }
  </style>
</head>
<body>
<div class="layout">
  <aside class="side">
    <h5 class="mb-2">Pre-screening</h5>
    <input type="search" id="searchBox" placeholder="Search name, email, phone…">
    <a href="prescreening.php" class="btn btn-sm btn-primary w-100 mb-2">+ New pre-screening</a>
    <div id="list"></div>
  </aside>
  <main class="main">
    <div id="detail">
      <p class="text-muted">Select a submission from the list.</p>
    </div>
  </main>
</div>

<script>
const rows = <?= json_encode($rows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const qLabels = <?= json_encode($qLabels, JSON_UNESCAPED_UNICODE) ?>;
const docLabels = <?= json_encode($docLabels, JSON_UNESCAPED_UNICODE) ?>;

function esc(s) {
  const d = document.createElement('div');
  d.textContent = s == null ? '' : String(s);
  return d.innerHTML;
}

function renderList(filter) {
  const q = (filter || '').toLowerCase();
  const list = document.getElementById('list');
  list.innerHTML = '';
  rows.forEach((r, i) => {
    const hay = [r.student_name, r.student_email, r.whatsapp_number, r.user_id].join(' ').toLowerCase();
    if (q && hay.indexOf(q) === -1) return;
    const el = document.createElement('div');
    el.className = 'list-item';
    el.dataset.idx = String(i);
    const wa = r.whatsapp_sent == 1;
    const em = r.email_sent == 1;
    el.innerHTML = '<strong>' + esc(r.student_name) + '</strong><br><small>' + esc(r.submitted_at || r.created_at) + '</small><br>'
      + '<span class="badge ' + (em ? 'badge-ok' : 'badge-warn') + ' me-1">Email</span>'
      + '<span class="badge ' + (wa ? 'badge-ok' : 'badge-warn') + '">WhatsApp</span>';
    el.onclick = () => showDetail(i, el);
    list.appendChild(el);
  });
}

function showDetail(idx, el) {
  document.querySelectorAll('.list-item').forEach(n => n.classList.remove('active'));
  if (el) el.classList.add('active');
  const r = rows[idx];
  if (!r) return;

  let qHtml = '<table class="table table-sm"><tbody>';
  Object.keys(qLabels).forEach(k => {
    qHtml += '<tr><th style="width:40%">' + esc(qLabels[k]) + '</th><td>' + esc(r[k] || '—') + '</td></tr>';
  });
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

  document.getElementById('detail').innerHTML = `
    <h4>${esc(r.student_name)}</h4>
    <p><strong>Source:</strong> ${esc(r.source || 'admin')}<br>
    <strong>Email:</strong> ${esc(r.student_email || '—')}<br>
    <strong>WhatsApp:</strong> ${esc(r.whatsapp_number)}<br>
    <strong>Reference:</strong> ${esc(r.user_id)}<br>
    <strong>Submitted:</strong> ${esc(r.submitted_at || '—')}</p>
    <h5>Answers</h5>${qHtml}
    <h5>Documents</h5>${dHtml}
  `;
}

document.getElementById('searchBox').addEventListener('input', e => renderList(e.target.value));
renderList('');
if (rows.length) {
  const first = document.querySelector('.list-item');
  if (first) showDetail(0, first);
}
</script>
</body>
</html>
