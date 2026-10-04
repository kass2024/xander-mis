(function () {
  'use strict';

  const root = document.getElementById('sc-wizard');
  if (!root || !window.SC_WIZARD) return;

  const cfg = window.SC_WIZARD;
  const state = {
    service: '',
    country_ref: '',
    country_name: '',
    offering_id: '',
    offering: null,
    customer_id: 0,
    customer: {},
    fee_type: '',
    fee_description: '',
    amount: '',
    currency: '',
    remaining_amount: '',
    remaining_currency: '',
    custom_title: '',
    custom_details: '',
    prepared_by_first: '',
    prepared_by_last: ''
  };

  const stepNames = ['service', 'destination', 'offering', 'customer', 'payment', 'review', 'done'];
  let step = 0;
  let serviceAdded = false;
  const panels = Array.from(root.querySelectorAll('[data-step]'));
  const stepItems = Array.from(document.querySelectorAll('.sc-steps [data-step-index]'));
  const errorBox = document.getElementById('sc-error');
  const backBtn = document.getElementById('sc-back');
  const nextBtn = document.getElementById('sc-next');

  function showError(message) {
    if (!errorBox) return;
    errorBox.hidden = !message;
    errorBox.textContent = message || '';
  }

  function offeringLabel() {
    if (state.service === 'work') return 'Select Available Job';
    if (state.service === 'visit') return 'Select Visit Visa Package';
    return 'Select School or Study Program';
  }

  function renderSteps() {
    panels.forEach((panel) => {
      panel.classList.toggle('is-active', Number(panel.dataset.step) === step);
    });
    stepItems.forEach((item) => {
      const index = Number(item.dataset.stepIndex);
      item.classList.toggle('is-active', index === step);
      item.classList.toggle('is-done', index < step);
    });
    if (backBtn) {
      backBtn.hidden = step === 0 || step === 6;
      backBtn.textContent = step === 5 ? 'Back / Edit' : 'Back';
    }
    if (nextBtn) {
      nextBtn.hidden = step === 6;
      nextBtn.textContent = step === 5 ? 'Confirm & Generate Contract' : 'Continue';
      nextBtn.disabled = step !== 6 && !stepValid();
    }
    const title = document.getElementById('sc-offering-title');
    if (title) title.textContent = offeringLabel();
    renderTrail();
  }

  function renderTrail() {
    const trail = document.getElementById('sc-trail');
    if (!trail) return;
    const serviceLabel = ({ study: 'Study', work: 'Work / Job', visit: 'Visit' })[state.service] || '';
    const offeringTitle = serviceAdded ? ((state.offering && (state.offering.title || state.offering.school_name)) || state.custom_title || '') : '';
    const chips = [
      ['Service', serviceLabel, 0],
      ['Country', state.country_name, 1],
      ['Program', offeringTitle, 2]
    ].filter((row) => row[1]);
    trail.hidden = chips.length === 0;
    trail.innerHTML = chips.map((row) => {
      const current = step === row[2];
      return '<span class="' + (current ? 'is-current' : 'is-done') + '">' + escapeHtml(row[0] + ': ' + row[1]) + '</span>';
    }).join('');
  }

  function stepValid() {
    if (step === 0) return state.service !== '';
    if (step === 1) return state.country_ref !== '';
    if (step === 2) return offeringReady();
    if (step === 3) return customerError() === '';
    if (step === 4) return feeError() === '';
    if (step === 5) return preparedError() === '' && feeError() === '';
    return true;
  }

  function offeringReady() {
    if (!serviceAdded || !state.offering_id) return false;
    if (state.offering_id === 'custom') {
      return (state.custom_title || '').trim() !== '';
    }
    return true;
  }

  function customerError() {
    const c = readCustomer();
    if (!c.first_name || !c.last_name) return 'First name and last name are required.';
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(c.email)) return 'A valid email address is required.';
    if ((c.phone.match(/\d/g) || []).length < 6) return 'A valid phone number is required.';
    if (!/^\d{4}-\d{2}-\d{2}$/.test(c.dob)) return 'Date of birth is required.';
    if (!c.nationality) return 'Nationality is required.';
    return '';
  }

  function feeError() {
    const fee = readFee();
    if (!fee.fee_type) return 'Fee type is required.';
    const amount = Number(fee.amount);
    if (!Number.isFinite(amount) || amount < 0) return 'Amount cannot be negative.';
    const otherKind = document.getElementById('sc-other-kind')?.value || '';
    if (fee.fee_type === 'other' && !otherKind) return 'Select the promotion type for this other fee.';
    const zeroAllowed = fee.fee_type === 'upfront' || fee.fee_type === 'promotion' || (fee.fee_type === 'other' && otherKind === 'promotion');
    if (amount <= 0 && !zeroAllowed) return 'Amount must be greater than zero. A Promotion type may be 0.';
    if (!fee.currency) return 'Currency is required.';
    const remaining = Number(fee.remaining_amount);
    if (!Number.isFinite(remaining) || remaining <= 0) return 'Remaining fees are required and must be greater than zero.';
    if (!fee.remaining_currency) return 'Remaining fees currency is required.';
    if (fee.fee_type === 'promotion' && !fee.fee_description) return 'Describe this fee.';
    if (fee.fee_type === 'other' && otherKind !== 'promotion' && !fee.fee_description) return 'Describe this fee.';
    return '';
  }

  function preparedError() {
    const first = (document.getElementById('sc-staff-first')?.value || '').trim();
    const last = (document.getElementById('sc-staff-last')?.value || '').trim();
    if (!first || !last) return 'Enter the staff first name and last name before generating the contract.';
    return '';
  }

  function readCustomer() {
    const val = (id) => (document.getElementById(id)?.value || '').trim();
    return {
      first_name: val('sc-first'),
      last_name: val('sc-last'),
      email: val('sc-email'),
      phone: val('sc-phone'),
      address: val('sc-address'),
      passport: val('sc-passport'),
      nationality: val('sc-nationality'),
      dob: val('sc-dob'),
      residence_country: val('sc-residence')
    };
  }

  function readFee() {
    return {
      fee_type: document.getElementById('sc-fee-type')?.value || '',
      fee_description: (document.getElementById('sc-fee-desc')?.value || '').trim(),
      amount: document.getElementById('sc-amount')?.value || '',
      currency: document.getElementById('sc-currency')?.value || '',
      remaining_amount: document.getElementById('sc-remaining')?.value || '',
      remaining_currency: document.getElementById('sc-remaining-currency')?.value || ''
    };
  }

  async function apiGet(params) {
    const url = new URL(cfg.api, window.location.origin);
    Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value));
    const res = await fetch(url.toString(), { credentials: 'same-origin' });
    const data = await res.json();
    if (!res.ok || data.ok === false) {
      throw new Error(data.error || 'Request failed.');
    }
    return data;
  }

  function clearOffering() {
    state.offering_id = '';
    state.offering = null;
  }

  let countries = [];
  let countryCursor = 0;
  const COUNTRY_ALIASES = {
    'united states': ['USA', 'US', 'America'],
    'united states of america': ['USA', 'US', 'America'],
    'united kingdom': ['UK', 'Britain', 'Great Britain'],
    'united kingdom of great britain and northern ireland': ['UK', 'Britain'],
    'united arab emirates': ['UAE', 'Emirates'],
    'czech republic': ['Czechia'],
    'czechia': ['Czech Republic'],
    'republic of korea': ['South Korea', 'Korea'],
    'korea south': ['Korea', 'South Korea'],
    'south korea': ['Korea'],
    'russian federation': ['Russia'],
    'viet nam': ['Vietnam'],
    'vietnam': ['Viet Nam'],
    'cote d ivoire': ['Ivory Coast'],
    'democratic republic of the congo': ['DRC', 'DR Congo'],
    'netherlands': ['Holland'],
    'myanmar': ['Burma'],
    'eswatini': ['Swaziland'],
    'cabo verde': ['Cape Verde'],
    'timor leste': ['East Timor'],
    'holy see': ['Vatican'],
    'vatican city': ['Vatican', 'Holy See'],
    'syrian arab republic': ['Syria'],
    'lao peoples democratic republic': ['Laos'],
    'iran islamic republic of': ['Iran'],
    'united republic of tanzania': ['Tanzania'],
    'republic of moldova': ['Moldova'],
    'north macedonia': ['Macedonia'],
    'state of palestine': ['Palestine']
  };

  function foldCountry(value) {
    return String(value || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .replace(/&/g, ' and ')
      .replace(/[^a-z0-9]+/g, ' ')
      .trim();
  }

  function countryRank(name, query) {
    const q = foldCountry(query);
    if (!q) return { score: 1, hint: '' };
    const folded = foldCountry(name);
    const words = folded.split(' ').filter(Boolean);
    const tokens = q.split(' ').filter(Boolean);
    const aliases = COUNTRY_ALIASES[folded] || [];
    const wordPrefix = tokens.every((token) => words.some((word) => word.startsWith(token)));
    if (folded.startsWith(q)) return { score: 100, hint: '' };
    if (wordPrefix) return { score: words[0] && words[0].startsWith(tokens[0]) ? 80 : 70, hint: '' };
    const hint = aliases.find((alias) => {
      const aliasWords = foldCountry(alias).split(' ').filter(Boolean);
      return tokens.every((token) => aliasWords.some((word) => word.startsWith(token)));
    }) || '';
    if (hint) return { score: 60, hint };
    if (tokens.every((token) => token.length >= 4 && folded.includes(token))) return { score: 30, hint: '' };
    return { score: 0, hint: '' };
  }

  function highlightCountry(name, query) {
    const tokens = foldCountry(query).split(' ').filter(Boolean);
    if (!tokens.length) return escapeHtml(name);
    const chars = Array.from(name);
    const foldedChars = chars.map((ch) => ch.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase());
    const folded = foldedChars.join('');
    const flags = chars.map(() => false);
    tokens.forEach((token) => {
      let from = 0;
      while (from < folded.length) {
        const at = folded.indexOf(token, from);
        if (at < 0) break;
        for (let i = at; i < at + token.length && i < flags.length; i += 1) flags[i] = true;
        from = at + Math.max(token.length, 1);
      }
    });
    let html = '';
    let open = false;
    chars.forEach((ch, index) => {
      if (flags[index] && !open) { html += '<mark>'; open = true; }
      if (!flags[index] && open) { html += '</mark>'; open = false; }
      html += escapeHtml(ch);
    });
    if (open) html += '</mark>';
    return html;
  }

  function selectCountry(input) {
    state.country_ref = input.value;
    state.country_name = input.dataset.name || '';
    serviceAdded = false;
    clearOffering();
    document.querySelectorAll('#sc-countries .sc-country-row').forEach((label) => {
      label.classList.toggle('is-selected', label.contains(input));
    });
    const selected = document.getElementById('sc-country-selected');
    if (selected) {
      selected.hidden = state.country_name === '';
      selected.textContent = state.country_name ? 'Selected: ' + state.country_name : '';
    }
    showError('');
    renderSteps();
  }

  function renderCountries() {
    const box = document.getElementById('sc-countries');
    const meta = document.getElementById('sc-country-meta');
    const clearBtn = document.getElementById('sc-country-clear');
    const query = (document.getElementById('sc-country-search')?.value || '').trim();
    if (clearBtn) clearBtn.hidden = query === '';
    const ranked = countries
      .map((country) => Object.assign({ country }, countryRank(country.name, query)))
      .filter((row) => row.score > 0);
    if (query) ranked.sort((a, b) => b.score - a.score || a.country.name.localeCompare(b.country.name));
    countryCursor = 0;
    if (meta) {
      if (!query) meta.textContent = countries.length ? countries.length + ' countries. Type to narrow the list.' : '';
      else if (!ranked.length) meta.textContent = 'No countries match “' + query + '”.';
      else meta.textContent = ranked.length + (ranked.length === 1 ? ' country matches' : ' countries match') + ' “' + query + '”. Press Enter to choose the highlighted one.';
    }
    const selected = document.getElementById('sc-country-selected');
    if (selected) {
      selected.hidden = !state.country_name;
      selected.textContent = state.country_name ? 'Selected: ' + state.country_name : '';
    }
    if (!box) return;
    if (!ranked.length) {
      box.innerHTML = '<p class="sc-country-empty">Try the start of the name, such as Fran for France, or a short name such as UAE.</p>';
      return;
    }
    box.innerHTML = ranked.map((row, index) => {
      const country = row.country;
      const checked = state.country_ref === country.ref;
      const classes = ['sc-country-row'];
      if (checked) classes.push('is-selected');
      if (query && index === 0) classes.push('is-active');
      const hint = row.hint ? '<span class="sc-country-hint">' + escapeHtml(row.hint) + '</span>' : '';
      return '<label class="' + classes.join(' ') + '" role="option">'
        + '<input type="radio" name="country_ref" value="' + escapeAttr(country.ref) + '" data-name="' + escapeAttr(country.name) + '"' + (checked ? ' checked' : '') + '>'
        + '<span class="sc-country-name">' + highlightCountry(country.name, query) + '</span>'
        + hint
        + '</label>';
    }).join('');
    box.querySelectorAll('input').forEach((input) => {
      input.addEventListener('change', () => selectCountry(input));
    });
    box.scrollTop = 0;
  }

  function moveCountryCursor(delta) {
    const rows = Array.from(document.querySelectorAll('#sc-countries .sc-country-row'));
    if (!rows.length) return;
    const current = rows.findIndex((row) => row.classList.contains('is-active'));
    countryCursor = current === -1
      ? (delta > 0 ? 0 : rows.length - 1)
      : (current + delta + rows.length) % rows.length;
    rows.forEach((row, index) => row.classList.toggle('is-active', index === countryCursor));
    rows[countryCursor].scrollIntoView({ block: 'nearest' });
  }

  async function loadCountries() {
    const box = document.getElementById('sc-countries');
    box.innerHTML = '<p class="sc-country-empty">Loading countries…</p>';
    state.country_ref = '';
    state.country_name = '';
    countries = [];
    const search = document.getElementById('sc-country-search');
    if (search) search.value = '';
    clearOffering();
    try {
      const data = await apiGet({ action: 'countries', service: state.service });
      countries = Array.isArray(data.countries) ? data.countries : [];
      if (!countries.length) {
        box.innerHTML = '<p class="sc-country-empty">No countries are currently available for this service.</p>';
        renderSteps();
        return;
      }
      renderCountries();
    } catch (err) {
      box.innerHTML = '<p class="sc-error">' + escapeHtml(err.message || 'Network error while loading countries.') + '</p>';
    }
    renderSteps();
  }

  async function loadOfferings() {
    const box = document.getElementById('sc-offerings');
    box.innerHTML = '<p class="sc-note">Loading options…</p>';
    serviceAdded = false;
    clearOffering();
    try {
      const data = await apiGet({
        action: 'offerings',
        service: state.service,
        country_ref: state.country_ref
      });
      const addForm = '<div class="sc-add-service"><h3>Add this service</h3><p class="sc-note">This contract uses <strong>' + escapeHtml(state.country_name || 'the selected country') + '</strong>. To make the service appear for that country next time, add it under Services and prices and choose the country there.</p><div class="sc-field"><label for="sc-custom-title">Service name</label><input id="sc-custom-title" placeholder="School, job, or visit package"></div><div class="sc-field"><label for="sc-custom-details">Details</label><textarea id="sc-custom-details" rows="3"></textarea></div><button type="button" class="sc-btn sc-btn-primary" id="sc-add-service">Add service</button></div>';
      if (!data.offerings.length) {
        box.innerHTML = '<p class="sc-empty">' + escapeHtml(data.message || 'No active option is listed for this destination. Add the service below.') + '</p>' + addForm;
        bindCustomService();
        renderSteps();
        return;
      }
      box.innerHTML = data.offerings.map((item) => {
        const detail = offeringDetail(item);
        return '<label class="sc-offer"><input type="radio" name="offering_id" value="' + escapeAttr(item.id) + '"> <strong>' + escapeHtml(item.title || '') + '</strong><p>' + escapeHtml(detail) + '</p></label>';
      }).join('');
      const byId = {};
      data.offerings.forEach((item) => { byId[item.id] = item; });
      box.querySelectorAll('input').forEach((input) => {
        input.addEventListener('change', () => {
          state.offering_id = input.value;
          state.offering = byId[input.value] || null;
          serviceAdded = false;
          const customTitle = document.getElementById('sc-custom-title');
          if (customTitle) customTitle.value = '';
          showPendingAdd(state.offering?.title || 'Selected service');
          showError('');
          renderSteps();
        });
      });
      box.insertAdjacentHTML('beforeend', addForm);
      bindCustomService();
    } catch (err) {
      box.innerHTML = '<p class="sc-error">' + escapeHtml(err.message || 'Network error while loading options.') + '</p>';
    }
    renderSteps();
  }

  function bindCustomService() {
    const button = document.getElementById('sc-add-service');
    const title = document.getElementById('sc-custom-title');
    if (!button || !title) return;
    const apply = () => {
      const name = title.value.trim();
      const details = (document.getElementById('sc-custom-details')?.value || '').trim();
      if (!name) {
        showError('Enter the service name, then add it.');
        return;
      }
      document.querySelectorAll('#sc-offerings input[name="offering_id"]').forEach((input) => { input.checked = false; });
      state.offering_id = 'custom';
      state.custom_title = name;
      state.custom_details = details;
      state.offering = { id: 'custom', title: name, description: details };
      serviceAdded = true;
      showError('');
      renderSteps();
      document.getElementById('sc-next')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };
    button.addEventListener('click', apply);
  }

  function showPendingAdd(title) {
    const box = document.getElementById('sc-offerings');
    if (!box) return;
    let bar = document.getElementById('sc-pending-add');
    if (!bar) {
      box.insertAdjacentHTML('afterbegin', '<div id="sc-pending-add" class="sc-add-service"><p>Selected service: <strong></strong></p><button type="button" class="sc-btn sc-btn-primary" id="sc-confirm-add">Add service</button><p class="sc-note">Add the service to bring up Continue.</p></div>');
      bar = document.getElementById('sc-pending-add');
      document.getElementById('sc-confirm-add')?.addEventListener('click', () => {
        if (!state.offering_id || state.offering_id === 'custom') return;
        serviceAdded = true;
        showError('');
        const note = bar.querySelector('.sc-note');
        if (note) note.textContent = 'Service added. Continue is now available.';
        renderSteps();
        document.getElementById('sc-next')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    }
    const strong = bar.querySelector('strong');
    if (strong) strong.textContent = title;
    bar.hidden = false;
  }

  function offeringDetail(item) {
    if (state.service === 'study') {
      return [item.level, item.city, item.duration, item.country_name].filter(Boolean).join(' · ');
    }
    if (state.service === 'work') {
      return [
        item.roles ? 'Jobs: ' + item.roles : '',
        item.processing ? 'Processing: ' + item.processing : '',
        item.salary ? 'Salary from: ' + item.salary : '',
        item.requirements || '',
        item.note || ''
      ].filter(Boolean).join(' · ') || item.description || '';
    }
    return [item.country_name, item.description].filter(Boolean).join(' · ');
  }

  function fillReview() {
    const customer = readCustomer();
    const fee = readFee();
    state.customer = customer;
    state.fee_type = fee.fee_type;
    state.fee_description = fee.fee_description;
    state.amount = fee.amount;
    state.currency = fee.currency;
    const feeLabel = ({
      upfront: 'Upfront Fee',
      commitment: 'Commitment Fee',
      service: 'Service Fee',
      promotion: 'Promotion',
      other: 'Other Fee'
    })[fee.fee_type] || fee.fee_type;
    const serviceLabel = ({ study: 'Study', work: 'Work / Job', visit: 'Visit' })[state.service] || state.service;
    const offering = state.offering || {};
    document.getElementById('sc-review').innerHTML = [
      block('Customer', [
        ['Full name', (customer.first_name + ' ' + customer.last_name).trim()],
        ['Email', customer.email],
        ['Phone', customer.phone]
      ]),
      block('Service', [['Selected service', serviceLabel]]),
      block('Destination', [['Country', state.country_name]]),
      block('Program details', programLines(offering)),
      block('Payment', [
        ['Fee type', feeLabel],
        ['Amount', fee.amount],
        ['Currency', fee.currency],
        ['Remaining fees', fee.remaining_amount || 'Required'],
        ['Remaining currency', fee.remaining_currency || 'Required'],
        ['Description', fee.fee_description]
      ].filter((row) => row[0] === 'Remaining fees' || row[0] === 'Remaining currency' || row[1])),
      block('Staff', [
        ['Prepared by', ((document.getElementById('sc-staff-first')?.value || '') + ' ' + (document.getElementById('sc-staff-last')?.value || '')).trim()],
        ['Staff ID', String(cfg.staffId || '')]
      ]),
      block('Contract', [['Template', 'Xander Global Scholars – Master International Services Agreement']])
    ].join('');
    const frame = document.getElementById('sc-contract-preview');
    if (frame && cfg.preview) {
      const params = new URLSearchParams({
        preview: '1',
        service: state.service,
        country: state.country_name,
        offering: offering.title || offering.school_name || '',
        details: offering.description || offering.program_name || '',
        fee_type: feeLabel,
        amount: fee.amount,
        currency: fee.currency,
        remaining: fee.remaining_amount,
        remaining_currency: fee.remaining_currency,
        customer: (customer.first_name + ' ' + customer.last_name).trim(),
        email: customer.email,
        phone: customer.phone,
        prepared_by: ((document.getElementById('sc-staff-first')?.value || '') + ' ' + (document.getElementById('sc-staff-last')?.value || '')).trim()
      });
      frame.src = cfg.preview + '?' + params.toString();
    }
  }

  function programLines(offering) {
    if (state.service === 'study') {
      return [
        ['School', offering.school_name || offering.title || ''],
        ['Program', offering.program_name || offering.description || ''],
        ['Level', offering.level || ''],
        ['Location', offering.city || '']
      ].filter((row) => row[1]);
    }
    if (state.service === 'work') {
      return [
        ['Job', offering.job_title || offering.title || ''],
        ['Jobs available', offering.roles || ''],
        ['Processing', offering.processing || ''],
        ['Salary from', offering.salary || ''],
        ['Requirements', offering.requirements || ''],
        ['Note', offering.note || '']
      ].filter((row) => row[1]);
    }
    return [
      ['Visit package', offering.package_name || offering.title || ''],
      ['Details', offering.description || '']
    ].filter((row) => row[1]);
  }

  function block(title, rows) {
    const body = rows.map((row) => '<p><strong>' + escapeHtml(row[0]) + ':</strong> ' + escapeHtml(row[1]) + '</p>').join('');
    return '<section><h3>' + escapeHtml(title) + '</h3>' + body + '</section>';
  }

  async function generate() {
    nextBtn.disabled = true;
    showError('');
    try {
      const res = await fetch(cfg.api, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': cfg.csrf },
        body: JSON.stringify({
          action: 'generate',
          csrf_token: cfg.csrf,
          service: state.service,
          country_ref: state.country_ref,
          offering_id: state.offering_id,
          customer_id: state.customer_id || 0,
          customer: readCustomer(),
          fee_type: readFee().fee_type,
          fee_description: readFee().fee_description,
          amount: readFee().amount,
          currency: readFee().currency,
          remaining_amount: readFee().remaining_amount,
          remaining_currency: readFee().remaining_currency,
          other_kind: document.getElementById('sc-other-kind')?.value || '',
          custom_title: (document.getElementById('sc-custom-title')?.value || state.custom_title || '').trim(),
          custom_details: (document.getElementById('sc-custom-details')?.value || state.custom_details || '').trim(),
          prepared_by_first: (document.getElementById('sc-staff-first')?.value || '').trim(),
          prepared_by_last: (document.getElementById('sc-staff-last')?.value || '').trim()
        })
      });
      const data = await res.json();
      if (!res.ok || !data.ok) {
        throw new Error(data.error || 'Could not generate the contract.');
      }
      document.getElementById('sc-reference').textContent = data.reference;
      const link = document.getElementById('sc-link');
      link.value = data.url;
      document.getElementById('sc-view').href = data.url;
      step = 6;
      renderSteps();
    } catch (err) {
      showError(err.message || 'Network error while generating the contract.');
      nextBtn.disabled = false;
    }
  }

  root.querySelectorAll('input[name="service"]').forEach((input) => {
    input.addEventListener('change', () => {
      const previous = state.service;
      state.service = input.value;
      if (previous !== state.service) {
        state.country_ref = '';
        state.country_name = '';
        serviceAdded = false;
        clearOffering();
      }
      showError('');
      renderSteps();
    });
  });

  document.getElementById('sc-prepared-btn')?.addEventListener('click', () => {
    const fields = document.getElementById('sc-prepared-fields');
    if (fields) {
      fields.hidden = false;
      document.getElementById('sc-staff-first')?.focus();
    }
  });

  document.getElementById('sc-fee-type')?.addEventListener('change', () => {
    const other = document.getElementById('sc-fee-desc-wrap');
    const kind = document.getElementById('sc-other-kind-wrap');
    const type = document.getElementById('sc-fee-type').value;
    if (other) other.hidden = type !== 'other' && type !== 'promotion';
    if (kind) kind.hidden = type !== 'other';
    if (type !== 'other') {
      const select = document.getElementById('sc-other-kind');
      if (select) select.value = '';
    }
    renderSteps();
  });
  document.getElementById('sc-other-kind')?.addEventListener('change', () => {
    const desc = document.getElementById('sc-fee-desc');
    if (document.getElementById('sc-other-kind').value === 'promotion' && desc && !desc.value.trim()) {
      desc.value = 'Promotion';
    }
    renderSteps();
  });
  const countrySearch = document.getElementById('sc-country-search');
  countrySearch?.addEventListener('input', renderCountries);
  countrySearch?.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowDown') {
      event.preventDefault();
      moveCountryCursor(1);
    } else if (event.key === 'ArrowUp') {
      event.preventDefault();
      moveCountryCursor(-1);
    } else if (event.key === 'Enter') {
      event.preventDefault();
      const rows = Array.from(document.querySelectorAll('#sc-countries .sc-country-row'));
      const row = rows.find((item) => item.classList.contains('is-active')) || (countrySearch.value.trim() ? rows[0] : null);
      const input = row?.querySelector('input');
      if (!input) return;
      input.checked = true;
      selectCountry(input);
    } else if (event.key === 'Escape') {
      countrySearch.value = '';
      renderCountries();
    }
  });
  document.getElementById('sc-country-clear')?.addEventListener('click', () => {
    if (countrySearch) countrySearch.value = '';
    renderCountries();
    countrySearch?.focus();
  });
  ['sc-amount', 'sc-currency', 'sc-fee-desc', 'sc-remaining', 'sc-remaining-currency', 'sc-staff-first', 'sc-staff-last', 'sc-first', 'sc-last', 'sc-email', 'sc-phone', 'sc-dob', 'sc-nationality'].forEach((id) => {
    document.getElementById(id)?.addEventListener('input', () => {
      renderSteps();
      if (step === 5 && (id === 'sc-staff-first' || id === 'sc-staff-last')) fillReview();
    });
  });

  let searchTimer = null;
  document.getElementById('sc-customer-search')?.addEventListener('input', (event) => {
    clearTimeout(searchTimer);
    const q = event.target.value.trim();
    const list = document.getElementById('sc-customer-results');
    if (q.length < 2) {
      list.innerHTML = '';
      return;
    }
    searchTimer = setTimeout(async () => {
      try {
        const data = await apiGet({ action: 'customers', q });
        if (!data.customers.length) {
          list.innerHTML = '<p class="sc-empty">No matching customer records.</p>';
          return;
        }
        list.innerHTML = data.customers.map((customer, index) => (
          '<button type="button" class="sc-btn sc-btn-ghost" data-customer="' + index + '" style="margin:0 8px 8px 0;">' +
          escapeHtml((customer.first_name + ' ' + customer.last_name).trim() + ' · ' + customer.email) +
          '</button>'
        )).join('');
        list.querySelectorAll('button').forEach((button) => {
          button.addEventListener('click', () => {
            const customer = data.customers[Number(button.dataset.customer)];
            state.customer_id = customer.id;
            setVal('sc-first', customer.first_name);
            setVal('sc-last', customer.last_name);
            setVal('sc-email', customer.email);
            setVal('sc-phone', customer.phone);
            setVal('sc-dob', (customer.dob || '').slice(0, 10));
            setVal('sc-nationality', customer.nationality);
            setVal('sc-passport', customer.passport);
            setVal('sc-address', customer.address);
            renderSteps();
          });
        });
      } catch (err) {
        list.innerHTML = '<p class="sc-error">' + escapeHtml(err.message || 'Could not search customers.') + '</p>';
      }
    }, 300);
  });

  backBtn?.addEventListener('click', () => {
    if (step > 0) step -= 1;
    showError('');
    renderSteps();
  });

  nextBtn?.addEventListener('click', async () => {
    if (step === 5) {
      const problem = customerError() || feeError() || preparedError();
      if (!state.service || !state.country_ref || !offeringReady() || problem) {
        showError(problem || 'Review the contract before generating the link.');
        return;
      }
      await generate();
      return;
    }
    if (!stepValid()) {
      showError(step === 3 ? customerError() : (step === 4 ? feeError() : (step === 5 ? preparedError() : 'Complete this step before continuing.')));
      return;
    }
    showError('');
    if (step === 0) await loadCountries();
    if (step === 1) await loadOfferings();
    step += 1;
    if (step === 5) {
      const feeProblem = feeError();
      if (feeProblem) {
        step -= 1;
        showError(state.service === 'visit'
          ? 'A visit contract cannot be generated until the remaining fees are added. ' + feeProblem
          : feeProblem);
        renderSteps();
        return;
      }
      fillReview();
      document.getElementById('sc-contract-preview')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    renderSteps();
  });

  document.getElementById('sc-copy')?.addEventListener('click', async () => {
    const link = document.getElementById('sc-link');
    try {
      await navigator.clipboard.writeText(link.value);
    } catch (err) {
      link.select();
      document.execCommand('copy');
    }
    const msg = document.getElementById('sc-copied');
    if (msg) {
      msg.hidden = false;
      setTimeout(() => { msg.hidden = true; }, 2000);
    }
  });

  function setVal(id, value) {
    const el = document.getElementById(id);
    if (el) el.value = value || '';
  }

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[ch]);
  }

  function escapeAttr(value) {
    return escapeHtml(value);
  }

  renderSteps();
})();
