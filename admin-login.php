<?php
/**
 * =========================================================
 * ADMIN LOGIN — PRODUCTION SAFE WITH STATUS-BASED ACCESS CONTROL
 * =========================================================
 * ✔ Keeps all existing UI/UX
 * ✔ Preserves ORIGINAL backend behavior
 * ✔ Dashboard-safe (NO breaking changes)
 * ✔ Strict status-based authentication (active only)
 * =========================================================
 */

session_start();

/**
 * REQUIRED DATABASES
 * ---------------------------------------------------------
 * db.php        → main system DB (admins, students, etc.)
 * database.php  → Cyprus DB (used later by dashboard)
 *
 * IMPORTANT:
 * - Login AUTHENTICATION uses ONLY $conn (db.php)
 * - $conn2 is loaded here ONLY so dashboard never sees NULL
 */
require_once 'db.php';        // provides $conn
require_once 'database.php';  // provides $conn2
require_once __DIR__ . '/helpers/mysqli_compat.php';
require_once __DIR__ . '/helpers/admin_password_reset.php';

xander_ensure_admin_password_reset_columns($conn);

$error = '';

/**
 * =========================================================
 * LOGIN HANDLER WITH STATUS-BASED ACCESS CONTROL
 * =========================================================
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    /**
     * SECURITY: Prepared statement (UPDATED to include status column)
     */
    // Username or email; no offices JOIN (table may be absent on some hosts).
    $stmt = $conn->prepare(
        "SELECT id, password_hash, full_name, role, status, office_id
         FROM admins
         WHERE username = ?
            OR (email IS NOT NULL AND email != '' AND LOWER(TRIM(email)) = LOWER(TRIM(?)))
         LIMIT 1"
    );

    if (!$stmt) {
        // Fail safely (no DB info leakage)
        $error = "System error. Please contact administrator.";
    } else {

        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $admin = pcvc_stmt_fetch_assoc($stmt);
        $stmt->close();

        /**
         * PASSWORD VERIFY (ORIGINAL BEHAVIOR)
         */
        if ($admin && password_verify($password, $admin['password_hash'])) {

            /**
             * 🔒 STATUS-BASED ACCESS CONTROL (STRICT ORDER)
             * Check status BEFORE creating session
             */
            $status = $admin['status'] ?? 'pending';
            
            // Log blocked login attempt (optional placeholder)
            // error_log("Login attempt for user: {$username}, status: {$status}");
            
            // Small delay for anti-brute force (0.5 seconds)
            usleep(500000);
            
            if ($status !== 'active') {
                // BLOCK LOGIN - Status is not active
                if ($status === 'pending') {
                    $error = "⚠️ Your account is pending approval. Please contact administrator.";
                } elseif ($status === 'deactive') {
                    $error = "🚫 Your account has been deactivated. Access denied.";
                } else {
                    $error = "Invalid username or password.";
                }
                // DO NOT create session - STOP execution
            } else {
                /**
                 * ✅ STATUS == 'active' → proceed with login normally
                 * SESSION HARDENING
                 */
                session_regenerate_id(true);

                /**
                 * 🔑 CRITICAL SESSION VARIABLES
                 * -------------------------------------------------
                 * These are REQUIRED by admin-dashboard.php
                 * DO NOT rename / remove
                 */
                $_SESSION['id']        = $admin['id'];
                $_SESSION['admin_id']  = $admin['id'];
                $_SESSION['username']  = $username;
                $_SESSION['name']      = $admin['full_name'];
                $_SESSION['role']       = $admin['role'];
                $_SESSION['office_id']  = (int) ($admin['office_id'] ?? 0);
                $_SESSION['office_name'] = '';

                $clr = $conn->prepare('UPDATE admins SET password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?');
                if ($clr) {
                    $aid = (int) $admin['id'];
                    $clr->bind_param('i', $aid);
                    $clr->execute();
                    $clr->close();
                }

                /**
                 * REDIRECT — NOTHING ELSE
                 */
                header("Location: admin-dashboard.php");
                exit;
            }

        } else {
            // Wrong username/password - add small delay for anti-brute force
            usleep(500000);
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Admin login portal for Xander Global Scholars">
<title>Login Portal | XANDER GLOBAL SCHOLARS</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* =========================================================
   PRODUCTION-READY ADMIN LOGIN — MODERN UI/UX
   Logo Colors: Dark Blue & Golden Orange
========================================================= */
:root {
  /* Logo Colors - Dark Blue & Golden Orange */
  --primary: #1e3a5f;
  --primary-dark: #0f2542;
  --primary-light: #2d4f7c;
  --accent: #ff8c42;
  --accent-dark: #e6732f;
  --accent-light: #ffa366;
  
  /* Neutral Colors */
  --bg: #f8fafc;
  --bg-light: #ffffff;
  --card: #ffffff;
  --text: #1e293b;
  --text-light: #64748b;
  --text-muted: #94a3b8;
  --muted: #64748b;
  --danger: #dc2626;
  --danger-light: #fee2e2;
  --warning: #f59e0b;
  --warning-light: #fef3c7;
  --success: #10b981;
  --border: #e2e8f0;
  --border-focus: #3b82f6;
  
  /* Shadows */
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  
  /* Transitions */
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  --transition-fast: all 0.15s ease;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  margin: 0;
  min-height: 100vh;
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 50%, #f8fafc 100%);
  background-image: 
    radial-gradient(circle at 20% 30%, rgba(30, 58, 95, 0.08) 0%, transparent 50%),
    radial-gradient(circle at 80% 70%, rgba(255, 140, 66, 0.08) 0%, transparent 50%),
    radial-gradient(circle at 50% 50%, rgba(30, 58, 95, 0.04) 0%, transparent 70%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  position: relative;
  overflow-x: hidden;
}

body::before {
  content: '';
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image: 
    repeating-linear-gradient(
      45deg,
      transparent,
      transparent 100px,
      rgba(30, 58, 95, 0.02) 100px,
      rgba(30, 58, 95, 0.02) 102px
    );
  pointer-events: none;
  z-index: 0;
}

.login-wrapper {
  max-width: 460px;
  width: 100%;
  position: relative;
  z-index: 10;
  animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.login-card {
  background: var(--card);
  border-radius: 20px;
  padding: 48px 40px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(226, 232, 240, 0.8);
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(10px);
}

.login-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 50%, var(--primary) 100%);
}

.brand {
  text-align: center;
  margin-bottom: 40px;
}

.brand img {
  height: 120px;
  width: auto;
  margin-bottom: 20px;
  transition: var(--transition);
  filter: drop-shadow(0 4px 12px rgba(30, 58, 95, 0.2));
}

.brand img:hover {
  transform: translateY(-3px) scale(1.02);
  filter: drop-shadow(0 6px 16px rgba(30, 58, 95, 0.25));
}

.brand h1 {
  font-size: 0.875rem;
  letter-spacing: 0.2em;
  color: var(--primary);
  font-weight: 600;
  text-transform: uppercase;
  margin-top: 12px;
}

h2 {
  text-align: center;
  margin-bottom: 32px;
  color: var(--text);
  font-size: 1.875rem;
  font-weight: 700;
  letter-spacing: -0.025em;
}

/* Error Message - Enhanced with animations */
.error {
  background: var(--danger-light);
  color: var(--danger);
  padding: 14px 16px;
  border-radius: 12px;
  margin-bottom: 20px;
  text-align: center;
  font-size: 0.95rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border: 1px solid rgba(220, 38, 38, 0.2);
  animation: shake 0.5s ease-in-out;
}

/* Warning message for pending status */
.error-warning {
  background: var(--warning-light);
  color: var(--warning);
  border: 1px solid rgba(245, 158, 11, 0.2);
}

/* Danger message for deactive status */
.error-danger {
  background: var(--danger-light);
  color: var(--danger);
  border: 1px solid rgba(220, 38, 38, 0.2);
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-8px); }
  75% { transform: translateX(8px); }
}

.error i {
  font-size: 1.1rem;
}

/* Form Styles */
.form-group {
  position: relative;
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  color: var(--text);
  font-size: 0.875rem;
  font-weight: 500;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-wrapper i {
  position: absolute;
  left: 16px;
  color: var(--muted);
  font-size: 1rem;
  z-index: 1;
  transition: var(--transition);
}

.input-wrapper input {
  width: 100%;
  padding: 14px 16px 14px 44px;
  border-radius: 12px;
  border: 2px solid var(--border);
  font-size: 0.95rem;
  transition: var(--transition);
  background: #fff;
  color: var(--text);
}

.input-wrapper input::placeholder {
  color: var(--muted);
}

.input-wrapper input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 4px rgba(30, 58, 95, 0.1);
}

.input-wrapper input:focus + i,
.input-wrapper:has(input:focus) i {
  color: var(--primary);
}

/* Password Toggle */
.password-toggle {
  position: absolute;
  right: 16px;
  background: none;
  border: none;
  color: var(--muted);
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition);
  z-index: 2;
}

.password-toggle:hover {
  color: var(--primary);
}

.password-toggle:focus {
  outline: 2px solid var(--primary);
  outline-offset: 2px;
  border-radius: 4px;
}

/* Remember Me */
.remember-me {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 28px;
  font-size: 0.875rem;
  color: var(--text-light);
}

.remember-me input[type="checkbox"] {
  width: 18px;
  height: 18px;
  margin: 0;
  cursor: pointer;
  accent-color: var(--primary);
}

.remember-me label {
  cursor: pointer;
  user-select: none;
}

.forgot-password-link {
  text-align: center;
  margin-bottom: 24px;
  font-size: 0.875rem;
}

.forgot-password-link a {
  color: var(--primary);
  font-weight: 500;
  text-decoration: none;
}

.forgot-password-link a:hover {
  text-decoration: underline;
}

/* Submit Button */
.submit-btn {
  width: 100%;
  padding: 16px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  color: #fff;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: var(--transition);
  position: relative;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(30, 58, 95, 0.25);
}

.submit-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.submit-btn:hover::before {
  left: 100%;
}

.submit-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(30, 58, 95, 0.35);
  background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
}

.submit-btn:active {
  transform: translateY(0);
  box-shadow: 0 4px 12px rgba(30, 58, 95, 0.25);
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  background: var(--muted);
}

.submit-btn .btn-text {
  position: relative;
  z-index: 1;
}

.submit-btn .btn-loader {
  display: none;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 2;
}

.submit-btn.loading .btn-text {
  opacity: 0;
}

.submit-btn.loading .btn-loader {
  display: block;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: translate(-50%, -50%) rotate(360deg); }
}

/* Footer */
.footer-note {
  text-align: center;
  font-size: 0.8rem;
  color: var(--text-muted);
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid var(--border);
  line-height: 1.6;
}

.footer-note i {
  color: var(--accent);
  margin-right: 6px;
}

/* Responsive Design */
@media (max-width: 480px) {
  .login-card {
    padding: 36px 28px;
    border-radius: 16px;
  }

  h2 {
    font-size: 1.5rem;
    margin-bottom: 28px;
  }

  .brand img {
    height: 100px;
  }

  .brand {
    margin-bottom: 32px;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Focus visible for keyboard navigation */
*:focus-visible {
  outline: 2px solid var(--primary);
  outline-offset: 2px;
  border-radius: 2px;
}
</style>
</head>

<body>

<div class="login-wrapper">
  <div class="login-card">

    <div class="brand">
      <img src="XANDER GLOBAL SCHOLARS LOGO1.png" alt="XANDER GLOBAL SCHOLARS" onerror="this.style.display='none'">
      <h1>XANDER GLOBAL SCHOLARS</h1>
    </div>

    <h2>Login Portal</h2>

    <?php if ($error): ?>
      <div class="error" role="alert">
        <i class="fas <?= strpos($error, '⚠️') !== false ? 'fa-exclamation-triangle' : (strpos($error, '🚫') !== false ? 'fa-ban' : 'fa-exclamation-circle') ?>"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <form method="post" id="loginForm" autocomplete="off" novalidate>
      <div class="form-group">
        <label for="username">Username</label>
        <div class="input-wrapper">
          <i class="fas fa-user"></i>
          <input 
            type="text" 
            id="username" 
            name="username" 
            placeholder="Enter your username" 
            required 
            autocomplete="username"
            autofocus
            aria-label="Username"
          >
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrapper">
          <i class="fas fa-lock"></i>
          <input 
            type="password" 
            id="password" 
            name="password" 
            placeholder="Enter your password" 
            required 
            autocomplete="current-password"
            aria-label="Password"
          >
          <button 
            type="button" 
            class="password-toggle" 
            id="passwordToggle"
            aria-label="Toggle password visibility"
            tabindex="0"
          >
            <i class="fas fa-eye" id="passwordIcon"></i>
          </button>
        </div>
      </div>

      <div class="remember-me">
        <input type="checkbox" id="rememberMe" name="remember_me" value="1">
        <label for="rememberMe">Remember me</label>
      </div>

      <p class="forgot-password-link">
        <a href="admin-forgot-password.php">Forgot password?</a>
      </p>

      <button type="submit" class="submit-btn" id="submitBtn">
        <span class="btn-text">Sign In</span>
        <span class="btn-loader">
          <span class="spinner"></span>
        </span>
      </button>
    </form>

    <div class="footer-note">
      <i class="fas fa-shield-alt"></i> Secure Login Portal
      <br>
      © <?= date('Y') ?> XANDER GLOBAL SCHOLARS. All rights reserved.
    </div>

  </div>
</div>

<script>
(function() {
  'use strict';

  const form = document.getElementById('loginForm');
  const passwordInput = document.getElementById('password');
  const passwordToggle = document.getElementById('passwordToggle');
  const passwordIcon = document.getElementById('passwordIcon');
  const submitBtn = document.getElementById('submitBtn');
  const usernameInput = document.getElementById('username');

  // Password visibility toggle
  passwordToggle.addEventListener('click', function() {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    passwordIcon.classList.toggle('fa-eye');
    passwordIcon.classList.toggle('fa-eye-slash');
  });

  // Keyboard support for password toggle
  passwordToggle.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      passwordToggle.click();
    }
  });

  // Form submission with loading state
  form.addEventListener('submit', function(e) {
    // Client-side validation
    if (!form.checkValidity()) {
      e.preventDefault();
      form.reportValidity();
      return;
    }

    // Show loading state
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;

    // Prevent double submission
    const isSubmitting = form.dataset.submitting === 'true';
    if (isSubmitting) {
      e.preventDefault();
      return;
    }
    form.dataset.submitting = 'true';
  });

  // Auto-focus username if empty
  if (!usernameInput.value) {
    usernameInput.focus();
  }

  // Clear error on input with animation
  const errorDiv = document.querySelector('.error');
  if (errorDiv) {
    const inputs = form.querySelectorAll('input');
    inputs.forEach(input => {
      input.addEventListener('input', function() {
        if (errorDiv.style.display !== 'none') {
          errorDiv.style.animation = 'fadeOut 0.3s ease-out';
          setTimeout(() => {
            errorDiv.style.display = 'none';
          }, 300);
        }
      });
    });
  }

  // Remember username from localStorage (if remember me was checked)
  const rememberedUsername = localStorage.getItem('admin_username');
  if (rememberedUsername && !usernameInput.value) {
    usernameInput.value = rememberedUsername;
    passwordInput.focus();
  }

  // Save username on successful login (handled by form submission)
  form.addEventListener('submit', function() {
    const rememberMe = document.getElementById('rememberMe').checked;
    if (rememberMe) {
      localStorage.setItem('admin_username', usernameInput.value);
    } else {
      localStorage.removeItem('admin_username');
    }
  });

  // Enhanced input validation feedback
  const inputs = form.querySelectorAll('input[required]');
  inputs.forEach(input => {
    input.addEventListener('blur', function() {
      if (input.validity.valid) {
        input.style.borderColor = 'var(--success)';
        setTimeout(() => {
          input.style.borderColor = '';
        }, 2000);
      }
    });
  });

  // Prevent form resubmission on page refresh
  if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
  }
})();
</script>

</body>
</html>