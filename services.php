<?php
require_once __DIR__ . '/site_session_bootstrap.php';
$current_lang = $_SESSION['current_language'] ?? 'en';
require __DIR__ . '/includes/services_catalog.php';
require_once __DIR__ . '/helpers/service_apply_links.php';
$pageTitle = st('page_title');
require_once __DIR__ . '/header.php';

$direct_card = isset($_GET['card']) ? preg_replace('/[^a-z0-9_-]/', '', (string) $_GET['card']) : '';
$valid_ids = xander_service_catalog_ids();
if ($direct_card !== '' && !in_array($direct_card, $valid_ids, true)) {
    $direct_card = '';
}
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ==========================================================
   XGS SERVICES PAGE — 2026 MODERN UI
   Brand-aligned (navy + gold), production-ready
========================================================== */
.svc-page {
  --svc-navy: #012F6B;
  --svc-navy-dark: #001A3D;
  --svc-blue: #254D81;
  --svc-orange: #F2A65A;
  --svc-orange-dark: #E6892E;
  --svc-orange-light: #FBC58A;
  --svc-ink: #0F172A;
  --svc-muted: #64748b;
  --svc-soft: #f8fafc;
  --svc-border: #e2e8f0;

  max-width: 1180px;
  margin: 0 auto;
  padding: 3rem 1.25rem 5rem;
  position: relative;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
  -webkit-font-smoothing: antialiased;
}

/* ===== AMBIENT BACKGROUND ===== */
body {
  background:
    radial-gradient(900px 380px at 100% -10%, rgba(242, 166, 90, 0.10), transparent 60%),
    radial-gradient(900px 380px at -10% 110%, rgba(1, 47, 107, 0.10), transparent 60%),
    #f1f5f9;
}

/* ===== HERO ===== */
.svc-page-hero {
  text-align: center;
  margin: 0 auto 2.5rem;
  max-width: 760px;
  position: relative;
  padding: 1rem 0 0.5rem;
}

.svc-page-hero::before {
  content: 'OUR SERVICES';
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.14em;
  color: var(--svc-orange-dark);
  background: linear-gradient(135deg, rgba(242, 166, 90, 0.16), rgba(242, 166, 90, 0.06));
  border: 1px solid rgba(242, 166, 90, 0.28);
  padding: 0.4rem 1rem;
  border-radius: 999px;
  margin-bottom: 1.25rem;
  box-shadow: 0 4px 14px rgba(242, 166, 90, 0.10);
}

.svc-page-hero h1 {
  font-size: clamp(2rem, 5vw, 3rem);
  color: var(--svc-navy);
  font-weight: 900;
  margin: 0 0 0.85rem;
  line-height: 1.12;
  letter-spacing: -0.025em;
  background: linear-gradient(120deg, var(--svc-navy) 0%, var(--svc-blue) 45%, var(--svc-orange) 100%);
  background-size: 200% auto;
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  animation: svcTitleShine 8s linear infinite;
}

@keyframes svcTitleShine {
  0%   { background-position: 0% center; }
  100% { background-position: 200% center; }
}

.svc-page-hero p {
  color: var(--svc-muted);
  font-size: clamp(1rem, 2vw, 1.1rem);
  max-width: 640px;
  margin: 0 auto;
  line-height: 1.65;
}

/* ===== DIRECT BANNER ===== */
.svc-direct-banner {
  background:
    radial-gradient(420px 160px at 100% 0%, rgba(242, 166, 90, 0.14), transparent 60%),
    linear-gradient(135deg, #ffffff 0%, #fef3e5 100%);
  border: 1px solid rgba(242, 166, 90, 0.28);
  border-radius: 18px;
  padding: 1.25rem 1.5rem;
  margin-bottom: 2rem;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.85rem;
  box-shadow: 0 12px 32px rgba(1, 47, 107, 0.08);
  position: relative;
  overflow: hidden;
}

.svc-direct-banner::before {
  content: '';
  position: absolute;
  top: 0; left: 0; bottom: 0;
  width: 4px;
  background: linear-gradient(180deg, var(--svc-orange), var(--svc-orange-dark));
}

.svc-direct-banner h2 {
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--svc-navy);
  margin: 0 0 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.svc-direct-banner h2::before {
  content: '\f0a1';
  font-family: 'Font Awesome 6 Free';
  font-weight: 900;
  color: var(--svc-orange);
  font-size: 0.9rem;
}

.svc-direct-banner p {
  margin: 0;
  font-size: 0.92rem;
  color: var(--svc-muted);
  line-height: 1.5;
}

.svc-direct-banner a {
  color: var(--svc-navy);
  font-weight: 700;
  text-decoration: none;
  white-space: nowrap;
  padding: 0.55rem 1.1rem;
  background: #fff;
  border: 1.5px solid var(--svc-navy);
  border-radius: 10px;
  font-size: 0.88rem;
  transition: all 0.22s cubic-bezier(0.22, 1, 0.36, 1);
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.svc-direct-banner a::before {
  content: '\f060';
  font-family: 'Font Awesome 6 Free';
  font-weight: 900;
  font-size: 0.78rem;
}

.svc-direct-banner a:hover {
  background: var(--svc-navy);
  color: #fff;
  transform: translateX(-2px);
  box-shadow: 0 8px 20px rgba(1, 47, 107, 0.20);
}

/* ===== GRID ===== */
.svc-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
  gap: 1.5rem;
}

/* ===== SERVICE CARD — premium ===== */
.svc-card {
  background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
  border-radius: 24px;
  border: 1px solid #eef2f7;
  box-shadow: 0 10px 32px rgba(1, 47, 107, 0.08);
  padding: 2rem 1.8rem 1.8rem;
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
  position: relative;
  overflow: hidden;
  transition:
    transform 0.32s cubic-bezier(0.22, 1, 0.36, 1),
    box-shadow 0.32s cubic-bezier(0.22, 1, 0.36, 1),
    border-color 0.32s ease;
  min-height: 420px;
}

/* Top accent line that animates in on hover */
.svc-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--svc-navy), var(--svc-orange));
  transform: scaleX(0);
  transform-origin: left center;
  transition: transform 0.4s cubic-bezier(0.22, 1, 0.36, 1);
}

/* Soft radial glow on hover */
.svc-card::after {
  content: '';
  position: absolute;
  inset: -1px;
  border-radius: 20px;
  background: radial-gradient(380px 200px at 100% 0%, rgba(242, 166, 90, 0.10), transparent 65%);
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none;
}

.svc-card:hover {
  transform: translateY(-8px);
  border-color: rgba(242, 166, 90, 0.35);
  box-shadow: 0 28px 70px rgba(1, 47, 107, 0.18);
}

.svc-card:hover::before { transform: scaleX(1); }
.svc-card:hover::after { opacity: 1; }

.svc-card.is-highlighted {
  border-color: var(--svc-orange);
  box-shadow:
    0 0 0 3px rgba(242, 166, 90, 0.18),
    0 16px 48px rgba(1, 47, 107, 0.12);
  animation: svcHighlight 1.8s ease-in-out;
}

.svc-card.is-highlighted::before { transform: scaleX(1); }

@keyframes svcHighlight {
  0%   { box-shadow: 0 0 0 0 rgba(242, 166, 90, 0.45); }
  60%  { box-shadow: 0 0 0 14px rgba(242, 166, 90, 0); }
  100% { box-shadow: 0 0 0 3px rgba(242, 166, 90, 0.18), 0 16px 48px rgba(1, 47, 107, 0.12); }
}

/* ===== CARD HEAD ===== */
.svc-card-head {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
  position: relative;
  z-index: 1;
}

.svc-card-icon {
  width: 64px;
  height: 64px;
  border-radius: 18px;
  background: linear-gradient(135deg, var(--svc-navy) 0%, var(--svc-blue) 70%, var(--svc-orange) 200%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  flex-shrink: 0;
  color: #fff;
  box-shadow:
    0 12px 26px rgba(1, 47, 107, 0.35),
    inset 0 0 0 1px rgba(255, 255, 255, 0.18);
  transition: transform 0.32s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.32s ease;
}

.svc-card:hover .svc-card-icon {
  transform: scale(1.08) rotate(-5deg);
  box-shadow:
    0 16px 34px rgba(1, 47, 107, 0.45),
    0 0 0 8px rgba(242, 166, 90, 0.12);
}

.svc-card-title {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--svc-navy);
  margin: 0;
  line-height: 1.25;
  letter-spacing: -0.01em;
}

.svc-card-tagline {
  font-size: 0.92rem;
  color: var(--svc-orange-dark);
  margin: 0.35rem 0 0;
  line-height: 1.45;
  font-weight: 600;
}

/* ===== CARD DESCRIPTION ===== */
.svc-card-description {
  font-size: 0.92rem;
  color: var(--svc-muted);
  line-height: 1.6;
  margin: 0;
  position: relative;
  z-index: 1;
}

/* ===== FEATURES LIST ===== */
.svc-card-features {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  margin: 0.5rem 0;
  position: relative;
  z-index: 1;
}

.svc-feature-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 0.88rem;
  color: #475569;
  font-weight: 500;
  padding: 0.5rem 0.8rem;
  background: linear-gradient(180deg, #f8fafc, #fbfcfe);
  border-radius: 10px;
  border: 1px solid #eef2f7;
  transition: all 0.2s ease;
}

.svc-feature-item:hover {
  background: linear-gradient(180deg, #f1f5f9, #f8fafc);
  border-color: rgba(242, 166, 90, 0.2);
  transform: translateX(4px);
}

.svc-feature-item i {
  color: #fff;
  font-size: 0.65rem;
  width: 20px;
  height: 20px;
  background: linear-gradient(135deg, var(--svc-orange), var(--svc-orange-dark));
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 3px 8px rgba(242, 166, 90, 0.25);
}

/* ===== HIGHLIGHT LINE ===== */
.svc-card-highlight {
  font-size: 0.95rem;
  color: #475569;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.85rem 1rem;
  background: linear-gradient(180deg, #f8fafc, #fbfcfe);
  border-radius: 12px;
  border: 1px solid #eef2f7;
  font-weight: 600;
  position: relative;
  z-index: 1;
}

.svc-card-highlight i {
  color: #fff;
  font-size: 0.75rem;
  width: 24px;
  height: 24px;
  background: linear-gradient(135deg, var(--svc-orange), var(--svc-orange-dark));
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(242, 166, 90, 0.35);
}

/* ===== ACTIONS ===== */
.svc-card-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.55rem;
  margin-top: auto;
  padding-top: 0.5rem;
  position: relative;
  z-index: 1;
}

.svc-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.7rem 1.2rem;
  border-radius: 12px;
  font-size: 0.86rem;
  font-weight: 700;
  cursor: pointer;
  text-decoration: none;
  border: none;
  font-family: inherit;
  transition: all 0.25s cubic-bezier(0.22, 1, 0.36, 1);
  position: relative;
  overflow: hidden;
}

.svc-btn::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.3);
  transform: translate(-50%, -50%);
  transition: width 0.6s ease, height 0.6s ease;
}

.svc-btn:active::before {
  width: 300px;
  height: 300px;
}

.svc-btn-primary {
  background: linear-gradient(135deg, var(--svc-navy) 0%, var(--svc-blue) 100%);
  color: #fff;
  box-shadow: 0 8px 20px rgba(1, 47, 107, 0.28);
  flex: 1;
  justify-content: center;
  transform: translateY(0);
}

.svc-btn-primary::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(120deg, transparent 30%, rgba(255,255,255,0.25) 50%, transparent 70%);
  transform: translateX(-100%);
  transition: transform 0.6s cubic-bezier(0.22, 1, 0.36, 1);
}

.svc-btn-primary:hover {
  background: linear-gradient(135deg, var(--svc-navy-dark) 0%, var(--svc-navy) 100%);
  color: #fff;
  transform: translateY(-3px);
  box-shadow: 0 16px 35px rgba(1, 47, 107, 0.40);
}

.svc-btn-primary:hover::after { transform: translateX(100%); }

.svc-btn-primary i {
  transition: transform 0.25s ease;
}

.svc-btn-primary:hover i {
  transform: translateX(4px) rotate(-10deg);
}

.svc-btn-outline {
  background: #fff;
  color: var(--svc-navy);
  border: 1.5px solid #cbd5e1;
  padding: 0.7rem 1rem;
  transform: translateY(0);
}

.svc-btn-outline:hover {
  background: var(--svc-navy);
  color: #fff;
  border-color: var(--svc-navy);
  transform: translateY(-3px);
  box-shadow: 0 10px 25px rgba(1, 47, 107, 0.20);
}

/* ===== TOAST ===== */
.svc-toast {
  position: fixed;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%) translateY(80px);
  background: linear-gradient(135deg, #16a34a, #22c55e);
  color: #fff;
  padding: 0.85rem 1.5rem;
  border-radius: 12px;
  font-size: 0.92rem;
  font-weight: 600;
  opacity: 0;
  transition: transform 0.32s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.32s ease;
  z-index: 9999;
  pointer-events: none;
  box-shadow: 0 16px 40px rgba(22, 163, 74, 0.35);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.svc-toast::before {
  content: '\f00c';
  font-family: 'Font Awesome 6 Free';
  font-weight: 900;
  font-size: 0.85rem;
}

.svc-toast.is-visible {
  transform: translateX(-50%) translateY(0);
  opacity: 1;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
  .svc-page { 
    padding: 2rem 1rem 4rem; 
  }
  
  .svc-grid { 
    grid-template-columns: 1fr; 
    gap: 1.25rem;
  }
  
  .svc-card { 
    padding: 1.5rem 1.2rem 1.3rem; 
    min-height: auto;
  }
  
  .svc-card-icon { 
    width: 56px; 
    height: 56px; 
    font-size: 1.6rem; 
  }
  
  .svc-card-title {
    font-size: 1.15rem;
  }
  
  .svc-card-tagline {
    font-size: 0.88rem;
  }
  
  .svc-card-description {
    font-size: 0.9rem;
  }
  
  .svc-feature-item {
    font-size: 0.85rem;
    padding: 0.45rem 0.7rem;
  }
  
  .svc-card-highlight {
    font-size: 0.9rem;
    padding: 0.75rem 0.9rem;
  }
  
  .svc-btn {
    padding: 0.65rem 1rem;
    font-size: 0.84rem;
  }
  
  .svc-direct-banner { 
    flex-direction: column; 
    align-items: flex-start; 
    padding: 1rem 1.2rem;
  }
  
  .svc-direct-banner a { 
    width: 100%; 
    justify-content: center; 
  }
}

@media (max-width: 480px) {
  .svc-page {
    padding: 1.5rem 0.75rem 3rem;
  }
  
  .svc-card {
    padding: 1.3rem 1rem 1.1rem;
    border-radius: 20px;
  }
  
  .svc-card-icon {
    width: 48px;
    height: 48px;
    font-size: 1.4rem;
  }
  
  .svc-card-title {
    font-size: 1.1rem;
  }
  
  .svc-card-tagline {
    font-size: 0.85rem;
  }
  
  .svc-card-description {
    font-size: 0.88rem;
  }
  
  .svc-feature-item {
    font-size: 0.82rem;
    padding: 0.4rem 0.6rem;
    gap: 0.5rem;
  }
  
  .svc-feature-item i {
    width: 18px;
    height: 18px;
    font-size: 0.6rem;
  }
  
  .svc-card-highlight {
    font-size: 0.85rem;
    padding: 0.7rem 0.8rem;
    gap: 0.5rem;
  }
  
  .svc-card-highlight i {
    width: 20px;
    height: 20px;
    font-size: 0.7rem;
  }
  
  .svc-btn {
    padding: 0.6rem 0.9rem;
    font-size: 0.82rem;
  }
  
  .svc-card-actions {
    gap: 0.5rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  .svc-card, .svc-btn, .svc-card-icon, .svc-toast,
  .svc-page-hero h1 { animation: none !important; transition: none !important; }
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
      
      <?php if (!empty(st($card['description_key']))): ?>
      <p class="svc-card-description"><?php echo htmlspecialchars(st($card['description_key']), ENT_QUOTES, 'UTF-8'); ?></p>
      <?php endif; ?>
      
      <div class="svc-card-features">
        <?php foreach ($card['point_keys'] as $pointKey): ?>
          <?php if (!empty(st($pointKey))): ?>
            <div class="svc-feature-item">
              <i class="fas fa-check" aria-hidden="true"></i>
              <span><?php echo htmlspecialchars(st($pointKey), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      
      <p class="svc-card-highlight">
        <i class="fas fa-star" aria-hidden="true"></i>
        <?php echo htmlspecialchars(st($card['highlight_key']), ENT_QUOTES, 'UTF-8'); ?>
      </p>
      <div class="svc-card-actions">
        <?php
        $applyHref = xander_service_apply_url($card['id'], $card['form'], $current_lang ?: null, true);
        $copyHref = xander_service_copy_url($card['id'], $card['form'], $current_lang ?: null);
        ?>
        <a class="svc-btn svc-btn-primary" href="<?php echo htmlspecialchars($applyHref, ENT_QUOTES, 'UTF-8'); ?>">
          <i class="fas fa-paper-plane" aria-hidden="true"></i>
          <?php echo htmlspecialchars(st('card_apply'), ENT_QUOTES, 'UTF-8'); ?>
        </a>
        <button type="button" class="svc-btn svc-btn-outline svc-copy-link" data-copy-url="<?php echo htmlspecialchars($copyHref, ENT_QUOTES, 'UTF-8'); ?>">
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
      const url = btn.getAttribute('data-copy-url') || window.location.href;
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
