<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/institution_portal.php';
require_once __DIR__ . '/helpers/institution_dashboard.php';
require_once __DIR__ . '/helpers/urls.php';

xander_institution_portal_ensure_schema($conn);

$search = trim((string) ($_GET['q'] ?? ''));
$accounts = xander_institution_admin_list_accounts($conn);

if ($search !== '') {
    $needle = mb_strtolower($search);
    $accounts = array_values(array_filter($accounts, static function (array $row) use ($needle): bool {
        $hay = mb_strtolower(
            (string) ($row['university_name'] ?? '') . ' '
            . (string) ($row['email'] ?? '') . ' '
            . (string) ($row['contact_name'] ?? '') . ' '
            . (string) ($row['country_name'] ?? '') . ' '
            . (string) ($row['city'] ?? '')
        );

        return str_contains($hay, $needle);
    }));
}

$totals = [
    'institutions' => count($accounts),
    'homepage_live' => 0,
    'active_scholarships' => 0,
    'pending_apps' => 0,
];

foreach ($accounts as $row) {
    $uid = (int) ($row['university_id'] ?? 0);
    if (!empty($row['homepage_published'])) {
        $totals['homepage_live']++;
    }
    if ($uid > 0) {
        $stats = xander_institution_full_dashboard_stats($conn, $uid);
        $totals['active_scholarships'] += (int) ($stats['active_scholarships'] ?? 0);
        $totals['pending_apps'] += (int) ($stats['pending_reviews'] ?? 0) + (int) ($stats['new_applications'] ?? 0);
    }
}

function adm_inst_h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Institution monitor | Xander Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root { --navy:#0a1f44; --orange:#f97316; }
    body { font-family: Inter, system-ui, sans-serif; background:#f1f5f9; color:#1e293b; }
    .page-wrap { max-width:1200px; margin:0 auto; padding:1.5rem 1rem 3rem; }
    .hero { background:linear-gradient(135deg,var(--navy),#1e4a8c); color:#fff; border-radius:16px; padding:1.5rem 1.75rem; margin-bottom:1.25rem; }
    .hero h1 { font-size:1.35rem; font-weight:800; margin:0 0 .35rem; }
    .stat-pill { background:rgba(255,255,255,.12); border-radius:10px; padding:.65rem .9rem; }
    .stat-pill strong { display:block; font-size:1.25rem; }
    .table-card { background:#fff; border-radius:14px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 4px 14px rgba(15,23,42,.06); }
    .table thead th { background:#f8fafc; font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; color:#64748b; }
    .badge-live { background:#dcfce7; color:#166534; }
    .badge-draft { background:#fef3c7; color:#92400e; }
    .btn-navy { background:var(--navy); color:#fff; border:none; }
    .btn-navy:hover { background:#152a5c; color:#fff; }
  </style>
</head>
<body>
<div class="page-wrap">
  <div class="hero">
    <h1><i class="bi bi-building me-2"></i>Registered institutions</h1>
    <p class="mb-3 opacity-75">Monitor every institution portal account, homepage publishing, and dashboard activity.</p>
    <div class="row g-2 mt-2">
      <div class="col-6 col-md-3"><div class="stat-pill"><strong><?= (int) $totals['institutions'] ?></strong><span class="small">Institutions</span></div></div>
      <div class="col-6 col-md-3"><div class="stat-pill"><strong><?= (int) $totals['homepage_live'] ?></strong><span class="small">Homepage live</span></div></div>
      <div class="col-6 col-md-3"><div class="stat-pill"><strong><?= (int) $totals['active_scholarships'] ?></strong><span class="small">Active scholarships</span></div></div>
      <div class="col-6 col-md-3"><div class="stat-pill"><strong><?= (int) $totals['pending_apps'] ?></strong><span class="small">Pending reviews</span></div></div>
    </div>
  </div>

  <form class="row g-2 mb-3" method="get">
    <div class="col-md-8">
      <input type="search" name="q" class="form-control" placeholder="Search institution, contact, email, country…" value="<?= adm_inst_h($search) ?>">
    </div>
    <div class="col-md-4 d-flex gap-2">
      <button type="submit" class="btn btn-navy flex-grow-1">Search</button>
      <?php if ($search !== ''): ?>
      <a href="admin-institutions-monitor.php" class="btn btn-outline-secondary">Clear</a>
      <?php endif; ?>
    </div>
  </form>

  <div class="table-card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Institution</th>
            <th>Contact</th>
            <th>Status</th>
            <th>Scholarships</th>
            <th>Applications</th>
            <th>Homepage</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php if ($accounts === []): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No registered institutions found.</td></tr>
        <?php else: ?>
          <?php foreach ($accounts as $row):
            $uid = (int) ($row['university_id'] ?? 0);
            $stats = $uid > 0 ? xander_institution_full_dashboard_stats($conn, $uid) : [];
            $status = (string) ($row['account_status'] ?? 'active');
            ?>
          <tr>
            <td>
              <strong><?= adm_inst_h((string) ($row['university_name'] ?? '')) ?></strong>
              <div class="small text-muted">
                <?= adm_inst_h(trim((string) ($row['city'] ?? '') . ($row['country_name'] ? ', ' . $row['country_name'] : ''))) ?>
              </div>
            </td>
            <td>
              <div><?= adm_inst_h((string) ($row['contact_name'] ?? '—')) ?></div>
              <div class="small text-muted"><?= adm_inst_h((string) ($row['email'] ?? '')) ?></div>
            </td>
            <td><span class="badge bg-<?= $status === 'active' ? 'success' : 'secondary' ?>"><?= adm_inst_h(ucfirst($status)) ?></span></td>
            <td><?= (int) ($stats['active_scholarships'] ?? 0) ?> active</td>
            <td>
              <?= (int) ($stats['total_applications'] ?? 0) ?> total
              <?php if ((int) ($stats['new_applications'] ?? 0) > 0): ?>
              <span class="badge bg-warning text-dark ms-1"><?= (int) $stats['new_applications'] ?> new</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($row['homepage_published'])): ?>
              <span class="badge badge-live">Published</span>
              <?php else: ?>
              <span class="badge badge-draft">Not live</span>
              <?php endif; ?>
            </td>
            <td class="text-end text-nowrap">
              <a class="btn btn-sm btn-navy" href="admin-institution-overview.php?university_id=<?= $uid ?>">Dashboard</a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
