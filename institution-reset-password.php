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

$error = '';
$message = '';
$tokenIn = '';
$validToken = false;
$accountId = 0;
$tokenHash = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['token'])) {
    $tokenIn = trim((string) $_POST['token']);
} elseif (isset($_GET['t'])) {
    $tokenIn = trim((string) $_GET['t']);
}

if ($tokenIn !== '' && preg_match('/^[a-f0-9]{64}$/i', $tokenIn)) {
    $tokenHash = hash('sha256', $tokenIn);
    $st = $conn->prepare('SELECT id, password_reset_expires, status FROM institution_portal_accounts WHERE password_reset_token = ? LIMIT 1');
    if ($st) {
        $st->bind_param('s', $tokenHash);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        if ($row && ($row['status'] ?? '') === 'active') {
            $expStr = $row['password_reset_expires'] ?? null;
            if ($expStr) {
                try {
                    $exp = new DateTimeImmutable((string) $expStr, new DateTimeZone('UTC'));
                    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
                    if ($exp >= $now) {
                        $validToken = true;
                        $accountId = (int) $row['id'];
                    } else {
                        $error = 'This reset link has expired. Please request a new one.';
                    }
                } catch (Exception $e) {
                    $error = 'Invalid reset link.';
                }
            } else {
                $error = 'Invalid reset link.';
            }
        } else {
            $error = 'Invalid or expired reset link.';
        }
    }
} elseif (isset($_GET['t'])) {
    $error = 'Invalid reset link.';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $validToken && $accountId > 0 && $tokenHash !== '') {
    if (!pcvc_csrf_validate_post()) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $p1 = (string) ($_POST['password'] ?? '');
        $p2 = (string) ($_POST['password_confirm'] ?? '');
        if (strlen($p1) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($p1 !== $p2) {
            $error = 'Passwords do not match.';
        } else {
            $newHash = password_hash($p1, PASSWORD_DEFAULT);
            $clr = $conn->prepare('
                UPDATE institution_portal_accounts
                SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL
                WHERE id = ? AND password_reset_token = ?
            ');
            if ($clr) {
                $clr->bind_param('sis', $newHash, $accountId, $tokenHash);
                $clr->execute();
                if ($clr->affected_rows > 0) {
                    $message = 'Your password has been updated. You can sign in now.';
                    $validToken = false;
                } else {
                    $error = 'Could not update password. Request a new reset link.';
                }
                $clr->close();
            } else {
                $error = 'System error. Please try again.';
            }
        }
    }
}

$showForm = $validToken && $message === '';
$loginUrl = 'institution-login.php';

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Set new password | <?= htmlspecialchars(PCVC_COMPANY_DISPLAY_NAME, ENT_QUOTES, 'UTF-8') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
    .password-field{position:relative}
    .password-field .form-control{padding-right:2.75rem}
    .password-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);border:0;background:transparent;color:#64748b;padding:4px 6px;cursor:pointer;line-height:1;font-size:1rem}
    .password-toggle:hover{color:var(--navy)}
  </style>
</head>
<body>
  <div class="shell">
    <aside class="brand">
      <div class="logo">
        <img src="XANDER GLOBAL SCHOLARS LOGO1.png" alt="" onerror="this.parentElement.style.display='none'">
      </div>
      <h1 class="h4 fw-bold">New password</h1>
      <p class="mb-0 opacity-90 small">Choose a strong password for your institution portal. You can change it again anytime from your profile.</p>
    </aside>
    <section class="panel">
      <h2 class="h4 fw-bold mb-1">Set new password</h2>
      <?php if ($message !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <p class="text-center mb-0"><a class="btn btn-navy" href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>">Sign in</a></p>
      <?php else: ?>
        <?php if ($error !== ''): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($showForm): ?>
        <p class="text-muted small mb-4">Enter your new password twice to confirm.</p>
        <form method="post" autocomplete="off">
          <?= pcvc_csrf_input() ?>
          <input type="hidden" name="token" value="<?= htmlspecialchars($tokenIn, ENT_QUOTES, 'UTF-8') ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold" for="password">New password</label>
            <div class="password-field">
              <input class="form-control" type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
              <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password"><i class="fas fa-eye"></i></button>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" for="password_confirm">Confirm password</label>
            <div class="password-field">
              <input class="form-control" type="password" id="password_confirm" name="password_confirm" required minlength="8" autocomplete="new-password">
              <button type="button" class="password-toggle" data-toggle-password="password_confirm" aria-label="Show password"><i class="fas fa-eye"></i></button>
            </div>
          </div>
          <button class="btn btn-navy w-100 mb-3" type="submit">Update password</button>
        </form>
        <?php elseif ($error === ''): ?>
        <p class="text-muted">Open the reset link from your email, or request a new one below.</p>
        <?php endif; ?>
        <p class="text-center small text-muted mb-0">
          <a href="institution-forgot-password.php">Request a new link</a> · <a href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>">Back to sign in</a>
        </p>
      <?php endif; ?>
    </section>
  </div>
  <script>
  document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    var input = document.getElementById(btn.getAttribute('data-toggle-password'));
    var icon = btn.querySelector('i');
    if (!input || !icon) return;
    btn.addEventListener('click', function () {
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      icon.classList.toggle('fa-eye', !show);
      icon.classList.toggle('fa-eye-slash', show);
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  });
  </script>
</body>
</html>
