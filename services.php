<?php
require_once __DIR__ . '/site_session_bootstrap.php';
$current_lang = $_SESSION['current_language'] ?? 'en';
require __DIR__ . '/includes/services_catalog.php';
$pageTitle = st('page_title');
require_once __DIR__ . '/header.php';

$direct_card = isset($_GET['card']) ? preg_replace('/[^a-z0-9_-]/', '', (string) $_GET['card']) : '';
$valid_ids = xander_service_catalog_ids();
if ($direct_card !== '' && !in_array($direct_card, $valid_ids, true)) {
    $direct_card = '';
}
?>
<style>
.svc-page {
  --svc-navy: #0a1f44;
  --svc-orange: #f97316;
  --svc-muted: #64748b;
  max-width: 1100px;
  margin: 0 auto;
  padding: 2rem 1.25rem 4rem;
}
.svc-page-hero {
  text-align: center;
  margin-bottom: 2rem;
}
.svc-page-hero h1 {
  font-size: clamp(1.75rem, 4vw, 2.25rem);
  color: var(--svc-navy);
  font-weight: 800;
  margin: 0 0 0.5rem;
}
.svc-page-hero p {
  color: var(--svc-muted);
  font-size: 1.05rem;
  max-width: 640px;
  margin: 0 auto;
  line-height: 1.55;
}
.svc-direct-banner {
  background: linear-gradient(135deg, #eff6ff, #fff7ed);
  border: 1px solid #dbeafe;
  border-radius: 12px;
  padding: 1rem 1.25rem;
  margin-bottom: 1.5rem;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}
.svc-direct-banner h2 {
  font-size: 1rem;
  color: var(--svc-navy);
  margin: 0 0 0.25rem;
}
.svc-direct-banner p {
  margin: 0;
  font-size: 0.9rem;
  color: var(--svc-muted);
}
.svc-direct-banner a {
  color: var(--svc-navy);
  font-weight: 600;
  text-decoration: none;
  white-space: nowrap;
}
.svc-direct-banner a:hover { text-decoration: underline; }
.svc-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
}
.svc-card {
  background: #fff;
  border-radius: 12px;
  border-left: 4px solid var(--svc-navy);
  box-shadow: 0 4px 16px rgba(10, 31, 68, 0.08);
  padding: 1.1rem 1.15rem;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  transition: box-shadow 0.2s, transform 0.2s;
}
.svc-card:hover {
  box-shadow: 0 8px 24px rgba(10, 31, 68, 0.12);
  transform: translateY(-2px);
}
.svc-card.is-highlighted {
  outline: 2px solid var(--svc-orange);
  outline-offset: 2px;
}
.svc-card-head {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}
.svc-card-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: var(--svc-navy);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}
.svc-card-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--svc-navy);
  margin: 0;
  line-height: 1.3;
}
.svc-card-tagline {
  font-size: 0.85rem;
  color: var(--svc-orange);
  margin: 0.2rem 0 0;
  line-height: 1.35;
}
.svc-card-highlight {
  font-size: 0.88rem;
  color: var(--svc-muted);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}
.svc-card-highlight i {
  color: var(--svc-orange);
  font-size: 0.75rem;
}
.svc-card-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: auto;
  padding-top: 0.35rem;
}
.svc-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.45rem 0.85rem;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  border: none;
  font-family: inherit;
}
.svc-btn-primary {
  background: var(--svc-navy);
  color: #fff;
}
.svc-btn-primary:hover { background: #152a5c; color: #fff; }
.svc-btn-outline {
  background: #fff;
  color: var(--svc-navy);
  border: 1.5px solid var(--svc-navy);
}
.svc-btn-outline:hover { background: #f8fafc; }
.svc-toast {
  position: fixed;
  bottom: 1.5rem;
  left: 50%;
  transform: translateX(-50%) translateY(80px);
  background: var(--svc-navy);
  color: #fff;
  padding: 0.65rem 1.25rem;
  border-radius: 8px;
  font-size: 0.9rem;
  opacity: 0;
  transition: transform 0.25s, opacity 0.25s;
  z-index: 9999;
  pointer-events: none;
}
.svc-toast.is-visible {
  transform: translateX(-50%) translateY(0);
  opacity: 1;
}
</style>

<main class="svc-page">
  <div class="svc-page-hero">
    <h1><?php echo htmlspecialchars(st('services_title'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p><?php echo htmlspecialchars(st('services_description'), ENT_QUOTES, 'UTF-8'); ?></p>
  </div>

  <?php if ($direct_card !== ''): ?>
  <div class="svc-direct-banner" id="svc-direct-banner">
    <div>
      <h2><?php echo htmlspecialchars(st('direct_header_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(st('direct_header_text'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <a href="services.php<?php echo !empty($current_lang) ? '?lang=' . rawurlencode((string) $current_lang) : ''; ?>"><?php echo htmlspecialchars(st('back_to_all'), ENT_QUOTES, 'UTF-8'); ?></a>
  </div>
  <?php endif; ?>

  <div class="svc-grid" id="services-grid">
    <?php foreach ($service_catalog_cards as $card): ?>
    <article
      class="svc-card<?php echo ($direct_card === $card['id']) ? ' is-highlighted' : ''; ?>"
      id="service-<?php echo htmlspecialchars($card['id'], ENT_QUOTES, 'UTF-8'); ?>"
      data-card-id="<?php echo htmlspecialchars($card['id'], ENT_QUOTES, 'UTF-8'); ?>"
    >
      <div class="svc-card-head">
        <div class="svc-card-icon" aria-hidden="true"><?php echo $card['icon']; ?></div>
        <div>
          <h2 class="svc-card-title"><?php echo htmlspecialchars(st($card['title_key']), ENT_QUOTES, 'UTF-8'); ?></h2>
          <p class="svc-card-tagline"><?php echo htmlspecialchars(st($card['subtitle_key']), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
      </div>
      <p class="svc-card-highlight">
        <i class="fas fa-check" aria-hidden="true"></i>
        <?php echo htmlspecialchars(st($card['highlight_key']), ENT_QUOTES, 'UTF-8'); ?>
      </p>
      <div class="svc-card-actions">
        <a class="svc-btn svc-btn-primary" href="<?php echo htmlspecialchars($card['form'], ENT_QUOTES, 'UTF-8'); ?>">
          <i class="fas fa-paper-plane" aria-hidden="true"></i>
          <?php echo htmlspecialchars(st('card_apply'), ENT_QUOTES, 'UTF-8'); ?>
        </a>
        <button type="button" class="svc-btn svc-btn-outline svc-copy-link" data-card="<?php echo htmlspecialchars($card['id'], ENT_QUOTES, 'UTF-8'); ?>">
          <i class="fas fa-link" aria-hidden="true"></i>
          <?php echo htmlspecialchars(st('card_copy'), ENT_QUOTES, 'UTF-8'); ?>
        </button>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</main>

<div class="svc-toast" id="svc-copy-toast" role="status" aria-live="polite"></div>

<script>
(function () {
  const toast = document.getElementById('svc-copy-toast');
  const lang = <?php echo json_encode($current_lang ?? 'en', JSON_UNESCAPED_UNICODE); ?>;

  function showToast(msg) {
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.add('is-visible');
    clearTimeout(showToast._t);
    showToast._t = setTimeout(function () { toast.classList.remove('is-visible'); }, 2200);
  }

  document.querySelectorAll('.svc-copy-link').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const card = btn.getAttribute('data-card') || '';
      let url = new URL('services.php', window.location.href);
      url.searchParams.set('card', card);
      if (lang) url.searchParams.set('lang', lang);
      url = url.href;
      const copied = <?php echo json_encode(st('card_copy') . ' ✓', JSON_UNESCAPED_UNICODE); ?>;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(function () { showToast(copied); }).catch(function () { fallbackCopy(url, copied); });
      } else {
        fallbackCopy(url, copied);
      }
    });
  });

  function fallbackCopy(text, okMsg) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand('copy');
      showToast(okMsg);
    } catch (e) {
      showToast(text);
    }
    document.body.removeChild(ta);
  }

  <?php if ($direct_card !== ''): ?>
  var el = document.getElementById('service-<?php echo htmlspecialchars($direct_card, ENT_QUOTES, 'UTF-8'); ?>');
  if (el) {
    setTimeout(function () { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 300);
  }
  <?php endif; ?>
})();
</script>

<?php include 'footer.php'; ?>
