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

  async function loadCountries() {
    const box = document.getElementById('sc-countries');
    box.innerHTML = '<p class="sc-note">Loading countries…</p>';
    state.country_ref = '';
    state.country_name = '';
    clearOffering();
    try {
      const data = await apiGet({ action: 'countries', service: state.service });
      if (!data.countries.length) {
        box.innerHTML = '<p class="sc-empty">No countries are currently available for this service.</p>';
        renderSteps();
        return;
      }
      box.innerHTML = data.countries.map((country) => (
        '<label class="sc-choice sc-country"><input type="radio" name="country_ref" value="' + escapeAttr(country.ref) + '" data-name="' + escapeAttr(country.name) + '"> <strong>' + escapeHtml(country.name) + '</strong></label>'
      )).join('');
      box.querySelectorAll('input').forEach((input) => {
        input.addEventListener('change', () => {
          state.country_ref = input.value;
          state.country_name = input.dataset.name || '';
          serviceAdded = false;
          clearOffering();
          box.querySelectorAll('.sc-choice').forEach((label) => label.classList.remove('is-selected'));
          const label = input.closest('.sc-choice');
          if (label) label.classList.add('is-selected');
          showError('');
          renderSteps();
        });
      });
      filterCountries();
    } catch (err) {
      box.innerHTML = '<p class="sc-error">' + escapeHtml(err.message || 'Network error while loading countries.') + '</p>';
    }
    renderSteps();
  }

  function filterCountries() {
    const query = (document.getElementById('sc-country-search')?.value || '').trim().toLowerCase();
    document.querySelectorAll('#sc-countries .sc-country').forEach((label) => {
      const name = (label.querySelector('strong')?.textContent || '').toLowerCase();
      label.hidden = query !== '' && !name.includes(query);
    });
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
      const addForm = '<div class="sc-add-service"><h3>Add this service</h3><div class="sc-field"><label for="sc-custom-title">Service name</label><input id="sc-custom-title" placeholder="School, job, or visit package"></div><div class="sc-field"><label for="sc-custom-details">Details</label><textarea id="sc-custom-details" rows="3"></textarea></div><button type="button" class="sc-btn sc-btn-primary" id="sc-add-service">Add service</button></div>';
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
  document.getElementById('sc-country-search')?.addEventListener('input', filterCountries);
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
