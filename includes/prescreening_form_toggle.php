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

  /** Strip to digits; remove duplicated country code if user pasted full number twice. */
  function normalizeWhatsappInput(el) {
    if (!el) return;
    let d = String(el.value || '').replace(/\D/g, '');
    if (d.length < 10) return;
    const codes = ['880','234','254','256','255','250','971','966','44','49','33','39','34','91','86','61','27','1'];
    codes.sort(function (a, b) { return b.length - a.length; });
    for (let i = 0; i < codes.length; i++) {
      const cc = codes[i];
      if (!d.startsWith(cc) || d.length < cc.length + 8) continue;
      const nat = d.slice(cc.length).replace(/^0+/, '');
      const doubled = cc + nat;
      if (nat.startsWith(cc) || nat.startsWith(doubled)) {
        d = cc + nat.replace(new RegExp('^' + cc), '').replace(new RegExp('^' + doubled), '');
      }
      if (d.startsWith(cc + cc)) {
        d = cc + d.slice(cc.length * 2);
      }
      el.value = d;
      return;
    }
    el.value = d;
  }

  document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form || !form.querySelector('select[name="service_type"]')) return;
    applyService(currentType());
    if (currentType() === 'work_abroad') {
      normalizeWhatsappInput(form.querySelector('input[name="whatsapp_number"]'));
    }
    if (!validateCountryMulti(form)) {
      e.preventDefault();
      e.stopPropagation();
    }
  }, true);
})();
</script>
