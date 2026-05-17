(function () {
  var cfgEl = document.getElementById('institution-dashboard-config');
  if (!cfgEl) {
    return;
  }

  var cfg;
  try {
    cfg = JSON.parse(cfgEl.textContent || '{}');
  } catch (e) {
    cfg = {};
  }

  var tab = cfg.tab || 'overview';
  var uniName = cfg.uniName || '';
  var uploadSection = document.getElementById('upload_section');
  if (uploadSection && (tab === 'scholarship' || tab === 'loan')) {
    uploadSection.value = tab;
  }

  var sidebar = document.getElementById('sidebar');
  var overlay = document.getElementById('sidebarOverlay');
  var menuOpen = document.getElementById('menuOpenBtn');
  var menuClose = document.getElementById('menuCloseBtn');
  var previewPanel = document.getElementById('previewPanel');
  var previewToggle = document.getElementById('previewToggleBtn');
  var fabSave = document.getElementById('fabSaveBtn');
  var profileForm = document.getElementById('profileForm');
  var profileMenu = document.getElementById('profileMenu');
  var profileMenuBtn = document.getElementById('profileMenuBtn');

  function openSidebar() {
    if (sidebar) {
      sidebar.classList.add('open');
    }
    if (overlay) {
      overlay.classList.add('show');
    }
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    if (sidebar) {
      sidebar.classList.remove('open');
    }
    if (overlay) {
      overlay.classList.remove('show');
    }
    document.body.style.overflow = '';
  }

  if (menuOpen) {
    menuOpen.addEventListener('click', openSidebar);
  }
  if (menuClose) {
    menuClose.addEventListener('click', closeSidebar);
  }
  if (overlay) {
    overlay.addEventListener('click', closeSidebar);
  }

  function closeProfileMenu() {
    if (profileMenu) {
      profileMenu.classList.remove('open');
    }
    if (profileMenuBtn) {
      profileMenuBtn.setAttribute('aria-expanded', 'false');
    }
  }

  if (profileMenuBtn && profileMenu) {
    profileMenuBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      var isOpen = profileMenu.classList.toggle('open');
      profileMenuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  }

  document.addEventListener('click', function (e) {
    if (profileMenu && !profileMenu.contains(e.target)) {
      closeProfileMenu();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeProfileMenu();
      closeSidebar();
    }
  });

  if (previewToggle && previewPanel) {
    previewToggle.addEventListener('click', function () {
      previewPanel.classList.toggle('mobile-open');
    });
  }

  if (fabSave && profileForm) {
    fabSave.addEventListener('click', function () {
      if (typeof profileForm.requestSubmit === 'function') {
        profileForm.requestSubmit();
      } else {
        profileForm.submit();
      }
    });
  }

  function val(id) {
    var el = document.getElementById(id);
    return el ? el.value.trim() : '';
  }

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function syncPreview() {
    var schName = val('f_sch_name') || uniName;
    var titleEl = document.getElementById('pv_title');
    var taglineEl = document.getElementById('pv_tagline');
    var schSummaryEl = document.getElementById('pv_sch_summary');
    var loanNameEl = document.getElementById('pv_loan_name');
    var loanSummaryEl = document.getElementById('pv_loan_summary');
    var pills = document.getElementById('pv_sch_pills');

    if (titleEl) {
      titleEl.textContent = schName;
    }
    if (taglineEl) {
      taglineEl.textContent = val('f_sch_tagline') || 'Scholarship and loan opportunities';
    }
    if (schSummaryEl) {
      schSummaryEl.textContent = val('f_sch_summary') || 'Your scholarship summary will appear here.';
    }
    if (loanNameEl) {
      loanNameEl.textContent = val('f_loan_name') || '-';
    }
    if (loanSummaryEl) {
      loanSummaryEl.textContent = val('f_loan_summary') || 'Loan partnership details will appear here.';
    }
    if (pills) {
      var amt = val('f_sch_amt');
      pills.innerHTML = amt ? '<span class="preview-pill">' + escapeHtml(amt) + '</span>' : '';
    }
  }

  if (tab === 'scholarship' || tab === 'loan') {
    ['f_sch_name', 'f_sch_tagline', 'f_sch_summary', 'f_sch_amt', 'f_loan_name', 'f_loan_summary'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) {
        el.addEventListener('input', syncPreview);
      }
    });
    syncPreview();
  }

  if (profileForm) {
    profileForm.addEventListener('submit', function () {
      document.querySelectorAll('.profile-file-input').forEach(function (input) {
        if (input.getAttribute('data-section') !== tab) {
          input.disabled = true;
        }
      });
    });
  }

  document.querySelectorAll('[data-confirm]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      if (!window.confirm('Remove this document')) {
        e.preventDefault();
      }
    });
  });

  window.addEventListener('resize', function () {
    if (window.innerWidth >= 993) {
      closeSidebar();
      if (previewPanel) {
        previewPanel.classList.remove('mobile-open');
      }
    }
  });
})();
