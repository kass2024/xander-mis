<?php
/** @var string $activeSection @var array $scholarships @var array|null $editScholarship @var int $universityId */
$isForm = ($activeSection === 'create' || $activeSection === 'edit' || !empty($editScholarship));
$sch = $editScholarship ?? [];
$schId = (int) ($sch['id'] ?? 0);
?>
<div class="page-head d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
  <div>
    <h1>Scholarships</h1>
    <p class="page-sub text-muted mb-0">Manage scholarship opportunities for international students</p>
  </div>
  <?php if (!$isForm): ?>
  <a href="index.php?tab=scholarships&section=create" class="btn btn-save"><i class="fas fa-plus me-1"></i> Create scholarship</a>
  <?php endif; ?>
</div>

<?php if ($isForm): ?>
<div class="panel">
  <form method="post" enctype="multipart/form-data" class="row g-3">
    <?= pcvc_csrf_input() ?>
    <input type="hidden" name="action" value="save_scholarship">
    <input type="hidden" name="scholarship_id" value="<?= $schId ?>">
    <div class="col-12"><h3 class="h5 fw-bold mb-0"><?= $schId ? 'Edit scholarship' : 'Create scholarship' ?></h3></div>
    <div class="col-md-8">
      <label class="form-label">Scholarship title *</label>
      <input class="form-control" name="title" required value="<?= xander_institution_h((string) ($sch['title'] ?? '')) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Deadline</label>
      <input class="form-control" type="date" name="deadline" value="<?= xander_institution_h((string) ($sch['deadline'] ?? '')) ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Tagline</label>
      <input class="form-control" name="tagline" value="<?= xander_institution_h((string) ($sch['tagline'] ?? '')) ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Summary *</label>
      <textarea class="form-control" name="summary" rows="3" required><?= xander_institution_h((string) ($sch['summary'] ?? '')) ?></textarea>
    </div>
    <div class="col-md-6">
      <label class="form-label">Eligibility requirements</label>
      <textarea class="form-control" name="eligibility" rows="3"><?= xander_institution_h((string) ($sch['eligibility'] ?? '')) ?></textarea>
    </div>
    <div class="col-md-6">
      <label class="form-label">Additional requirements</label>
      <textarea class="form-control" name="requirements" rows="3"><?= xander_institution_h((string) ($sch['requirements'] ?? '')) ?></textarea>
    </div>
    <div class="col-md-6">
      <label class="form-label">Tuition coverage</label>
      <input class="form-control" name="tuition_coverage" value="<?= xander_institution_h((string) ($sch['tuition_coverage'] ?? '')) ?>" placeholder="e.g. 100% tuition">
    </div>
    <div class="col-md-6">
      <label class="form-label">Award amount</label>
      <input class="form-control" name="award_amount" value="<?= xander_institution_h((string) ($sch['award_amount'] ?? '')) ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Accommodation details</label>
      <textarea class="form-control" name="accommodation_details" rows="2"><?= xander_institution_h((string) ($sch['accommodation_details'] ?? '')) ?></textarea>
    </div>
    <div class="col-12">
      <label class="form-label">Benefits</label>
      <textarea class="form-control" name="benefits" rows="2"><?= xander_institution_h((string) ($sch['benefits'] ?? '')) ?></textarea>
    </div>
    <div class="col-md-4">
      <label class="form-label">Status</label>
      <select class="form-select" name="status">
        <?php foreach (['draft' => 'Draft', 'active' => 'Active', 'expired' => 'Expired'] as $k => $lbl): ?>
        <option value="<?= $k ?>" <?= (($sch['status'] ?? 'draft') === $k) ? 'selected' : '' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4 d-flex align-items-end">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_published" value="1" id="pubSch" <?= !empty($sch['is_published']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="pubSch">Publish on homepage</label>
      </div>
    </div>
    <div class="col-md-4">
      <label class="form-label">Brochure (PDF/image)</label>
      <input class="form-control" type="file" name="brochure" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
      <?php if (!empty($sch['brochure_path'])): ?>
      <small class="text-muted"><a href="../<?= xander_institution_h((string) $sch['brochure_path']) ?>" target="_blank">Current brochure</a></small>
      <?php endif; ?>
    </div>
    <div class="col-12 d-flex gap-2">
      <button type="submit" class="btn btn-save">Save scholarship</button>
      <a href="index.php?tab=scholarships" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php else: ?>
<div class="data-table-wrap panel p-0 overflow-hidden">
  <table class="table table-hover mb-0 inst-table">
    <thead>
      <tr>
        <th>Title</th>
        <th>Deadline</th>
        <th>Status</th>
        <th>Homepage</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($scholarships)): ?>
      <tr><td colspan="5" class="text-center text-muted py-4">No scholarships yet. <a href="index.php?tab=scholarships&section=create">Create your first scholarship</a>.</td></tr>
      <?php else: ?>
      <?php foreach ($scholarships as $s): ?>
      <tr>
        <td><strong><?= xander_institution_h((string) ($s['title'] ?? '')) ?></strong><br><small class="text-muted"><?= xander_institution_h((string) ($s['tagline'] ?? '')) ?></small></td>
        <td><?= !empty($s['deadline']) ? xander_institution_h(date('M j, Y', strtotime((string) $s['deadline']))) : '—' ?></td>
        <td><span class="badge-status status-<?= xander_institution_h((string) ($s['status'] ?? 'draft')) ?>"><?= xander_institution_h((string) ($s['status'] ?? '')) ?></span></td>
        <td><?= !empty($s['is_published']) ? '<span class="text-success">Published</span>' : '<span class="text-muted">Draft</span>' ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-primary" href="index.php?tab=scholarships&section=edit&id=<?= (int) $s['id'] ?>">Edit</a>
          <a class="btn btn-sm btn-outline-secondary" href="<?= xander_institution_h(xander_institution_scholarship_apply_url((int) $s['id'])) ?>" target="_blank">Apply link</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
