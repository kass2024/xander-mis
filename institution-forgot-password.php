<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/company_branding.php';
require_once __DIR__ . '/helpers/csrf.php';
require_once __DIR__ . '/helpers/institution_password_reset.php';

if (!empty($_SESSION['institution_account_id'])) {
    header('Location: institution/index.php');
    exit;
}

xander_ensure_institution_password_reset_columns($conn);

$message = '';
$error = '';
$prefillEmail = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!pcvc_csrf_validate_post()) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $email = xander_institution_email_norm((string) ($_POST['email'] ?? ''));
        $prefillEmail = $email;
        $result = xander_institution_password_reset_request($conn, $email);
        if ($result === 'invalid_input') {
            $error = 'Please enter the email address you used to register your institution.';
        } elseif ($result === 'db_error') {
            $error = 'Something went wrong. Please try again in a few minutes.';
        } else {
            $message = 'If an account exists for that email, we have sent a password reset link. Check your inbox and spam folder.';
        }
    }
} elseif (!empty($_GET['email'])) {
    $prefillEmail = xander_institution_email_norm((string) $_GET['email']);
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Password reset | <?= htmlspecialchars(PCVC_COMPANY_DISPLAY_NAME, ENT_QUOTES, 'UTF-8') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root{--navy:#012F6B;--gold:#F2A65A;}
    body{font-family:Inter,system-ui,sans-serif;min-height:100vh;margin:0;display:flex;align-items:center;justify-content:center;padding:24px;
      background:radial-gradient(ellipse 90% 70% at 10% 0%,rgba(1,47,107,.12),transparent 55%),#eef2f7;}
    .shell{max-width:980px;width:100%;background:#fff;border-radius:28px;overflow:hidden;box-shadow:0 32px 64px -16px rgba(1,47,107,.25);
      display:grid;grid-template-columns:1fr;}
    @media(min-width:900px){.shell{grid-template-columns:1fr 1fr;min-height:520px}}
    .brand{background:linear-gradient(165deg,var(--navy) 0%,#254D81 55%,#002765 100%);color:#fff;padding:48px 36px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;}
    .brand .logo{width:140px;height:140px;border-radius:50%;background:rgba(255,255,255,.1);border:3px solid rgba(242,166,90,.5);display:flex;align-items:center;justify-content:center;margin-bottom:20px;}
    .brand .logo img{width:88%;height:88%;object-fit:contain}
    .panel{padding:44px 40px}
    .panel::before{content:'';display:block;height:4px;background:linear-gradient(90deg,var(--navy),var(--gold));margin:-44px -40px 28px}
    .btn-navy{background:var(--navy);border-color:var(--navy);color:#fff;font-weight:600}
    .btn-navy:hover{background:#002765;border-color:#002765;color:#fff}
  </style>
</head>
<body>
  <div class="shell">
    <aside class="brand">
      <div class="logo">
        <img src="XANDER GLOBAL SCHOLARS LOGO1.png" alt="" onerror="this.parentElement.style.display='none'">
      </div>
      <h1 class="h4 fw-bold">Reset password</h1>
      <p class="mb-0 opacity-90 small">Enter your institution login email and we will send you a secure link to choose a new password.</p>
    </aside>
    <section class="panel">
      <h2 class="h4 fw-bold mb-1">Password reset</h2>
      <p class="text-muted small mb-4">Use the same email you registered with your institution portal account.</p>
      <?php if ($message !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <?php if ($message === ''): ?>
      <form method="post" autocomplete="off">
        <?= pcvc_csrf_input() ?>
        <div class="mb-3">
          <label class="form-label fw-semibold" for="email">Email</label>
          <input class="form-control" type="email" id="email" name="email" required value="<?= htmlspecialchars($prefillEmail, ENT_QUOTES, 'UTF-8') ?>" placeholder="you@university.edu">
        </div>
        <button class="btn btn-navy w-100 mb-3" type="submit">Send reset link</button>
      </form>
      <?php endif; ?>
      <p class="text-center small text-muted mb-0">
        <a href="institution-login.php">Back to sign in</a>
        <?php if ($message === ''): ?> · <a href="institution-signup.php">Register your institution</a><?php endif; ?>
      </p>
    </section>
  </div>
</body>
</html>
