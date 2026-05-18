<?php
/** @var array<string,int> $stats @var array $activity @var string $uniName */
?>
<div class="page-head mb-4">
  <h1>Dashboard</h1>
  <p class="page-sub text-muted mb-0">Overview for <?= xander_institution_h($uniName) ?></p>
</div>

<div class="stat-grid">
  <article class="stat-card">
    <div class="stat-icon"><i class="fas fa-inbox"></i></div>
    <div class="stat-body">
      <span class="stat-label">Total applications</span>
      <strong class="stat-value"><?= (int) ($stats['total_applications'] ?? 0) ?></strong>
    </div>
  </article>
  <article class="stat-card stat-sch">
    <div class="stat-icon"><i class="fas fa-award"></i></div>
    <div class="stat-body">
      <span class="stat-label">Active scholarships</span>
      <strong class="stat-value"><?= (int) ($stats['active_scholarships'] ?? 0) ?></strong>
    </div>
  </article>
  <article class="stat-card">
    <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
    <div class="stat-body">
      <span class="stat-label">Pending reviews</span>
      <strong class="stat-value"><?= (int) ($stats['pending_reviews'] ?? 0) ?></strong>
    </div>
  </article>
  <article class="stat-card stat-loan">
    <div class="stat-icon"><i class="fas fa-user-check"></i></div>
    <div class="stat-body">
      <span class="stat-label">Approved students</span>
      <strong class="stat-value"><?= (int) ($stats['approved_students'] ?? 0) ?></strong>
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
