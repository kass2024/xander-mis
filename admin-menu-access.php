<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/role.php';
require_once __DIR__ . '/helpers/admin_menu_permissions.php';

if (empty($_SESSION['admin_id']) || !pcvc_is_superadmin_role($_SESSION['role'] ?? '')) {
    http_response_code(403);
    exit('Menu Access is only available to Superadmin.');
}

xander_admin_menu_ensure_table($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu Access Control</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root { --brand: #012f6b; --brand-light: #1a4a8a; --bg: #f0f4fa; }
    body { background: var(--bg); font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; min-height: 100vh; }
    .hero { background: linear-gradient(135deg, var(--brand), var(--brand-light)); color: #fff; padding: 1.25rem 1.5rem; }
    .hero h1 { font-size: 1.35rem; margin: 0; font-weight: 700; }
    .hero p { margin: .35rem 0 0; opacity: .9; font-size: .9rem; }
    .layout { display: grid; grid-template-columns: 320px 1fr; gap: 0; min-height: calc(100vh - 80px); }
    @media (max-width: 900px) { .layout { grid-template-columns: 1fr; } }
    .panel-left { background: #fff; border-right: 1px solid #dde3ef; padding: 1rem; overflow-y: auto; }
    .panel-right { padding: 1.25rem; overflow-y: auto; }
    .admin-search { border-radius: 10px; border: 1px solid #cbd5e1; padding: .55rem .75rem; width: 100%; margin-bottom: .75rem; }
    .admin-item { padding: .65rem .75rem; border-radius: 10px; cursor: pointer; border: 1px solid transparent; margin-bottom: 6px; }
    .admin-item:hover { background: #f1f5f9; }
    .admin-item.active { background: #e8f0ff; border-color: #93b4e8; }
    .admin-item .meta { font-size: .78rem; color: #64748b; }
    .badge-role { font-size: .7rem; text-transform: uppercase; }
    .menu-card { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 12px; overflow: hidden; box-shadow: 0 1px 4px rgba(15,23,42,.04); }
    .menu-card-head { display: flex; align-items: center; gap: 10px; padding: .85rem 1rem; background: #f8fafc; border-bottom: 1px solid #eef2f7; cursor: pointer; }
    .menu-card-head input { width: 18px; height: 18px; }
    .menu-card-body { padding: .5rem 1rem 1rem 1.25rem; display: none; }
    .menu-card.open .menu-card-body { display: block; }
    .sub-row { display: flex; align-items: center; gap: 8px; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: .9rem; }
    .sub-row:last-child { border-bottom: none; }
    .toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 1rem; align-items: center; }
    .stat-pill { background: #fff; border: 1px solid #e2e8f0; border-radius: 999px; padding: 4px 12px; font-size: .8rem; color: #475569; }
    .empty-state { text-align: center; color: #64748b; padding: 3rem 1rem; }
    .toast-box { position: fixed; bottom: 20px; right: 20px; z-index: 99; }
  </style>
</head>
<body>
  <div class="hero">
    <h1><i class="bi bi-shield-lock me-2"></i>Menu Access Control</h1>
    <p>Select an admin account and choose which sidebar menus and submenus they can access. Superadmin always has full access.</p>
  </div>

  <div class="layout">
    <aside class="panel-left">
      <input type="search" class="admin-search" id="adminSearch" placeholder="Search name, username, email…">
      <div id="adminList"></div>
    </aside>
    <main class="panel-right">
      <div id="editorEmpty" class="empty-state">
        <i class="bi bi-person-badge display-4 d-block mb-2"></i>
        Select an admin from the list to configure menu access.
      </div>
      <div id="editor" class="d-none">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
          <div>
            <h2 class="h5 mb-1" id="editorName">—</h2>
            <div class="text-muted small" id="editorMeta">—</div>
          </div>
          <span class="badge bg-secondary" id="editorSource">—</span>
        </div>
        <div class="toolbar">
          <span class="stat-pill" id="statMenus">0 menus</span>
          <span class="stat-pill" id="statSubs">0 submenus</span>
          <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAll">Select all</button>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearAll">Clear all</button>
          <button type="button" class="btn btn-sm btn-outline-warning" id="btnRoleDefault">Use role default</button>
          <button type="button" class="btn btn-sm btn-primary ms-auto" id="btnSave"><i class="bi bi-check2"></i> Save access</button>
        </div>
        <div id="readonlyNote" class="alert alert-info d-none">This account is Superadmin — full access cannot be restricted.</div>
        <div id="menuTree"></div>
      </div>
    </main>
  </div>

  <div class="toast-box"><div id="toast" class="toast align-items-center text-bg-dark border-0" role="alert"><div class="d-flex"><div class="toast-body" id="toastBody"></div></div></div></div>

<script>
const API = 'api/admin-menu-permissions.php';
let registry = [];
let admins = [];
let selectedAdminId = 0;
let readonly = false;
let roleDefault = null;
/** @type {Record<string, boolean>} */
let menuChecked = {};
/** @type {Record<string, Record<string, boolean>>} */
let subChecked = {};

async function api(action, opts = {}) {
  const url = opts.method === 'POST'
    ? API + '?action=' + encodeURIComponent(action)
    : API + '?action=' + encodeURIComponent(action) + (opts.query || '');
  const res = await fetch(url, {
    method: opts.method || 'GET',
    headers: opts.method === 'POST' ? { 'Content-Type': 'application/json' } : {},
    body: opts.body ? JSON.stringify(opts.body) : undefined,
    credentials: 'same-origin',
  });
  return res.json();
}

function toast(msg, ok = true) {
  const el = document.getElementById('toast');
  document.getElementById('toastBody').textContent = msg;
  el.classList.toggle('text-bg-success', ok);
  el.classList.toggle('text-bg-danger', !ok);
  bootstrap.Toast.getOrCreateInstance(el).show();
}

function renderAdminList(filter = '') {
  const q = filter.toLowerCase();
  const box = document.getElementById('adminList');
  box.innerHTML = '';
  admins.forEach(a => {
    const hay = [a.name, a.username, a.email, a.role].join(' ').toLowerCase();
    if (q && !hay.includes(q)) return;
    const div = document.createElement('div');
    div.className = 'admin-item' + (a.id === selectedAdminId ? ' active' : '');
    div.innerHTML = '<strong>' + esc(a.name) + '</strong>'
      + (a.is_superadmin ? ' <span class="badge bg-dark badge-role">superadmin</span>' : ' <span class="badge bg-light text-dark badge-role">' + esc(a.role) + '</span>')
      + '<div class="meta">' + esc(a.username) + ' · ' + esc(a.email) + '</div>';
    div.onclick = () => loadAdmin(a.id);
    box.appendChild(div);
  });
}

function esc(s) {
  const d = document.createElement('div');
  d.textContent = s == null ? '' : String(s);
  return d.innerHTML;
}

function countStats() {
  const menus = Object.keys(menuChecked).filter(k => menuChecked[k]).length;
  let subs = 0;
  Object.keys(subChecked).forEach(m => {
    subs += Object.keys(subChecked[m] || {}).filter(f => subChecked[m][f]).length;
  });
  document.getElementById('statMenus').textContent = menus + ' menu' + (menus === 1 ? '' : 's');
  document.getElementById('statSubs').textContent = subs + ' submenu' + (subs === 1 ? '' : 's');
}

function applyAccess(access) {
  menuChecked = {};
  subChecked = {};
  registry.forEach(m => {
    menuChecked[m.key] = (access.menus || []).includes(m.key);
    subChecked[m.key] = {};
    Object.keys(m.links || {}).forEach(file => {
      const allowed = access.submenus && access.submenus[m.key];
      subChecked[m.key][file] = allowed ? allowed.includes(file) : menuChecked[m.key];
    });
  });
  countStats();
  renderMenuTree();
}

async function loadAdmin(id) {
  selectedAdminId = id;
  renderAdminList(document.getElementById('adminSearch').value);
  const data = await api('get', { query: '&admin_id=' + id });
  if (!data.ok) { toast(data.message || 'Load failed', false); return; }

  document.getElementById('editorEmpty').classList.add('d-none');
  document.getElementById('editor').classList.remove('d-none');
  const a = data.admin;
  document.getElementById('editorName').textContent = a.username;
  document.getElementById('editorMeta').textContent = (data.admin.email || '') + ' · Role: ' + (data.admin.role || '');
  readonly = !!data.readonly;
  roleDefault = data.role_default || null;
  document.getElementById('readonlyNote').classList.toggle('d-none', !readonly);
  document.getElementById('btnSave').disabled = readonly;
  document.getElementById('editorSource').textContent = data.access.is_custom ? 'Custom access' : 'Role default';
  document.getElementById('editorSource').className = 'badge ' + (data.access.is_custom ? 'bg-primary' : 'bg-secondary');
  applyAccess(data.access);
}

function renderMenuTree() {
  const tree = document.getElementById('menuTree');
  tree.innerHTML = '';
  registry.forEach(m => {
    const card = document.createElement('div');
    card.className = 'menu-card' + (menuChecked[m.key] ? ' open' : '');
    const head = document.createElement('div');
    head.className = 'menu-card-head';
    head.innerHTML = '<input type="checkbox" data-menu="' + esc(m.key) + '" ' + (menuChecked[m.key] ? 'checked' : '') + (readonly ? ' disabled' : '') + '>'
      + '<i class="bi ' + esc(m.icon) + '"></i><strong>' + esc(m.title) + '</strong>'
      + '<span class="ms-auto small text-muted">' + Object.keys(m.links).length + ' items</span>';
    const body = document.createElement('div');
    body.className = 'menu-card-body';
    Object.entries(m.links).forEach(([file, label]) => {
      const row = document.createElement('div');
      row.className = 'sub-row';
      row.innerHTML = '<input type="checkbox" data-menu="' + esc(m.key) + '" data-file="' + esc(file) + '" '
        + ((subChecked[m.key] && subChecked[m.key][file]) ? 'checked' : '') + (readonly ? ' disabled' : '') + '>'
        + '<span>' + esc(label) + '</span><code class="ms-auto small text-muted">' + esc(file) + '</code>';
      body.appendChild(row);
    });
    card.appendChild(head);
    card.appendChild(body);
    tree.appendChild(card);

    head.querySelector('input[type=checkbox]').addEventListener('change', e => {
      const on = e.target.checked;
      menuChecked[m.key] = on;
      card.classList.toggle('open', on);
      Object.keys(m.links).forEach(file => { subChecked[m.key][file] = on; });
      body.querySelectorAll('input[data-file]').forEach(cb => { cb.checked = on; });
      countStats();
    });
    head.addEventListener('click', ev => {
      if (ev.target.tagName === 'INPUT') return;
      card.classList.toggle('open');
    });
    body.querySelectorAll('input[data-file]').forEach(cb => {
      cb.addEventListener('change', e => {
        const file = e.target.dataset.file;
        subChecked[m.key][file] = e.target.checked;
        const any = Object.values(subChecked[m.key]).some(Boolean);
        menuChecked[m.key] = any;
        head.querySelector('input[data-menu]').checked = any;
        card.classList.toggle('open', any);
        countStats();
      });
    });
  });
}

document.getElementById('adminSearch').addEventListener('input', e => renderAdminList(e.target.value));

document.getElementById('btnSelectAll').onclick = () => {
  if (readonly) return;
  registry.forEach(m => {
    menuChecked[m.key] = true;
    Object.keys(m.links).forEach(f => { subChecked[m.key][f] = true; });
  });
  renderMenuTree();
  countStats();
};

document.getElementById('btnClearAll').onclick = () => {
  if (readonly) return;
  registry.forEach(m => {
    menuChecked[m.key] = false;
    Object.keys(m.links).forEach(f => { subChecked[m.key][f] = false; });
  });
  renderMenuTree();
  countStats();
};

document.getElementById('btnRoleDefault').onclick = async () => {
  if (!selectedAdminId || readonly) return;
  if (!confirm('Reset this admin to role-based default menus?')) return;
  const data = await api('reset', { method: 'POST', body: { admin_id: selectedAdminId } });
  toast(data.message || (data.ok ? 'Reset' : 'Failed'), data.ok);
  if (data.ok) loadAdmin(selectedAdminId);
};

document.getElementById('btnSave').onclick = async () => {
  if (!selectedAdminId || readonly) return;
  const menus = Object.keys(menuChecked).filter(k => menuChecked[k]);
  const submenus = {};
  menus.forEach(m => {
    submenus[m] = Object.keys(subChecked[m] || {}).filter(f => subChecked[m][f]);
  });
  const data = await api('save', { method: 'POST', body: { admin_id: selectedAdminId, menus, submenus } });
  toast(data.message || (data.ok ? 'Saved' : 'Failed'), data.ok);
  if (data.ok) loadAdmin(selectedAdminId);
};

(async function init() {
  const schema = await api('schema');
  if (!schema.ok || !schema.exists) {
    toast('Could not create menu permissions table. Check server error log.', false);
    return;
  }
  const [reg, adm] = await Promise.all([
    api('registry'),
    api('admins'),
  ]);
  if (!reg.ok || !adm.ok) {
    toast('Could not load menu data', false);
    return;
  }
  registry = reg.registry;
  admins = adm.admins.filter(a => !a.is_superadmin);
  renderAdminList();
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

