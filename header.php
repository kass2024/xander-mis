<?php
// ============================================
// LANGUAGE SWITCHING SYSTEM - HEADER ONLY
// ============================================

require_once __DIR__ . '/site_session_bootstrap.php';

$current_lang = $_SESSION['current_language'];

// ============================================
// TRANSLATION ARRAYS FOR HEADER ONLY
// ============================================

$header_translations = [
    'en' => [
        // Navigation
        'nav_home' => 'Home',
        'nav_about' => 'About',
        'nav_programs' => 'Programs',
        'nav_services' => 'Services',
        'nav_universities' => 'Universities',
        'nav_partners' => 'Partners',
        'nav_testimonials' => 'Testimonials',
        'nav_contact' => 'Contact',
        'nav_blog' => 'Blog',
        'nav_payment' => 'Payment',
        'nav_pay_service' => 'Pay Service',
        'nav_other_payment' => 'Other Payment',
        'sign_up' => 'Sign Up',
        'login' => 'Login',
        'nav_elearning' => 'E-Learning',
        'login_student' => 'Student Login',
        'login_admin' => 'Admin Login',
        'login_institution' => 'Institution Login',
        'signup_student' => 'Student',
        'signup_admin' => 'Admin',
        'signup_institution' => 'Institution',
        'admin_login' => 'Admin Login',
        'student_login' => 'Student Login',
        'apply_link' => 'Apply',
        
        // Language switcher
        'current_language' => 'English',
        'switch_to_french' => 'Switch to French',
        'switch_to_english' => 'Switch to English',
    ],
    
    'fr' => [
        // Navigation
        'nav_home' => 'Accueil',
        'nav_about' => 'À propos',
        'nav_programs' => 'Programmes',
        'nav_services' => 'Services',
        'nav_universities' => 'Universités',
        'nav_partners' => 'Partenaires',
        'nav_testimonials' => 'Témoignages',
        'nav_contact' => 'Contact',
        'nav_blog' => 'Blog',
        'nav_payment' => 'Paiement',
        'nav_pay_service' => 'Payer le service',
        'nav_other_payment' => 'Autre paiement',
        'sign_up' => 'S\'inscrire',
        'login' => 'Connexion',
        'nav_elearning' => 'E-Learning',
        'login_student' => 'Connexion étudiant',
        'login_admin' => 'Connexion admin',
        'login_institution' => 'Connexion institution',
        'signup_student' => 'Étudiant',
        'signup_admin' => 'Admin',
        'signup_institution' => 'Institution',
        'admin_login' => 'Connexion admin',
        'student_login' => 'Connexion étudiant',
        'apply_link' => 'Postuler',
        
        // Language switcher
        'current_language' => 'Français',
        'switch_to_french' => 'Passer au français',
        'switch_to_english' => 'Passer à l\'anglais',
    ]
];

// Function to translate header text - only define if not already defined
if (!function_exists('ht')) {
    function ht($key) {
        global $header_translations, $current_lang;
        return isset($header_translations[$current_lang][$key]) ? $header_translations[$current_lang][$key] : $key;
    }
}

// Get page title if not set
$pageTitle = $pageTitle ?? 'Xander Global Scholars';
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ============================================
   HEADER STYLES ONLY
============================================ */
:root {
  --primary: #1e3a5f;
  --primary-dark: #0f2542;
  --primary-light: #2d4f7c;
  --accent: #ff8c42;
  --accent-dark: #e6732f;
  --accent-light: #ffa366;
  --bg: #f8fafc;
  --card: #ffffff;
  --text: #1e293b;
  --text-light: #64748b;
  --border: #e2e8f0;
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

* { 
  box-sizing: border-box; 
  margin: 0; 
  padding: 0; 
}

body { 
  font-family: 'Inter', sans-serif; 
  background: var(--bg); 
  color: var(--text); 
  line-height: 1.6; 
}

/* ===== HEADER (narrow two-row) ===== */
.site-header {
  background: linear-gradient(180deg, var(--primary-dark) 0%, var(--primary) 100%);
  position: sticky;
  top: 0;
  z-index: 1000;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.header-inner {
  max-width: 1180px;
  margin: 0 auto;
  padding: 0 clamp(12px, 3vw, 20px);
}

.header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.header-row-main {
  min-height: 64px;
  padding: 10px 0;
}

.header-row-sub {
  justify-content: center;
  gap: clamp(20px, 5vw, 48px);
  padding: 6px 0 10px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.header-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
  min-width: 0;
  max-width: min(100%, 280px);
  text-decoration: none;
  transition: opacity 0.2s ease;
}

.header-logo:hover {
  opacity: 0.95;
}

.header-logo-mark {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  padding: 6px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.96);
  box-shadow:
    0 2px 8px rgba(0, 0, 0, 0.2),
    0 0 0 1px rgba(255, 255, 255, 0.15);
}

.header-logo img {
  display: block;
  height: 44px;
  width: auto;
  max-width: 100%;
  object-fit: contain;
  filter: none;
}

.header-logo .brand {
  color: #fff;
  font-weight: 700;
  font-size: 1.05rem;
  line-height: 1.2;
  letter-spacing: -0.02em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 11.5rem;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
}

@media (min-width: 1100px) {
  .header-logo .brand {
    max-width: 16rem;
  }
}

.header-nav-primary {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: clamp(10px, 2vw, 22px);
  flex: 1 1 auto;
  min-width: 0;
}

.header-nav-primary > a {
  color: rgba(255, 255, 255, 0.92);
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  white-space: nowrap;
  padding: 4px 0;
  transition: var(--transition);
}

.header-nav-primary > a:hover,
.header-nav-primary > a.is-active {
  color: var(--accent);
}

.header-auth {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.hdr-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  font-size: 0.8125rem;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
  border: none;
  font-family: inherit;
  white-space: nowrap;
  transition: var(--transition);
  text-decoration: none;
  touch-action: manipulation;
  -webkit-tap-highlight-color: transparent;
}

.hdr-btn--outline {
  color: #fff;
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.55);
}

.hdr-btn--outline:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: #fff;
}

.hdr-btn--solid {
  color: #fff;
  background: var(--accent);
  border: 1px solid var(--accent);
}

.hdr-btn--solid:hover {
  background: var(--accent-dark);
  border-color: var(--accent-dark);
}

.site-header,
.header-inner,
.header-row,
.header-auth {
  overflow: visible;
}

.hdr-dropdown {
  position: relative;
}

.hdr-dropdown.is-open {
  z-index: 1105;
}

.hdr-dropdown-menu {
  display: none;
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  min-width: 200px;
  background: linear-gradient(180deg, #1a3354 0%, var(--primary-dark) 100%);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 10px;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.35);
  padding: 6px 0;
  z-index: 1100;
  list-style: none;
}

.hdr-dropdown-menu::before {
  content: '';
  position: absolute;
  top: -6px;
  right: 24px;
  width: 12px;
  height: 12px;
  background: #1a3354;
  border-left: 1px solid rgba(255, 255, 255, 0.12);
  border-top: 1px solid rgba(255, 255, 255, 0.12);
  transform: rotate(45deg);
}

.hdr-dropdown.is-open .hdr-dropdown-menu {
  display: block;
  animation: hdrDropIn 0.2s ease;
}

@keyframes hdrDropIn {
  from { opacity: 0; transform: translateY(-6px); }
  to { opacity: 1; transform: translateY(0); }
}

.hdr-dropdown-menu a {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 16px;
  color: rgba(255, 255, 255, 0.95);
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  transition: background 0.15s ease;
}

.hdr-dropdown-menu a:last-child {
  border-bottom: none;
}

.hdr-dropdown-menu a:hover {
  background: rgba(255, 255, 255, 0.08);
  color: var(--accent-light);
}

.hdr-dropdown-menu a i {
  width: 18px;
  text-align: center;
  opacity: 0.9;
}

.header-row-sub a,
.header-row-sub .hdr-sub-link {
  color: rgba(255, 255, 255, 0.9);
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  border-radius: 4px;
  transition: var(--transition);
}

.header-row-sub a:hover {
  color: var(--accent);
}

.language-switcher--compact .language-switcher-toggle {
  padding: 7px 10px;
  font-size: 0.8rem;
}

.mobile-menu-toggle {
  display: none;
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 6px;
  width: 42px;
  height: 42px;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
}

.mobile-nav-panel {
  display: none;
}

/* Language switcher */
.language-switcher {
  position: relative;
}

.language-switcher-toggle {
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.28);
  padding: 7px 10px;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 500;
  font-size: 0.8rem;
  transition: var(--transition);
  font-family: inherit;
}

.language-switcher-toggle:hover {
  background: rgba(255, 255, 255, 0.18);
}

.language-switcher-dropdown {
  position: absolute;
  top: calc(100% + 6px);
  right: 0;
  background: #fff;
  border-radius: 8px;
  box-shadow: var(--shadow-lg);
  min-width: 150px;
  display: none;
  z-index: 1100;
  border: 1px solid var(--border);
  overflow: hidden;
}

.language-switcher-dropdown.active {
  display: block;
}

.language-option {
  padding: 10px 14px;
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  color: var(--text);
  font-size: 0.875rem;
  border-bottom: 1px solid var(--border);
}

.language-option:last-child { border-bottom: none; }
.language-option:hover { background: var(--bg); }
.language-option.active { font-weight: 600; color: var(--primary); }

.hdr-sub-dropdown { position: relative; }
.hdr-sub-dropdown .hdr-dropdown-menu { left: 50%; right: auto; transform: translateX(-50%); }
.hdr-sub-dropdown .hdr-dropdown-menu::before { left: 50%; right: auto; margin-left: -6px; }

.hdr-sub-dropdown .hdr-sub-trigger {
  background: none;
  border: none;
  cursor: pointer;
  font-family: inherit;
  touch-action: manipulation;
  -webkit-tap-highlight-color: transparent;
}

@media (max-width: 992px) {
  .header-nav-primary { display: none; }
  .mobile-menu-toggle { display: inline-flex; }

  .mobile-nav-panel.is-open {
    display: block;
    padding: 12px 0 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
  }

  .mobile-nav-panel a {
    display: block;
    color: #fff;
    text-decoration: none;
    padding: 12px 8px;
    font-size: 0.9375rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  }

  .mobile-nav-panel a:hover { color: var(--accent); }

  .header-logo .brand { display: none; }

  .header-logo-mark {
    width: 52px;
    height: 52px;
    padding: 5px;
  }

  .header-logo img {
    height: 42px;
  }

  .hdr-btn span.hdr-btn-label { display: none; }
  .hdr-btn { padding: 7px 10px; }

  .header-row-sub {
    gap: 16px;
    padding: 8px 0;
  }

  /* Full-width inset panel on mobile (position set in JS below trigger) */
  .hdr-dropdown.is-open .hdr-dropdown-menu {
    box-sizing: border-box;
    min-width: 0;
    animation: hdrDropInMobile 0.2s ease;
  }

  @keyframes hdrDropInMobile {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  .hdr-dropdown-menu::before { display: none; }
}

@media (min-width: 993px) {
  .mobile-nav-panel { display: none !important; }
  .header-nav-primary { display: flex; }
}

@media (max-width: 576px) {
  .language-switcher-toggle .language-name { display: none; }
}

</style>
</head>
<body>
<?php require __DIR__ . '/includes/site_header_markup.php'; ?>

<script>
(function () {
  const mobileMenuToggle = document.getElementById('mobileMenuToggle');
  const mobileNavPanel = document.getElementById('mobileNavPanel');
  const languageToggle = document.getElementById('languageToggle');
  const languageDropdown = document.getElementById('languageDropdown');

  const MOBILE_HDR_BP = 992;
  const MOBILE_MENU_INSET = 12;

  function isMobileHeader() {
    return window.innerWidth <= MOBILE_HDR_BP;
  }

  function resetDropdownMenuPosition(wrap) {
    const menu = wrap ? wrap.querySelector('.hdr-dropdown-menu') : null;
    if (menu) {
      menu.removeAttribute('style');
    }
  }

  function positionDropdownMenu(wrap, btn) {
    const menu = wrap.querySelector('.hdr-dropdown-menu');
    if (!menu || !isMobileHeader()) {
      return;
    }
    const rect = btn.getBoundingClientRect();
    menu.style.position = 'fixed';
    menu.style.top = Math.round(rect.bottom + 8) + 'px';
    menu.style.left = MOBILE_MENU_INSET + 'px';
    menu.style.right = MOBILE_MENU_INSET + 'px';
    menu.style.width = 'auto';
    menu.style.maxWidth = 'none';
    menu.style.transform = 'none';
    menu.style.boxSizing = 'border-box';
  }

  function closeAllDropdowns(except) {
    document.querySelectorAll('[data-dropdown].is-open').forEach((el) => {
      if (el !== except) {
        el.classList.remove('is-open');
        const btn = el.querySelector('[data-dropdown-toggle]');
        if (btn) btn.setAttribute('aria-expanded', 'false');
        resetDropdownMenuPosition(el);
      }
    });
    if (languageDropdown) languageDropdown.classList.remove('active');
    if (languageToggle) languageToggle.setAttribute('aria-expanded', 'false');
  }

  function toggleDropdown(wrap, btn) {
    const willOpen = !wrap.classList.contains('is-open');
    closeAllDropdowns(willOpen ? wrap : null);
    if (willOpen) {
      wrap.classList.add('is-open');
      btn.setAttribute('aria-expanded', 'true');
      positionDropdownMenu(wrap, btn);
    } else {
      wrap.classList.remove('is-open');
      btn.setAttribute('aria-expanded', 'false');
      resetDropdownMenuPosition(wrap);
    }
  }

  window.addEventListener('resize', () => {
    document.querySelectorAll('[data-dropdown].is-open').forEach((wrap) => {
      const btn = wrap.querySelector('[data-dropdown-toggle]');
      if (!btn) return;
      if (isMobileHeader()) {
        positionDropdownMenu(wrap, btn);
      } else {
        resetDropdownMenuPosition(wrap);
      }
    });
  });

  document.querySelectorAll('[data-dropdown]').forEach((wrap) => {
    const btn = wrap.querySelector('[data-dropdown-toggle]');
    if (!btn) return;

    let suppressClick = false;

    btn.addEventListener('touchend', (e) => {
      e.preventDefault();
      e.stopPropagation();
      suppressClick = true;
      toggleDropdown(wrap, btn);
      window.setTimeout(() => { suppressClick = false; }, 450);
    }, { passive: false });

    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      if (suppressClick) {
        e.preventDefault();
        return;
      }
      toggleDropdown(wrap, btn);
    });
  });

  if (mobileMenuToggle && mobileNavPanel) {
    mobileMenuToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      const open = mobileNavPanel.classList.toggle('is-open');
      mobileMenuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      const icon = mobileMenuToggle.querySelector('i');
      if (icon) {
        icon.classList.toggle('fa-bars', !open);
        icon.classList.toggle('fa-times', open);
      }
      if (open) closeAllDropdowns();
    });
  }

  if (languageToggle && languageDropdown) {
    languageToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      closeAllDropdowns();
      const open = languageDropdown.classList.toggle('active');
      languageToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.querySelectorAll('.language-option').forEach((opt) => {
      opt.addEventListener('click', () => languageDropdown.classList.remove('active'));
    });
  }

  document.addEventListener('click', (e) => {
    if (!e.target.closest('[data-dropdown]')) {
      closeAllDropdowns();
    }
    if (!e.target.closest('.language-switcher')) {
      if (languageDropdown) languageDropdown.classList.remove('active');
      if (languageToggle) languageToggle.setAttribute('aria-expanded', 'false');
    }
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeAllDropdowns();
      if (mobileNavPanel) mobileNavPanel.classList.remove('is-open');
      if (mobileMenuToggle) {
        mobileMenuToggle.setAttribute('aria-expanded', 'false');
        const icon = mobileMenuToggle.querySelector('i');
        if (icon) { icon.classList.add('fa-bars'); icon.classList.remove('fa-times'); }
      }
    }
  });

  document.addEventListener('DOMContentLoaded', () => {
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    document.querySelectorAll('.header-nav-primary > a, .mobile-nav-panel > a').forEach((link) => {
      const href = link.getAttribute('href') || '';
      if (href === currentPage || (currentPage === '' && href === 'index.php')) {
        link.classList.add('is-active');
      }
    });
  });
})();
</script>