<?php
declare(strict_types=1);

/**
 * Homepage: institution scholarships, education loans, or promotional banners.
 * Expects $conn (mysqli), optional it() and $current_lang from index.php.
 */

if (!isset($conn) || !($conn instanceof mysqli)) {
    return;
}

require_once dirname(__DIR__) . '/helpers/institution_dashboard.php';
require_once dirname(__DIR__) . '/helpers/urls.php';

$langQs = !empty($current_lang) ? '?lang=' . rawurlencode((string) $current_lang) : '';

$scholarships = xander_homepage_published_scholarships($conn, 12);
$loans = xander_homepage_published_loans($conn, 12);

$hasScholarships = $scholarships !== [];
$hasLoans = $loans !== [];
$showPromo = !$hasScholarships && !$hasLoans;

$t = static function (string $key, string $fallback) use ($langQs): string {
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
    ],
    [
        'icon' => 'fa-hand-holding-dollar',
        'accent' => 'promo-b',
        'title' => $t('promo_loan_title', 'Education loans & scholarships'),
        'desc' => $t('promo_loan_desc', 'Explore funding up to 90% and loan partners for Canada, USA, UK, and more.'),
        'cta' => $t('promo_loan_cta', 'View funding options'),
        'url' => 'loan-providers.php' . $langQs,
    ],
    [
        'icon' => 'fa-calendar-check',
        'accent' => 'promo-c',
        'title' => $t('promo_consult_title', 'Free expert consultation'),
        'desc' => $t('promo_consult_desc', 'Book a no-cost session with our advisors to plan your international journey.'),
        'cta' => $t('promo_consult_cta', 'Book consultation'),
        'url' => 'contact.php' . $langQs,
    ],
];
?>
<style>
.hp-opp {
  padding: 4.5rem 1.25rem;
  background: linear-gradient(180deg, #f8fafc 0%, #fff 55%, #fef9f3 100%);
}
.hp-opp__wrap { max-width: 1180px; margin: 0 auto; }
.hp-opp__head { text-align: center; max-width: 680px; margin: 0 auto 2rem; }
.hp-opp__head h2 {
  font-size: clamp(1.6rem, 3.5vw, 2.1rem);
  font-weight: 800;
  color: #0a1f44;
  margin: 0 0 .5rem;
}
.hp-opp__head p { color: #64748b; margin: 0; }
.hp-opp__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.25rem;
}
.hp-opp-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.35rem;
  box-shadow: 0 8px 28px rgba(10, 31, 68, .06);
  display: flex;
  flex-direction: column;
  transition: transform .2s, box-shadow .2s;
}
.hp-opp-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 40px rgba(10, 31, 68, .1);
}
.hp-opp-card--sch::before,
.hp-opp-card--loan::before {
  content: '';
  display: block;
  height: 4px;
  border-radius: 4px 4px 0 0;
  margin: -1.35rem -1.35rem 1rem;
}
.hp-opp-card--sch::before { background: linear-gradient(90deg, #0a1f44, #e87722); }
.hp-opp-card--loan::before { background: linear-gradient(90deg, #0d9488, #1e4a8c); }
.hp-opp-top {
  display: flex;
  justify-content: space-between;
  gap: .5rem;
  font-size: .78rem;
  margin-bottom: .5rem;
}
.hp-opp-uni { font-weight: 700; color: #0a1f44; }
.hp-opp-country { color: #64748b; }
.hp-opp-card h3 {
  font-size: 1.05rem;
  font-weight: 800;
  color: #0a1f44;
  margin: 0 0 .4rem;
  line-height: 1.3;
}
.hp-opp-tag { font-size: .85rem; color: #475569; margin: 0 0 .5rem; }
.hp-opp-summary { font-size: .86rem; color: #64748b; flex: 1; margin-bottom: .75rem; line-height: 1.5; }
.hp-opp-meta {
  list-style: none;
  padding: 0;
  margin: 0 0 1rem;
  font-size: .8rem;
  color: #334155;
}
.hp-opp-meta li { margin-bottom: .25rem; }
.hp-opp-meta i { color: #e87722; width: 1.1rem; }
.hp-opp-meta--loan i { color: #0d9488; }
.hp-opp-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .4rem;
  padding: .55rem 1rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: .88rem;
  text-decoration: none;
  margin-top: auto;
}
.hp-opp-btn--sch { background: #0a1f44; color: #fff; }
.hp-opp-btn--sch:hover { background: #1e4a8c; color: #fff; }
.hp-opp-btn--loan { background: #0d9488; color: #fff; }
.hp-opp-btn--loan:hover { background: #0f766e; color: #fff; }
.hp-opp-sub { margin-top: 2.5rem; }
.hp-opp-sub h3 {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0a1f44;
  text-align: center;
  margin-bottom: 1.25rem;
}
.hp-promo-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 1rem;
}
.hp-promo-banner {
  border-radius: 18px;
  padding: 1.75rem 1.5rem;
  color: #fff;
  min-height: 200px;
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
  box-shadow: 0 12px 32px rgba(10, 31, 68, .15);
}
.hp-promo-banner::after {
  content: '';
  position: absolute;
  width: 180px;
  height: 180px;
  border-radius: 50%;
  background: rgba(255,255,255,.08);
  right: -40px;
  top: -40px;
}
.hp-promo-banner.promo-a { background: linear-gradient(135deg, #0a1f44, #1e4a8c); }
.hp-promo-banner.promo-b { background: linear-gradient(135deg, #b45309, #e87722); }
.hp-promo-banner.promo-c { background: linear-gradient(135deg, #0d9488, #0369a1); }
.hp-promo-banner i.fa {
  font-size: 1.75rem;
  margin-bottom: .75rem;
  opacity: .9;
}
.hp-promo-banner h4 { font-size: 1.1rem; font-weight: 800; margin: 0 0 .5rem; position: relative; z-index: 1; }
.hp-promo-banner p { font-size: .88rem; opacity: .92; flex: 1; margin: 0 0 1rem; line-height: 1.5; position: relative; z-index: 1; }
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
}
.hp-promo-banner a:hover { filter: brightness(1.05); color: #0a1f44; }
</style>

<section class="hp-opp" id="opportunities">
  <div class="hp-opp__wrap">
    <header class="hp-opp__head fade-in">
      <h2>
        <?php if ($showPromo): ?>
        <?= htmlspecialchars($t('promo_section_title', 'Plan your journey with Xander'), ENT_QUOTES, 'UTF-8') ?>
        <?php elseif ($hasScholarships && $hasLoans): ?>
        <?= htmlspecialchars($t('opp_mixed_title', 'Scholarships & education loans'), ENT_QUOTES, 'UTF-8') ?>
        <?php elseif ($hasLoans): ?>
        <?= htmlspecialchars($t('loans_home_title', 'Institution education loans'), ENT_QUOTES, 'UTF-8') ?>
        <?php else: ?>
        <?= htmlspecialchars($t('scholarships_home_title', 'Institution scholarships'), ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
      </h2>
      <p>
        <?php if ($showPromo): ?>
        <?= htmlspecialchars($t('promo_section_desc', 'Apply, fund your studies, or speak with an advisor — we guide you end to end.'), ENT_QUOTES, 'UTF-8') ?>
        <?php elseif ($hasScholarships && $hasLoans): ?>
        <?= htmlspecialchars($t('opp_mixed_desc', 'Funding opportunities published by our partner institutions.'), ENT_QUOTES, 'UTF-8') ?>
        <?php elseif ($hasLoans): ?>
        <?= htmlspecialchars($t('loans_home_desc', 'Student loan programs from partner institutions — apply directly.'), ENT_QUOTES, 'UTF-8') ?>
        <?php else: ?>
        <?= htmlspecialchars($t('scholarships_home_desc', 'Apply directly to scholarship programs from partner universities.'), ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
      </p>
    </header>

    <?php if ($showPromo): ?>
    <div class="hp-promo-grid">
      <?php foreach ($promoBanners as $banner): ?>
      <article class="hp-promo-banner <?= htmlspecialchars($banner['accent'], ENT_QUOTES, 'UTF-8') ?> fade-in">
        <i class="fas <?= htmlspecialchars($banner['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
        <h4><?= htmlspecialchars($banner['title'], ENT_QUOTES, 'UTF-8') ?></h4>
        <p><?= htmlspecialchars($banner['desc'], ENT_QUOTES, 'UTF-8') ?></p>
        <a href="<?= htmlspecialchars($banner['url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($banner['cta'], ENT_QUOTES, 'UTF-8') ?> →</a>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>

    <?php if ($hasScholarships): ?>
    <?php if ($hasLoans): ?>
    <h3 class="hp-opp-subtitle text-center h6 fw-bold text-muted mb-3"><?= htmlspecialchars($t('scholarships_home_title', 'Scholarships'), ENT_QUOTES, 'UTF-8') ?></h3>
    <?php endif; ?>
    <div class="hp-opp__grid mb-4">
      <?php foreach ($scholarships as $sch):
          $schId = (int) ($sch['id'] ?? 0);
          $applyUrl = $schId > 0
              ? xander_institution_scholarship_apply_url($schId)
              : pcvc_url('/scholarship-apply.php');
          if ($schId <= 0 && !empty($sch['university_id'])) {
              $applyUrl = pcvc_url('/student-application.php');
          }
          ?>
      <article class="hp-opp-card hp-opp-card--sch fade-in">
        <div class="hp-opp-top">
          <span class="hp-opp-uni"><?= htmlspecialchars((string) ($sch['university_name'] ?? '')) ?></span>
          <?php if (!empty($sch['country_name'])): ?>
          <span class="hp-opp-country"><?= htmlspecialchars((string) $sch['country_name']) ?></span>
          <?php endif; ?>
        </div>
        <h3><?= htmlspecialchars((string) ($sch['title'] ?? '')) ?></h3>
        <?php if (!empty($sch['tagline'])): ?>
        <p class="hp-opp-tag"><?= htmlspecialchars((string) $sch['tagline']) ?></p>
        <?php endif; ?>
        <p class="hp-opp-summary"><?= htmlspecialchars(mb_substr((string) ($sch['summary'] ?? ''), 0, 140)) ?><?= mb_strlen((string) ($sch['summary'] ?? '')) > 140 ? '…' : '' ?></p>
        <ul class="hp-opp-meta">
          <?php if (!empty($sch['award_amount'])): ?><li><i class="fas fa-coins"></i> <?= htmlspecialchars((string) $sch['award_amount']) ?></li><?php endif; ?>
          <?php if (!empty($sch['tuition_coverage'])): ?><li><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars((string) $sch['tuition_coverage']) ?></li><?php endif; ?>
          <?php if (!empty($sch['deadline'])): ?><li><i class="fas fa-calendar"></i> <?= htmlspecialchars(date('M j, Y', strtotime((string) $sch['deadline']))) ?></li><?php endif; ?>
        </ul>
        <a href="<?= htmlspecialchars($applyUrl) ?>" class="hp-opp-btn hp-opp-btn--sch">
          <i class="fas fa-paper-plane"></i> <?= htmlspecialchars($t('card_apply', 'Apply now'), ENT_QUOTES, 'UTF-8') ?>
        </a>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($hasLoans): ?>
    <div class="hp-opp-sub">
      <h3><?= htmlspecialchars($t('loans_home_title', 'Education loan opportunities'), ENT_QUOTES, 'UTF-8') ?></h3>
      <div class="hp-opp__grid">
        <?php foreach ($loans as $loan):
            $loanUrl = xander_institution_loan_apply_url($loan);
            $loanTitle = (string) ($loan['title'] ?? '');
            $lender = trim((string) ($loan['loan_institution_name'] ?? ''));
            ?>
        <article class="hp-opp-card hp-opp-card--loan fade-in">
          <div class="hp-opp-top">
            <span class="hp-opp-uni"><?= htmlspecialchars((string) ($loan['university_name'] ?? '')) ?></span>
            <?php if (!empty($loan['country_name'])): ?>
            <span class="hp-opp-country"><?= htmlspecialchars((string) $loan['country_name']) ?></span>
            <?php endif; ?>
          </div>
          <h3><?= htmlspecialchars($loanTitle) ?></h3>
          <?php if ($lender !== ''): ?>
          <p class="hp-opp-tag"><i class="fas fa-building-columns me-1"></i><?= htmlspecialchars($lender) ?></p>
          <?php endif; ?>
          <p class="hp-opp-summary"><?= htmlspecialchars(mb_substr((string) ($loan['summary'] ?? ''), 0, 140)) ?><?= mb_strlen((string) ($loan['summary'] ?? '')) > 140 ? '…' : '' ?></p>
          <ul class="hp-opp-meta hp-opp-meta--loan">
            <?php if (!empty($loan['loan_coverage'])): ?><li><i class="fas fa-shield-halved"></i> <?= htmlspecialchars((string) $loan['loan_coverage']) ?></li><?php endif; ?>
            <?php if (!empty($loan['rates_notes'])): ?><li><i class="fas fa-percent"></i> <?= htmlspecialchars((string) $loan['rates_notes']) ?></li><?php endif; ?>
          </ul>
          <a href="<?= htmlspecialchars($loanUrl) ?>" class="hp-opp-btn hp-opp-btn--loan"<?= str_starts_with($loanUrl, 'http') ? ' target="_blank" rel="noopener"' : '' ?>>
            <i class="fas fa-arrow-right"></i> <?= htmlspecialchars($t('loan_apply_cta', 'Explore loan'), ENT_QUOTES, 'UTF-8') ?>
          </a>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
  </div>
</section>
