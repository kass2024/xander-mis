<?php
declare(strict_types=1);

/** @var bool $readonly */
/** @var array<string,mixed> $prefill */
/** @var mysqli|null $conn */
/** @var bool $asyncDocs */

require_once __DIR__ . '/../helpers/prescreening_options.php';

$readonly = !empty($readonly);
$dis = $readonly ? 'disabled' : '';
$prefill = $prefill ?? [];
$conn = $conn ?? null;
$asyncDocs = !empty($asyncDocs);

$serviceType = (string) ($prefill['service_type'] ?? '');
if ($serviceType === '' && !empty($_POST['service_type'])) {
    $serviceType = (string) $_POST['service_type'];
}
$serviceTypes = xander_prescreening_service_types();
$panelStudyClass = $serviceType === 'study_abroad' ? '' : 'd-none';
$panelWorkClass = $serviceType === 'work_abroad' ? '' : 'd-none';
?>
<div class="card-panel" id="serviceTypeCard">
  <h2><i class="bi bi-signpost-split me-1"></i> Type of service</h2>
  <p class="small text-muted mb-3">Choose Study Abroad or Work Abroad — the matching form appears below. All other fields are optional.</p>
  <label class="form-label">What do you need?</label>
  <select name="service_type" id="serviceTypeSelect" class="form-select" <?= $dis ?>>
    <option value="">Select service type</option>
    <?php foreach ($serviceTypes as $key => $label): ?>
    <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
      <?= $serviceType === $key ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
    <?php endforeach; ?>
  </select>
</div>

<?php
include __DIR__ . '/prescreening_study_form.php';
include __DIR__ . '/prescreening_work_form.php';
include __DIR__ . '/prescreening_form_toggle.php';
