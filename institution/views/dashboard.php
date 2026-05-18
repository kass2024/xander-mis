<?php
/** @var array<string,int> $stats @var array $activity @var string $uniName */
?>
<?php
$_totalApps     = (int) ($stats['total_applications'] ?? 0);
$_activeSch     = (int) ($stats['active_scholarships'] ?? 0);
$_pendingRev    = (int) ($stats['pending_reviews'] ?? 0);
$_approvedStud  = (int) ($stats['approved_students'] ?? 0);
$_newApps       = (int) ($stats['new_applications'] ?? 0);
$_greetingHour  = (int) date('G');
$_greeting      = $_greetingHour < 12 ? 'Good morning' : ($_greetingHour < 18 ? 'Good afternoon' : 'Good evening');
?>
<div class="dash-hero mb-4">
  <div class="dash-hero-grid">
    <div>
      <span class="dash-hero-badge"><i class="fas fa-building-columns me-1"></i> Institution Portal</span>
      <h1 class="dash-hero-title"><?= xander_institution_h($_greeting) ?>, <?= xander_institution_h(explode(' ', (string)$uniName)[0] ?? 'Team') ?> 👋</h1>
      <p class="text-muted mb-3" style="max-width:560px;">Here's how your scholarships and applications are doing today. Keep momentum by reviewing pending applications and publishing new opportunities.</p>
      <div class="d-flex flex-wrap gap-2">
        <a href="index.php?tab=scholarships&section=create" class="btn btn-save"><i class="fas fa-plus me-1"></i> New scholarship</a>
        <a href="index.php?tab=applications&section=new" class="btn btn-outline-primary" style="border-radius:12px;padding:13px 22px;font-weight:700;">
          <i class="fas fa-inbox me-1"></i> Review applications
          <?php if ($_newApps > 0): ?>
            <span class="badge bg-warning text-dark ms-1" style="border-radius:999px;font-weight:700;"><?= $_newApps ?> new</span>
          <?php endif; ?>
        </a>
      </div>
    </div>
    <div class="dash-hero-ring-wrap">
      <?php
      $_total = max(1, $_totalApps);
      $_pct = (int) round(($_approvedStud / $_total) * 100);
      ?>
      <div class="ring-block">
        <div class="ring-chart" style="--pct: <?= $_pct ?>; --ring-color: var(--gold);">
          <div class="ring-chart-inner">
            <strong><?= $_pct ?>%</strong>
            <small>Approved</small>
          </div>
        </div>
        <p class="ring-caption">Approval rate this period</p>
      </div>
    </div>
  </div>
</div>

<div class="stat-grid">
  <article class="stat-card">
    <div class="stat-icon" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#1e40af;"><i class="fas fa-inbox"></i></div>
    <div class="stat-body">
      <span class="stat-label">Total applications</span>
      <strong class="stat-value"><?= $_totalApps ?></strong>
      <small class="stat-meta">All time</small>
    </div>
  </article>
  <article class="stat-card stat-sch">
    <div class="stat-icon"><i class="fas fa-award"></i></div>
    <div class="stat-body">
      <span class="stat-label">Active scholarships</span>
      <strong class="stat-value"><?= $_activeSch ?></strong>
      <small class="stat-meta">Published & open</small>
    </div>
  </article>
  <article class="stat-card">
    <div class="stat-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e;"><i class="fas fa-hourglass-half"></i></div>
    <div class="stat-body">
      <span class="stat-label">Pending reviews</span>
      <strong class="stat-value"><?= $_pendingRev ?></strong>
      <small class="stat-meta">Awaiting your decision</small>
    </div>
  </article>
  <article class="stat-card stat-loan">
    <div class="stat-icon" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);color:#166534;"><i class="fas fa-user-check"></i></div>
    <div class="stat-body">
      <span class="stat-label">Approved students</span>
      <strong class="stat-value"><?= $_approvedStud ?></strong>
      <small class="stat-meta">Successful applicants</small>
    </div>
  </article>
</div>

<div class="overview-layout mt-4">
  <section class="panel">
    <h3 class="h6 fw-bold mb-3">Application analytics</h3>
    <div class="analytics-bars">
      <div class="stacked-row">
        <span>New</span>
        <div class="stacked-track"><span class="stacked-fill sch" style="width:<?= min(100, (int) ($stats['new_applications'] ?? 0) * 10) ?>%"></span></div>
        <strong><?= (int) ($stats['new_applications'] ?? 0) ?></strong>
      </div>
      <div class="stacked-row">
        <span>Under review</span>
        <div class="stacked-track"><span class="stacked-fill loan" style="width:<?= min(100, (int) ($stats['pending_reviews'] ?? 0) * 10) ?>%"></span></div>
        <strong><?= (int) ($stats['pending_reviews'] ?? 0) ?></strong>
      </div>
      <div class="stacked-row">
        <span>Accepted</span>
        <div class="stacked-track"><span class="stacked-fill" style="width:<?= min(100, (int) ($stats['approved_students'] ?? 0) * 10) ?>%;background:#16a34a"></span></div>
        <strong><?= (int) ($stats['approved_students'] ?? 0) ?></strong>
      </div>
    </div>
    <div class="quick-actions mt-4">
      <a href="index.php?tab=scholarships&section=create" class="quick-action qa-sch"><i class="fas fa-plus"></i><span>Create scholarship</span></a>
      <a href="index.php?tab=applications" class="quick-action qa-loan"><i class="fas fa-inbox"></i><span>Review applications</span></a>
      <a href="index.php?tab=programs&section=create" class="quick-action qa-profile"><i class="fas fa-graduation-cap"></i><span>Add program</span></a>
    </div>
  </section>

  <section class="panel overview-side">
    <h3 class="h6 fw-bold mb-3"><i class="fas fa-bell me-1"></i> Recent activity</h3>
    <?php if (empty($activity)): ?>
    <p class="text-muted small mb-0">No scholarship applications yet. Publish a scholarship to start receiving applications.</p>
    <?php else: ?>
    <ul class="activity-list">
      <?php foreach ($activity as $row): ?>
      <li>
        <strong><?= xander_institution_h((string) ($row['applicant_name'] ?? '')) ?></strong>
        <span class="badge-status status-<?= xander_institution_h((string) ($row['status'] ?? 'new')) ?>"><?= xander_institution_h((string) ($row['status'] ?? '')) ?></span>
        <small class="d-block text-muted"><?= xander_institution_h((string) ($row['scholarship_title'] ?? '')) ?> · <?= xander_institution_h(date('M j, Y', strtotime((string) ($row['created_at'] ?? 'now')))) ?></small>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>
</div>
