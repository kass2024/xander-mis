<?php
declare(strict_types=1);
?>
<script>
(function () {
  function clearRequired(panel) {
    if (!panel) return;
    panel.querySelectorAll('input, select, textarea').forEach(function (el) {
      if (el.type === 'hidden' || el.classList.contains('prescreen-doc-path')) return;
      el.required = false;
      el.removeAttribute('required');
    });
  }

  function applyService(type) {
    const card = document.getElementById('serviceTypeCard');
    if (card) {
      card.classList.toggle('border-primary', !!type);
      card.classList.toggle('border', !!type);
    }
    document.querySelectorAll('[data-service-panel]').forEach(function (panel) {
      const match = type && panel.getAttribute('data-service-panel') === type;
      panel.classList.toggle('d-none', !match);
      panel.setAttribute('aria-hidden', match ? 'false' : 'true');
      clearRequired(panel);
    });
    document.querySelectorAll('.prescreen-contact-readonly').forEach(function (row) {
      row.classList.toggle('d-none', type === 'work_abroad');
    });
    document.querySelectorAll('.prescreen-work-contact').forEach(function (el) {
      el.required = false;
      el.removeAttribute('required');
    });
  }

  function currentType() {
    const sel = document.querySelector('select[name="service_type"]');
    return sel ? sel.value : '';
  }

  document.addEventListener('DOMContentLoaded', function () {
    const sel = document.querySelector('select[name="service_type"]');
    if (sel) {
      sel.addEventListener('change', function () { applyService(currentType()); });
      applyService(currentType());
    }
    document.addEventListener('prescreen:service', function (e) {
      applyService(e.detail && e.detail.type ? e.detail.type : currentType());
    });
  });

  function selectedCountryCount(selectEl) {
    if (!selectEl) return 0;
    return Array.from(selectEl.selectedOptions).filter(function (o) {
      return o.value && o.value.trim() !== '';
    }).length;
  }

  function validateCountryMulti(form) {
    const type = currentType();
    if (type === 'work_abroad') {
      const sel = form.querySelector('select.prescreen-country-multi[name="work_country_destination[]"]');
      if (selectedCountryCount(sel) < 2) {
        alert('Please select at least two countries of interest for work abroad.');
        sel && sel.focus();
        return false;
      }
    }
    if (type === 'study_abroad') {
      const sel = form.querySelector('select.prescreen-country-multi[name="country_interest[]"]');
      if (selectedCountryCount(sel) < 2) {
        alert('Please select at least two countries of interest.');
        sel && sel.focus();
        return false;
      }
    }
    return true;
  }

  document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form || !form.querySelector('select[name="service_type"]')) return;
    applyService(currentType());
    if (!validateCountryMulti(form)) {
      e.preventDefault();
      e.stopPropagation();
    }
  }, true);
})();
</script>
