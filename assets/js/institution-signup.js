(function () {
  var cfgEl = document.getElementById('inst-signup-config');
  var cfg = {};
  if (cfgEl) {
    try {
      cfg = JSON.parse(cfgEl.textContent || '{}');
    } catch (e) {
      cfg = {};
    }
  }

  function apiUrl(path) {
    var base = String(cfg.apiBase || '').replace(/\/$/, '');
    return base ? base + '/' + path.replace(/^\//, '') : path;
  }

  var nameInput = document.getElementById('institution_name');
  var results = document.getElementById('lookupResults');
  var uniId = document.getElementById('university_id');
  var portalBlock = document.getElementById('has_portal_block');
  var form = document.getElementById('instSignupForm');
  var debounce;

  if (!nameInput || !results || !uniId) {
    return;
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/"/g, '&quot;');
  }

  function fillInstitution(item) {
    if (!item) {
      return;
    }
    uniId.value = item.id || 0;
    nameInput.value = item.name || '';
    if (item.region_id) {
      document.getElementById('region_id').value = String(item.region_id);
    }
    if (item.country_id) {
      document.getElementById('country_id').value = String(item.country_id);
    }
    if (item.city) {
      document.getElementById('city').value = item.city;
    }
    if (item.website) {
      document.getElementById('website').value = item.website;
    }
    if (item.institution_phone) {
      document.getElementById('institution_phone').value = item.institution_phone;
    }
    if (item.institution_kind) {
      document.getElementById('institution_kind').value = item.institution_kind;
    }
    if (portalBlock) {
      portalBlock.value = item.has_portal ? '1' : '0';
    }
    results.classList.remove('show');
  }

  function renderResults(items) {
    if (!items.length) {
      results.innerHTML = '<div class="lookup-item text-muted">No match — continue as new</div>';
      results.classList.add('show');
      return;
    }
    results.innerHTML = items
      .map(function (it) {
        var badge = it.has_portal ? '<span class="lookup-badge">Has portal</span>' : '';
        var loc = [it.city, it.country_name].filter(Boolean).join(', ');
        return (
          '<div class="lookup-item" data-json="' +
          encodeURIComponent(JSON.stringify(it)) +
          '"><strong>' +
          escapeHtml(it.name) +
          '</strong>' +
          badge +
          '<small>' +
          escapeHtml(loc || [it.region_name, it.country_name].filter(Boolean).join(' · ')) +
          '</small></div>'
        );
      })
      .join('');
    results.classList.add('show');
    results.querySelectorAll('.lookup-item[data-json]').forEach(function (el) {
      el.addEventListener('click', function () {
        try {
          fillInstitution(JSON.parse(decodeURIComponent(el.getAttribute('data-json'))));
        } catch (err) {
          /* ignore */
        }
      });
    });
  }

  function search(q) {
    if (q.length < 2) {
      results.classList.remove('show');
      return;
    }
    fetch(apiUrl('api/institution-lookup.php?q=' + encodeURIComponent(q)), { credentials: 'same-origin' })
      .then(function (r) {
        return r.json();
      })
      .then(function (json) {
        if (json.success && json.data && json.data.items) {
          renderResults(json.data.items);
        }
      })
      .catch(function () {});
  }

  nameInput.addEventListener('input', function () {
    uniId.value = '0';
    if (portalBlock) {
      portalBlock.value = '0';
    }
    clearTimeout(debounce);
    debounce = setTimeout(function () {
      search(nameInput.value.trim());
    }, 280);
  });

  document.addEventListener('click', function (e) {
    if (!results.contains(e.target) && e.target !== nameInput) {
      results.classList.remove('show');
    }
  });

  if (form) {
    form.addEventListener('submit', function (e) {
      if (portalBlock && portalBlock.value === '1') {
        e.preventDefault();
        alert('This institution already has a portal account. Please sign in instead.');
      }
    });
  }
})();
