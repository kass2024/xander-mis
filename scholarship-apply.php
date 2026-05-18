<?php
declare(strict_types=1);

require_once __DIR__ . '/site_session_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/csrf.php';
pcvc_csrf_token();
require_once __DIR__ . '/helpers/institution_dashboard.php';
require_once __DIR__ . '/helpers/institution_portal.php';
require_once __DIR__ . '/helpers/urls.php';

xander_institution_portal_ensure_schema($conn);

$id = (int) ($_GET['id'] ?? 0);
$sch = $id > 0 ? xander_public_load_scholarship($conn, $id) : null;

$message = '';
$messageType = 'success';
$submitted = false;
$docFields = xander_scholarship_application_document_fields();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && pcvc_csrf_validate_post()) {
    $postId = (int) ($_POST['scholarship_id'] ?? 0);
    $result = xander_submit_scholarship_application($conn, $postId, $_POST, $_FILES);
    $message = $result['message'];
    $messageType = $result['ok'] ? 'success' : 'danger';
    $submitted = $result['ok'];
    if ($result['ok']) {
        $sch = xander_public_load_scholarship($conn, $postId);
    } elseif ($postId > 0) {
        $sch = xander_public_load_scholarship($conn, $postId);
    }
}

if (!$sch && !$submitted) {
    http_response_code(404);
    exit('Scholarship not found or no longer available.');
}

$pageTitle = $sch ? (string) ($sch['title'] ?? 'Scholarship Application') : 'Application submitted';
$old = static function (string $key) use ($submitted): string {
    if ($submitted) {
        return '';
    }
    return htmlspecialchars((string) ($_POST[$key] ?? ''), ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> | Xander Global Scholars</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --navy:#0a1f44; --navy-dark:#061538; --blue:#1e4a8c;
      --gold:#e87722; --gold-light:#fbbf24; --mint:#0d9488;
      --ink:#0f172a; --muted:#64748b; --bg:#eef2f7;
      --border:#e2e8f0; --soft:#f8fafc;
    }
    * { box-sizing: border-box; }
    body {
      font-family: 'Inter', system-ui, sans-serif;
      background:
        radial-gradient(900px 380px at 100% -10%, rgba(232, 119, 34, 0.10), transparent 60%),
        radial-gradient(900px 380px at -10% 110%, rgba(10, 31, 68, 0.10), transparent 60%),
        var(--bg);
      margin: 0;
      -webkit-font-smoothing: antialiased;
      color: var(--ink);
    }

    /* HERO */
    .apply-hero {
      background:
        radial-gradient(700px 220px at 100% 0%, rgba(232, 119, 34, 0.30), transparent 60%),
        linear-gradient(135deg, var(--navy) 0%, var(--blue) 100%);
      color: #fff;
      padding: 3rem 1.25rem 4.25rem;
      position: relative;
      overflow: hidden;
    }
    .apply-hero::after {
      content: '';
      position: absolute;
      left: 0; right: 0; bottom: 0;
      height: 70px;
      background: linear-gradient(180deg, transparent, var(--bg));
      pointer-events: none;
    }
    .apply-hero-inner {
      max-width: 980px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
    }
    .apply-hero .uni {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      opacity: .92;
      font-size: .85rem;
      font-weight: 600;
      padding: 0.4rem 0.85rem;
      background: rgba(255,255,255,0.12);
      border: 1px solid rgba(255,255,255,0.18);
      border-radius: 999px;
      margin-bottom: 1rem;
    }
    .apply-hero h1 {
      font-size: clamp(1.65rem, 4vw, 2.25rem);
      font-weight: 800;
      margin: 0 0 .6rem;
      letter-spacing: -0.02em;
      line-height: 1.2;
    }
    .apply-pills { display: flex; flex-wrap: wrap; gap: .55rem; margin-top: 1.1rem; }
    .apply-pill {
      background: rgba(255,255,255,.14);
      border: 1px solid rgba(255,255,255,.22);
      padding: .4rem .95rem;
      border-radius: 999px;
      font-size: .8rem;
      font-weight: 600;
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
    }

    /* WRAP */
    .apply-wrap {
      max-width: 980px;
      margin: -2.5rem auto 3.5rem;
      padding: 0 1rem;
      position: relative;
      z-index: 2;
    }
    .apply-card {
      background: #fff;
      border-radius: 22px;
      box-shadow: 0 24px 60px rgba(10, 31, 68, .14);
      padding: 2.25rem 2rem;
      border: 1px solid #eef2f7;
    }

    /* WIZARD STEPPER */
    .wizard-steps {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
      margin-bottom: 1.75rem;
      position: relative;
    }
    .wiz-step {
      text-align: center;
      position: relative;
      padding-top: 38px;
    }
    .wiz-step__dot {
      position: absolute;
      top: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: #fff;
      border: 2px solid #cbd5e1;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      color: var(--muted);
      font-size: 0.85rem;
      transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 2;
    }
    .wiz-step__label {
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--muted);
      transition: color 0.2s ease;
    }
    .wiz-step.is-active .wiz-step__dot {
      background: linear-gradient(135deg, var(--gold), var(--gold-light));
      border-color: var(--gold);
      color: #1e1e1e;
      box-shadow: 0 6px 18px rgba(232, 119, 34, 0.35);
      transform: translateX(-50%) scale(1.1);
    }
    .wiz-step.is-active .wiz-step__label {
      color: var(--navy);
      font-weight: 700;
    }
    .wiz-step.is-done .wiz-step__dot {
      background: var(--navy);
      border-color: var(--navy);
      color: #fff;
    }
    .wiz-step.is-done .wiz-step__dot::after {
      content: '\f00c';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
      font-size: 0.7rem;
    }
    .wiz-step.is-done .wiz-step__dot span { display: none; }
    .wiz-step.is-done .wiz-step__label { color: var(--navy); }
    .wiz-progress-track {
      position: absolute;
      top: 16px;
      left: calc(100%/8);
      right: calc(100%/8);
      height: 3px;
      background: #e2e8f0;
      border-radius: 2px;
      z-index: 1;
    }
    .wiz-progress-fill {
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      background: linear-gradient(90deg, var(--navy), var(--gold));
      border-radius: 2px;
      transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      width: 0;
    }
    @media (max-width: 640px) {
      .wiz-step__label { font-size: 0.66rem; }
      .wizard-steps { gap: 4px; }
    }

    /* WIZARD PANELS */
    .wiz-panel { display: none; animation: wizFade 0.32s ease; }
    .wiz-panel.is-active { display: block; }
    @keyframes wizFade {
      from { opacity: 0; transform: translateY(8px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* FORM SECTION */
    .form-section {
      border: 1px solid #eef2f7;
      border-radius: 16px;
      padding: 1.5rem 1.5rem 1.25rem;
      background: linear-gradient(180deg, #fff, #fbfdff);
      box-shadow: 0 4px 14px rgba(10, 31, 68, 0.04);
    }
    .form-section h2 {
      font-size: 1.05rem;
      font-weight: 800;
      color: var(--navy);
      margin: 0 0 0.4rem;
      display: flex;
      align-items: center;
      gap: .6rem;
    }
    .form-section h2 i {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: linear-gradient(135deg, #fef3c7, #fde68a);
      color: #92400e;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 0.95rem;
    }
    .form-section .section-desc {
      color: var(--muted);
      font-size: 0.88rem;
      margin: 0 0 1.25rem;
      padding-left: 48px;
    }

    /* INPUTS */
    .form-label {
      font-size: 0.78rem;
      font-weight: 700;
      color: #334155;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin-bottom: 6px;
    }
    .form-control, .form-select {
      border-radius: 10px;
      border: 1.5px solid #e2e8f0;
      padding: 11px 14px;
      font-size: 0.94rem;
      transition: all 0.18s ease;
      background: #fff;
    }
    .form-control:hover, .form-select:hover { border-color: #cbd5e1; }
    .form-control:focus, .form-select:focus {
      border-color: var(--blue);
      box-shadow: 0 0 0 4px rgba(30, 74, 140, 0.14);
    }
    .form-control.is-invalid {
      border-color: #dc2626;
      box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.12);
    }
    textarea.form-control { min-height: 120px; }

    /* DOC ROWS (modern uploader) */
    .doc-row {
      background: #fff;
      border: 2px dashed #cbd5e1;
      border-radius: 14px;
      padding: 1rem 1.15rem;
      margin-bottom: 0.85rem;
      transition: all 0.18s ease;
      position: relative;
    }
    .doc-row:hover {
      border-color: var(--blue);
      background: #f8fbff;
    }
    .doc-row.has-file {
      border-style: solid;
      border-color: #16a34a;
      background: linear-gradient(180deg, #f0fdf4, #fff);
    }
    .doc-row label {
      font-weight: 700;
      font-size: 0.92rem;
      color: var(--ink);
      margin-bottom: 0.4rem;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }
    .doc-row label::before {
      content: '\f0c6';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
      color: var(--blue);
      transition: color 0.18s ease;
    }
    .doc-row.has-file label::before {
      content: '\f00c';
      color: #16a34a;
    }
    .doc-row .hint {
      font-size: 0.78rem;
      color: var(--muted);
      margin-top: 0.35rem;
    }
    .doc-row .file-meta {
      display: none;
      margin-top: 0.5rem;
      padding: 0.55rem 0.85rem;
      background: #dcfce7;
      border-radius: 8px;
      font-size: 0.82rem;
      color: #14532d;
      font-weight: 600;
    }
    .doc-row.has-file .file-meta { display: flex; align-items: center; gap: 0.5rem; }
    .req-badge {
      font-size: .66rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .04em;
      color: #b45309;
      background: linear-gradient(135deg, #fef3c7, #fde68a);
      padding: .2rem .55rem;
      border-radius: 6px;
      margin-left: auto;
    }

    /* SCH DETAIL */
    .sch-detail {
      background: linear-gradient(135deg, #f0f7ff 0%, #fff 100%);
      border: 1px solid #dbeafe;
      border-left: 4px solid var(--blue);
      padding: 1.2rem 1.3rem;
      border-radius: 0 12px 12px 0;
      margin-bottom: 1.5rem;
      font-size: .92rem;
      color: #334155;
      line-height: 1.7;
    }
    .sch-detail strong { color: var(--navy); }

    /* BUTTONS */
    .btn-apply, .btn-wiz {
      border-radius: 12px;
      font-weight: 700;
      padding: 0.85rem 1.6rem;
      font-size: 0.94rem;
      border: 0;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      font-family: inherit;
      letter-spacing: 0.01em;
    }
    .btn-apply, .btn-wiz-primary {
      background: linear-gradient(135deg, var(--gold), var(--gold-light));
      color: #1e1e1e;
      box-shadow: 0 8px 22px rgba(232, 119, 34, 0.32);
    }
    .btn-apply:hover, .btn-wiz-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 30px rgba(232, 119, 34, 0.42);
      color: #1e1e1e;
      filter: brightness(1.04);
    }
    .btn-wiz-secondary {
      background: #fff;
      color: var(--ink);
      border: 1.5px solid #cbd5e1;
    }
    .btn-wiz-secondary:hover {
      background: #f8fafc;
      border-color: #94a3b8;
    }
    .file-note {
      font-size: 0.85rem;
      color: var(--muted);
      margin-bottom: 1.25rem;
      padding: 0.7rem 0.95rem;
      background: #f1f5f9;
      border-radius: 10px;
      border-left: 3px solid var(--blue);
    }

    /* WIZARD FOOTER */
    .wiz-foot {
      display: flex;
      gap: 0.5rem;
      align-items: center;
      flex-wrap: wrap;
      margin-top: 1.5rem;
      padding-top: 1.25rem;
      border-top: 1px solid #f1f5f9;
    }
    .wiz-foot .wiz-spacer { flex: 1; }

    /* REVIEW SUMMARY */
    .review-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 0.85rem;
      margin-top: 1rem;
    }
    @media (max-width: 640px) { .review-grid { grid-template-columns: 1fr; } }
    .review-item {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 0.85rem 1rem;
    }
    .review-item .label {
      font-size: 0.72rem;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin: 0 0 0.2rem;
    }
    .review-item .value {
      font-size: 0.95rem;
      color: var(--ink);
      font-weight: 600;
      margin: 0;
      word-break: break-word;
    }
    .review-item .value.empty { color: var(--muted); font-weight: 400; font-style: italic; }
    .review-block {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 0.85rem 1rem;
      margin-top: 0.85rem;
    }
    .review-block .label {
      font-size: 0.72rem;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin: 0 0 0.35rem;
    }
    .review-docs {
      display: grid;
      grid-template-columns: 1fr;
      gap: 0.5rem;
      margin-top: 0.85rem;
    }
    .review-doc {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      padding: 0.65rem 0.85rem;
      border-radius: 8px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      font-size: 0.88rem;
    }
    .review-doc i { color: #16a34a; }
    .review-doc.missing { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
    .review-doc.missing i { color: #dc2626; }

    /* SUCCESS */
    .apply-success {
      text-align: center;
      padding: 3rem 1.5rem;
    }
    .apply-success .ok-circle {
      width: 88px;
      height: 88px;
      border-radius: 50%;
      background: linear-gradient(135deg, #16a34a, #22c55e);
      color: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 2.4rem;
      box-shadow: 0 12px 30px rgba(22, 163, 74, 0.30);
      margin-bottom: 1.25rem;
      animation: okPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    @keyframes okPop {
      0% { transform: scale(0); opacity: 0; }
      60% { transform: scale(1.1); opacity: 1; }
      100% { transform: scale(1); }
    }

    @media (max-width: 576px) {
      .apply-card { padding: 1.5rem 1.15rem; }
      .apply-wrap { padding: 0 0.85rem; }
      .form-section { padding: 1.15rem 1rem 1rem; }
      .form-section .section-desc { padding-left: 0; }
    }
  </style>
</head>
<body>
  <div class="apply-hero">
    <div class="apply-hero-inner text-center text-md-start">
      <p class="uni mb-0"><i class="fas fa-university me-1"></i> <?= htmlspecialchars((string) ($sch['university_name'] ?? '')) ?></p>
      <h1><?= htmlspecialchars((string) ($sch['title'] ?? '')) ?></h1>
      <?php if (!empty($sch['tagline'])): ?>
      <p class="mb-0 opacity-90"><?= htmlspecialchars((string) $sch['tagline']) ?></p>
      <?php endif; ?>
      <div class="apply-pills">
        <?php if (!empty($sch['country_name'])): ?><span class="apply-pill"><i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars((string) $sch['country_name']) ?></span><?php endif; ?>
        <?php if (!empty($sch['award_amount'])): ?><span class="apply-pill"><i class="fas fa-coins me-1"></i><?= htmlspecialchars((string) $sch['award_amount']) ?></span><?php endif; ?>
        <?php if (!empty($sch['tuition_coverage'])): ?><span class="apply-pill"><i class="fas fa-graduation-cap me-1"></i><?= htmlspecialchars((string) $sch['tuition_coverage']) ?></span><?php endif; ?>
        <?php if (!empty($sch['deadline'])): ?><span class="apply-pill"><i class="fas fa-calendar me-1"></i>Deadline: <?= htmlspecialchars(date('M j, Y', strtotime((string) $sch['deadline']))) ?></span><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="apply-wrap">
    <div class="apply-card">
      <?php if ($message !== ''): ?>
      <div class="alert alert-<?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <?php if ($submitted): ?>
      <div class="text-center py-4">
        <i class="fas fa-circle-check fa-3x text-success mb-3"></i>
        <h2 class="h4 fw-bold">Thank you!</h2>
        <p class="text-muted">Your application and supporting documents have been sent to the institution for review.</p>
        <a href="<?= htmlspecialchars(pcvc_url('/index.php#opportunities')) ?>" class="btn btn-outline-primary mt-2">Back to homepage</a>
      </div>
      <?php else: ?>

      <?php if (!empty($sch['summary']) || !empty($sch['eligibility']) || !empty($sch['requirements'])): ?>
      <div class="sch-detail">
        <?php if (!empty($sch['summary'])): ?><p class="mb-2"><?= nl2br(htmlspecialchars((string) $sch['summary'])) ?></p><?php endif; ?>
        <?php if (!empty($sch['eligibility'])): ?>
        <p class="mb-1"><strong>Eligibility:</strong> <?= nl2br(htmlspecialchars((string) $sch['eligibility'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($sch['requirements'])): ?>
        <p class="mb-0"><strong>Requirements:</strong> <?= nl2br(htmlspecialchars((string) $sch['requirements'])) ?></p>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- WIZARD STEPPER -->
      <div class="wizard-steps" id="wizardSteps">
        <div class="wiz-progress-track"><div class="wiz-progress-fill" id="wizProgressFill"></div></div>
        <div class="wiz-step is-active" data-step="1">
          <span class="wiz-step__dot"><span>1</span></span>
          <span class="wiz-step__label">Personal</span>
        </div>
        <div class="wiz-step" data-step="2">
          <span class="wiz-step__dot"><span>2</span></span>
          <span class="wiz-step__label">Academic</span>
        </div>
        <div class="wiz-step" data-step="3">
          <span class="wiz-step__dot"><span>3</span></span>
          <span class="wiz-step__label">Documents</span>
        </div>
        <div class="wiz-step" data-step="4">
          <span class="wiz-step__dot"><span>4</span></span>
          <span class="wiz-step__label">Review</span>
        </div>
      </div>

      <form method="post" enctype="multipart/form-data" class="scholarship-apply-form" id="scholarshipApplyForm" novalidate>
        <?= pcvc_csrf_input() ?>
        <input type="hidden" name="scholarship_id" value="<?= (int) ($sch['id'] ?? 0) ?>">

        <!-- STEP 1: PERSONAL -->
        <section class="wiz-panel is-active" data-panel="1">
          <div class="form-section">
            <h2><i class="fas fa-user"></i> Personal information</h2>
            <p class="section-desc">Tell us who you are. We'll use this to contact you about your application.</p>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Full name *</label>
                <input class="form-control" name="applicant_name" required value="<?= $old('applicant_name') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Email *</label>
                <input class="form-control" type="email" name="applicant_email" required value="<?= $old('applicant_email') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Phone *</label>
                <input class="form-control" name="applicant_phone" required value="<?= $old('applicant_phone') ?>" placeholder="+1 555 123 4567">
              </div>
              <div class="col-md-6">
                <label class="form-label">Nationality *</label>
                <input class="form-control" name="nationality" required value="<?= $old('nationality') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Date of birth</label>
                <input class="form-control" type="date" name="date_of_birth" value="<?= $old('date_of_birth') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Current address</label>
                <input class="form-control" name="address" value="<?= $old('address') ?>" placeholder="Street, City, Country">
              </div>
            </div>
          </div>
          <div class="wiz-foot">
            <a href="<?= htmlspecialchars(pcvc_url('/index.php#opportunities')) ?>" class="btn-wiz btn-wiz-secondary" style="text-decoration:none;">
              <i class="fas fa-times"></i> Cancel
            </a>
            <div class="wiz-spacer"></div>
            <button type="button" class="btn-wiz btn-wiz-primary" data-wiz-next="2">
              Continue <i class="fas fa-arrow-right"></i>
            </button>
          </div>
        </section>

        <!-- STEP 2: ACADEMIC -->
        <section class="wiz-panel" data-panel="2">
          <div class="form-section">
            <h2><i class="fas fa-graduation-cap"></i> Academic profile</h2>
            <p class="section-desc">Share your educational background and motivation for applying.</p>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Education level *</label>
                <input class="form-control" name="education_level" required placeholder="e.g. Bachelor's graduate" value="<?= $old('education_level') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">GPA / grade average</label>
                <input class="form-control" name="gpa_or_grade" placeholder="e.g. 3.8 / 85%" value="<?= $old('gpa_or_grade') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Field of study</label>
                <input class="form-control" name="field_of_study" value="<?= $old('field_of_study') ?>" placeholder="e.g. Computer Science">
              </div>
              <div class="col-md-6">
                <label class="form-label">Program you are applying for</label>
                <input class="form-control" name="intended_program" value="<?= $old('intended_program') ?>" placeholder="e.g. MSc in Data Science">
              </div>
              <div class="col-12">
                <label class="form-label">Current institution</label>
                <input class="form-control" name="current_institution" value="<?= $old('current_institution') ?>" placeholder="University / school name">
              </div>
              <div class="col-12">
                <label class="form-label">Personal statement / motivation *</label>
                <textarea class="form-control" name="statement" rows="6" required placeholder="Why are you applying? What are your goals? How will this scholarship help you?"><?= $submitted ? '' : htmlspecialchars((string) ($_POST['statement'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                <div class="text-muted small mt-1"><i class="fas fa-lightbulb me-1"></i> Tip: 150–300 words is the sweet spot for a strong statement.</div>
              </div>
            </div>
          </div>
          <div class="wiz-foot">
            <button type="button" class="btn-wiz btn-wiz-secondary" data-wiz-back="1">
              <i class="fas fa-arrow-left"></i> Back
            </button>
            <div class="wiz-spacer"></div>
            <button type="button" class="btn-wiz btn-wiz-primary" data-wiz-next="3">
              Continue <i class="fas fa-arrow-right"></i>
            </button>
          </div>
        </section>

        <!-- STEP 3: DOCUMENTS -->
        <section class="wiz-panel" data-panel="3">
          <div class="form-section">
            <h2><i class="fas fa-paperclip"></i> Supporting documents</h2>
            <p class="section-desc">Upload your supporting files. PDF, Word, JPG, or PNG up to 12 MB per file.</p>
            <p class="file-note">
              <i class="fas fa-info-circle me-1"></i>
              Required items are marked. Optional documents may strengthen your application.
            </p>
            <?php foreach ($docFields as $fieldName => $meta): ?>
            <div class="doc-row" data-doc-row="<?= htmlspecialchars($fieldName) ?>">
              <label for="<?= htmlspecialchars($fieldName) ?>">
                <?= htmlspecialchars((string) $meta['label']) ?>
                <?php if (!empty($meta['required'])): ?><span class="req-badge">Required</span><?php endif; ?>
              </label>
              <input
                class="form-control form-control-sm doc-input"
                type="file"
                id="<?= htmlspecialchars($fieldName) ?>"
                name="<?= htmlspecialchars($fieldName) ?>"
                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp"
                <?= !empty($meta['required']) ? 'required' : '' ?>
                data-required="<?= !empty($meta['required']) ? '1' : '0' ?>"
              >
              <div class="hint"><i class="fas fa-file-arrow-up me-1"></i> Accepted: PDF, DOC, DOCX, JPG, PNG · max 12 MB</div>
              <div class="file-meta"><i class="fas fa-circle-check"></i> <span class="file-meta-name"></span></div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="wiz-foot">
            <button type="button" class="btn-wiz btn-wiz-secondary" data-wiz-back="2">
              <i class="fas fa-arrow-left"></i> Back
            </button>
            <div class="wiz-spacer"></div>
            <button type="button" class="btn-wiz btn-wiz-primary" data-wiz-next="4">
              Review <i class="fas fa-arrow-right"></i>
            </button>
          </div>
        </section>

        <!-- STEP 4: REVIEW & SUBMIT -->
        <section class="wiz-panel" data-panel="4">
          <div class="form-section">
            <h2><i class="fas fa-clipboard-check"></i> Review your application</h2>
            <p class="section-desc">Double-check your details below, then submit when you're ready.</p>
            <h4 style="font-size:0.86rem;font-weight:800;color:var(--navy);text-transform:uppercase;letter-spacing:0.04em;margin:1.25rem 0 0.4rem;"><i class="fas fa-user me-1" style="color:var(--gold);"></i> Personal</h4>
            <div class="review-grid" id="reviewPersonal"></div>
            <h4 style="font-size:0.86rem;font-weight:800;color:var(--navy);text-transform:uppercase;letter-spacing:0.04em;margin:1.5rem 0 0.4rem;"><i class="fas fa-graduation-cap me-1" style="color:var(--gold);"></i> Academic</h4>
            <div class="review-grid" id="reviewAcademic"></div>
            <div class="review-block" id="reviewStatementBlock" style="display:none;">
              <p class="label">Personal statement</p>
              <p class="value" id="reviewStatement" style="white-space:pre-wrap;"></p>
            </div>
            <h4 style="font-size:0.86rem;font-weight:800;color:var(--navy);text-transform:uppercase;letter-spacing:0.04em;margin:1.5rem 0 0.4rem;"><i class="fas fa-paperclip me-1" style="color:var(--gold);"></i> Documents</h4>
            <div class="review-docs" id="reviewDocs"></div>
          </div>
          <div class="wiz-foot">
            <button type="button" class="btn-wiz btn-wiz-secondary" data-wiz-back="3">
              <i class="fas fa-arrow-left"></i> Back
            </button>
            <div class="wiz-spacer"></div>
            <button type="submit" class="btn-apply" id="finalSubmitBtn">
              <i class="fas fa-paper-plane"></i> Submit application
            </button>
          </div>
        </section>
      </form>

      <script>
      (function() {
        'use strict';
        var form = document.getElementById('scholarshipApplyForm');
        if (!form) return;
        var totalSteps = 4;
        var currentStep = 1;
        var stepsRoot = document.getElementById('wizardSteps');
        var progressFill = document.getElementById('wizProgressFill');
        var panels = form.querySelectorAll('.wiz-panel');
        var stepEls = stepsRoot.querySelectorAll('.wiz-step');

        function updateProgress() {
          var pct = ((currentStep - 1) / (totalSteps - 1)) * 100;
          progressFill.style.width = pct + '%';
          stepEls.forEach(function(el) {
            var step = parseInt(el.getAttribute('data-step'), 10);
            el.classList.toggle('is-active', step === currentStep);
            el.classList.toggle('is-done', step < currentStep);
          });
        }

        function showPanel(step) {
          panels.forEach(function(p) {
            p.classList.toggle('is-active', parseInt(p.getAttribute('data-panel'), 10) === step);
          });
          currentStep = step;
          updateProgress();
          if (step === 4) buildReview();
          window.scrollTo({ top: form.getBoundingClientRect().top + window.scrollY - 80, behavior: 'smooth' });
        }

        function validatePanel(step) {
          var panel = form.querySelector('.wiz-panel[data-panel="' + step + '"]');
          if (!panel) return true;
          var valid = true;
          var firstInvalid = null;
          panel.querySelectorAll('[required]').forEach(function(input) {
            input.classList.remove('is-invalid');
            var ok = true;
            if (input.type === 'file') {
              ok = input.files && input.files.length > 0;
            } else {
              ok = String(input.value || '').trim().length > 0;
            }
            if (!ok) {
              valid = false;
              input.classList.add('is-invalid');
              if (!firstInvalid) firstInvalid = input;
            }
          });
          if (!valid && firstInvalid) {
            firstInvalid.focus();
          }
          return valid;
        }

        form.querySelectorAll('[data-wiz-next]').forEach(function(btn) {
          btn.addEventListener('click', function() {
            if (!validatePanel(currentStep)) return;
            var next = parseInt(btn.getAttribute('data-wiz-next'), 10);
            if (next >= 1 && next <= totalSteps) showPanel(next);
          });
        });

        form.querySelectorAll('[data-wiz-back]').forEach(function(btn) {
          btn.addEventListener('click', function() {
            var prev = parseInt(btn.getAttribute('data-wiz-back'), 10);
            if (prev >= 1 && prev <= totalSteps) showPanel(prev);
          });
        });

        // Live validation reset
        form.querySelectorAll('.form-control, .form-select').forEach(function(input) {
          input.addEventListener('input', function() { input.classList.remove('is-invalid'); });
          input.addEventListener('change', function() { input.classList.remove('is-invalid'); });
        });

        // Doc upload feedback
        form.querySelectorAll('.doc-input').forEach(function(input) {
          input.addEventListener('change', function() {
            var row = input.closest('.doc-row');
            if (!row) return;
            if (input.files && input.files.length > 0) {
              var f = input.files[0];
              var sizeKB = Math.round(f.size / 1024);
              var sizeStr = sizeKB > 1024 ? (sizeKB / 1024).toFixed(1) + ' MB' : sizeKB + ' KB';
              row.classList.add('has-file');
              var nameEl = row.querySelector('.file-meta-name');
              if (nameEl) nameEl.textContent = f.name + ' (' + sizeStr + ')';
              input.classList.remove('is-invalid');
            } else {
              row.classList.remove('has-file');
            }
          });
        });

        // Review builder
        function setReviewItem(container, label, value) {
          var item = document.createElement('div');
          item.className = 'review-item';
          var v = value && String(value).trim().length > 0 ? String(value) : '—';
          var empty = v === '—' ? ' empty' : '';
          item.innerHTML = '<p class="label">' + label + '</p><p class="value' + empty + '">' + escapeHtml(v) + '</p>';
          container.appendChild(item);
        }
        function escapeHtml(s) {
          return String(s).replace(/[&<>"']/g, function(c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
          });
        }
        function fieldVal(name) {
          var el = form.querySelector('[name="' + name + '"]');
          return el ? el.value : '';
        }

        function buildReview() {
          var p = document.getElementById('reviewPersonal');
          var a = document.getElementById('reviewAcademic');
          var sb = document.getElementById('reviewStatementBlock');
          var st = document.getElementById('reviewStatement');
          var d = document.getElementById('reviewDocs');
          if (!p || !a || !d) return;
          p.innerHTML = '';
          a.innerHTML = '';
          d.innerHTML = '';
          setReviewItem(p, 'Full name', fieldVal('applicant_name'));
          setReviewItem(p, 'Email', fieldVal('applicant_email'));
          setReviewItem(p, 'Phone', fieldVal('applicant_phone'));
          setReviewItem(p, 'Nationality', fieldVal('nationality'));
          setReviewItem(p, 'Date of birth', fieldVal('date_of_birth'));
          setReviewItem(p, 'Address', fieldVal('address'));

          setReviewItem(a, 'Education level', fieldVal('education_level'));
          setReviewItem(a, 'GPA / grade', fieldVal('gpa_or_grade'));
          setReviewItem(a, 'Field of study', fieldVal('field_of_study'));
          setReviewItem(a, 'Intended program', fieldVal('intended_program'));
          setReviewItem(a, 'Current institution', fieldVal('current_institution'));

          var statement = fieldVal('statement');
          if (statement && statement.trim().length > 0) {
            sb.style.display = 'block';
            st.textContent = statement;
          } else {
            sb.style.display = 'none';
          }

          form.querySelectorAll('.doc-input').forEach(function(input) {
            var row = input.closest('.doc-row');
            var labelEl = row && row.querySelector('label');
            var labelText = labelEl ? labelEl.firstChild.textContent.trim() : input.name;
            var required = input.getAttribute('data-required') === '1';
            var doc = document.createElement('div');
            if (input.files && input.files.length > 0) {
              doc.className = 'review-doc';
              var f = input.files[0];
              doc.innerHTML = '<i class="fas fa-circle-check"></i> <strong>' + escapeHtml(labelText) + ':</strong>&nbsp;' + escapeHtml(f.name);
            } else {
              doc.className = 'review-doc' + (required ? ' missing' : '');
              var icon = required ? 'fa-circle-exclamation' : 'fa-circle-minus';
              var note = required ? ' (Required — please go back and upload)' : ' (Not uploaded)';
              doc.innerHTML = '<i class="fas ' + icon + '"></i> <strong>' + escapeHtml(labelText) + ':</strong>&nbsp;<em>' + note + '</em>';
            }
            d.appendChild(doc);
          });
        }

        // Final submit guard: validate all steps before allowing submit
        form.addEventListener('submit', function(e) {
          for (var s = 1; s <= 3; s++) {
            if (!validatePanel(s)) {
              e.preventDefault();
              showPanel(s);
              return;
            }
          }
          var btn = document.getElementById('finalSubmitBtn');
          if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
          }
        });

        updateProgress();
      })();
      </script>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
