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
   XANDER HEADER — PRODUCTION-READY NAV (2026)
============================================ */
:root {
  --primary: #012F6B;
  --primary-dark: #001A3D;
  --primary-light: #254D81;
  --accent: #F2A65A;
  --accent-dark: #E6892E;
  --accent-light: #FBC58A;
  --bg: #f8fafc;
  --card: #ffffff;
  --text: #0F172A;
  --text-light: #64748b;
  --border: #e2e8f0;
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  --header-shadow: 0 6px 28px rgba(1, 26, 61, 0.35), 0 1px 0 rgba(255,255,255,0.06) inset;
  --transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  background: var(--bg);
  color: var(--text);
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
}

/* ===== HEADER (premium glass + gradient) ===== */
.site-header {
  background:
    radial-gradient(800px 200px at 12% -20%, rgba(242, 166, 90, 0.15), transparent 60%),
    radial-gradient(900px 220px at 92% 120%, rgba(37, 77, 129, 0.30), transparent 60%),
    linear-gradient(180deg, var(--primary-dark) 0%, var(--primary) 70%, #002457 100%);
  position: sticky;
  top: 0;
  z-index: 1000;
  box-shadow: var(--header-shadow);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  backdrop-filter: saturate(140%) blur(6px);
  -webkit-backdrop-filter: saturate(140%) blur(6px);
}

.site-header::after {
  content: '';
  position: absolute;
  left: 0; right: 0; bottom: -1px;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(242, 166, 90, 0.55), transparent);
  pointer-events: none;
}

.header-inner {
  max-width: 1240px;
  margin: 0 auto;
  padding: 0 clamp(14px, 3vw, 24px);
  position: relative;
}

.header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}

.header-row-main {
  min-height: 72px;
  padding: 12px 0;
}

.header-row-sub {
  justify-content: flex-start;
  gap: clamp(22px, 5vw, 56px);
  padding: 8px 0 12px;
  border-top: 1px solid rgba(255, 255, 255, 0.07);
  background: linear-gradient(180deg, rgba(255,255,255,0.02), transparent);
  margin-left: 280px; /* Align with main navigation start */
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
  width: 58px;
  height: 58px;
  padding: 6px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.98);
  box-shadow:
    0 6px 18px rgba(0, 0, 0, 0.25),
    0 0 0 1px rgba(255, 255, 255, 0.20),
    0 0 0 4px rgba(242, 166, 90, 0.08);
  transition: var(--transition);
}

.header-logo:hover .header-logo-mark {
  transform: translateY(-1px);
  box-shadow:
    0 10px 22px rgba(0, 0, 0, 0.30),
    0 0 0 1px rgba(255, 255, 255, 0.25),
    0 0 0 4px rgba(242, 166, 90, 0.15);
}

.header-logo img {
  display: block;
  height: 46px;
  width: auto;
  max-width: 100%;
  object-fit: contain;
  filter: none;
}

.header-logo .brand {
  color: #fff;
  font-weight: 800;
  font-size: 1.08rem;
  line-height: 1.18;
  letter-spacing: -0.02em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 12rem;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.35);
  background: linear-gradient(180deg, #fff 0%, #f6ddc1 110%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

@media (min-width: 1100px) {
  .header-logo .brand { max-width: 16rem; font-size: 1.12rem; }
}

.header-nav-primary {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: nowrap;
  gap: clamp(2px, 0.9vw, 10px);
  flex: 1 1 auto;
  min-width: 0;
}

.header-nav-primary > a {
  color: rgba(255, 255, 255, 0.92);
  text-decoration: none;
  font-size: clamp(0.82rem, 1vw, 0.92rem);
  font-weight: 600;
  white-space: nowrap;
  padding: 9px clamp(7px, 1vw, 12px);
  flex-shrink: 0;
  border-radius: 10px;
  position: relative;
  transition: var(--transition);
  letter-spacing: 0.005em;
}

.header-nav-primary > a::after {
  content: '';
  position: absolute;
  left: 14px;
  right: 14px;
  bottom: 4px;
  height: 2px;
  background: linear-gradient(90deg, var(--accent), var(--accent-light));
  border-radius: 2px;
  transform: scaleX(0);
  transform-origin: center;
  transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}

.header-nav-primary > a:hover {
  color: #fff;
  background: rgba(255, 255, 255, 0.06);
}

.header-nav-primary > a:hover::after,
.header-nav-primary > a.is-active::after {
  transform: scaleX(1);
}

.header-nav-primary > a.is-active {
  color: var(--accent);
  background: rgba(242, 166, 90, 0.08);
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
  gap: 7px;
  padding: 9px 16px;
  font-size: 0.84rem;
  font-weight: 700;
  border-radius: 10px;
  cursor: pointer;
  border: none;
  font-family: inherit;
  white-space: nowrap;
  transition: var(--transition);
  text-decoration: none;
  touch-action: manipulation;
  -webkit-tap-highlight-color: transparent;
  letter-spacing: 0.01em;
}

.hdr-btn--outline {
  color: #fff;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.30);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}

.hdr-btn--outline:hover {
  background: rgba(255, 255, 255, 0.14);
  border-color: rgba(255, 255, 255, 0.6);
  transform: translateY(-1px);
}

.hdr-btn--solid {
  color: #1e1e1e;
  background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
  border: 1px solid var(--accent);
  box-shadow:
    0 6px 16px rgba(242, 166, 90, 0.35),
    0 0 0 1px rgba(255,255,255,0.08) inset;
}

.hdr-btn--solid:hover {
  color: #1e1e1e;
  filter: brightness(1.05);
  transform: translateY(-1px);
  box-shadow:
    0 10px 22px rgba(242, 166, 90, 0.45),
    0 0 0 1px rgba(255,255,255,0.12) inset;
}

.hdr-btn--solid:active {
  transform: translateY(0);
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
  top: calc(100% + 12px);
  right: 0;
  min-width: 240px;
  background:
    linear-gradient(180deg, rgba(26, 51, 84, 0.98) 0%, rgba(1, 26, 61, 0.98) 100%);
  border: 1px solid rgba(255, 255, 255, 0.14);
  border-radius: 14px;
  box-shadow:
    0 20px 50px rgba(0, 0, 0, 0.45),
    0 0 0 1px rgba(255, 255, 255, 0.04) inset;
  padding: 8px;
  z-index: 1100;
  list-style: none;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

.hdr-dropdown-menu::before {
  content: '';
  position: absolute;
  top: -6px;
  right: 28px;
  width: 12px;
  height: 12px;
  background: #1a3354;
  border-left: 1px solid rgba(255, 255, 255, 0.14);
  border-top: 1px solid rgba(255, 255, 255, 0.14);
  transform: rotate(45deg);
}

.hdr-dropdown.is-open .hdr-dropdown-menu {
  display: block;
  animation: hdrDropIn 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes hdrDropIn {
  from { opacity: 0; transform: translateY(-8px) scale(0.98); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}

.hdr-dropdown-menu a {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 14px;
  color: rgba(255, 255, 255, 0.94);
  text-decoration: none;
  font-size: 0.88rem;
  font-weight: 600;
  border-radius: 10px;
  transition: all 0.18s ease;
  position: relative;
}

.hdr-dropdown-menu a + a {
  margin-top: 2px;
}

.hdr-dropdown-menu a:hover {
  background: linear-gradient(90deg, rgba(242, 166, 90, 0.18), rgba(242, 166, 90, 0.05));
  color: #fff;
  transform: translateX(2px);
}

.hdr-dropdown-menu a i {
  width: 28px;
  height: 28px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.06);
  border-radius: 8px;
  font-size: 0.85rem;
  color: var(--accent-light);
  flex-shrink: 0;
  transition: all 0.18s ease;
}

.hdr-dropdown-menu a:hover i {
  background: rgba(242, 166, 90, 0.20);
  color: #fff;
}

.header-row-sub a,
.header-row-sub .hdr-sub-link {
  color: rgba(255, 255, 255, 0.88);
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  border-radius: 999px;
  border: 1px solid transparent;
  transition: var(--transition);
}

.header-row-sub a:hover,
.header-row-sub .hdr-sub-link:hover {
  color: var(--accent);
  background: rgba(255, 255, 255, 0.06);
  border-color: rgba(255, 255, 255, 0.12);
}

.header-row-sub a i,
.header-row-sub .hdr-sub-link > i:first-child {
  color: var(--accent-light);
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
  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: 10px;
  width: 44px;
  height: 44px;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  transition: var(--transition);
}
.mobile-menu-toggle:hover {
  background: rgba(255, 255, 255, 0.16);
  border-color: rgba(255, 255, 255, 0.45);
}
.mobile-menu-toggle:active {
  transform: scale(0.96);
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
  
  .header-row-sub {
    margin-left: 0;
    justify-content: center;
  }

  .mobile-nav-panel {
    position: fixed;
    top: 0;
    right: -360px;
    width: min(340px, 88vw);
    height: 100vh;
    background:
      linear-gradient(180deg, rgba(0, 26, 61, 0.98) 0%, rgba(1, 47, 107, 0.98) 100%);
    border-left: 1px solid rgba(255, 255, 255, 0.10);
    box-shadow: -16px 0 48px rgba(0, 0, 0, 0.45);
    padding: 24px 18px 32px;
    z-index: 1080;
    overflow-y: auto;
    transition: right 0.32s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
  }

  .mobile-nav-panel.is-open {
    display: block !important;
    right: 0;
  }

  .mobile-nav-panel::before {
    content: '';
    display: block;
    height: 4px;
    width: 44px;
    background: linear-gradient(90deg, var(--accent), var(--accent-light));
    border-radius: 2px;
    margin: 0 0 20px;
  }

  .mobile-nav-panel a {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #fff;
    text-decoration: none;
    padding: 14px 14px;
    font-size: 0.95rem;
    font-weight: 600;
    border-radius: 10px;
    margin-bottom: 4px;
    transition: all 0.18s ease;
    position: relative;
  }

  .mobile-nav-panel a::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    width: 3px;
    height: 0;
    background: var(--accent);
    border-radius: 2px;
    transform: translateY(-50%);
    transition: height 0.18s ease;
  }

  .mobile-nav-panel a:hover {
    background: rgba(255, 255, 255, 0.08);
    color: var(--accent-light);
    padding-left: 18px;
  }

  .mobile-nav-panel a:hover::before {
    height: 22px;
  }

  .mobile-nav-overlay {
    position: fixed;
    inset: 0;
    background: rgba(1, 26, 61, 0.55);
    z-index: 1075;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.28s ease;
    backdrop-filter: blur(2px);
  }

  .mobile-nav-overlay.is-open {
    opacity: 1;
    pointer-events: auto;
  }

  .header-logo .brand { display: none; }

  .header-logo-mark {
    width: 54px;
    height: 54px;
    padding: 5px;
  }

  .header-logo img { height: 44px; }

  .hdr-btn span.hdr-btn-label { display: none; }
  .hdr-btn { padding: 9px 12px; }

  .header-row-sub {
    gap: 16px;
    padding: 8px 0;
  }

  .hdr-dropdown.is-open .hdr-dropdown-menu {
    box-sizing: border-box;
    min-width: 0;
    animation: hdrDropInMobile 0.22s ease;
  }

  @keyframes hdrDropInMobile {
    from { opacity: 0; transform: translateY(-4px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .hdr-dropdown-menu::before { display: none; }
}

@media (min-width: 993px) {
  .mobile-nav-panel { display: none !important; }
  .header-nav-primary { display: flex; }
}

@media (min-width: 993px) and (max-width: 1200px) {
  .header-logo .brand { max-width: 9.5rem; font-size: 0.98rem; }
  .header-logo { max-width: min(100%, 220px); }
  .header-nav-primary > a { padding: 8px 8px; font-size: 0.84rem; }
  .hdr-btn { padding: 8px 10px; font-size: 0.82rem; }
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

  const mobileNavOverlay = document.getElementById('mobileNavOverlay');

  function closeMobilePanel() {
    if (!mobileNavPanel) return;
    mobileNavPanel.classList.remove('is-open');
    if (mobileMenuToggle) mobileMenuToggle.setAttribute('aria-expanded', 'false');
    const icon = mobileMenuToggle && mobileMenuToggle.querySelector('i');
    if (icon) { icon.classList.add('fa-bars'); icon.classList.remove('fa-times'); }
    if (mobileNavOverlay) mobileNavOverlay.classList.remove('is-open');
    document.body.style.overflow = '';
  }

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
      if (mobileNavOverlay) mobileNavOverlay.classList.toggle('is-open', open);
      document.body.style.overflow = open ? 'hidden' : '';
      if (open) closeAllDropdowns();
    });
  }

  if (mobileNavOverlay) {
    mobileNavOverlay.addEventListener('click', closeMobilePanel);
  }

  if (mobileNavPanel) {
    mobileNavPanel.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', closeMobilePanel);
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeMobilePanel();
      closeAllDropdowns();
    }
  });

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