<?php
declare(strict_types=1);

/** @var bool $readonly */
/** @var array<string,mixed> $prefill */
/** @var mysqli|null $conn */

require_once __DIR__ . '/../helpers/prescreening_options.php';

$readonly = !empty($readonly);
$ro = $readonly ? 'readonly' : '';
$dis = $readonly ? 'disabled' : '';
$prefill = $prefill ?? [];
$dbConn = $conn ?? null;

$studyCountries = xander_prescreening_study_countries($dbConn);
?>
<div class="card-panel prescreen-panel prescreen-study-panel <?= htmlspecialchars($panelStudyClass ?? 'd-none', ENT_QUOTES, 'UTF-8') ?>" data-service-panel="study_abroad">
  <h2><i class="bi bi-mortarboard me-1"></i> Study Abroad — pre-screening</h2>
  <p class="small text-muted mb-3">Questions marked with * are required. Other fields are optional.</p>

  <div class="mb-3">
    <label class="form-label"><span class="q-num">1.</span> Highest level of education?</label>
    <?= xander_prescreening_render_select(
        'education_level',
        xander_prescreening_education_levels(),
        (string) ($prefill['education_level'] ?? ''),
        false,
        $readonly
    ) ?>
  </div>
  <div class="mb-3">
    <label class="form-label"><span class="q-num">2.</span> Course or program?</label>
    <?= xander_prescreening_render_select(
        'course_program',
        xander_prescreening_course_programs(),
        (string) ($prefill['course_program'] ?? ''),
        false,
        $readonly
    ) ?>
  </div>
  <div class="mb-3">
    <label class="form-label"><span class="q-num">3.</span> Countries of interest? <span class="text-muted small">(tick at least one)</span></label>
    <?= xander_prescreening_render_country_multi_select(
        'country_interest',
        $studyCountries,
        (string) ($prefill['country_interest'] ?? ''),
        $readonly,
        1,
        'Search country…'
    ) ?>
  </div>
  <div class="mb-3">
    <label class="form-label"><span class="q-num">4.</span> Open to India, Cyprus, Malta (under $5k/year)?</label>
    <?= xander_prescreening_render_select(
        'open_other_countries',
        xander_prescreening_yes_no_maybe(),
        (string) ($prefill['open_other_countries'] ?? ''),
        false,
        $readonly
    ) ?>
  </div>
  <div class="mb-3">
    <label class="form-label"><span class="q-num">5.</span> Tuition budget per year? <span class="text-danger">*</span></label>
    <?= xander_prescreening_render_select(
        'budget_tuition',
        xander_prescreening_tuition_budgets(),
        (string) ($prefill['budget_tuition'] ?? ''),
        true,
        $readonly
    ) ?>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label"><span class="q-num">6.</span> Sponsor?</label>
      <?= xander_prescreening_render_select('sponsor', ['Self', 'Parent', 'Sponsor'], (string) ($prefill['sponsor'] ?? ''), false, $readonly) ?>
    </div>
    <div class="col-md-6">
      <label class="form-label"><span class="q-num">7.</span> Afford deposit and accommodation?</label>
      <?= xander_prescreening_render_select('afford_deposit', ['Yes', 'No'], (string) ($prefill['afford_deposit'] ?? ''), false, $readonly) ?>
    </div>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label"><span class="q-num">8.</span> Valid passport? <span class="text-danger">*</span></label>
      <?= xander_prescreening_render_select('has_valid_passport', ['Yes', 'No'], (string) ($prefill['has_valid_passport'] ?? ''), true, $readonly) ?>
    </div>
    <div class="col-md-6">
      <label class="form-label"><span class="q-num">9.</span> Academic documents ready?</label>
      <?= xander_prescreening_render_select('academic_docs_ready', ['Yes', 'No', 'Partially'], (string) ($prefill['academic_docs_ready'] ?? ''), false, $readonly) ?>
    </div>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label"><span class="q-num">10.</span> English level?</label>
      <?= xander_prescreening_render_select('english_level', ['Basic', 'Good', 'Test done'], (string) ($prefill['english_level'] ?? ''), false, $readonly) ?>
    </div>
    <div class="col-md-6">
      <label class="form-label"><span class="q-num">11.</span> IELTS / TOEFL / Duolingo?</label>
      <?= xander_prescreening_render_select(
        'english_test_taken',
        xander_prescreening_english_tests(),
        (string) ($prefill['english_test_taken'] ?? ''),
        false,
        $readonly
    ) ?>
    </div>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label"><span class="q-num">12.</span> Ever denied a visa?</label>
      <?= xander_prescreening_render_select('visa_denied', ['Yes', 'No'], (string) ($prefill['visa_denied'] ?? ''), false, $readonly) ?>
    </div>
    <div class="col-md-6">
      <label class="form-label"><span class="q-num">13.</span> Planned intake?</label>
      <?= xander_prescreening_render_select(
        'planned_intake',
        xander_prescreening_planned_intakes(),
        (string) ($prefill['planned_intake'] ?? ''),
        false,
        $readonly,
        'Select intake'
    ) ?>
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label"><span class="q-num">14.</span> Will you attend online or in person?</label>
    <?= xander_prescreening_render_select(
        'study_attendance_mode',
        xander_prescreening_attendance_modes(),
        (string) ($prefill['study_attendance_mode'] ?? ''),
        false,
        $readonly
    ) ?>
  </div>
  <div class="mb-0">
    <label class="form-label"><span class="q-num">15.</span> Ready to apply now?</label>
    <?= xander_prescreening_render_select('ready_to_apply', ['Yes', 'No'], (string) ($prefill['ready_to_apply'] ?? ''), false, $readonly) ?>
  </div>
</div>

<div class="card-panel prescreen-panel prescreen-study-panel <?= htmlspecialchars($panelStudyClass ?? 'd-none', ENT_QUOTES, 'UTF-8') ?>" data-service-panel="study_abroad">
  <h2>Documents</h2>
  <p class="small text-muted mb-3">Valid passport is required. Other documents are optional.</p>
  <?php
  $docLabels = xander_prescreening_document_labels();
  $requiredDocKeys = ['doc_valid_passport'];
  include __DIR__ . '/prescreening_documents_partial.php';
  ?>
</div>
