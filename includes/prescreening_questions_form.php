<?php
/** @var bool $readonly */
$readonly = !empty($readonly);
$ro = $readonly ? 'readonly' : '';
$dis = $readonly ? 'disabled' : '';
?>
<div class="card-panel">
  <h2>Pre-screening questions</h2>
  <div class="mb-3">
    <label class="form-label"><span class="q-num">1.</span> Highest level of education? <span class="text-danger">*</span></label>
    <input type="text" name="education_level" class="form-control" required <?= $ro ?> value="<?= htmlspecialchars((string)($prefill['education_level'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="mb-3">
    <label class="form-label"><span class="q-num">2.</span> Course or program? <span class="text-danger">*</span></label>
    <input type="text" name="course_program" class="form-control" required <?= $ro ?> value="<?= htmlspecialchars((string)($prefill['course_program'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="mb-3">
    <label class="form-label"><span class="q-num">3.</span> Country of interest? <span class="text-danger">*</span></label>
    <input type="text" name="country_interest" class="form-control" required <?= $ro ?> value="<?= htmlspecialchars((string)($prefill['country_interest'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="mb-3">
    <label class="form-label"><span class="q-num">4.</span> Open to India, Cyprus, Malta (under $15k/year)?</label>
    <textarea name="open_other_countries" class="form-control" rows="2" <?= $ro ?>><?= htmlspecialchars((string)($prefill['open_other_countries'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label"><span class="q-num">5.</span> Tuition budget per year? <span class="text-danger">*</span></label>
    <input type="text" name="budget_tuition" class="form-control" required <?= $ro ?> value="<?= htmlspecialchars((string)($prefill['budget_tuition'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label"><span class="q-num">6.</span> Funds for application/visa fees? <span class="text-danger">*</span></label>
      <select name="funds_application_visa" class="form-select" required <?= $dis ?>>
        <option value="">—</option>
        <?php foreach (['Yes', 'No'] as $v): ?>
        <option value="<?= $v ?>" <?= (($prefill['funds_application_visa'] ?? '') === $v) ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label"><span class="q-num">7.</span> Sponsor? <span class="text-danger">*</span></label>
      <select name="sponsor" class="form-select" required <?= $dis ?>>
        <option value="">—</option>
        <?php foreach (['Self', 'Parent', 'Sponsor'] as $v): ?>
        <option value="<?= $v ?>" <?= (($prefill['sponsor'] ?? '') === $v) ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label"><span class="q-num">8.</span> Afford deposit and accommodation? <span class="text-danger">*</span></label>
      <select name="afford_deposit" class="form-select" required <?= $dis ?>>
        <option value="">—</option>
        <?php foreach (['Yes', 'No'] as $v): ?>
        <option value="<?= $v ?>" <?= (($prefill['afford_deposit'] ?? '') === $v) ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label"><span class="q-num">9.</span> Valid passport? <span class="text-danger">*</span></label>
      <select name="has_valid_passport" class="form-select" required <?= $dis ?>>
        <option value="">—</option>
        <?php foreach (['Yes', 'No'] as $v): ?>
        <option value="<?= $v ?>" <?= (($prefill['has_valid_passport'] ?? '') === $v) ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label"><span class="q-num">10.</span> Academic documents ready? <span class="text-danger">*</span></label>
    <select name="academic_docs_ready" class="form-select" required <?= $dis ?>>
      <option value="">—</option>
      <?php foreach (['Yes', 'No', 'Partially'] as $v): ?>
      <option value="<?= $v ?>" <?= (($prefill['academic_docs_ready'] ?? '') === $v) ? 'selected' : '' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label"><span class="q-num">11.</span> English level? <span class="text-danger">*</span></label>
      <select name="english_level" class="form-select" required <?= $dis ?>>
        <option value="">—</option>
        <?php foreach (['Basic', 'Good', 'Test done'] as $v): ?>
        <option value="<?= $v ?>" <?= (($prefill['english_level'] ?? '') === $v) ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label"><span class="q-num">12.</span> IELTS/TOEFL/Duolingo?</label>
      <input type="text" name="english_test_taken" class="form-control" <?= $ro ?> value="<?= htmlspecialchars((string)($prefill['english_test_taken'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label"><span class="q-num">13.</span> Ever denied a visa? <span class="text-danger">*</span></label>
      <select name="visa_denied" class="form-select" required <?= $dis ?>>
        <option value="">—</option>
        <?php foreach (['Yes', 'No'] as $v): ?>
        <option value="<?= $v ?>" <?= (($prefill['visa_denied'] ?? '') === $v) ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label"><span class="q-num">14.</span> Planned intake? <span class="text-danger">*</span></label>
      <input type="text" name="planned_intake" class="form-control" required <?= $ro ?> value="<?= htmlspecialchars((string)($prefill['planned_intake'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
  </div>
  <div class="mb-0">
    <label class="form-label"><span class="q-num">15.</span> Ready to apply now? <span class="text-danger">*</span></label>
    <select name="ready_to_apply" class="form-select" required <?= $dis ?>>
      <option value="">—</option>
      <?php foreach (['Yes', 'No'] as $v): ?>
      <option value="<?= $v ?>" <?= (($prefill['ready_to_apply'] ?? '') === $v) ? 'selected' : '' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<div class="card-panel">
  <h2>Documents</h2>
  <?php if (!empty($asyncDocs)): ?>
  <p class="small text-muted mb-3">Files upload as soon as you pick them — submit is faster.</p>
  <?php endif; ?>
  <?php foreach ($docLabels as $key => $label): ?>
    <?php
    $existingPath = (string) ($prefill[$key] ?? '');
    $hasFile = $existingPath !== '';
    ?>
    <div class="mb-3 prescreen-doc-row" data-doc-key="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
      <label class="form-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
      <input type="hidden"
             name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>_existing"
             class="prescreen-doc-path"
             value="<?= htmlspecialchars($existingPath, ENT_QUOTES, 'UTF-8') ?>">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <input type="file"
               name="<?= empty($asyncDocs) ? htmlspecialchars($key, ENT_QUOTES, 'UTF-8') : '' ?>"
               class="form-control prescreen-doc-input flex-grow-1"
               data-doc-key="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
               <?= $readonly ? 'disabled' : '' ?>>
        <span class="prescreen-doc-status small <?= $hasFile ? 'text-success' : 'text-muted' ?>">
          <?php if ($hasFile): ?>
            <i class="bi bi-check-circle-fill"></i> Saved
          <?php else: ?>
            <span class="prescreen-doc-status-idle">Optional</span>
          <?php endif; ?>
        </span>
      </div>
    </div>
  <?php endforeach; ?>
</div>
