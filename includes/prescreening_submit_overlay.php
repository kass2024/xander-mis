<?php
/** Pre-screening submit overlay — include once on student/admin form pages. */
?>
<style>
.ps-submit-overlay {
  position: fixed; inset: 0; z-index: 99999;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
}
.ps-submit-overlay.hidden { display: none; }
.ps-submit-box {
  background: #fff; border-radius: 16px; padding: 28px 24px;
  width: 92%; max-width: 360px; text-align: center;
  box-shadow: 0 20px 50px rgba(0,0,0,.2);
}
.ps-submit-spinner {
  width: 44px; height: 44px; margin: 0 auto 14px;
  border: 4px solid #e2e8f0; border-top-color: #2563eb;
  border-radius: 50%; animation: ps-spin .8s linear infinite;
}
.ps-submit-overlay.is-success .ps-submit-spinner {
  animation: none; border: none; background: #dcfce7; color: #15803d;
  font-size: 26px; font-weight: 700; line-height: 44px;
}
@keyframes ps-spin { to { transform: rotate(360deg); } }
.ps-doc-status.uploading { color: #2563eb !important; }
.ps-doc-status.uploading .prescreen-doc-status-idle::before { content: ''; }
</style>
<div id="psSubmitOverlay" class="ps-submit-overlay hidden" aria-live="polite">
  <div class="ps-submit-box">
    <div id="psSubmitSpinner" class="ps-submit-spinner" aria-hidden="true"></div>
    <p id="psSubmitTitle" style="font-weight:700;margin:0 0 6px">Submitting</p>
    <p id="psSubmitMsg" class="small text-muted mb-0">Saving your answers…</p>
  </div>
</div>
<script>
window.PrescreenSubmitUI = (function () {
  const overlay = () => document.getElementById('psSubmitOverlay');
  const spinner = () => document.getElementById('psSubmitSpinner');
  const title = () => document.getElementById('psSubmitTitle');
  const msg = () => document.getElementById('psSubmitMsg');

  function start(message) {
    const o = overlay();
    if (!o) return;
    o.classList.remove('hidden', 'is-success');
    if (spinner()) spinner().textContent = '';
    if (title()) title().textContent = 'Submitting';
    if (msg()) msg().textContent = message || 'Saving your answers…';
  }

  function success(message, reloadMs) {
    const o = overlay();
    if (!o) return;
    o.classList.add('is-success');
    if (spinner()) spinner().textContent = '\u2713';
    if (title()) title().textContent = 'Thank you';
    if (msg()) msg().textContent = message || 'Pre-screening submitted successfully.';
    setTimeout(() => location.reload(), reloadMs || 2800);
  }

  function error(message) {
    const o = overlay();
    if (o) o.classList.add('hidden');
    alert(message || 'Submission failed.');
  }

  function hide() {
    const o = overlay();
    if (o) o.classList.add('hidden');
  }

  return { start, success, error, hide };
})();
</script>
