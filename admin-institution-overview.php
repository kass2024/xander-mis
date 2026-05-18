<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/institution_portal.php';
require_once __DIR__ . '/helpers/institution_dashboard.php';
require_once __DIR__ . '/helpers/urls.php';

xander_institution_portal_ensure_schema($conn);

$universityId = (int) ($_GET['university_id'] ?? 0);
$account = xander_institution_admin_account_by_university($conn, $universityId);

if (!$account) {
    http_response_code(404);
    echo 'Institution not found.';
    exit;
}

$stats = xander_institution_full_dashboard_stats($conn, $universityId);
$scholarships = xander_institution_list_scholarships($conn, $universityId);
$programs = xander_institution_list_programs($conn, $universityId);
$recentApps = array_slice(xander_institution_list_applications($conn, $universityId), 0, 8);

function adm_ov_h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$homepageUrl = pcvc_public_url('/index.php#opportunities');
$portalLoginUrl = pcvc_public_url('/institution-login.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= adm_ov_h((string) ($account['university_name'] ?? 'Institution')) ?> | Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body { font-family: Inter, system-ui, sans-serif; background:#f1f5f9; }
    .wrap { max-width:1100px; margin:0 auto; padding:1.25rem 1rem 2.5rem; }
    .card-panel { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:1.25rem; margin-bottom:1rem; }
    .stat { background:#f8fafc; border-radius:10px; padding:.85rem; text-align:center; }
    .stat strong { font-size:1.4rem; color:#0a1f44; display:block; }
    .btn-navy { background:#0a1f44; color:#fff; }
    .btn-navy:hover { background:#152a5c; color:#fff; }
  </style>
</head>
<body>
<div class="wrap">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
      <a href="admin-institutions-monitor.php" class="text-decoration-none small">&larr; All institutions</a>
      <h1 class="h4 fw-bold mt-1 mb-0"><?= adm_ov_h((string) ($account['university_name'] ?? '')) ?></h1>
      <p class="text-muted small mb-0">
        <?= adm_ov_h((string) ($account['contact_name'] ?? '')) ?>
        &middot; <?= adm_ov_h((string) ($account['email'] ?? '')) ?>
      </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-sm btn-outline-secondary" href="<?= adm_ov_h($portalLoginUrl) ?>" target="_blank" rel="noopener">Institution login</a>
      <a class="btn btn-sm btn-navy" href="<?= adm_ov_h($homepageUrl) ?>" target="_blank" rel="noopener">View homepage offers</a>
    </div>
  </div>

  <div class="row g-2 mb-3">
    <?php
    $statCards = [
        ['Applications', (int) ($stats['total_applications'] ?? 0)],
        ['New', (int) ($stats['new_applications'] ?? 0)],
        ['Under review', (int) ($stats['pending_reviews'] ?? 0)],
        ['Accepted', (int) ($stats['approved_students'] ?? 0)],
        ['Active scholarships', (int) ($stats['active_scholarships'] ?? 0)],
        ['Programs', (int) ($stats['active_programs'] ?? 0)],
    ];
    foreach ($statCards as [$label, $val]): ?>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="stat"><strong><?= $val ?></strong><span class="small text-muted"><?= adm_ov_h($label) ?></span></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card-panel">
        <h2 class="h6 fw-bold">Profile &amp; publishing</h2>
        <ul class="list-unstyled small mb-0">
          <li>Account status: <strong><?= adm_ov_h(ucfirst((string) ($account['account_status'] ?? 'active'))) ?></strong></li>
          <li>Homepage: <strong><?= !empty($account['homepage_published']) ? 'Published' : 'Not published' ?></strong></li>
          <li>Scholarship profile: <strong><?= !empty($account['profile_complete_scholarship']) ? 'Complete' : 'Incomplete' ?></strong></li>
          <li>Loan profile: <strong><?= !empty($account['profile_complete_loan']) ? 'Complete' : 'Incomplete' ?></strong></li>
          <li>Last login: <strong><?= adm_ov_h($account['last_login_at'] ? (string) $account['last_login_at'] : 'Never') ?></strong></li>
          <li>Registered: <strong><?= adm_ov_h((string) ($account['registered_at'] ?? '')) ?></strong></li>
        </ul>
        <?php if (!empty($account['website'])): ?>
        <p class="small mt-2 mb-0"><a href="<?= adm_ov_h((string) $account['website']) ?>" target="_blank" rel="noopener">Institution website</a></p>
        <?php endif; ?>
      </div>

      <div class="card-panel">
        <h2 class="h6 fw-bold">Scholarships (<?= count($scholarships) ?>)</h2>
        <?php if ($scholarships === []): ?>
        <p class="text-muted small mb-0">No scholarships listed.</p>
        <?php else: ?>
        <ul class="list-group list-group-flush small">
          <?php foreach (array_slice($scholarships, 0, 6) as $s): ?>
          <li class="list-group-item px-0 d-flex justify-content-between">
            <span><?= adm_ov_h((string) ($s['title'] ?? '')) ?></span>
            <span class="badge bg-secondary"><?= adm_ov_h((string) ($s['status'] ?? '')) ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card-panel">
        <h2 class="h6 fw-bold">Recent applications</h2>
        <?php if ($recentApps === []): ?>
        <p class="text-muted small mb-0">No applications yet.</p>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Applicant</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($recentApps as $app): ?>
              <tr>
                <td><?= adm_ov_h(trim((string) ($app['first_name'] ?? '') . ' ' . (string) ($app['last_name'] ?? ''))) ?></td>
                <td><span class="badge bg-light text-dark"><?= adm_ov_h((string) ($app['status'] ?? '')) ?></span></td>
                <td class="text-muted small"><?= adm_ov_h(substr((string) ($app['created_at'] ?? ''), 0, 10)) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <div class="card-panel">
        <h2 class="h6 fw-bold">Programs (<?= count($programs) ?>)</h2>
        <?php if ($programs === []): ?>
        <p class="text-muted small mb-0">No programs listed.</p>
        <?php else: ?>
        <ul class="list-group list-group-flush small">
          <?php foreach (array_slice($programs, 0, 6) as $p): ?>
          <li class="list-group-item px-0"><?= adm_ov_h((string) ($p['title'] ?? $p['name'] ?? '')) ?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>
