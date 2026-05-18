<?php
declare(strict_types=1);

/**
 * Homepage: institution dashboard scholarships & loans (banner cards), or Xander promos if empty.
 */

if (!isset($conn) || !($conn instanceof mysqli)) {
    return;
}

require_once dirname(__DIR__) . '/helpers/institution_dashboard.php';
require_once dirname(__DIR__) . '/helpers/urls.php';

$langQs = !empty($current_lang) ? '?lang=' . rawurlencode((string) $current_lang) : '';

$highlights = xander_homepage_institution_highlights($conn, 12);
$showPromo = $highlights === [];

$t = static function (string $key, string $fallback): string {
    if (function_exists('it')) {
        $v = it($key);
        if ($v !== $key && $v !== '') {
            return $v;
        }
    }
    return $fallback;
};

$promoBanners = [
    [
        'icon' => 'fa-rocket',
        'accent' => 'promo-a',
        'title' => $t('promo_apply_title', 'Start your study abroad application'),
        'desc' => $t('promo_apply_desc', 'AI-assisted admissions with expert review — universities, visas, and documents in one flow.'),
        'cta' => $t('promo_apply_cta', 'Apply now'),
        'url' => 'student-application.php' . $langQs,
        'uni' => '',
        'meta' => '',
    ],
    [
        'icon' => 'fa-hand-holding-dollar',
        'accent' => 'promo-b',
        'title' => $t('promo_loan_title', 'Education loans & scholarships'),
        'desc' => $t('promo_loan_desc', 'Explore funding up to 90% and loan partners for Canada, USA, UK, and more.'),
        'cta' => $t('promo_loan_cta', 'View funding options'),
        'url' => 'loan-providers.php' . $langQs,
        'uni' => '',
        'meta' => '',
    ],
    [
        'icon' => 'fa-calendar-check',
        'accent' => 'promo-c',
        'title' => $t('promo_consult_title', 'Free expert consultation'),
        'desc' => $t('promo_consult_desc', 'Book a no-cost session with our advisors to plan your international journey.'),
        'cta' => $t('promo_consult_cta', 'Book consultation'),
        'url' => 'contact.php' . $langQs,
        'uni' => '',
        'meta' => '',
    ],
];

$sectionTitle = $showPromo
    ? $t('promo_section_title', 'Plan your journey with Xander')
    : $t('inst_opp_title', 'Opportunities from our partner institutions');
$sectionDesc = $showPromo
    ? $t('promo_section_desc', 'Apply, fund your studies, or speak with an advisor — we guide you end to end.')
    : $t('inst_opp_desc', 'Scholarships and education loans published by institutions on Xander Global Scholars.');
?>
<style>
.hp-opp {
  padding: 4.5rem 1.25rem;
  background: linear-gradient(180deg, #f8fafc 0%, #fff 55%, #fef9f3 100%);
}
.hp-opp__wrap { max-width: 1180px; margin: 0 auto; }
.hp-opp__head { text-align: center; max-width: 720px; margin: 0 auto 2rem; }
.hp-opp__head h2 {
  font-size: clamp(1.6rem, 3.5vw, 2.1rem);
  font-weight: 800;
  color: #0a1f44;
  margin: 0 0 .5rem;
}
.hp-opp__head p { color: #64748b; margin: 0; line-height: 1.65; }
.hp-opp__badge {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  font-size: .75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: #0d9488;
  background: rgba(13, 148, 136, .1);
  padding: .3rem .75rem;
  border-radius: 999px;
  margin-bottom: .75rem;
}
.hp-promo-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.15rem;
}
.hp-promo-banner {
  border-radius: 18px;
  padding: 1.65rem 1.45rem;
  color: #fff;
  min-height: 220px;
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
  box-shadow: 0 14px 36px rgba(10, 31, 68, .16);
  transition: transform .25s ease, box-shadow .25s ease;
}
.hp-promo-banner:hover {
  transform: translateY(-6px);
  box-shadow: 0 22px 48px rgba(10, 31, 68, .22);
}
.hp-promo-banner::after {
  content: '';
  position: absolute;
  width: 200px;
  height: 200px;
  border-radius: 50%;
  background: rgba(255,255,255,.09);
  right: -50px;
  top: -50px;
}
.hp-promo-banner.promo-a { background: linear-gradient(135deg, #0a1f44, #1e4a8c); }
.hp-promo-banner.promo-b { background: linear-gradient(135deg, #b45309, #e87722); }
.hp-promo-banner.promo-c { background: linear-gradient(135deg, #0d9488, #0369a1); }
.hp-promo-banner i.fa.icon-main {
  font-size: 1.6rem;
  margin-bottom: .65rem;
  opacity: .95;
  position: relative;
  z-index: 1;
}
.hp-promo-uni {
  font-size: .72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .04em;
  opacity: .85;
  position: relative;
  z-index: 1;
}
.hp-promo-banner h4 {
  font-size: 1.12rem;
  font-weight: 800;
  margin: .35rem 0 .45rem;
  line-height: 1.3;
  position: relative;
  z-index: 1;
}
.hp-promo-banner p.desc {
  font-size: .88rem;
  opacity: .93;
  flex: 1;
  margin: 0 0 .65rem;
  line-height: 1.55;
  position: relative;
  z-index: 1;
}
.hp-promo-meta {
  font-size: .78rem;
  opacity: .88;
  margin-bottom: .85rem;
  position: relative;
  z-index: 1;
}
.hp-promo-banner a {
  align-self: flex-start;
  background: #fff;
  color: #0a1f44;
  font-weight: 700;
  padding: .5rem 1rem;
  border-radius: 999px;
  text-decoration: none;
  font-size: .85rem;
  position: relative;
  z-index: 1;
  transition: transform .2s;
}
.hp-promo-banner a:hover { transform: scale(1.03); color: #0a1f44; }
.hp-promo-banner.promo-b a { color: #b45309; }
.hp-promo-banner.promo-c a { color: #0d9488; }
.hp-opp__cta-row { text-align: center; margin-top: 1.75rem; }
.hp-opp__cta-row a {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .65rem 1.25rem;
  border-radius: 999px;
  background: #0a1f44;
  color: #fff;
  font-weight: 700;
  text-decoration: none;
  font-size: .9rem;
}
.hp-opp__cta-row a:hover { background: #1e4a8c; color: #fff; }
</style>

<section class="hp-opp" id="opportunities">
  <div class="hp-opp__wrap">
    <header class="hp-opp__head fade-in">
      <?php if (!$showPromo): ?>
      <span class="hp-opp__badge"><i class="fas fa-building-columns"></i> <?= htmlspecialchars($t('inst_opp_badge', 'From institution dashboard'), ENT_QUOTES, 'UTF-8') ?></span>
      <?php endif; ?>
      <h2><?= htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8') ?></h2>
      <p><?= htmlspecialchars($sectionDesc, ENT_QUOTES, 'UTF-8') ?></p>
    </header>

    <div class="hp-promo-grid">
      <?php
      $cards = $showPromo ? $promoBanners : $highlights;
      foreach ($cards as $card):
          $accent = (string) ($card['accent'] ?? 'promo-a');
          $ext = !empty($card['url']) && str_starts_with((string) $card['url'], 'http');
      ?>
      <article class="hp-promo-banner <?= htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') ?> fade-in">
        <i class="fas <?= htmlspecialchars((string) ($card['icon'] ?? 'fa-star'), ENT_QUOTES, 'UTF-8') ?> icon-main"></i>
        <?php if (!empty($card['uni'])): ?>
        <span class="hp-promo-uni"><?= htmlspecialchars((string) $card['uni']) ?><?php if (!empty($card['country'])): ?> · <?= htmlspecialchars((string) $card['country']) ?><?php endif; ?></span>
        <?php endif; ?>
        <h4><?= htmlspecialchars((string) ($card['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h4>
        <p class="desc"><?= htmlspecialchars((string) ($card['desc'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        <?php if (!empty($card['meta'])): ?>
        <p class="hp-promo-meta"><?= htmlspecialchars((string) $card['meta'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <a href="<?= htmlspecialchars((string) ($card['url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"<?= $ext ? ' target="_blank" rel="noopener"' : '' ?>>
          <?= htmlspecialchars((string) ($card['cta'] ?? 'Learn more'), ENT_QUOTES, 'UTF-8') ?> →
        </a>
      </article>
      <?php endforeach; ?>
    </div>

    <?php if (!$showPromo): ?>
    <div class="hp-opp__cta-row fade-in">
      <a href="services.php<?= htmlspecialchars($langQs, ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($t('insights_cta_all', 'Explore all services'), ENT_QUOTES, 'UTF-8') ?>
        <i class="fas fa-arrow-right"></i>
      </a>
    </div>
    <?php endif; ?>
  </div>
</section>
