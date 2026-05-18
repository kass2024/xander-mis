<?php
/** @var string $activeSection @var array $programs @var array|null $editProgram @var array $typeLabels */
$isForm = ($activeSection === 'create' || $activeSection === 'edit' || !empty($editProgram));
$p = $editProgram ?? [];
$pId = (int) ($p['id'] ?? 0);
?>
<div class="page-head d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
  <div>
    <h1>Programs</h1>
    <p class="page-sub text-muted mb-0">Manage academic programs offered by your institution</p>
  </div>
  <?php if (!$isForm): ?>
  <a href="index.php?tab=programs&section=create" class="btn btn-save"><i class="fas fa-plus me-1"></i> Add program</a>
  <?php endif; ?>
</div>

<?php if ($isForm): ?>
<div class="panel">
  <div class="panel-head">
    <div class="icon loan"><i class="fas fa-graduation-cap"></i></div>
    <div>
      <h3 class="h5 fw-bold mb-1"><?= $pId ? 'Edit program' : 'Add a new program' ?></h3>
      <p class="text-muted small mb-0">Programs help students discover what your institution offers.</p>
    </div>
  </div>
  <form method="post" class="row g-3">
    <?= pcvc_csrf_input() ?>
    <input type="hidden" name="action" value="save_program">
    <input type="hidden" name="program_id" value="<?= $pId ?>">
    <div class="col-md-8">
      <label class="form-label">Program title *</label>
      <input class="form-control" name="title" required value="<?= xander_institution_h((string) ($p['title'] ?? '')) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Program type *</label>
      <select class="form-select" name="program_type" required>
        <?php foreach ($typeLabels as $k => $lbl): ?>
        <option value="<?= xander_institution_h($k) ?>" <?= (($p['program_type'] ?? '') === $k) ? 'selected' : '' ?>><?= xander_institution_h($lbl) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12">
      <label class="form-label">Overview</label>
      <textarea class="form-control" name="summary" rows="3"><?= xander_institution_h((string) ($p['summary'] ?? '')) ?></textarea>
    </div>
    <div class="col-md-4">
      <label class="form-label">Tuition information</label>
      <input class="form-control" name="tuition_notes" value="<?= xander_institution_h((string) ($p['tuition_notes'] ?? '')) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Duration</label>
      <input class="form-control" name="duration" value="<?= xander_institution_h((string) ($p['duration'] ?? '')) ?>" placeholder="e.g. 4 years">
    </div>
    <div class="col-md-4">
      <label class="form-label">Intake dates</label>
      <input class="form-control" name="intake_dates" value="<?= xander_institution_h((string) ($p['intake_dates'] ?? '')) ?>" placeholder="Fall 2026, Spring 2027">
    </div>
    <div class="col-md-6">
      <label class="form-label">Program requirements</label>
      <textarea class="form-control" name="requirements" rows="3"><?= xander_institution_h((string) ($p['requirements'] ?? '')) ?></textarea>
    </div>
    <div class="col-md-6">
      <label class="form-label">Language requirements</label>
      <textarea class="form-control" name="language_requirements" rows="3"><?= xander_institution_h((string) ($p['language_requirements'] ?? '')) ?></textarea>
    </div>
    <div class="col-md-4">
      <label class="form-label">Status</label>
      <select class="form-select" name="status">
        <option value="active" <?= (($p['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
        <option value="draft" <?= (($p['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Draft</option>
      </select>
    </div>
    <div class="col-12 d-flex gap-2 pt-2">
      <button type="submit" class="btn btn-save"><i class="fas fa-save me-1"></i> Save program</button>
      <a href="index.php?tab=programs" class="btn btn-outline-secondary" style="border-radius:12px;padding:11px 22px;font-weight:600;">Cancel</a>
    </div>
  </form>
</div>
<?php else: ?>
<div class="data-table-wrap panel p-0 overflow-hidden">
  <table class="table table-hover mb-0 inst-table">
    <thead><tr><th>Program</th><th>Type</th><th>Duration</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($programs)): ?>
      <tr>
        <td colspan="5" class="text-center py-5">
          <div style="font-size:2.5rem; color:#cbd5e1;"><i class="fas fa-graduation-cap"></i></div>
          <h4 class="h6 fw-bold mt-2 mb-1">No programs yet</h4>
          <p class="text-muted small mb-3">Add your first academic program to start receiving applications.</p>
          <a href="index.php?tab=programs&section=create" class="btn btn-save"><i class="fas fa-plus me-1"></i> Add your first program</a>
        </td>
      </tr>
      <?php else: ?>
      <?php foreach ($programs as $row): ?>
      <tr>
        <td><strong><?= xander_institution_h((string) ($row['title'] ?? '')) ?></strong></td>
        <td><?= xander_institution_h($typeLabels[(string) ($row['program_type'] ?? '')] ?? (string) ($row['program_type'] ?? '')) ?></td>
        <td><?= xander_institution_h((string) ($row['duration'] ?? '—')) ?></td>
        <td><?= xander_institution_h((string) ($row['status'] ?? '')) ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-primary" href="index.php?tab=programs&section=edit&id=<?= (int) $row['id'] ?>">Edit</a>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete this program?');">
            <?= pcvc_csrf_input() ?>
            <input type="hidden" name="action" value="delete_program">
            <input type="hidden" name="program_id" value="<?= (int) $row['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
