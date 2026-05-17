<?php
declare(strict_types=1);

/** @var bool $readonly */
/** @var array<string,mixed> $prefill */
/** @var mysqli|null $conn */
/** @var bool $editableContact */

require_once __DIR__ . '/../helpers/prescreening_options.php';
require_once __DIR__ . '/../helpers/prescreening_work_profile.php';

$readonly = !empty($readonly);
$dis = $readonly ? 'disabled' : '';
$prefill = $prefill ?? [];
$dbConn = $conn ?? null;
$editableContact = !empty($editableContact);

$workCountries = xander_prescreening_work_countries($dbConn);
$checklistLabels = xander_prescreening_work_checklist_labels();
$profile = xander_prescreening_work_profile_unpack($prefill);
$home = $profile['home'];
$em = $profile['emergency'];

$checkedRaw = (string) ($prefill['work_docs_checklist'] ?? '');
$checkedKeys = [];
if ($checkedRaw !== '' && str_starts_with(trim($checkedRaw), '[')) {
    $decoded = json_decode($checkedRaw, true);
    if (is_array($decoded)) {
        foreach ($checklistLabels as $key => $label) {
            if (in_array($label, $decoded, true)) {
                $checkedKeys[] = $key;
            }
        }
    }
}
if ($checkedKeys === [] && isset($_POST['work_checklist']) && is_array($_POST['work_checklist'])) {
    $checkedKeys = array_map('strval', $_POST['work_checklist']);
}
?>
<div class="card-panel prescreen-panel prescreen-work-panel <?= htmlspecialchars($panelWorkClass ?? 'd-none', ENT_QUOTES, 'UTF-8') ?>" data-service-panel="work_abroad">
  <h2><i class="bi bi-briefcase me-1"></i> Work Abroad application</h2>
  <p class="small text-muted mb-3">All fields are optional — submit with whatever information you have.</p>

  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label">Name</label>
      <input type="text" name="student_name" class="form-control prescreen-work-contact"
             <?= ($readonly && !$editableContact) ? 'readonly' : '' ?>
             value="<?= htmlspecialchars((string) ($prefill['student_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Email</label>
      <input type="email" name="student_email" class="form-control prescreen-work-contact"
             <?= ($readonly && !$editableContact) ? 'readonly' : '' ?>
             value="<?= htmlspecialchars((string) ($prefill['student_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Phone number</label>
      <input type="tel" name="whatsapp_number" class="form-control prescreen-work-contact"
             <?= ($readonly && !$editableContact) ? 'readonly' : '' ?>
             value="<?= htmlspecialchars((string) ($prefill['whatsapp_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Country destination</label>
      <?= xander_prescreening_render_select(
          'work_country_destination',
          $workCountries,
          (string) ($prefill['work_country_destination'] ?? ''),
          false,
          $readonly,
          'Select work destination country'
      ) ?>
    </div>
    <div class="col-12">
      <label class="form-label">Address notes</label>
      <textarea name="applicant_address" class="form-control" rows="2" <?= $readonly ? 'readonly' : '' ?>
                placeholder="Optional extra address details"><?= htmlspecialchars((string) ($prefill['applicant_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>
  </div>

  <h3 class="h6 text-primary mt-2"><i class="bi bi-house me-1"></i> Home address <span class="text-muted fw-normal">(optional)</span></h3>
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label">Country</label>
      <?= xander_prescreening_render_select('prescreen_home_country', $workCountries, (string) ($home['country'] ?? ''), false, $readonly, 'Select country') ?>
    </div>
    <div class="col-md-6">
      <label class="form-label">Province / State</label>
      <input type="text" name="prescreen_province_state" class="form-control" <?= $readonly ? 'readonly' : '' ?>
             value="<?= htmlspecialchars((string) ($home['province_state'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">District</label>
      <input type="text" name="prescreen_district" class="form-control" <?= $readonly ? 'readonly' : '' ?>
             value="<?= htmlspecialchars((string) ($home['district'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Sector</label>
      <input type="text" name="prescreen_sector" class="form-control" <?= $readonly ? 'readonly' : '' ?>
             value="<?= htmlspecialchars((string) ($home['sector'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Cell / Ward</label>
      <input type="text" name="prescreen_cell_ward" class="form-control" <?= $readonly ? 'readonly' : '' ?>
             value="<?= htmlspecialchars((string) ($home['cell_ward'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Village</label>
      <input type="text" name="prescreen_village" class="form-control" <?= $readonly ? 'readonly' : '' ?>
             value="<?= htmlspecialchars((string) ($home['village'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
  </div>

  <h3 class="h6 text-primary"><i class="bi bi-telephone me-1"></i> Emergency contact <span class="text-muted fw-normal">(optional)</span></h3>
  <p class="small text-muted">Full name, relationship, phone, and email — leave blank if unknown.</p>
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label">Full name</label>
      <input type="text" name="emergency_full_name" class="form-control" <?= $readonly ? 'readonly' : '' ?>
             value="<?= htmlspecialchars((string) ($em['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Relationship</label>
      <input type="text" name="emergency_relationship" class="form-control" <?= $readonly ? 'readonly' : '' ?>
             value="<?= htmlspecialchars((string) ($em['relationship'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Phone number</label>
      <input type="tel" name="emergency_phone" class="form-control" <?= $readonly ? 'readonly' : '' ?>
             value="<?= htmlspecialchars((string) ($em['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="emergency_area_code" value="<?= htmlspecialchars((string) ($em['area_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="emergency_phone_number" value="<?= htmlspecialchars((string) ($em['phone_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Email address</label>
      <input type="email" name="emergency_email" class="form-control" <?= $readonly ? 'readonly' : '' ?>
             value="<?= htmlspecialchars((string) ($em['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label fw-semibold">Documents checklist</label>
    <div class="border rounded p-3 bg-light">
      <?php foreach ($checklistLabels as $key => $label): ?>
      <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="work_checklist[]"
               value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
               id="wc_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
               <?= in_array($key, $checkedKeys, true) ? 'checked' : '' ?> <?= $dis ?>>
        <label class="form-check-label" for="wc_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="card-panel prescreen-panel prescreen-work-panel <?= htmlspecialchars($panelWorkClass ?? 'd-none', ENT_QUOTES, 'UTF-8') ?>" data-service-panel="work_abroad">
  <h2>Upload documents <span class="text-muted fw-normal">(optional)</span></h2>
  <?php
  $docLabels = xander_prescreening_work_document_labels();
  include __DIR__ . '/prescreening_documents_partial.php';
  ?>
</div>
