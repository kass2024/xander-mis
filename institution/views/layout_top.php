<?php
/** @var string $uniName @var string $contactName @var string $accountEmail @var string $activeTab @var string $activeSection */
/** @var int $overallPct @var string $userInitials @var string $flash @var string $flashType */
$navTabs = [
    'dashboard' => ['icon' => 'fa-chart-pie', 'label' => 'Dashboard'],
    'scholarships' => ['icon' => 'fa-award', 'label' => 'Scholarships'],
    'programs' => ['icon' => 'fa-graduation-cap', 'label' => 'Programs'],
    'applications' => ['icon' => 'fa-inbox', 'label' => 'Applications'],
    'website' => ['icon' => 'fa-globe', 'label' => 'Homepage'],
    'profile' => ['icon' => 'fa-user-gear', 'label' => 'Settings'],
];
$activeSection = $activeSection ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Institution Portal | <?= xander_institution_h($uniName) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="dashboard.css">
</head>
<body>
  <header class="app-header">
    <div class="header-left">
      <button type="button" class="icon-btn menu-btn" id="menuOpenBtn" aria-label="Open menu"><i class="fas fa-bars"></i></button>
      <div class="brand-lockup">
        <span class="brand-icon"><i class="fas fa-building-columns"></i></span>
        <span class="brand-text"><?= xander_institution_h($uniName) ?></span>
      </div>
    </div>
    <div class="header-right">
      <span class="readiness-pill" title="Portal"><strong>XGS</strong> Institution</span>
      <a href="logout.php" class="icon-btn logout-btn" title="Sign out"><i class="fas fa-right-from-bracket"></i></a>
      <div class="profile-menu" id="profileMenu">
        <button type="button" class="profile-trigger" id="profileMenuBtn">
          <span class="user-avatar"><?= xander_institution_h($userInitials) ?></span>
          <span class="profile-trigger-text">
            <strong><?= xander_institution_h($contactName) ?></strong>
            <small><?= xander_institution_h($accountEmail) ?></small>
          </span>
        </button>
      </div>
    </div>
  </header>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <div class="shell">
    <aside class="sidebar" id="sidebar">
      <button type="button" class="sidebar-close" id="menuCloseBtn"><i class="fas fa-times"></i></button>
      <p class="sidebar-label">School & Institution</p>
      <nav class="nav-pill">
        <?php foreach ($navTabs as $key => $nav): ?>
        <a href="index.php?tab=<?= urlencode($key) ?>" class="<?= $activeTab === $key ? 'active' : '' ?>">
          <i class="fas <?= xander_institution_h($nav['icon']) ?>"></i> <?= xander_institution_h($nav['label']) ?>
        </a>
        <?php endforeach; ?>
      </nav>
      <?php if ($activeTab === 'scholarships'): ?>
      <p class="sidebar-label mt-3">Scholarships</p>
      <nav class="nav-sub">
        <a href="index.php?tab=scholarships" class="<?= $activeSection === '' ? 'active' : '' ?>">All scholarships</a>
        <a href="index.php?tab=scholarships&section=create" class="<?= $activeSection === 'create' ? 'active' : '' ?>">Create scholarship</a>
        <a href="index.php?tab=scholarships&section=active" class="<?= $activeSection === 'active' ? 'active' : '' ?>">Active</a>
        <a href="index.php?tab=scholarships&section=draft" class="<?= $activeSection === 'draft' ? 'active' : '' ?>">Drafts</a>
        <a href="index.php?tab=scholarships&section=expired" class="<?= $activeSection === 'expired' ? 'active' : '' ?>">Expired</a>
      </nav>
      <?php endif; ?>
      <?php if ($activeTab === 'programs'): ?>
      <p class="sidebar-label mt-3">Programs</p>
      <nav class="nav-sub">
        <a href="index.php?tab=programs" class="<?= $activeSection === '' ? 'active' : '' ?>">All programs</a>
        <a href="index.php?tab=programs&section=create" class="<?= $activeSection === 'create' ? 'active' : '' ?>">Add program</a>
      </nav>
      <?php endif; ?>
      <?php if ($activeTab === 'applications'): ?>
      <p class="sidebar-label mt-3">Applications</p>
      <nav class="nav-sub">
        <a href="index.php?tab=applications" class="<?= $activeSection === '' ? 'active' : '' ?>">All</a>
        <a href="index.php?tab=applications&section=new" class="<?= $activeSection === 'new' ? 'active' : '' ?>">New</a>
        <a href="index.php?tab=applications&section=under_review" class="<?= $activeSection === 'under_review' ? 'active' : '' ?>">Under review</a>
        <a href="index.php?tab=applications&section=accepted" class="<?= $activeSection === 'accepted' ? 'active' : '' ?>">Accepted</a>
        <a href="index.php?tab=applications&section=rejected" class="<?= $activeSection === 'rejected' ? 'active' : '' ?>">Rejected</a>
        <a href="index.php?tab=applications&section=waitlisted" class="<?= $activeSection === 'waitlisted' ? 'active' : '' ?>">Waitlisted</a>
      </nav>
      <?php endif; ?>
    </aside>
    <div class="main">
      <?php if ($flash !== ''): ?>
      <div class="alert alert-<?= xander_institution_h($flashType) ?> border-0 shadow-sm"><?= xander_institution_h($flash) ?></div>
      <?php endif; ?>
