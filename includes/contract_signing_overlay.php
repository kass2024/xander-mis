<?php

/** Shared signing overlay — include once per contract page (before </body>). */

?>

<style>

.contract-submit-overlay {

  position: fixed;

  inset: 0;

  background: rgba(15, 23, 42, 0.58);

  backdrop-filter: blur(5px);

  display: flex;

  align-items: center;

  justify-content: center;

  z-index: 99999;

}

.contract-submit-overlay.hidden { display: none; }

.contract-submit-box {

  background: #fff;

  padding: 32px 28px;

  width: 92%;

  max-width: 380px;

  border-radius: 18px;

  text-align: center;

  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);

}

.contract-submit-spinner {

  width: 48px;

  height: 48px;

  margin: 0 auto 18px;

  border: 4px solid #e2e8f0;

  border-top-color: #2563eb;

  border-radius: 50%;

  animation: contract-spin 0.85s linear infinite;

}

.contract-submit-overlay.is-success .contract-submit-spinner {

  animation: none;

  border: none;

  background: #dcfce7;

  color: #15803d;

  font-size: 28px;

  font-weight: 700;

  line-height: 48px;

}

.contract-submit-overlay.is-success .contract-submit-track { display: none; }

.contract-submit-overlay.is-success .contract-submit-hint { display: none; }

@keyframes contract-spin { to { transform: rotate(360deg); } }

.contract-submit-title { font-weight: 700; font-size: 17px; margin: 0 0 8px; color: #0f172a; }

.contract-submit-hint { font-size: 13px; color: #64748b; margin: 0 0 16px; line-height: 1.45; }

.contract-submit-track {

  width: 100%;

  height: 10px;

  background: #e2e8f0;

  border-radius: 999px;

  overflow: hidden;

}

.contract-submit-bar {

  width: 0%;

  height: 100%;

  background: linear-gradient(90deg, #2563eb, #4f46e5);

  border-radius: 999px;

  transition: width 0.35s ease;

}

.contract-submit-status { font-size: 13px; color: #475569; margin-top: 12px; line-height: 1.45; }

.contract-submit-overlay.is-success .contract-submit-status { color: #15803d; font-weight: 600; }

</style>

<div id="contractSubmitOverlay" class="contract-submit-overlay hidden" aria-live="polite" aria-busy="true">

  <div class="contract-submit-box">

    <div class="contract-submit-spinner" id="contractSubmitSpinner" aria-hidden="true"></div>

    <p class="contract-submit-title" id="contractSubmitTitle">Submitting your signature</p>

    <p class="contract-submit-hint" id="contractSubmitHint">Generating your signed contract PDF.<br>Please do not close or refresh this page.</p>

    <div class="contract-submit-track">

      <div id="contractSubmitBar" class="contract-submit-bar"></div>

    </div>

    <p id="contractSubmitStatus" class="contract-submit-status">Starting…</p>

  </div>

</div>

<script>

window.ContractSigningUI = (function () {

  let timer = null;

  let reloadTimer = null;

  const overlay = () => document.getElementById('contractSubmitOverlay');

  const bar = () => document.getElementById('contractSubmitBar');

  const status = () => document.getElementById('contractSubmitStatus');

  const spinner = () => document.getElementById('contractSubmitSpinner');

  const title = () => document.getElementById('contractSubmitTitle');



  function resetVisual() {

    const o = overlay();

    const sp = spinner();

    if (o) o.classList.remove('is-success');

    if (sp) sp.textContent = '';

    const t = title();

    if (t) t.textContent = 'Submitting your signature';

    const b = bar();

    if (b) {

      b.style.width = '0%';

      b.style.background = '';

    }

  }



  function start(opts) {

    clearTimeout(reloadTimer);

    resetVisual();

    const o = overlay();

    const b = bar();

    const s = status();

    if (!o || !b) return;

    if (opts && opts.submitBtn) opts.submitBtn.disabled = true;

    o.classList.remove('hidden');

    o.setAttribute('aria-busy', 'true');

    b.style.width = '0%';

    if (s) s.textContent = (opts && opts.message) || 'Securing your signature…';

    let value = 0;

    clearInterval(timer);

    timer = setInterval(() => {

      if (value < 92) {

        value += Math.random() * 7;

        b.style.width = Math.min(value, 92) + '%';

      }

    }, 280);

  }



  function setMessage(msg) {

    const s = status();

    if (s) s.textContent = msg;

  }



  function finish(opts) {

    clearInterval(timer);

    const o = overlay();

    const b = bar();

    const sp = spinner();

    const t = title();

    const msg = (opts && opts.message) || 'Your contract was signed successfully.';

    if (o) {

      o.classList.add('is-success');

      o.setAttribute('aria-busy', 'false');

    }

    if (b) b.style.width = '100%';

    if (sp) sp.textContent = '\u2713';

    if (t) t.textContent = 'Success';

    setMessage(msg);

  }



  function finishAndReload(message, delayMs) {

    const wait = typeof delayMs === 'number' ? delayMs : 2800;

    finish({ message: message || 'Your contract was signed successfully.' });

    clearTimeout(reloadTimer);

    reloadTimer = setTimeout(() => {

      window.location.reload();

    }, wait);

  }



  function hide(opts) {

    clearInterval(timer);

    clearTimeout(reloadTimer);

    const o = overlay();

    if (o) {

      o.classList.add('hidden');

      o.setAttribute('aria-busy', 'false');

    }

    resetVisual();

    if (opts && opts.submitBtn) opts.submitBtn.disabled = false;

  }



  return { start, setMessage, finish, finishAndReload, hide };

})();

</script>

