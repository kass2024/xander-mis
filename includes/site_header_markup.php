<header class="site-header">
  <div class="header-inner">
    <div class="header-row header-row-main">
      <a href="index.php" class="header-logo" title="Xander Global Scholars">
        <span class="header-logo-mark">
          <img src="XANDER GLOBAL SCHOLARS LOGO.png" alt="Xander Global Scholars" width="56" height="56" onerror="this.closest('.header-logo-mark').style.display='none'">
        </span>
        <span class="brand">Xander Global Scholars</span>
      </a>

      <nav class="header-nav-primary" id="headerNavPrimary" aria-label="<?php echo htmlspecialchars($current_lang === 'fr' ? 'Navigation principale' : 'Main navigation', ENT_QUOTES, 'UTF-8'); ?>">
        <a href="index.php"><?php echo ht('nav_home'); ?></a>
        <a href="about.php"><?php echo ht('nav_about'); ?></a>
        <a href="services.php"><?php echo ht('nav_services'); ?></a>
        <a href="partners.php"><?php echo ht('nav_partners'); ?></a>
        <a href="testimonials.php"><?php echo ht('nav_testimonials'); ?></a>
        <a href="contact.php"><?php echo ht('nav_contact'); ?></a>
      </nav>

      <div class="header-auth">
        <div class="hdr-dropdown" data-dropdown>
          <button type="button" class="hdr-btn hdr-btn--outline" data-dropdown-toggle aria-expanded="false">
            <i class="fas fa-right-to-bracket" aria-hidden="true"></i>
            <span class="hdr-btn-label"><?php echo ht('login'); ?></span>
            <i class="fas fa-chevron-down" style="font-size:0.65rem;opacity:0.85" aria-hidden="true"></i>
          </button>
          <div class="hdr-dropdown-menu" role="menu">
            <a href="student-login.php" role="menuitem"><i class="fas fa-user-graduate"></i> <?php echo ht('login_student'); ?></a>
            <a href="admin-login.php" role="menuitem"><i class="fas fa-user-shield"></i> <?php echo ht('login_admin'); ?></a>
            <a href="institution-login.php" role="menuitem"><i class="fas fa-building-columns"></i> <?php echo ht('login_institution'); ?></a>
          </div>
        </div>

        <div class="hdr-dropdown" data-dropdown>
          <button type="button" class="hdr-btn hdr-btn--solid" data-dropdown-toggle aria-expanded="false">
            <i class="fas fa-user-plus" aria-hidden="true"></i>
            <span class="hdr-btn-label"><?php echo ht('sign_up'); ?></span>
            <i class="fas fa-chevron-down" style="font-size:0.65rem;opacity:0.9" aria-hidden="true"></i>
          </button>
          <div class="hdr-dropdown-menu" role="menu">
            <a href="student-application.php" role="menuitem"><i class="fas fa-user-graduate"></i> <?php echo ht('signup_student'); ?></a>
            <a href="register.php" role="menuitem"><i class="fas fa-user-shield"></i> <?php echo ht('signup_admin'); ?></a>
            <a href="institution-signup.php" role="menuitem"><i class="fas fa-building-columns"></i> <?php echo ht('signup_institution'); ?></a>
          </div>
        </div>

        <div class="language-switcher language-switcher--compact">
          <button type="button" class="language-switcher-toggle" id="languageToggle" aria-expanded="false" aria-haspopup="true" aria-controls="languageDropdown">
            <span class="language-flag" aria-hidden="true"><?php echo $current_lang === 'fr' ? '🇫🇷' : '🇬🇧'; ?></span>
            <span class="language-name"><?php echo $current_lang === 'fr' ? 'FR' : 'EN'; ?></span>
            <i class="fas fa-chevron-down" aria-hidden="true" style="font-size:0.65rem"></i>
          </button>
          <div class="language-switcher-dropdown" id="languageDropdown" role="menu">
            <a href="?lang=en" class="language-option <?php echo $current_lang === 'en' ? 'active' : ''; ?>" role="menuitem">
              <span class="language-flag">🇬🇧</span><span class="language-name">English</span>
            </a>
            <a href="?lang=fr" class="language-option <?php echo $current_lang === 'fr' ? 'active' : ''; ?>" role="menuitem">
              <span class="language-flag">🇫🇷</span><span class="language-name">Français</span>
            </a>
          </div>
        </div>

        <button type="button" class="mobile-menu-toggle" id="mobileMenuToggle" aria-expanded="false" aria-controls="mobileNavPanel" aria-label="Menu">
          <i class="fas fa-bars" aria-hidden="true"></i>
        </button>
      </div>
    </div>

    <div class="header-row header-row-sub">
      <a href="https://elearning.xanderglobalscholars.com/" target="_blank" rel="noopener noreferrer"><?php echo ht('nav_elearning'); ?></a>
      <div class="hdr-sub-dropdown hdr-dropdown" data-dropdown>
        <button type="button" class="hdr-sub-trigger hdr-sub-link" data-dropdown-toggle aria-expanded="false">
          <i class="fas fa-credit-card" aria-hidden="true"></i>
          <?php echo ht('nav_payment'); ?>
          <i class="fas fa-chevron-down" style="font-size:0.65rem" aria-hidden="true"></i>
        </button>
        <div class="hdr-dropdown-menu" role="menu">
          <a href="payment.php" role="menuitem"><i class="fas fa-file-invoice-dollar"></i> <?php echo ht('nav_pay_service'); ?></a>
          <a href="payother.php" role="menuitem"><i class="fas fa-hand-holding-usd"></i> <?php echo ht('nav_other_payment'); ?></a>
        </div>
      </div>
    </div>

    <nav id="mobileNavPanel" class="mobile-nav-panel" aria-label="<?php echo htmlspecialchars($current_lang === 'fr' ? 'Menu mobile' : 'Mobile menu', ENT_QUOTES, 'UTF-8'); ?>">
      <a href="index.php"><?php echo ht('nav_home'); ?></a>
      <a href="about.php"><?php echo ht('nav_about'); ?></a>
      <a href="services.php"><?php echo ht('nav_services'); ?></a>
      <a href="partners.php"><?php echo ht('nav_partners'); ?></a>
      <a href="testimonials.php"><?php echo ht('nav_testimonials'); ?></a>
      <a href="contact.php"><?php echo ht('nav_contact'); ?></a>
      <a href="programs.php"><?php echo ht('nav_programs'); ?></a>
      <a href="universities.php"><?php echo ht('nav_universities'); ?></a>
    </nav>
  </div>
</header>
