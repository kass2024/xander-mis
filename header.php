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

/* ===== HEADER ===== */
.site-header { 
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  padding: 0;
  box-shadow: var(--shadow-lg); 
  position: sticky; 
  top: 0; 
  z-index: 1000; 
  border-bottom: 3px solid var(--accent);
}

.header-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 12px clamp(12px, 3vw, 28px);
  flex-wrap: wrap;
  row-gap: 10px;
  position: relative;
  z-index: 2;
}

.header-apply-text-short {
  display: none;
}

.header-left { 
  order: 1;
  display: flex; 
  align-items: center; 
  gap: 10px; 
  flex: 1 1 auto;
  min-width: 0;
}

.site-header img { 
  height: 48px; 
  width: auto; 
  max-width: 100%;
  transition: var(--transition);
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.site-header img:hover {
  transform: scale(1.05);
}

.brand { 
  color: #fff; 
  font-weight: 700; 
  font-size: clamp(0.8rem, 2.8vw, 1.1rem);
  white-space: nowrap;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
  overflow: hidden;
  text-overflow: ellipsis;
  min-width: 0;
  max-width: min(100%, 12rem);
}
@media (min-width: 400px) {
  .brand { max-width: min(100%, 16rem); }
}
@media (min-width: 576px) {
  .brand { max-width: none; }
}

/* Navigation */
.main-nav { 
  order: 3;
  display: flex; 
  gap: 24px; 
  align-items: center;
  margin: 0 12px;
}

.main-nav a { 
  color: #fff; 
  text-decoration: none; 
  font-size: 0.95rem; 
  font-weight: 500; 
  opacity: 0.9; 
  transition: var(--transition); 
  white-space: nowrap;
  padding: 6px 0;
  position: relative;
}

.main-nav a:hover { 
  opacity: 1; 
  transform: translateY(-2px);
}

.main-nav a::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 0;
  height: 2px;
  background: var(--accent);
  transition: width 0.3s ease;
}

.main-nav a:hover::after {
  width: 100%;
}

.mobile-menu-toggle { 
  display: none; 
  background: rgba(255, 255, 255, 0.1);
  color: #fff; 
  font-size: 1.35rem; 
  line-height: 1;
  cursor: pointer; 
  padding: 10px 12px;
  min-width: 44px;
  min-height: 44px;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.25);
  transition: var(--transition);
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
}

.mobile-menu-toggle:hover {
  background: rgba(255, 255, 255, 0.2);
}

/* Language Switcher Styles */
.language-switcher {
  position: relative;
}

.language-switcher-toggle {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 10px 16px;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
  font-size: 0.9rem;
  transition: var(--transition);
  backdrop-filter: blur(10px);
  white-space: nowrap;
}

.language-switcher-toggle:hover {
  background: rgba(255, 255, 255, 0.25);
  border-color: var(--accent);
  transform: translateY(-2px);
}

.language-switcher-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 8px;
  background: white;
  border-radius: 8px;
  box-shadow: var(--shadow-lg);
  min-width: 160px;
  overflow: hidden;
  display: none;
  z-index: 1001;
  border: 1px solid var(--border);
}

.language-switcher-dropdown.active {
  display: block;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.language-option {
  padding: 12px 16px;
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  color: var(--text);
  font-weight: 500;
  transition: var(--transition);
  border-bottom: 1px solid var(--border);
}

.language-option:last-child {
  border-bottom: none;
}

.language-option:hover {
  background: var(--bg);
  color: var(--primary);
}

.language-option.active {
  background: linear-gradient(135deg, rgba(30, 58, 95, 0.1) 0%, rgba(255, 140, 66, 0.1) 100%);
  color: var(--primary);
  font-weight: 600;
}

.language-flag {
  font-size: 1.2rem;
}

.language-name {
  flex: 1;
  font-size: 0.9rem;
}

/* Header tools (right side) */
.header-tools {
  order: 2;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 8px;
  flex: 0 1 auto;
  min-width: 0;
}

/* Quick login — always visible (esp. mobile) */
.header-quick-login {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

.header-login-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 8px 10px;
  font-size: 0.75rem;
  font-weight: 700;
  color: #fff;
  text-decoration: none;
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.1);
  transition: var(--transition);
  white-space: nowrap;
  line-height: 1.2;
}

.header-login-btn:hover {
  background: rgba(255, 255, 255, 0.22);
  border-color: var(--accent);
  color: #fff;
  transform: translateY(-1px);
}

.header-login-btn--student {
  background: rgba(255, 140, 66, 0.2);
  border-color: rgba(255, 160, 100, 0.6);
}

.header-login-btn--student:hover {
  background: rgba(255, 140, 66, 0.35);
}

.header-login-btn i {
  font-size: 0.85rem;
  opacity: 0.95;
}

.header-login-label {
  display: inline;
}

/* Apply / Get Started — primary CTA */
.header-apply-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 9px 14px;
  font-size: 0.85rem;
  font-weight: 700;
  color: #fff;
  text-decoration: none;
  border-radius: 8px;
  background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
  box-shadow: 0 4px 12px rgba(255, 140, 66, 0.35);
  transition: var(--transition);
  white-space: nowrap;
  flex-shrink: 0;
}

.header-apply-btn:hover {
  color: #fff;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(255, 140, 66, 0.45);
}

@media (max-width: 400px) {
  .header-login-btn {
    font-size: 0.65rem;
    padding: 7px 8px;
    white-space: normal;
    line-height: 1.2;
    text-align: center;
  }
}

/* Header actions container (legacy — merged into header-tools) */
.header-actions {
  display: contents;
}

/* Dropdown Menu Styles */
.dropdown {
  position: relative;
  display: inline-block;
}

.dropbtn {
  background-color: transparent;
  color: white;
  padding: 12px 16px;
  font-size: 0.95rem;
  border: none;
  cursor: pointer;
  border-radius: 8px;
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
  opacity: 0.9;
}

.dropbtn:hover {
  background-color: rgba(255, 255, 255, 0.15);
  transform: translateY(-2px);
  opacity: 1;
}

.dropbtn i {
  transition: transform 0.3s ease;
}

.dropdown:hover .dropbtn i.fa-chevron-down {
  transform: rotate(180deg);
}

.dropdown-content {
  display: none;
  position: absolute;
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  min-width: 220px;
  box-shadow: var(--shadow-xl);
  z-index: 9999;
  border-radius: 12px;
  top: 100%;
  left: 0;
  margin-top: 8px;
  border: 1px solid rgba(229, 231, 235, 0.8);
  backdrop-filter: blur(10px);
  opacity: 0;
  transform: translateY(-10px);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  visibility: hidden;
}

.dropdown:hover .dropdown-content {
  display: block;
  opacity: 1;
  transform: translateY(0);
  visibility: visible;
}

.dropdown-content a {
  color: #1e293b;
  padding: 14px 20px;
  text-decoration: none;
  display: block;
  transition: all 0.3s ease;
  border-bottom: 1px solid rgba(229, 231, 235, 0.3);
  font-weight: 500;
  position: relative;
  overflow: hidden;
}

.dropdown-content a:first-child {
  border-radius: 12px 12px 0 0;
}

.dropdown-content a:last-child {
  border-radius: 0 0 12px 12px;
  border-bottom: none;
}

.dropdown-content a:hover {
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
  color: white;
  transform: translateX(8px);
  box-shadow: 0 6px 20px rgba(30, 58, 95, 0.35);
  padding-left: 28px;
}

.dropdown-content a i {
  margin-right: 10px;
  width: 20px;
  text-align: center;
}

/* Desktop: logo | nav | tools */
@media (min-width: 993px) {
  .header-top {
    flex-wrap: nowrap;
    align-items: center;
  }
  .header-left {
    order: 1;
    flex: 0 1 auto;
  }
  .main-nav {
    order: 2;
    display: flex !important;
    flex: 1 1 auto;
    justify-content: center;
    margin: 0 10px;
    flex-wrap: wrap;
    row-gap: 4px;
    min-width: 0;
  }
  .header-tools {
    order: 3;
    flex: 0 0 auto;
  }
  .mobile-menu-toggle {
    display: none !important;
  }
}

@media (max-width: 1100px) {
  .main-nav {
    gap: 16px;
  }
  
  .main-nav a, .dropbtn {
    font-size: 0.9rem;
  }
}

@media (max-width: 992px) {
  .site-header {
    display: flex;
    flex-direction: column;
    align-items: stretch;
  }

  .header-left {
    order: 1;
    flex: 1 1 auto;
    min-width: 0;
  }

  .header-tools {
    order: 2;
    flex: 0 0 auto;
  }

  .main-nav {
    display: none;
    margin: 0;
    order: 3;
    flex: 1 1 100%;
    width: 100%;
  }
  
  .mobile-menu-toggle {
    display: inline-flex;
  }
  
  .main-nav.menu-open {
    display: flex;
    flex-direction: column;
    gap: 16px;
    background: linear-gradient(180deg, var(--primary-dark) 0%, var(--primary) 100%);
    padding: 16px clamp(12px, 3vw, 24px) 20px;
    border-radius: 0 0 14px 14px;
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    box-shadow: var(--shadow-lg);
    z-index: 1001;
    max-height: min(70vh, 520px);
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
  }
  
  .language-switcher-toggle {
    padding: 8px 12px;
    font-size: 0.85rem;
    min-height: 44px;
  }
  
  .dropdown {
    width: 100%;
    margin: 6px 0;
  }
  
  .dropbtn {
    width: 100%;
    justify-content: space-between;
    background: rgba(255, 255, 255, 0.1);
    min-height: 44px;
  }
  
  .dropdown-content {
    position: static;
    box-shadow: none;
    margin-top: 10px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
  }
  
  .dropdown-content a {
    border-left: 3px solid var(--accent);
    padding-left: 20px;
    color: white;
    min-height: 44px;
    display: flex;
    align-items: center;
  }
  
  .dropdown-content a:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateX(5px);
  }

  .header-apply-text-long {
    display: none;
  }
  .header-apply-text-short {
    display: inline;
  }
}

@media (max-width: 768px) {
  .header-top {
    padding-top: 10px;
    padding-bottom: 10px;
  }
  
  .site-header img {
    height: 40px;
  }
  
  .language-switcher-dropdown {
    right: 0;
    left: auto;
  }
}

@media (max-width: 576px) {
  .header-tools {
    gap: 6px;
  }
  
  .language-switcher-toggle .language-name {
    display: none;
  }
  
  .language-switcher-toggle {
    padding: 8px 10px;
    min-width: 44px;
    min-height: 44px;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .site-header img {
    height: 34px;
  }
}
</style>
</head>
<body>
<header class="site-header">
  <div class="header-top">
    <div class="header-left">
      <img src="XANDER GLOBAL SCHOLARS LOGO.png" alt="" width="48" height="48" onerror="this.style.display='none'">
      <div class="brand">Xander Global Scholars</div>
    </div>

    <div class="header-tools">
      <div class="header-quick-login" role="navigation" aria-label="<?php echo htmlspecialchars($current_lang === 'fr' ? 'Connexion' : 'Login', ENT_QUOTES, 'UTF-8'); ?>">
        <a href="admin-login.php" class="header-login-btn header-login-btn--admin" title="<?php echo htmlspecialchars(ht('admin_login'), ENT_QUOTES, 'UTF-8'); ?>">
          <i class="fas fa-user-shield" aria-hidden="true"></i>
          <span class="header-login-label"><?php echo ht('admin_login'); ?></span>
        </a>
        <a href="student-login.php" class="header-login-btn header-login-btn--student" title="<?php echo htmlspecialchars(ht('student_login'), ENT_QUOTES, 'UTF-8'); ?>">
          <i class="fas fa-user-graduate" aria-hidden="true"></i>
          <span class="header-login-label"><?php echo ht('student_login'); ?></span>
        </a>
      </div>

      <div class="language-switcher">
        <button type="button" class="language-switcher-toggle" id="languageToggle" aria-expanded="false" aria-haspopup="true" aria-controls="languageDropdown">
          <span class="language-flag" aria-hidden="true">
            <?php echo $current_lang === 'fr' ? '🇫🇷' : '🇬🇧'; ?>
          </span>
          <span class="language-name">
            <?php echo $current_lang === 'fr' ? 'FR' : 'GB'; ?>
          </span>
          <i class="fas fa-chevron-down" aria-hidden="true"></i>
        </button>

        <div class="language-switcher-dropdown" id="languageDropdown" role="menu">
          <a href="?lang=en" class="language-option <?php echo $current_lang === 'en' ? 'active' : ''; ?>" role="menuitem">
            <span class="language-flag">🇬🇧</span>
            <span class="language-name">English</span>
            <?php if ($current_lang === 'en'): ?>
              <i class="fas fa-check" aria-hidden="true"></i>
            <?php endif; ?>
          </a>
          <a href="?lang=fr" class="language-option <?php echo $current_lang === 'fr' ? 'active' : ''; ?>" role="menuitem">
            <span class="language-flag">🇫🇷</span>
            <span class="language-name">Français</span>
            <?php if ($current_lang === 'fr'): ?>
              <i class="fas fa-check" aria-hidden="true"></i>
            <?php endif; ?>
          </a>
        </div>
      </div>

      <a href="register.php" class="header-apply-btn">
        <i class="fas fa-user-plus" aria-hidden="true"></i>
        <span class="header-apply-text-long"><?php echo ht('sign_up'); ?></span>
        <span class="header-apply-text-short"><?php echo ht('sign_up'); ?></span>
      </a>

      <button type="button" class="mobile-menu-toggle" id="mobileMenuToggle" aria-expanded="false" aria-controls="mainNav" aria-label="Menu">
        <i class="fas fa-bars" aria-hidden="true"></i>
      </button>
    </div>

    <nav id="mainNav" class="main-nav" aria-label="<?php echo htmlspecialchars($current_lang === 'fr' ? 'Navigation principale' : 'Main navigation', ENT_QUOTES, 'UTF-8'); ?>">
      <a href="index.php"><?php echo ht('nav_home'); ?></a>
      <a href="about.php"><?php echo ht('nav_about'); ?></a>
      <a href="programs.php"><?php echo ht('nav_programs'); ?></a>
      <a href="services.php"><?php echo ht('nav_services'); ?></a>
      <a href="universities.php"><?php echo ht('nav_universities'); ?></a>
      <a href="partners.php"><?php echo ht('nav_partners'); ?></a>
      <a href="testimonials.php"><?php echo ht('nav_testimonials'); ?></a>
      <a href="contact.php"><?php echo ht('nav_contact'); ?></a>
      <a href="https://elearning.xanderglobalscholars.com/" target="_blank" rel="noopener noreferrer">E-Learning</a>

      <div class="dropdown">
        <button type="button" class="dropbtn">
          <i class="fas fa-credit-card"></i> <?php echo ht('nav_payment'); ?> <i class="fas fa-chevron-down"></i>
        </button>
        <div class="dropdown-content">
          <a href="payment.php">
            <i class="fas fa-file-invoice-dollar"></i> <?php echo ht('nav_pay_service'); ?>
          </a>
          <a href="payother.php">
            <i class="fas fa-hand-holding-usd"></i> <?php echo ht('nav_other_payment'); ?>
          </a>
        </div>
      </div>
    </nav>
  </div>
</header>

<script>
// Enhanced JavaScript for mobile menu toggle and language switcher
const mobileMenuToggle = document.getElementById('mobileMenuToggle');
const mainNav = document.getElementById('mainNav');
const languageToggle = document.getElementById('languageToggle');
const languageDropdown = document.getElementById('languageDropdown');
// Mobile menu toggle
if (mobileMenuToggle && mainNav) {
  mobileMenuToggle.addEventListener('click', (e) => {
    e.stopPropagation();
    const open = mainNav.classList.toggle('menu-open');
    mobileMenuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    const icon = mobileMenuToggle.querySelector('i');
    if (icon) {
      icon.classList.toggle('fa-bars');
      icon.classList.toggle('fa-times');
    }
  });

  // Close menu when clicking outside
  document.addEventListener('click', (e) => {
    if (!mainNav.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
      mainNav.classList.remove('menu-open');
      mobileMenuToggle.setAttribute('aria-expanded', 'false');
      const icon = mobileMenuToggle.querySelector('i');
      if (icon) {
        icon.classList.add('fa-bars');
        icon.classList.remove('fa-times');
      }
    }
  });
}

// Language switcher toggle
if (languageToggle && languageDropdown) {
  languageToggle.addEventListener('click', (e) => {
    e.stopPropagation();
    const open = languageDropdown.classList.toggle('active');
    languageToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  // Close language dropdown when clicking outside
  document.addEventListener('click', (e) => {
    if (!languageToggle.contains(e.target) && !languageDropdown.contains(e.target)) {
      languageDropdown.classList.remove('active');
      languageToggle.setAttribute('aria-expanded', 'false');
    }
  });

  // Close dropdown when selecting a language
  document.querySelectorAll('.language-option').forEach(option => {
    option.addEventListener('click', () => {
      languageDropdown.classList.remove('active');
    });
  });
}

// Dropdown hover functionality
document.querySelectorAll('.dropdown').forEach(dropdown => {
  const dropdownContent = dropdown.querySelector('.dropdown-content');
  
  dropdown.addEventListener('mouseenter', () => {
    dropdownContent.style.display = 'block';
    dropdownContent.style.opacity = '1';
    dropdownContent.style.transform = 'translateY(0)';
    dropdownContent.style.visibility = 'visible';
  });
  
  dropdown.addEventListener('mouseleave', () => {
    dropdownContent.style.display = 'none';
    dropdownContent.style.opacity = '0';
    dropdownContent.style.transform = 'translateY(-10px)';
    dropdownContent.style.visibility = 'hidden';
  });
});

// Close all dropdowns when pressing Escape key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    if (mainNav) mainNav.classList.remove('menu-open');
    if (mobileMenuToggle) mobileMenuToggle.setAttribute('aria-expanded', 'false');
    if (languageDropdown) languageDropdown.classList.remove('active');
    if (languageToggle) languageToggle.setAttribute('aria-expanded', 'false');
    if (mobileMenuToggle) {
      const icon = mobileMenuToggle.querySelector('i');
      if (icon) {
        icon.classList.add('fa-bars');
        icon.classList.remove('fa-times');
      }
    }
  }
});

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    const targetId = this.getAttribute('href');
    if (targetId === '#') return;
    
    const targetElement = document.querySelector(targetId);
    if (targetElement) {
      e.preventDefault();
      targetElement.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  });
});

// Add active state to current page link
document.addEventListener('DOMContentLoaded', () => {
  const currentPage = window.location.pathname.split('/').pop();
  const navLinks = document.querySelectorAll('#mainNav > a');
  
  navLinks.forEach(link => {
    const linkPage = link.getAttribute('href');
    if (linkPage === currentPage || 
        (currentPage === '' && linkPage === 'index.php') ||
        (currentPage === 'index.php' && linkPage === '')) {
      link.style.opacity = '1';
      link.style.fontWeight = '600';
      link.style.color = 'var(--accent-light)';
    }
  });
});
</script>