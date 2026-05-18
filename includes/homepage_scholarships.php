<?php
declare(strict_types=1);

/**
 * Renders published institution scholarships on the homepage.
 * Expects $conn (mysqli) and optional pcvc_url().
 */
if (!isset($conn) || !($conn instanceof mysqli)) {
    return;
}

require_once dirname(__DIR__) . '/helpers/institution_dashboard.php';

$homepageScholarships = xander_homepage_published_scholarships($conn, 12);
if ($homepageScholarships === []) {
    return;
}
?>
<section class="scholarships-home section-padding" id="scholarships">
  <div class="section-header">
    <h2 class="section-title"><?php echo function_exists('it') ? it('scholarships_home_title') : 'Institution Scholarships'; ?></h2>
    <p class="section-description"><?php echo function_exists('it') ? it('scholarships_home_desc') : 'Apply directly to scholarship programs from our partner universities and institutions.'; ?></p>
  </div>
  <div class="scholarships-home-grid">
    <?php foreach ($homepageScholarships as $sch):
        $schId = (int) ($sch['id'] ?? 0);
        $applyUrl = $schId > 0
            ? xander_institution_scholarship_apply_url($schId)
            : pcvc_url('/scholarship-apply.php');
        if ($schId <= 0 && !empty($sch['university_id'])) {
            $applyUrl = pcvc_url('/student-application.php');
        }
    ?>
    <article class="scholarship-home-card fade-in">
      <div class="sch-card-top">
        <span class="sch-uni"><?= htmlspecialchars((string) ($sch['university_name'] ?? '')) ?></span>
        <?php if (!empty($sch['country_name'])): ?>
        <span class="sch-country"><?= htmlspecialchars((string) $sch['country_name']) ?></span>
        <?php endif; ?>
      </div>
      <h3><?= htmlspecialchars((string) ($sch['title'] ?? '')) ?></h3>
      <?php if (!empty($sch['tagline'])): ?>
      <p class="sch-tagline"><?= htmlspecialchars((string) $sch['tagline']) ?></p>
      <?php endif; ?>
      <p class="sch-summary"><?= htmlspecialchars(mb_substr((string) ($sch['summary'] ?? ''), 0, 140)) ?><?= mb_strlen((string) ($sch['summary'] ?? '')) > 140 ? '…' : '' ?></p>
      <ul class="sch-meta">
        <?php if (!empty($sch['award_amount'])): ?><li><i class="fas fa-coins"></i> <?= htmlspecialchars((string) $sch['award_amount']) ?></li><?php endif; ?>
        <?php if (!empty($sch['tuition_coverage'])): ?><li><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars((string) $sch['tuition_coverage']) ?></li><?php endif; ?>
        <?php if (!empty($sch['deadline'])): ?><li><i class="fas fa-calendar"></i> <?= htmlspecialchars(date('M j, Y', strtotime((string) $sch['deadline']))) ?></li><?php endif; ?>
      </ul>
      <a href="<?= htmlspecialchars($applyUrl) ?>" class="sch-apply-btn">
        <i class="fas fa-paper-plane"></i> Apply now
      </a>
    </article>
    <?php endforeach; ?>
  </div>
</section>
