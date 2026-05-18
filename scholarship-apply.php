<?php
declare(strict_types=1);

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/csrf.php';
require_once __DIR__ . '/helpers/institution_dashboard.php';
require_once __DIR__ . '/helpers/institution_portal.php';
require_once __DIR__ . '/helpers/urls.php';

xander_institution_portal_ensure_schema($conn);

$id = (int) ($_GET['id'] ?? 0);
$sch = $id > 0 ? xander_public_load_scholarship($conn, $id) : null;

$message = '';
$messageType = 'success';
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && pcvc_csrf_validate_post()) {
    $postId = (int) ($_POST['scholarship_id'] ?? 0);
    $result = xander_submit_scholarship_application($conn, $postId, $_POST);
    $message = $result['message'];
    $messageType = $result['ok'] ? 'success' : 'danger';
    $submitted = $result['ok'];
    if ($result['ok']) {
        $sch = xander_public_load_scholarship($conn, $postId);
    }
}

if (!$sch && !$submitted) {
    http_response_code(404);
    exit('Scholarship not found or no longer available.');
}

$pageTitle = $sch ? (string) ($sch['title'] ?? 'Scholarship Application') : 'Application submitted';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> | Xander Global Scholars</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root { --navy: #012F6B; --gold: #F2A65A; }
    body { font-family: Inter, system-ui, sans-serif; background: #f1f5f9; }
    .apply-hero { background: linear-gradient(135deg, var(--navy), #254D81); color: #fff; padding: 48px 24px; }
    .apply-wrap { max-width: 820px; margin: -32px auto 48px; padding: 0 16px; }
    .apply-card { background: #fff; border-radius: 16px; box-shadow: 0 12px 40px rgba(1,47,107,.1); padding: 32px; }
    .apply-meta { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 12px; }
    .apply-pill { background: rgba(255,255,255,.15); padding: 6px 12px; border-radius: 999px; font-size: .85rem; }
    .btn-apply { background: var(--gold); border: 0; color: #1e293b; font-weight: 700; padding: 12px 28px; border-radius: 10px; }
    .btn-apply:hover { filter: brightness(1.05); color: #1e293b; }
  </style>
</head>
<body>
  <div class="apply-hero text-center">
    <p class="mb-1 opacity-75"><i class="fas fa-university me-1"></i> <?= htmlspecialchars((string) ($sch['university_name'] ?? '')) ?></p>
    <h1 class="h2 fw-bold mb-2"><?= htmlspecialchars((string) ($sch['title'] ?? '')) ?></h1>
    <?php if (!empty($sch['tagline'])): ?>
    <p class="mb-0 opacity-90"><?= htmlspecialchars((string) $sch['tagline']) ?></p>
    <?php endif; ?>
    <div class="apply-meta justify-content-center">
      <?php if (!empty($sch['country_name'])): ?><span class="apply-pill"><?= htmlspecialchars((string) $sch['country_name']) ?></span><?php endif; ?>
      <?php if (!empty($sch['award_amount'])): ?><span class="apply-pill"><?= htmlspecialchars((string) $sch['award_amount']) ?></span><?php endif; ?>
      <?php if (!empty($sch['deadline'])): ?><span class="apply-pill">Deadline: <?= htmlspecialchars(date('M j, Y', strtotime((string) $sch['deadline']))) ?></span><?php endif; ?>
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
        <p class="text-muted">Your application has been sent to the institution for review.</p>
        <a href="<?= htmlspecialchars(pcvc_url('/index.php#scholarships')) ?>" class="btn btn-outline-primary mt-2">Browse more scholarships</a>
      </div>
      <?php else: ?>
      <?php if (!empty($sch['summary'])): ?>
      <p class="text-muted"><?= nl2br(htmlspecialchars((string) $sch['summary'])) ?></p>
      <hr>
      <?php endif; ?>

      <form method="post" class="row g-3">
        <?= pcvc_csrf_input() ?>
        <input type="hidden" name="scholarship_id" value="<?= (int) ($sch['id'] ?? 0) ?>">
        <div class="col-md-6">
          <label class="form-label">Full name *</label>
          <input class="form-control" name="applicant_name" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email *</label>
          <input class="form-control" type="email" name="applicant_email" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Phone</label>
          <input class="form-control" name="applicant_phone">
        </div>
        <div class="col-md-6">
          <label class="form-label">Nationality</label>
          <input class="form-control" name="nationality">
        </div>
        <div class="col-md-6">
          <label class="form-label">Date of birth</label>
          <input class="form-control" type="date" name="date_of_birth">
        </div>
        <div class="col-md-6">
          <label class="form-label">Education level</label>
          <input class="form-control" name="education_level" placeholder="e.g. Bachelor's graduate">
        </div>
        <div class="col-12">
          <label class="form-label">Current institution</label>
          <input class="form-control" name="current_institution">
        </div>
        <div class="col-12">
          <label class="form-label">Personal statement / motivation</label>
          <textarea class="form-control" name="statement" rows="5" placeholder="Why you are applying for this scholarship..."></textarea>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-apply"><i class="fas fa-paper-plane me-2"></i>Submit application</button>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
