<?php
declare(strict_types=1);

/**
 * Homepage — student-facing services hub (insight / bento layout).
 * Expects: $current_lang, it() from index.php
 */

$hp_services = [
    ['id' => 'admissions', 'icon' => '🎓', 'title' => 'card1_title', 'tag' => 'card1_subtitle', 'tip' => 'insight_tip_admissions', 'form' => 'student-application.php'],
    ['id' => 'scholarships', 'icon' => '💰', 'title' => 'card2_title', 'tag' => 'card2_subtitle', 'tip' => 'insight_tip_scholarships', 'form' => 'index.php#scholarships'],
    ['id' => 'i20', 'icon' => '📄', 'title' => 'card3_title', 'tag' => 'card3_subtitle', 'tip' => 'insight_tip_i20', 'form' => 'form-20.php'],
    ['id' => 'credit', 'icon' => '🔁', 'title' => 'card4_title', 'tag' => 'card4_subtitle', 'tip' => 'insight_tip_credit', 'form' => 'credit_transfer.php'],
    ['id' => 'visa', 'icon' => '✈️', 'title' => 'card5_title', 'tag' => 'card5_subtitle', 'tip' => 'insight_tip_visa', 'form' => 'visa.php'],
    ['id' => 'jobs', 'icon' => '💼', 'title' => 'card6_title', 'tag' => 'card6_subtitle', 'tip' => 'insight_tip_jobs', 'form' => 'job-application.php'],
    ['id' => 'airticket', 'icon' => '🛫', 'title' => 'card7_title', 'tag' => 'card7_subtitle', 'tip' => 'insight_tip_airticket', 'form' => 'air-ticket-reservation.php'],
];

$hp_journey = [
    ['num' => '01', 'title' => 'process_step1', 'desc' => 'process_step1_desc'],
    ['num' => '02', 'title' => 'process_step2', 'desc' => 'process_step2_desc'],
    ['num' => '03', 'title' => 'process_step3', 'desc' => 'process_step3_desc'],
    ['num' => '04', 'title' => 'process_step4', 'desc' => 'process_step4_desc'],
    ['num' => '05', 'title' => 'process_step5', 'desc' => 'process_step5_desc'],
];

$langQs = !empty($current_lang) ? '?lang=' . rawurlencode((string) $current_lang) : '';
$servicesPageUrl = 'services.php' . $langQs;

$hp_form_url = static function (string $form) use ($langQs): string {
    if (str_contains($form, '#')) {
        $hashPos = strpos($form, '#');
        $base = substr($form, 0, $hashPos);
        $hash = substr($form, $hashPos);
        if ($langQs) {
            $base .= (str_contains($base, '?') ? '&' : '?') . ltrim($langQs, '?');
        }
        return $base . $hash;
    }
    if ($langQs) {
        return $form . (str_contains($form, '?') ? '&' : '?') . ltrim($langQs, '?');
    }
    return $form;
};
?>
<style>
.hp-insights {
  --hp-navy: #0a1f44;
  --hp-blue: #1e4a8c;
  --hp-orange: #e87722;
  --hp-mint: #0d9488;
  --hp-surface: #f8fafc;
  --hp-card: #ffffff;
  --hp-muted: #64748b;
  padding: 5rem 1.25rem;
  background: linear-gradient(165deg, #f0f4fa 0%, #fff 42%, #fef9f3 100%);
  position: relative;
  overflow: hidden;
}
.hp-insights::before {
  content: '';
  position: absolute;
  width: 520px;
  height: 520px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(30, 74, 140, 0.08) 0%, transparent 70%);
  top: -120px;
  right: -80px;
  pointer-events: none;
}
.hp-insights__wrap {
  max-width: 1180px;
  margin: 0 auto;
  position: relative;
  z-index: 1;
}
.hp-insights__head {
  text-align: center;
  max-width: 720px;
  margin: 0 auto 2.5rem;
}
.hp-insights__eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--hp-orange);
  background: rgba(232, 119, 34, 0.12);
  padding: 0.35rem 0.85rem;
  border-radius: 999px;
  margin-bottom: 0.85rem;
}
.hp-insights__title {
  font-size: clamp(1.75rem, 4vw, 2.35rem);
  font-weight: 800;
  color: var(--hp-navy);
  margin: 0 0 0.75rem;
  line-height: 1.2;
}
.hp-insights__desc {
  color: var(--hp-muted);
  font-size: 1.05rem;
  line-height: 1.65;
  margin: 0;
}
.hp-insights__layout {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 1.5rem;
  align-items: start;
}
@media (max-width: 992px) {
  .hp-insights__layout { grid-template-columns: 1fr; }
}
.hp-insights__bento {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}
@media (max-width: 640px) {
  .hp-insights__bento { grid-template-columns: 1fr; }
}
.hp-svc {
  background: var(--hp-card);
  border-radius: 18px;
  padding: 1.5rem 1.35rem 1.25rem;
  border: 1px solid rgba(10, 31, 68, 0.08);
  box-shadow:
    0 1px 0 rgba(255,255,255,0.6) inset,
    0 10px 32px rgba(10, 31, 68, 0.07);
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
  transition: transform 0.32s cubic-bezier(0.4, 0, 0.2, 1),
              box-shadow 0.32s cubic-bezier(0.4, 0, 0.2, 1),
              border-color 0.32s ease;
  position: relative;
  overflow: hidden;
}
.hp-svc::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--hp-navy), var(--hp-orange));
  transform: scaleX(0);
  transform-origin: left center;
  transition: transform 0.36s cubic-bezier(0.4, 0, 0.2, 1);
}
.hp-svc::after {
  content: '';
  position: absolute;
  inset: -1px;
  border-radius: 18px;
  background: radial-gradient(380px 180px at 100% 0%, rgba(232, 119, 34, 0.10), transparent 65%);
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none;
}
.hp-svc:hover {
  transform: translateY(-6px);
  box-shadow:
    0 1px 0 rgba(255,255,255,0.7) inset,
    0 22px 48px rgba(10, 31, 68, 0.16);
  border-color: rgba(30, 74, 140, 0.18);
}
.hp-svc:hover::before { transform: scaleX(1); }
.hp-svc:hover::after { opacity: 1; }
.hp-svc--featured {
  grid-column: span 2;
  background:
    radial-gradient(560px 200px at 100% 0%, rgba(232, 119, 34, 0.25), transparent 60%),
    linear-gradient(135deg, var(--hp-navy) 0%, var(--hp-blue) 100%);
  color: #fff;
  border-color: rgba(255,255,255,0.10);
}
.hp-svc--featured::before {
  background: linear-gradient(90deg, var(--hp-orange), #fbbf24);
  transform: scaleX(1);
}
.hp-svc--featured .hp-svc__tag,
.hp-svc--featured .hp-svc__tip,
.hp-svc--featured .hp-svc__title { color: rgba(255,255,255,0.94); }
.hp-svc--featured .hp-svc__tip { color: rgba(255,255,255,0.78); }
.hp-svc--featured .hp-svc__title { font-size: 1.25rem; }
.hp-svc--featured .hp-svc__cta {
  background: #fff;
  color: var(--hp-navy);
  box-shadow: 0 6px 16px rgba(0,0,0,0.18);
}
.hp-svc--featured .hp-svc__cta:hover {
  background: var(--hp-orange);
  color: #fff;
  transform: translateX(2px);
}
.hp-svc--featured:hover {
  transform: translateY(-6px);
  box-shadow:
    0 1px 0 rgba(255,255,255,0.10) inset,
    0 24px 56px rgba(10, 31, 68, 0.35);
}
@media (max-width: 640px) {
  .hp-svc--featured { grid-column: span 1; }
}
.hp-svc__top {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}
.hp-svc__icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: linear-gradient(135deg, rgba(10, 31, 68, 0.10), rgba(30, 74, 140, 0.16));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(10, 31, 68, 0.10), 0 0 0 1px rgba(255,255,255,0.6) inset;
  transition: transform 0.32s cubic-bezier(0.4, 0, 0.2, 1);
}
.hp-svc:hover .hp-svc__icon {
  transform: scale(1.08) rotate(-3deg);
}
.hp-svc--featured .hp-svc__icon {
  background: rgba(255,255,255,0.18);
  box-shadow: 0 0 0 1px rgba(255,255,255,0.20) inset;
}
.hp-svc__cta {
  position: relative;
  overflow: hidden;
}
.hp-svc__cta::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(120deg, transparent 30%, rgba(255,255,255,0.20) 50%, transparent 70%);
  transform: translateX(-100%);
  transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}
.hp-svc:hover .hp-svc__cta::after {
  transform: translateX(100%);
}
.hp-svc__cta i {
  transition: transform 0.28s ease;
}
.hp-svc:hover .hp-svc__cta i {
  transform: translateX(3px);
}
.hp-svc__title {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--hp-navy);
  margin: 0;
  line-height: 1.3;
}
.hp-svc__tag {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--hp-orange);
  margin: 0.15rem 0 0;
}
.hp-svc__tip {
  font-size: 0.88rem;
  color: var(--hp-muted);
  line-height: 1.5;
  margin: 0;
  flex: 1;
}
.hp-svc__cta {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  align-self: flex-start;
  padding: 0.45rem 0.9rem;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 600;
  text-decoration: none;
  background: var(--hp-navy);
  color: #fff;
  transition: background 0.2s;
}
.hp-svc__cta:hover { background: var(--hp-blue); color: #fff; }
.hp-insights__aside {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.hp-insight-panel {
  background: var(--hp-card);
  border-radius: 16px;
  padding: 1.25rem;
  border: 1px solid rgba(10, 31, 68, 0.08);
  box-shadow: 0 8px 24px rgba(10, 31, 68, 0.06);
}
.hp-insight-panel h3 {
  font-size: 1rem;
  font-weight: 700;
  color: var(--hp-navy);
  margin: 0 0 1rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}
.hp-journey-item {
  display: flex;
  gap: 0.75rem;
  padding: 0.65rem 0;
  border-bottom: 1px solid #eef2f7;
}
.hp-journey-item:last-child { border-bottom: none; padding-bottom: 0; }
.hp-journey-num {
  font-size: 0.7rem;
  font-weight: 800;
  color: var(--hp-orange);
  min-width: 1.5rem;
}
.hp-journey-item strong {
  display: block;
  font-size: 0.88rem;
  color: var(--hp-navy);
  margin-bottom: 0.15rem;
}
.hp-journey-item span {
  font-size: 0.78rem;
  color: var(--hp-muted);
  line-height: 1.4;
}
.hp-stat-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.65rem;
}
.hp-mini-stat {
  background: linear-gradient(135deg, #f8fafc, #fff);
  border-radius: 10px;
  padding: 0.75rem;
  text-align: center;
  border: 1px solid #e8eef5;
}
.hp-mini-stat b {
  display: block;
  font-size: 1.1rem;
  color: var(--hp-navy);
}
.hp-mini-stat small {
  font-size: 0.72rem;
  color: var(--hp-muted);
  line-height: 1.3;
}
.hp-insights__footer {
  margin-top: 2rem;
  text-align: center;
}
.hp-insights__footer a.hp-btn-all {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.85rem 1.75rem;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--hp-navy), var(--hp-blue));
  color: #fff;
  font-weight: 700;
  text-decoration: none;
  box-shadow: 0 8px 24px rgba(10, 31, 68, 0.2);
  transition: transform 0.2s, box-shadow 0.2s;
}
.hp-insights__footer a.hp-btn-all:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 32px rgba(10, 31, 68, 0.28);
  color: #fff;
}
</style>

<section class="hp-insights" id="services-insights">
  <div class="hp-insights__wrap">
    <header class="hp-insights__head fade-in">
      <span class="hp-insights__eyebrow">
        <i class="fas fa-compass" aria-hidden="true"></i>
        <?php echo htmlspecialchars(it('insights_eyebrow'), ENT_QUOTES, 'UTF-8'); ?>
      </span>
      <h2 class="hp-insights__title"><?php echo htmlspecialchars(it('insights_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p class="hp-insights__desc"><?php echo htmlspecialchars(it('insights_description'), ENT_QUOTES, 'UTF-8'); ?></p>
    </header>

    <div class="hp-insights__layout">
      <div class="hp-insights__bento">
        <?php foreach ($hp_services as $i => $svc):
          $featured = ($i === 0);
          $formUrl = $hp_form_url($svc['form']);
          ?>
        <article class="hp-svc<?php echo $featured ? ' hp-svc--featured fade-in' : ' fade-in'; ?>">
          <div class="hp-svc__top">
            <div class="hp-svc__icon" aria-hidden="true"><?php echo $svc['icon']; ?></div>
            <div>
              <h3 class="hp-svc__title"><?php echo htmlspecialchars(it($svc['title']), ENT_QUOTES, 'UTF-8'); ?></h3>
              <p class="hp-svc__tag"><?php echo htmlspecialchars(it($svc['tag']), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
          </div>
          <p class="hp-svc__tip"><?php echo htmlspecialchars(it($svc['tip']), ENT_QUOTES, 'UTF-8'); ?></p>
          <a class="hp-svc__cta" href="<?php echo htmlspecialchars($formUrl, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars(it('card_apply'), ENT_QUOTES, 'UTF-8'); ?>
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
          </a>
        </article>
        <?php endforeach; ?>
      </div>

      <aside class="hp-insights__aside">
        <div class="hp-insight-panel fade-in">
          <h3><i class="fas fa-route" style="color:var(--hp-orange)"></i> <?php echo htmlspecialchars(it('insights_journey_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
          <?php foreach ($hp_journey as $step): ?>
          <div class="hp-journey-item">
            <span class="hp-journey-num"><?php echo htmlspecialchars($step['num'], ENT_QUOTES, 'UTF-8'); ?></span>
            <div>
              <strong><?php echo htmlspecialchars(it($step['title']), ENT_QUOTES, 'UTF-8'); ?></strong>
              <span><?php echo htmlspecialchars(it($step['desc']), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="hp-insight-panel fade-in">
          <h3><i class="fas fa-chart-line" style="color:var(--hp-mint)"></i> <?php echo htmlspecialchars(it('insights_at_glance'), ENT_QUOTES, 'UTF-8'); ?></h3>
          <div class="hp-stat-row">
            <div class="hp-mini-stat">
              <b>50+</b>
              <small><?php echo htmlspecialchars(it('stats_students'), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
            <div class="hp-mini-stat">
              <b>20+</b>
              <small><?php echo htmlspecialchars(it('stats_countries'), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
            <div class="hp-mini-stat">
              <b>$100K+</b>
              <small><?php echo htmlspecialchars(it('stats_scholarships'), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
            <div class="hp-mini-stat">
              <b>7</b>
              <small><?php echo htmlspecialchars(it('insights_services_count'), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
          </div>
        </div>
      </aside>
    </div>

    <div class="hp-insights__footer fade-in">
      <a href="<?php echo htmlspecialchars($servicesPageUrl, ENT_QUOTES, 'UTF-8'); ?>" class="hp-btn-all">
        <?php echo htmlspecialchars(it('insights_cta_all'), ENT_QUOTES, 'UTF-8'); ?>
        <i class="fas fa-external-link-alt" aria-hidden="true"></i>
      </a>
    </div>
  </div>
</section>
