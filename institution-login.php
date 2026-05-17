<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/company_branding.php';
require_once __DIR__ . '/helpers/csrf.php';
require_once __DIR__ . '/helpers/institution_portal.php';
require_once __DIR__ . '/helpers/urls.php';

xander_institution_portal_ensure_schema($conn);

if (!empty($_SESSION['institution_account_id'])) {
    header('Location: ' . pcvc_url('/institution/index.php'));
    exit;
}

$error = '';
$info = '';
$prefillEmail = '';
if (!empty($_GET['email'])) {
    $prefillEmail = xander_institution_email_norm((string) $_GET['email']);
}
if (!empty($_GET['registered'])) {
    $info = 'Account created. Check your email for your temporary password, then sign in below.';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!pcvc_csrf_validate_post()) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $email = xander_institution_email_norm((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $auth = xander_institution_authenticate($conn, $email, $password);
        if (!$auth['ok']) {
            $error = $auth['message'];
        } else {
            $acc = $auth['account'];
            session_regenerate_id(true);
            $_SESSION['institution_account_id'] = (int) $acc['id'];
            $_SESSION['institution_university_id'] = (int) $acc['university_id'];
            $_SESSION['institution_email'] = xander_institution_email_norm((string) $acc['email']);
            $_SESSION['institution_name'] = trim((string) $acc['contact_name']);
            $_SESSION['institution_university_name'] = trim((string) ($acc['university_name'] ?? ''));

            $up = $conn->prepare('UPDATE institution_portal_accounts SET last_login_at = NOW() WHERE id = ?');
            if ($up) {
                $aid = (int) $acc['id'];
                $up->bind_param('i', $aid);
                $up->execute();
                $up->close();
            }

            header('Location: ' . pcvc_url('/institution/index.php'));
            exit;
        }
    }
}

$appRoot = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Institution sign in | <?= htmlspecialchars(PCVC_COMPANY_DISPLAY_NAME, ENT_QUOTES, 'UTF-8') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root{--navy:#012F6B;--gold:#F2A65A;--muted:#64748b;}
    body{font-family:Inter,system-ui,sans-serif;min-height:100vh;margin:0;display:flex;align-items:center;justify-content:center;padding:24px;
      background:radial-gradient(ellipse 90% 70% at 10% 0%,rgba(1,47,107,.12),transparent 55%),
      radial-gradient(ellipse 60% 50% at 100% 100%,rgba(242,166,90,.2),transparent 50%),#eef2f7;}
    .shell{max-width:980px;width:100%;background:#fff;border-radius:28px;overflow:hidden;box-shadow:0 32px 64px -16px rgba(1,47,107,.25);
      display:grid;grid-template-columns:1fr;}
    @media(min-width:900px){.shell{grid-template-columns:1fr 1fr;min-height:560px}}
    .brand{background:linear-gradient(165deg,var(--navy) 0%,#254D81 55%,#002765 100%);color:#fff;padding:48px 36px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;}
    .brand .logo{width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,.1);border:3px solid rgba(242,166,90,.5);display:flex;align-items:center;justify-content:center;margin-bottom:20px;}
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
      <h1 class="h4 fw-bold">Institution Portal</h1>
      <p class="mb-0 opacity-90 small">Universities & schools — track applicants and manage your partnership profile.</p>
    </aside>
    <section class="panel">
      <h2 class="h4 fw-bold mb-1">Sign in</h2>
      <p class="text-muted small mb-4">Use the email you registered with your institution.</p>
      <?php if ($info !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($info, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <form method="post" autocomplete="off">
        <?= pcvc_csrf_input() ?>
        <div class="mb-3">
          <label class="form-label fw-semibold">Work email</label>
          <input class="form-control" type="email" name="email" required value="<?= htmlspecialchars($prefillEmail, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Password</label>
          <input class="form-control" type="password" name="password" required>
        </div>
        <button class="btn btn-navy w-100 mb-3" type="submit">Sign in</button>
        <p class="text-center small text-muted mb-0">No account? <a href="institution-signup.php">Register your institution</a></p>
      </form>
    </section>
  </div>
</body>
</html>
