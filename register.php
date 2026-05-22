<?php
declare(strict_types=1);

$bannerSuccess = '';
$bannerWarning = '';
$bannerError = '';

if (!empty($_GET['success'])) {
    if (!empty($_GET['email_failed'])) {
        $bannerWarning = 'Your account was created, but we could not send the email. Please contact support or try forgot-password after an admin approves your account.';
    } else {
        $bannerSuccess = 'Registration received. Check your inbox (and spam) for your login link and temporary password.';
    }
}

if (!empty($_GET['error'])) {
    switch ((string) $_GET['error']) {
        case 'username_taken':
            $bannerError = 'That username is already taken. Please choose another.';
            break;
        case 'email_taken':
            $bannerError = 'That email is already registered. Sign in or use a different email.';
            break;
        case 'invalid':
            $bannerError = 'Please fill in all fields with valid information.';
            break;
        default:
            $bannerError = 'Something went wrong. Please try again in a few minutes.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Create account | XANDER GLOBAL SCHOLARS</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">
<style>
:root {
  --primary: #1e3a5f;
  --primary-dark: #0f2542;
  --primary-light: #2d4f7c;
  --accent: #ff8c42;
  --accent-dark: #e6732f;
  --bg: #f8fafc;
  --card: #ffffff;
  --text: #1e293b;
  --text-muted: #64748b;
  --border: #e2e8f0;
  --success: #059669;
  --success-bg: #d1fae5;
  --warning: #b45309;
  --warning-bg: #fef3c7;
  --danger: #dc2626;
  --danger-bg: #fee2e2;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: Inter, system-ui, -apple-system, sans-serif;
  min-height: 100vh;
  color: var(--text);
  background: linear-gradient(145deg, #f1f5f9 0%, #e2e8f0 45%, #f8fafc 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
}
.page-shell {
  width: 100%;
  max-width: 520px;
}
.card {
  background: var(--card);
  border-radius: 20px;
  box-shadow: 0 20px 40px -12px rgba(15, 37, 66, 0.18), 0 0 0 1px rgba(226, 232, 240, 0.9);
  overflow: hidden;
}
.card::before {
  content: '';
  display: block;
  height: 4px;
  background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 50%, var(--primary) 100%);
}
.card-inner { padding: 40px 36px 36px; }
@media (max-width: 480px) {
  .card-inner { padding: 32px 22px 28px; }
}
.brand {
  text-align: center;
  margin-bottom: 28px;
}
.brand img {
  height: 88px;
  width: auto;
  filter: drop-shadow(0 4px 12px rgba(30, 58, 95, 0.2));
}
h1 {
  font-size: 1.5rem;
  font-weight: 700;
  text-align: center;
  letter-spacing: -0.02em;
  color: var(--primary-dark);
  margin-bottom: 8px;
}
.subtitle {
  text-align: center;
  font-size: 0.9375rem;
  color: var(--text-muted);
  line-height: 1.55;
  margin-bottom: 22px;
}
.notice {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 14px 16px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(30, 58, 95, 0.06) 0%, rgba(255, 140, 66, 0.08) 100%);
  border: 1px solid rgba(30, 58, 95, 0.12);
  font-size: 0.8125rem;
  line-height: 1.5;
  color: var(--primary-dark);
  margin-bottom: 26px;
}
.notice i {
  color: var(--accent-dark);
  margin-top: 2px;
  flex-shrink: 0;
}
.banner {
  border-radius: 12px;
  padding: 14px 16px;
  font-size: 0.875rem;
  line-height: 1.45;
  margin-bottom: 22px;
}
.banner.success { background: var(--success-bg); color: #065f46; border: 1px solid rgba(5, 150, 105, 0.25); }
.banner.warning { background: var(--warning-bg); color: var(--warning); border: 1px solid rgba(180, 83, 9, 0.25); }
.banner.error { background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(220, 38, 38, 0.2); }
.form-group { margin-bottom: 18px; }
.form-group label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--primary-dark);
  margin-bottom: 8px;
}
.input-wrap {
  position: relative;
}
.input-wrap i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  font-size: 0.95rem;
  pointer-events: none;
  z-index: 1;
}
.input-wrap input {
  width: 100%;
  padding: 13px 14px 13px 42px;
  border: 2px solid var(--border);
  border-radius: 12px;
  font-size: 0.9375rem;
  transition: border-color 0.2s, box-shadow 0.2s;
  background: #fff;
  color: var(--text);
}
.input-wrap input::placeholder { color: #94a3b8; }
.input-wrap input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 4px rgba(30, 58, 95, 0.12);
}
.field-hint {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-top: 6px;
}
.inline-status {
  font-size: 0.8125rem;
  margin-top: 6px;
  min-height: 1.25em;
  display: flex;
  align-items: center;
  gap: 6px;
}
.inline-status.error { color: var(--danger); }
.inline-status.success { color: var(--success); }
.input-wrap input.is-invalid { border-color: var(--danger); box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.10); }
.input-wrap input.is-valid   { border-color: var(--success); box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.10); }
/* intl-tel-input integration with our icon-padded input */
.iti { width: 100%; display: block; }
.input-wrap.tel-wrap i.fa-phone { display: none; }
.input-wrap.tel-wrap input { padding-left: 96px !important; }
.iti__flag-container { z-index: 2; }
.iti__selected-flag { padding: 0 8px 0 12px; }
.submit-btn {
  width: 100%;
  margin-top: 10px;
  padding: 15px 20px;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  cursor: pointer;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  box-shadow: 0 4px 14px rgba(30, 58, 95, 0.35);
  transition: transform 0.15s, box-shadow 0.15s, filter 0.15s;
  position: relative;
  overflow: hidden;
}
.submit-btn:hover:not(:disabled) {
  filter: brightness(1.05);
  transform: translateY(-1px);
  box-shadow: 0 8px 22px rgba(30, 58, 95, 0.4);
}
.submit-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
}
.submit-btn .spinner {
  display: none;
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255,255,255,0.35);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.75s linear infinite;
  margin: 0 auto;
}
.submit-btn.is-loading .btn-label { display: none; }
.submit-btn.is-loading .spinner { display: block; }
@keyframes spin { to { transform: rotate(360deg); } }
.footer-links {
  text-align: center;
  margin-top: 24px;
  padding-top: 22px;
  border-top: 1px solid var(--border);
  font-size: 0.875rem;
}
.footer-links a {
  color: var(--primary);
  font-weight: 600;
  text-decoration: none;
}
.footer-links a:hover { text-decoration: underline; }
.footer-links .sep { color: #cbd5e1; margin: 0 10px; }
.copyright {
  text-align: center;
  font-size: 0.75rem;
  color: #94a3b8;
  margin-top: 18px;
}
</style>
</head>
<body>
<div class="page-shell">
  <div class="card">
    <div class="card-inner">
      <div class="brand">
        <img src="XANDER GLOBAL SCHOLARS LOGO1.png" alt="XANDER GLOBAL SCHOLARS" onerror="this.style.display='none'">
      </div>
      <h1>Create your account</h1>
      <p class="subtitle">Register for agent access. An administrator will review and activate your account.</p>

      <div class="notice" role="note">
        <i class="fas fa-shield-halved" aria-hidden="true"></i>
        <span>A secure temporary password will be emailed to you with the admin login link. You can change it after your account is approved.</span>
      </div>

      <?php if ($bannerSuccess !== ''): ?>
        <div class="banner success"><?= htmlspecialchars($bannerSuccess, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <?php if ($bannerWarning !== ''): ?>
        <div class="banner warning"><?= htmlspecialchars($bannerWarning, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <?php if ($bannerError !== ''): ?>
        <div class="banner error"><?= htmlspecialchars($bannerError, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <?php if ($bannerSuccess !== '' || $bannerWarning !== ''): ?>
      <?php else: ?>
      <form method="post" action="register_staff.php" id="staffForm" autocomplete="off">
        <div class="form-group">
          <label for="first_name">First name</label>
          <div class="input-wrap">
            <i class="fas fa-user" aria-hidden="true"></i>
            <input type="text" name="first_name" id="first_name" required maxlength="120" placeholder="Given name" value="<?= htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div id="firstNameStatus" class="inline-status" aria-live="polite"></div>
        </div>
        <div class="form-group">
          <label for="last_name">Last name</label>
          <div class="input-wrap">
            <i class="fas fa-user" aria-hidden="true"></i>
            <input type="text" name="last_name" id="last_name" required maxlength="120" placeholder="Family name" value="<?= htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>
        <div class="form-group">
          <label for="username">Username</label>
          <div class="input-wrap">
            <i class="fas fa-at" aria-hidden="true"></i>
            <input type="text" name="username" id="username" required maxlength="64" placeholder="Choose a unique username" autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <p class="field-hint">Same username you will use on the admin login page.</p>
        </div>
        <div class="form-group">
          <label for="phone_number">Phone</label>
          <div class="input-wrap tel-wrap">
            <i class="fas fa-phone" aria-hidden="true"></i>
            <input type="tel" name="phone_number" id="phone_number" required maxlength="40" placeholder="7XXXXXXXX" value="<?= htmlspecialchars($_POST['phone_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <input type="hidden" name="phone_e164" id="phone_e164" value="">
          <div id="phoneStatus" class="inline-status" aria-live="polite"></div>
        </div>
        <div class="form-group">
          <label for="email">Work email</label>
          <div class="input-wrap">
            <i class="fas fa-envelope" aria-hidden="true"></i>
            <input type="email" name="email" id="email" required maxlength="255" placeholder="you@example.com" autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div id="emailStatus" class="inline-status" aria-live="polite"></div>
        </div>
        <input type="hidden" name="role" value="agent">
        <button type="submit" id="submitBtn" class="submit-btn">
          <span class="btn-label">Submit registration</span>
          <span class="spinner" aria-hidden="true"></span>
        </button>
      </form>
      <?php endif; ?>

      <div class="footer-links">
        <a href="admin-login.php">Already have an account? Sign in</a>
        <span class="sep">|</span>
        <a href="index.php">Back to home</a>
      </div>
      <p class="copyright">© <?= date('Y') ?> XANDER GLOBAL SCHOLARS</p>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js"></script>
<script>
(function () {
  const form = document.getElementById('staffForm');
  if (!form) return;
  const firstNameInput = document.getElementById('first_name');
  const emailInput = document.getElementById('email');
  const phoneInput = document.getElementById('phone_number');
  const phoneE164  = document.getElementById('phone_e164');
  const firstNameStatus = document.getElementById('firstNameStatus');
  const emailStatus = document.getElementById('emailStatus');
  const phoneStatus = document.getElementById('phoneStatus');
  const submitBtn = document.getElementById('submitBtn');
  let firstTimer, emailTimer, phoneTimer;
  const EMAIL_RE = /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i;

  // Field validity state — submit only enabled when all are valid
  const state = { firstName: true, email: false, phone: false };

  function setStatus(el, type, html) {
    el.className = 'inline-status' + (type ? ' ' + type : '');
    el.innerHTML = html;
  }
  function setFieldValidity(input, ok) {
    input.classList.toggle('is-valid', ok === true);
    input.classList.toggle('is-invalid', ok === false);
  }
  function refreshSubmit() {
    submitBtn.disabled = !(state.firstName && state.email && state.phone);
  }

  /* ---------- intl-tel-input on phone ---------- */
  const iti = window.intlTelInput(phoneInput, {
    initialCountry: 'auto',
    separateDialCode: true,
    nationalMode: true,
    preferredCountries: ['rw', 'ke', 'ug', 'tz', 'us', 'gb'],
    utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js',
    geoIpLookup: function (cb) {
      fetch('https://ipapi.co/json/')
        .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
        .then(function (d) { cb(d && d.country_code ? d.country_code : 'RW'); })
        .catch(function () { cb('RW'); });
    }
  });

  function validatePhone() {
    const raw = phoneInput.value.trim();
    if (raw === '') {
      setStatus(phoneStatus, '', '');
      setFieldValidity(phoneInput, null);
      phoneE164.value = '';
      state.phone = false;
      refreshSubmit();
      return;
    }
    let ok = false;
    try { ok = iti.isValidNumber(); } catch (e) { ok = false; }
    if (ok) {
      const e164 = (typeof window.intlTelInputUtils !== 'undefined')
        ? iti.getNumber(window.intlTelInputUtils.numberFormat.E164)
        : iti.getNumber();
      phoneE164.value = e164 || '';
      setFieldValidity(phoneInput, true);
      setStatus(phoneStatus, 'success', '<i class="fas fa-circle-check"></i> Valid number');
      state.phone = true;
    } else {
      phoneE164.value = '';
      setFieldValidity(phoneInput, false);
      const code = (typeof iti.getValidationError === 'function') ? iti.getValidationError() : -1;
      let msg = 'Enter a valid phone number';
      const utils = window.intlTelInputUtils;
      if (utils) {
        if (code === utils.validationError.TOO_SHORT) msg = 'Number is too short';
        else if (code === utils.validationError.TOO_LONG) msg = 'Number is too long';
        else if (code === utils.validationError.INVALID_COUNTRY_CODE) msg = 'Invalid country code';
        else if (code === utils.validationError.NOT_A_NUMBER) msg = 'Only digits allowed';
      }
      setStatus(phoneStatus, 'error', '<i class="fas fa-circle-exclamation"></i> ' + msg);
      state.phone = false;
    }
    refreshSubmit();
  }
  phoneInput.addEventListener('blur', validatePhone);
  phoneInput.addEventListener('keyup', function () {
    clearTimeout(phoneTimer);
    phoneTimer = setTimeout(validatePhone, 250);
  });
  phoneInput.addEventListener('countrychange', validatePhone);

  /* ---------- first name (existence check) ---------- */
  firstNameInput.addEventListener('input', function () {
    clearTimeout(firstTimer);
    firstTimer = setTimeout(checkFirstName, 450);
  });
  function checkFirstName() {
    const v = firstNameInput.value.trim();
    if (v.length < 2) {
      setStatus(firstNameStatus, '', '');
      state.firstName = true;
      refreshSubmit();
      return;
    }
    setStatus(firstNameStatus, '', '<i class="fas fa-spinner fa-spin"></i> Checking…');
    fetch('check_user.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'type=first_name&value=' + encodeURIComponent(v)
    })
      .then(function (r) { return r.text(); })
      .then(function (data) {
        if (data === 'exists') {
          setStatus(firstNameStatus, 'error', '<i class="fas fa-circle-exclamation"></i> This first name is already registered');
          state.firstName = false;
        } else {
          setStatus(firstNameStatus, 'success', '<i class="fas fa-circle-check"></i> OK');
          state.firstName = true;
        }
        refreshSubmit();
      })
      .catch(function () {
        setStatus(firstNameStatus, '', '');
        state.firstName = true;
        refreshSubmit();
      });
  }

  /* ---------- email (format + existence) ---------- */
  emailInput.addEventListener('input', function () {
    clearTimeout(emailTimer);
    emailTimer = setTimeout(checkEmail, 350);
  });
  function checkEmail() {
    const v = emailInput.value.trim();
    if (v === '') {
      setStatus(emailStatus, '', '');
      setFieldValidity(emailInput, null);
      state.email = false;
      refreshSubmit();
      return;
    }
    if (!EMAIL_RE.test(v)) {
      setStatus(emailStatus, 'error', '<i class="fas fa-circle-exclamation"></i> Enter a valid email address (e.g. you@example.com)');
      setFieldValidity(emailInput, false);
      state.email = false;
      refreshSubmit();
      return;
    }
    setStatus(emailStatus, '', '<i class="fas fa-spinner fa-spin"></i> Checking availability…');
    fetch('check_user.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'type=email&value=' + encodeURIComponent(v)
    })
      .then(function (r) { return r.text(); })
      .then(function (data) {
        if (data === 'exists') {
          setStatus(emailStatus, 'error', '<i class="fas fa-circle-exclamation"></i> Email already in use');
          setFieldValidity(emailInput, false);
          state.email = false;
        } else {
          setStatus(emailStatus, 'success', '<i class="fas fa-circle-check"></i> Looks good');
          setFieldValidity(emailInput, true);
          state.email = true;
        }
        refreshSubmit();
      })
      .catch(function () {
        // Network failure: don't block if format is fine
        setStatus(emailStatus, 'success', '<i class="fas fa-circle-check"></i> Looks good');
        setFieldValidity(emailInput, true);
        state.email = true;
        refreshSubmit();
      });
  }

  /* ---------- submit guard ---------- */
  form.addEventListener('submit', function (e) {
    validatePhone();
    // Replace phone_number value with E.164 for server-side consistency
    if (phoneE164.value) {
      phoneInput.value = phoneE164.value;
    }
    if (!(state.firstName && state.email && state.phone)) {
      e.preventDefault();
      if (!state.email) emailInput.focus();
      else if (!state.phone) phoneInput.focus();
      return;
    }
    submitBtn.classList.add('is-loading');
    submitBtn.disabled = true;
  });

  refreshSubmit();
})();
</script>
</body>
</html>
