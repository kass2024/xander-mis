<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/company_branding.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/institution_portal.php';
require_once __DIR__ . '/../helpers/urls.php';

xander_institution_portal_ensure_schema($conn);

$accountId = (int) ($_SESSION['institution_account_id'] ?? 0);
$universityId = (int) ($_SESSION['institution_university_id'] ?? 0);
$contactName = (string) ($_SESSION['institution_name'] ?? '');
$uniName = (string) ($_SESSION['institution_university_name'] ?? '');
$accountEmail = (string) ($_SESSION['institution_email'] ?? '');

$university = xander_institution_load_university_by_id($conn, $universityId);
if ($university) {
    $uniName = (string) ($university['name'] ?? $uniName);
}

$activeTab = (string) ($_GET['tab'] ?? 'overview');
if (!in_array($activeTab, ['overview', 'scholarship', 'loan', 'profile'], true)) {
    $activeTab = 'overview';
}

if (!empty($_SESSION['institution_flash'])) {
    $flash = (string) ($_SESSION['institution_flash']['message'] ?? '');
    $flashType = (string) ($_SESSION['institution_flash']['type'] ?? 'success');
    unset($_SESSION['institution_flash']);
} else {
    $flash = '';
    $flashType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && pcvc_csrf_validate_post()) {
    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'delete_doc') {
        $docId = (int) ($_POST['doc_id'] ?? 0);
        if ($docId > 0 && xander_institution_delete_document($conn, $docId, $universityId)) {
            $flash = 'Document removed.';
        } else {
            $flash = 'Could not remove document.';
            $flashType = 'danger';
        }
    } elseif ($action === 'update_account') {
        $activeTab = 'profile';
        $upd = xander_institution_update_account_profile($conn, $accountId, $_POST);
        $flash = $upd['message'];
        $flashType = $upd['ok'] ? 'success' : 'danger';
        if ($upd['ok']) {
            $_SESSION['institution_name'] = trim((string) ($_POST['contact_name'] ?? ''));
            $_SESSION['institution_email'] = xander_institution_email_norm((string) ($_POST['email'] ?? ''));
            $contactName = (string) $_SESSION['institution_name'];
            $accountEmail = (string) $_SESSION['institution_email'];
        }
    } elseif ($action === 'change_password') {
        $activeTab = 'profile';
        $pw = xander_institution_change_account_password(
            $conn,
            $accountId,
            (string) ($_POST['current_password'] ?? ''),
            (string) ($_POST['new_password'] ?? ''),
            (string) ($_POST['new_password_confirm'] ?? '')
        );
        $flash = $pw['message'];
        $flashType = $pw['ok'] ? 'success' : 'danger';
    } else {
        $postTab = (string) ($_POST['upload_section'] ?? '');
        if (in_array($postTab, ['scholarship', 'loan'], true)) {
            $activeTab = $postTab;
        }
        $save = xander_institution_save_profile($conn, $universityId, $_POST);
        if ($save['ok']) {
            $flash = $save['message'];
            $section = (string) ($_POST['upload_section'] ?? '');
            if (!empty($_FILES['profile_file']['name']) && is_array($_FILES['profile_file'])) {
                $upload = xander_institution_store_upload(
                    $conn,
                    $universityId,
                    $section !== '' ? $section : $activeTab,
                    $_FILES['profile_file'],
                    (string) ($_POST['file_label'] ?? '')
                );
                if ($upload['ok']) {
                    $flash .= ' ' . $upload['message'];
                } else {
                    $flash .= ' ' . $upload['message'];
                    $flashType = 'warning';
                }
            }
        } else {
            $flash = $save['message'];
            $flashType = 'danger';
        }
    }

    $_SESSION['institution_flash'] = ['message' => $flash, 'type' => $flashType];
    header('Location: ' . pcvc_url('/institution/index.php?tab=' . urlencode($activeTab)));
    exit;
}

$account = xander_institution_load_account($conn, $accountId) ?? [];
if ($account) {
    $contactName = trim((string) ($account['contact_name'] ?? $contactName));
    $accountEmail = xander_institution_email_norm((string) ($account['email'] ?? $accountEmail));
}

$profile = xander_institution_load_profile($conn, $universityId);
$docsScholarship = xander_institution_list_documents($conn, $universityId, 'scholarship');
$docsLoan = xander_institution_list_documents($conn, $universityId, 'loan');

$overview = xander_institution_dashboard_overview($profile, count($docsScholarship), count($docsLoan));
$schPct = (int) ($overview['scholarship_pct'] ?? 0);
$loanPct = (int) ($overview['loan_pct'] ?? 0);
$overallPct = (int) ($overview['overall_pct'] ?? 0);

$overviewNavClass = $activeTab === 'overview' ? 'active' : '';
$schNavClass = $activeTab === 'scholarship' ? 'active' : '';
$loanNavClass = $activeTab === 'loan' ? 'active' : '';
$profileNavClass = $activeTab === 'profile' ? 'active' : '';
$schPaneClass = $activeTab === 'scholarship' ? 'tab-pane active' : 'tab-pane';
$loanPaneClass = $activeTab === 'loan' ? 'tab-pane active' : 'tab-pane';
$profilePaneClass = $activeTab === 'profile' ? 'tab-pane active' : 'tab-pane';
$overviewTabUrl = 'index.php?tab=overview';
$isEditorTab = in_array($activeTab, ['scholarship', 'loan'], true);
$userInitials = xander_institution_initials($contactName);
$profileTabUrl = 'index.php?tab=profile';
$loanNamePlaceholder = '-';
$pvTitle = xander_institution_str_or((string) ($profile['scholarship_program_name'] ?? ''), $uniName);
$pvTagline = xander_institution_str_or((string) ($profile['scholarship_tagline'] ?? ''), 'Scholarship and loan opportunities');
$pvSchSummary = xander_institution_str_or((string) ($profile['scholarship_summary'] ?? ''), 'Your scholarship summary will appear here.');
$pvLoanName = xander_institution_str_or((string) ($profile['loan_institution_name'] ?? ''), $loanNamePlaceholder);
$pvLoanSummary = xander_institution_str_or((string) ($profile['loan_summary'] ?? ''), 'Loan partnership details will appear here.');
$flashAlertClass = $flash !== '' ? 'alert alert-' . xander_institution_h($flashType) : '';
$instDashConfigJson = json_encode(
    ['tab' => $activeTab, 'uniName' => $uniName],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
);
$fontsCssUrl = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap';
$scholarshipTabUrl = 'index.php?tab=scholarship';
$loanTabUrl = 'index.php?tab=loan';
$showWelcome = !empty($_GET['welcome']);
$overviewSchTitle = xander_institution_str_or((string) ($overview['scholarship_title'] ?? ''), 'Not set yet');
$overviewLoanTitle = xander_institution_str_or((string) ($overview['loan_title'] ?? ''), 'Not set yet');
$pageHeading = $activeTab === 'overview' ? 'Dashboard overview' : 'Institution content hub';
$logoutUrl = 'logout.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Institution Portal | <?= xander_institution_h($uniName) ?></title>
  <link href="<?php echo xander_institution_h($fontsCssUrl); ?>" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="dashboard.css">
  <!-- legacy inline styles superseded by dashboard.css -->
  <style id="legacy-dash-style" hidden>
    :root{--navy:#012F6B;--navy2:#254D81;--gold:#F2A65A;--bg:#eef2f7;--card:#fff;--muted:#64748b;}
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,system-ui,sans-serif;background:var(--bg);color:#0f172a;min-height:100vh}
    .shell{display:grid;grid-template-columns:260px 1fr;min-height:100vh}
    @media(max-width:992px){.shell{grid-template-columns:1fr}}
    .sidebar{background:linear-gradient(180deg,var(--navy) 0%,#001a3d 100%);color:#fff;padding:28px 22px;display:flex;flex-direction:column;gap:20px}
    .sidebar-brand{font-weight:800;font-size:1.05rem;line-height:1.35}
    .sidebar-brand small{display:block;font-weight:500;opacity:.75;font-size:.78rem;margin-top:6px}
    .nav-pill{display:flex;flex-direction:column;gap:8px}
    .nav-pill a{color:rgba(255,255,255,.85);text-decoration:none;padding:12px 14px;border-radius:12px;font-weight:600;font-size:.9rem;display:flex;align-items:center;gap:10px;transition:.2s}
    .nav-pill a:hover,.nav-pill a.active{background:rgba(255,255,255,.12);color:#fff}
    .nav-pill a.active{border-left:3px solid var(--gold);padding-left:11px}
    .progress-ring{background:rgba(255,255,255,.08);border-radius:16px;padding:16px;text-align:center}
    .progress-ring .pct{font-size:2rem;font-weight:800;color:var(--gold)}
    .main{padding:24px 28px 48px;overflow-x:hidden}
    .top{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:24px}
    .top h1{font-size:1.65rem;font-weight:800;color:var(--navy);margin:0 0 6px}
    .layout{display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start}
    @media(max-width:1200px){.layout{grid-template-columns:1fr}}
    .panel{background:var(--card);border-radius:20px;border:1px solid #e2e8f0;box-shadow:0 8px 32px rgba(1,47,107,.06);padding:28px}
    .panel-head{display:flex;align-items:center;gap:12px;margin-bottom:22px;padding-bottom:16px;border-bottom:1px solid #f1f5f9}
    .panel-head .icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.25rem}
    .panel-head .icon.sch{background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e}
    .panel-head .icon.loan{background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#1e40af}
    .form-label{font-weight:600;font-size:.85rem;color:#334155}
    .form-control,.form-select{border-radius:10px;border-color:#e2e8f0;padding:10px 12px}
    .form-control:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(242,166,90,.25)}
  .upload-zone{border:2px dashed #cbd5e1;border-radius:14px;padding:24px;text-align:center;background:#f8fafc;transition:.2s}
    .upload-zone:hover{border-color:var(--gold);background:#fffbeb}
    .doc-list{margin-top:16px;display:flex;flex-direction:column;gap:8px}
    .doc-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;background:#f8fafc;border-radius:10px;font-size:.85rem}
    .preview{position:sticky;top:24px}
    .preview-card{background:var(--card);border-radius:20px;overflow:hidden;box-shadow:0 12px 40px rgba(1,47,107,.12);border:1px solid #e2e8f0}
    .preview-hero{padding:24px;background:linear-gradient(135deg,var(--navy),var(--navy2));color:#fff}
    .preview-hero h2{font-size:1.2rem;font-weight:800;margin:0 0 8px}
    .preview-hero p{margin:0;opacity:.9;font-size:.88rem}
    .preview-body{padding:20px;font-size:.88rem;color:#334155}
    .preview-body h4{font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin:16px 0 8px}
    .preview-pill{display:inline-block;background:#f1f5f9;padding:4px 10px;border-radius:999px;font-size:.75rem;font-weight:600;margin:2px 4px 2px 0}
    .btn-save{background:linear-gradient(135deg,var(--navy),var(--navy2));color:#fff;font-weight:700;border:0;padding:12px 28px;border-radius:12px}
    .btn-save:hover{filter:brightness(1.08);color:#fff}
    .tab-pane{display:none}
    .tab-pane.active{display:block}
    .hint{font-size:.8rem;color:var(--muted);margin-top:4px}
  </style>
</head>
<body>
  <header class="app-header">
    <div class="header-left">
      <button type="button" class="icon-btn menu-btn" id="menuOpenBtn" aria-label="Open menu"><i class="fas fa-bars"></i></button>
      <div class="brand-lockup">
        <span class="brand-icon"><i class="fas fa-building-columns"></i></span>
        <span class="brand-text"><?php echo xander_institution_h($uniName); ?></span>
      </div>
    </div>
    <div class="header-right">
      <span class="readiness-pill" title="Content readiness"><strong><?php echo (int) $overallPct; ?>%</strong> ready</span>
      <?php if ($isEditorTab): ?>
      <button type="button" class="icon-btn preview-btn" id="previewToggleBtn" aria-label="Preview"><i class="fas fa-eye"></i></button>
      <?php endif; ?>
      <a href="<?php echo xander_institution_h($logoutUrl); ?>" class="icon-btn logout-btn" title="Sign out" aria-label="Sign out"><i class="fas fa-right-from-bracket"></i></a>
      <div class="profile-menu" id="profileMenu">
        <button type="button" class="profile-trigger" id="profileMenuBtn" aria-expanded="false" aria-haspopup="true">
          <span class="user-avatar"><?php echo xander_institution_h($userInitials); ?></span>
          <span class="profile-trigger-text">
            <strong><?php echo xander_institution_h($contactName); ?></strong>
            <small><?php echo xander_institution_h($accountEmail); ?></small>
          </span>
          <i class="fas fa-chevron-down chev"></i>
        </button>
        <div class="profile-dropdown" id="profileDropdown" role="menu">
          <a href="<?php echo xander_institution_h($profileTabUrl); ?>" role="menuitem"><i class="fas fa-user-gear"></i> My profile</a>
          <a href="<?php echo xander_institution_h($logoutUrl); ?>" class="logout" role="menuitem"><i class="fas fa-right-from-bracket"></i> Sign out</a>
        </div>
      </div>
    </div>
  </header>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  <div class="shell">
    <aside class="sidebar" id="sidebar">
      <button type="button" class="sidebar-close" id="menuCloseBtn" aria-label="Close menu"><i class="fas fa-times"></i></button>
      <p class="sidebar-label">Sections</p>
      <nav class="nav-pill">
        <a href="<?php echo xander_institution_h($overviewTabUrl); ?>" class="<?php echo xander_institution_h($overviewNavClass); ?>"><i class="fas fa-chart-pie"></i> Overview</a>
        <a href="<?php echo xander_institution_h($scholarshipTabUrl); ?>" class="<?php echo xander_institution_h($schNavClass); ?>"><i class="fas fa-award"></i> Scholarship</a>
        <a href="<?php echo xander_institution_h($loanTabUrl); ?>" class="<?php echo xander_institution_h($loanNavClass); ?>"><i class="fas fa-hand-holding-dollar"></i> Loan</a>
        <a href="<?php echo xander_institution_h($profileTabUrl); ?>" class="<?php echo xander_institution_h($profileNavClass); ?>"><i class="fas fa-user-gear"></i> My profile</a>
      </nav>
    </aside>

    <div class="main">
      <div class="page-head mb-3">
        <h1><?php echo xander_institution_h($pageHeading); ?></h1>
        <p class="page-sub text-muted mb-0"><?php echo xander_institution_h($uniName); ?></p>
        <?php if ($showWelcome): ?>
        <div class="alert alert-success py-2 mt-3 mb-0">Welcome. Start with your dashboard overview, then complete scholarship and loan content.</div>
        <?php endif; ?>
      </div>

      <?php if ($flash !== ''): ?>
      <div class="<?php echo xander_institution_h($flashAlertClass); ?> border-0 shadow-sm"><?php echo xander_institution_h($flash); ?></div>
      <?php endif; ?>

      <?php if ($activeTab === 'overview'): ?>
      <div class="dashboard-overview" id="tab-overview">
        <section class="dash-hero panel">
          <div class="dash-hero-grid">
            <div class="dash-hero-copy">
              <span class="dash-hero-badge"><i class="fas fa-building-columns me-1"></i> Institution portal</span>
              <h2 class="dash-hero-title">Your content readiness</h2>
              <p class="text-muted mb-0">Manage scholarship and loan information students will see on the public homepage.</p>
            </div>
            <div class="dash-hero-ring-wrap">
              <div class="ring-chart ring-overall" style="--pct: <?php echo (int) $overallPct; ?>; --ring-color: #f2a65a;">
                <div class="ring-chart-inner">
                  <strong><?php echo (int) $overallPct; ?>%</strong>
                  <small>Overall</small>
                </div>
              </div>
            </div>
          </div>
        </section>

        <div class="stat-grid">
          <article class="stat-card stat-sch">
            <div class="stat-icon"><i class="fas fa-award"></i></div>
            <div class="stat-body">
              <span class="stat-label">Scholarship</span>
              <strong class="stat-value"><?php echo (int) $schPct; ?>%</strong>
              <div class="stat-bar"><span style="width:<?php echo (int) $schPct; ?>%"></span></div>
            </div>
          </article>
          <article class="stat-card stat-loan">
            <div class="stat-icon"><i class="fas fa-hand-holding-dollar"></i></div>
            <div class="stat-body">
              <span class="stat-label">Loan partner</span>
              <strong class="stat-value"><?php echo (int) $loanPct; ?>%</strong>
              <div class="stat-bar"><span style="width:<?php echo (int) $loanPct; ?>%"></span></div>
            </div>
          </article>
          <article class="stat-card stat-docs">
            <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
            <div class="stat-body">
              <span class="stat-label">Documents</span>
              <strong class="stat-value"><?php echo (int) ($overview['docs_total'] ?? 0); ?></strong>
              <p class="stat-meta mb-0"><?php echo (int) ($overview['docs_scholarship'] ?? 0); ?> scholarship · <?php echo (int) ($overview['docs_loan'] ?? 0); ?> loan</p>
            </div>
          </article>
          <article class="stat-card stat-status">
            <div class="stat-icon"><i class="fas fa-globe"></i></div>
            <div class="stat-body">
              <span class="stat-label">Homepage</span>
              <strong class="stat-value stat-value-sm"><?php echo !empty($overview['homepage_published']) ? 'Published' : 'Draft'; ?></strong>
              <p class="stat-meta mb-0">Preview before going live</p>
            </div>
          </article>
        </div>

        <div class="overview-layout">
          <section class="panel overview-rings-panel">
            <h3 class="h6 fw-bold text-uppercase text-muted mb-3">Progress breakdown</h3>
            <div class="rings-row">
              <div class="ring-block">
                <div class="ring-chart ring-sch" style="--pct: <?php echo (int) $schPct; ?>; --ring-color: #f59e0b;">
                  <div class="ring-chart-inner">
                    <strong><?php echo (int) $schPct; ?>%</strong>
                    <small>Scholarship</small>
                  </div>
                </div>
                <p class="ring-caption"><?php echo xander_institution_h($overviewSchTitle); ?></p>
              </div>
              <div class="ring-block">
                <div class="ring-chart ring-loan" style="--pct: <?php echo (int) $loanPct; ?>; --ring-color: #2563eb;">
                  <div class="ring-chart-inner">
                    <strong><?php echo (int) $loanPct; ?>%</strong>
                    <small>Loan</small>
                  </div>
                </div>
                <p class="ring-caption"><?php echo xander_institution_h($overviewLoanTitle); ?></p>
              </div>
            </div>
            <div class="stacked-chart mt-4" aria-hidden="true">
              <div class="stacked-row">
                <span>Scholarship fields</span>
                <div class="stacked-track"><span class="stacked-fill sch" style="width:<?php echo (int) $schPct; ?>%"></span></div>
                <strong><?php echo (int) $schPct; ?>%</strong>
              </div>
              <div class="stacked-row">
                <span>Loan fields</span>
                <div class="stacked-track"><span class="stacked-fill loan" style="width:<?php echo (int) $loanPct; ?>%"></span></div>
                <strong><?php echo (int) $loanPct; ?>%</strong>
              </div>
            </div>
          </section>

          <section class="panel overview-side">
            <h3 class="h6 fw-bold mb-3">Quick actions</h3>
            <div class="quick-actions">
              <a href="<?php echo xander_institution_h($scholarshipTabUrl); ?>" class="quick-action qa-sch">
                <i class="fas fa-pen-to-square"></i>
                <span>Edit scholarship</span>
                <i class="fas fa-arrow-right qa-arrow"></i>
              </a>
              <a href="<?php echo xander_institution_h($loanTabUrl); ?>" class="quick-action qa-loan">
                <i class="fas fa-pen-to-square"></i>
                <span>Edit loan content</span>
                <i class="fas fa-arrow-right qa-arrow"></i>
              </a>
              <a href="<?php echo xander_institution_h($profileTabUrl); ?>" class="quick-action qa-profile">
                <i class="fas fa-user-gear"></i>
                <span>Account settings</span>
                <i class="fas fa-arrow-right qa-arrow"></i>
              </a>
            </div>

            <h3 class="h6 fw-bold mt-4 mb-3">Next steps</h3>
            <ul class="next-steps-list">
              <?php foreach (($overview['next_steps'] ?? []) as $step): ?>
              <li class="next-step next-step-<?php echo xander_institution_h((string) ($step['tone'] ?? 'sch')); ?>">
                <i class="fas <?php echo xander_institution_h((string) ($step['icon'] ?? 'fa-circle')); ?>"></i>
                <a href="<?php echo xander_institution_h((string) ($step['url'] ?? '#')); ?>"><?php echo xander_institution_h((string) ($step['text'] ?? '')); ?></a>
              </li>
              <?php endforeach; ?>
            </ul>

            <div class="overview-preview-mini mt-4">
              <p class="small text-muted fw-semibold mb-2"><i class="fas fa-eye me-1"></i> Homepage preview</p>
              <div class="preview-card preview-card-mini">
                <div class="preview-hero">
                  <h2><?php echo xander_institution_h($pvTitle); ?></h2>
                  <p><?php echo xander_institution_h($pvTagline); ?></p>
                </div>
                <div class="preview-body">
                  <h4>Scholarship</h4>
                  <p class="small mb-2"><?php echo xander_institution_h(strlen($pvSchSummary) > 120 ? substr($pvSchSummary, 0, 117) . '...' : $pvSchSummary); ?></p>
                  <h4>Loan partner</h4>
                  <p class="small mb-0"><?php echo xander_institution_h(strlen($pvLoanSummary) > 100 ? substr($pvLoanSummary, 0, 97) . '...' : $pvLoanSummary); ?></p>
                </div>
              </div>
            </div>
          </section>
        </div>
      </div>
      <?php elseif ($activeTab === 'profile'): ?>
      <div class="<?php echo xander_institution_h($profilePaneClass); ?>" id="tab-profile">
        <div class="panel mb-4">
          <div class="panel-head">
            <div class="icon profile"><i class="fas fa-user-gear"></i></div>
            <div><h3 class="h5 fw-bold mb-0">My profile</h3></div>
          </div>
          <form method="post" class="row g-3 mb-0">
            <?php echo pcvc_csrf_input(); ?>
            <input type="hidden" name="action" value="update_account">
            <div class="col-md-6">
              <input class="form-control" name="contact_name" required placeholder="Full name *" value="<?php echo xander_institution_h((string) ($account['contact_name'] ?? $contactName)); ?>">
            </div>
            <div class="col-md-6">
              <input class="form-control" name="contact_title" placeholder="Job title" value="<?php echo xander_institution_h((string) ($account['contact_title'] ?? '')); ?>">
            </div>
            <div class="col-md-6">
              <input class="form-control" type="email" name="email" required placeholder="Email *" value="<?php echo xander_institution_h($accountEmail); ?>">
            </div>
            <div class="col-md-6">
              <input class="form-control" name="phone" placeholder="Phone" value="<?php echo xander_institution_h((string) ($account['phone'] ?? '')); ?>">
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-save"><i class="fas fa-save me-2"></i>Save profile</button>
            </div>
          </form>
          <div class="profile-section">
            <h4 class="h6 fw-bold mb-3">Change password</h4>
            <form method="post" class="row g-3">
              <?php echo pcvc_csrf_input(); ?>
              <input type="hidden" name="action" value="change_password">
              <div class="col-md-4">
                <input class="form-control" type="password" name="current_password" required placeholder="Current password" autocomplete="current-password">
              </div>
              <div class="col-md-4">
                <input class="form-control" type="password" name="new_password" required minlength="8" placeholder="New password (min 8)" autocomplete="new-password">
              </div>
              <div class="col-md-4">
                <input class="form-control" type="password" name="new_password_confirm" required minlength="8" placeholder="Confirm new password" autocomplete="new-password">
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-outline-primary">Update password</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php else: ?>
      <form method="post" enctype="multipart/form-data" id="profileForm">
        <?php echo pcvc_csrf_input(); ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="upload_section" id="upload_section" value="<?php echo xander_institution_h($activeTab); ?>">
        <div class="layout">
          <div class="editor">
            <div class="<?php echo xander_institution_h($schPaneClass); ?>" id="tab-scholarship">
              <div class="panel mb-4">
                <div class="panel-head">
                  <div class="icon sch"><i class="fas fa-award"></i></div>
                  <div>
                    <h3 class="h5 fw-bold mb-0">Full scholarship program</h3>
                    <p class="text-muted small mb-0">Describe your complete scholarship offering for international students.</p>
                  </div>
                </div>
                <div class="row g-3">
                  <div class="col-md-8">
                    <label class="form-label">Program name *</label>
                    <input class="form-control" name="scholarship_program_name" id="f_sch_name" value="<?= xander_institution_h($profile['scholarship_program_name'] ?? '') ?>" placeholder="e.g. Global Excellence Scholarship">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Application deadline</label>
                    <input class="form-control" type="date" name="scholarship_deadline" id="f_sch_deadline" value="<?= xander_institution_h($profile['scholarship_deadline'] ?? '') ?>">
                  </div>
                  <div class="col-12">
                    <label class="form-label">Tagline</label>
                    <input class="form-control" name="scholarship_tagline" id="f_sch_tagline" value="<?= xander_institution_h($profile['scholarship_tagline'] ?? '') ?>" placeholder="Short headline for your program">
                  </div>
                  <div class="col-12">
                    <label class="form-label">Program overview *</label>
                    <textarea class="form-control" name="scholarship_summary" id="f_sch_summary" rows="4" placeholder="What the scholarship covers, duration, levels of study..."><?= xander_institution_h($profile['scholarship_summary'] ?? '') ?></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Eligibility</label>
                    <textarea class="form-control" name="scholarship_eligibility" id="f_sch_elig" rows="3"><?= xander_institution_h($profile['scholarship_eligibility'] ?? '') ?></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Benefits and coverage</label>
                    <textarea class="form-control" name="scholarship_benefits" id="f_sch_ben" rows="3"><?= xander_institution_h($profile['scholarship_benefits'] ?? '') ?></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Award amount / value</label>
                    <input class="form-control" name="scholarship_amount_notes" id="f_sch_amt" value="<?= xander_institution_h($profile['scholarship_amount_notes'] ?? '') ?>" placeholder="e.g. Full tuition + living allowance">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Apply URL</label>
                    <input class="form-control" type="url" name="scholarship_apply_url" id="f_sch_url" value="<?= xander_institution_h($profile['scholarship_apply_url'] ?? '') ?>" placeholder="https://">
                  </div>
                </div>
                <div class="mt-4">
                  <label class="form-label">Upload scholarship documents</label>
                  <div class="upload-zone">
                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                    <p class="mb-2 small text-muted">PDF, Word, or images — max 12 MB</p>
                    <input type="file" name="profile_file" class="form-control form-control-sm profile-file-input" data-section="scholarship" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp">
                    <input type="text" class="form-control form-control-sm mt-2" name="file_label" placeholder="Document label (optional)">
                  </div>
                  <?php if ($docsScholarship): ?>
                  <div class="doc-list">
                    <?php foreach ($docsScholarship as $doc): ?>
                    <div class="doc-item">
                      <span><i class="fas fa-file-alt me-2 text-warning"></i><?= xander_institution_h(xander_institution_str_or((string) ($doc['label'] ?? ''), (string) ($doc['original_name'] ?? ''))) ?></span>
                      <span class="d-flex gap-1">
                        <a href="../<?= xander_institution_h($doc['stored_path']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">View</a>
                        <button type="submit" form="del-doc-<?= (int) $doc['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="1">Delete</button>
                      </span>
                    </div>
                    <?php endforeach; ?>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="<?php echo xander_institution_h($loanPaneClass); ?>" id="tab-loan">
              <div class="panel mb-4">
                <div class="panel-head">
                  <div class="icon loan"><i class="fas fa-hand-holding-dollar"></i></div>
                  <div>
                    <h3 class="h5 fw-bold mb-0">Loan institution partnership</h3>
                    <p class="text-muted small mb-0">Partner bank or lender details for student study loans.</p>
                  </div>
                </div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Loan program name</label>
                    <input class="form-control" name="loan_program_name" id="f_loan_prog" value="<?= xander_institution_h($profile['loan_program_name'] ?? '') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Lender / institution name *</label>
                    <input class="form-control" name="loan_institution_name" id="f_loan_name" value="<?= xander_institution_h($profile['loan_institution_name'] ?? '') ?>">
                  </div>
                  <div class="col-12">
                    <label class="form-label">Partnership overview *</label>
                    <textarea class="form-control" name="loan_summary" id="f_loan_summary" rows="4"><?= xander_institution_h($profile['loan_summary'] ?? '') ?></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">What the loan covers</label>
                    <textarea class="form-control" name="loan_coverage" id="f_loan_cov" rows="3"><?= xander_institution_h($profile['loan_coverage'] ?? '') ?></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Eligibility</label>
                    <textarea class="form-control" name="loan_eligibility" id="f_loan_elig" rows="3"><?= xander_institution_h($profile['loan_eligibility'] ?? '') ?></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Rates & terms (notes)</label>
                    <input class="form-control" name="loan_rates_notes" id="f_loan_rates" value="<?= xander_institution_h($profile['loan_rates_notes'] ?? '') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Contact email</label>
                    <input class="form-control" type="email" name="loan_contact_email" id="f_loan_email" value="<?= xander_institution_h($profile['loan_contact_email'] ?? '') ?>">
                  </div>
                  <div class="col-12">
                    <label class="form-label">Apply / info URL</label>
                    <input class="form-control" type="url" name="loan_apply_url" id="f_loan_url" value="<?= xander_institution_h($profile['loan_apply_url'] ?? '') ?>">
                  </div>
                </div>
                <div class="mt-4">
                  <label class="form-label">Upload loan documents</label>
                  <div class="upload-zone">
                    <i class="fas fa-file-invoice-dollar fa-2x text-muted mb-2"></i>
                    <p class="mb-2 small text-muted">Loan guides, rate sheets, application forms</p>
                    <input type="file" name="profile_file" class="form-control form-control-sm profile-file-input" data-section="loan" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp">
                  </div>
                  <?php if ($docsLoan): ?>
                  <div class="doc-list">
                    <?php foreach ($docsLoan as $doc): ?>
                    <div class="doc-item">
                      <span><i class="fas fa-file-alt me-2 text-primary"></i><?= xander_institution_h(xander_institution_str_or((string) ($doc['label'] ?? ''), (string) ($doc['original_name'] ?? ''))) ?></span>
                      <span class="d-flex gap-1">
                        <a href="../<?= xander_institution_h($doc['stored_path']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">View</a>
                        <button type="submit" form="del-doc-<?= (int) $doc['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="1">Delete</button>
                      </span>
                    </div>
                    <?php endforeach; ?>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="desktop-save-wrap mt-3">
              <button type="submit" class="btn btn-save btn-lg"><i class="fas fa-save me-2"></i>Save all changes</button>
            </div>
          </div>

          <aside class="preview" id="previewPanel">
            <p class="small text-muted fw-semibold mb-2"><i class="fas fa-eye me-1"></i> Live preview (homepage style)</p>
            <div class="preview-card" id="livePreview">
              <div class="preview-hero">
                <h2 id="pv_title"><?= xander_institution_h($pvTitle) ?></h2>
                <p id="pv_tagline"><?= xander_institution_h($pvTagline) ?></p>
              </div>
              <div class="preview-body">
                <h4>Scholarship</h4>
                <p id="pv_sch_summary"><?= xander_institution_h($pvSchSummary) ?></p>
                <div id="pv_sch_pills">
                  <?php if (!empty($profile['scholarship_amount_notes'])): ?><span class="preview-pill"><?= xander_institution_h($profile['scholarship_amount_notes']) ?></span><?php endif; ?>
                </div>
                <h4>Loan partner</h4>
                <p id="pv_loan_name" class="fw-bold mb-1"><?= xander_institution_h($pvLoanName) ?></p>
                <p id="pv_loan_summary" class="mb-0"><?= xander_institution_h($pvLoanSummary) ?></p>
              </div>
            </div>
          </aside>
        </div>
      </form>
      <?php endif; ?>

      <?php foreach (array_merge($docsScholarship, $docsLoan) as $doc): ?>
      <form method="post" id="del-doc-<?= (int) $doc['id'] ?>" class="d-none">
        <?= pcvc_csrf_input() ?>
        <input type="hidden" name="action" value="delete_doc">
        <input type="hidden" name="doc_id" value="<?= (int) $doc['id'] ?>">
      </form>
      <?php endforeach; ?>
    </div>
  </div>

  <nav class="mobile-bottom-nav" aria-label="Dashboard navigation">
    <a href="<?php echo xander_institution_h($overviewTabUrl); ?>" class="<?php echo xander_institution_h($overviewNavClass); ?>"><i class="fas fa-chart-pie"></i><span>Home</span></a>
    <a href="<?php echo xander_institution_h($scholarshipTabUrl); ?>" class="<?php echo xander_institution_h($schNavClass); ?>"><i class="fas fa-award"></i><span>Scholarship</span></a>
    <a href="<?php echo xander_institution_h($loanTabUrl); ?>" class="<?php echo xander_institution_h($loanNavClass); ?>"><i class="fas fa-hand-holding-dollar"></i><span>Loan</span></a>
    <a href="<?php echo xander_institution_h($profileTabUrl); ?>" class="<?php echo xander_institution_h($profileNavClass); ?>"><i class="fas fa-user-gear"></i><span>Profile</span></a>
  </nav>
  <?php if ($isEditorTab): ?>
  <button type="button" class="fab-save" id="fabSaveBtn" aria-label="Save" title="Save"><i class="fas fa-save"></i></button>
  <?php endif; ?>

  <script type="application/json" id="institution-dashboard-config"><?php echo $instDashConfigJson; ?></script>
  <script src="dashboard.js"></script>
</body>
</html>
