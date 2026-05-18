<?php
/** @var string $activeSection @var array $applications @var array|null $viewApp @var array $statusLabels @var array $viewAppDocs */
$viewId = (int) ($_GET['id'] ?? 0);
$viewAppDocs = $viewAppDocs ?? [];
?>
<div class="page-head mb-4">
  <h1>Applications</h1>
  <p class="page-sub text-muted mb-0">Review and manage student scholarship applications</p>
</div>

<?php if (!empty($viewApp)): ?>
<div class="panel mb-4">
  <div class="d-flex justify-content-between align-items-start mb-4 pb-3" style="border-bottom:1px solid #f1f5f9;">
    <div class="d-flex align-items-center gap-3">
      <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#1e40af;display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:800;flex-shrink:0;">
        <?= xander_institution_h(strtoupper(substr((string)($viewApp['applicant_name'] ?? 'A'), 0, 1))) ?>
      </div>
      <div>
        <h3 class="h5 fw-bold mb-1"><?= xander_institution_h((string) ($viewApp['applicant_name'] ?? '')) ?></h3>
        <p class="text-muted small mb-0"><i class="fas fa-award me-1"></i> <?= xander_institution_h((string) ($viewApp['scholarship_title'] ?? '')) ?></p>
        <span class="badge-status status-<?= xander_institution_h((string) ($viewApp['status'] ?? 'new')) ?> mt-2 d-inline-block"><?= xander_institution_h($statusLabels[(string) ($viewApp['status'] ?? '')] ?? '') ?></span>
      </div>
    </div>
    <a href="index.php?tab=applications<?= $activeSection !== '' ? '&section=' . urlencode($activeSection) : '' ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-weight:600;"><i class="fas fa-arrow-left me-1"></i> Back</a>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-6"><strong>Email:</strong> <?= xander_institution_h((string) ($viewApp['applicant_email'] ?? '')) ?></div>
    <div class="col-md-6"><strong>Phone:</strong> <?= xander_institution_h((string) ($viewApp['applicant_phone'] ?? '—')) ?></div>
    <div class="col-md-6"><strong>Nationality:</strong> <?= xander_institution_h((string) ($viewApp['nationality'] ?? '—')) ?></div>
    <div class="col-md-6"><strong>Education:</strong> <?= xander_institution_h((string) ($viewApp['education_level'] ?? '—')) ?></div>
    <div class="col-md-6"><strong>Program:</strong> <?= xander_institution_h((string) ($viewApp['intended_program'] ?? '—')) ?></div>
    <div class="col-md-6"><strong>Field of study:</strong> <?= xander_institution_h((string) ($viewApp['field_of_study'] ?? '—')) ?></div>
    <div class="col-md-6"><strong>GPA / grade:</strong> <?= xander_institution_h((string) ($viewApp['gpa_or_grade'] ?? '—')) ?></div>
    <div class="col-12"><strong>Address:</strong> <?= xander_institution_h((string) ($viewApp['address'] ?? '—')) ?></div>
    <div class="col-12"><strong>Statement:</strong><p class="mb-0 mt-1"><?= nl2br(xander_institution_h((string) ($viewApp['statement'] ?? ''))) ?></p></div>
  </div>
  <?php if (!empty($viewAppDocs)): ?>
  <div class="border-top pt-3 mb-3">
    <h4 class="h6 fw-bold mb-2"><i class="fas fa-paperclip me-1"></i> Uploaded documents</h4>
    <ul class="list-group list-group-flush">
      <?php foreach ($viewAppDocs as $doc): ?>
      <li class="list-group-item d-flex justify-content-between align-items-center px-0">
        <span>
          <strong><?= xander_institution_h((string) ($doc['label'] ?? $doc['document_type'] ?? 'Document')) ?></strong>
          <br><small class="text-muted"><?= xander_institution_h((string) ($doc['original_name'] ?? '')) ?></small>
        </span>
        <a class="btn btn-sm btn-outline-primary" href="download-application-doc.php?id=<?= (int) ($doc['id'] ?? 0) ?>">
          <i class="fas fa-download"></i> Download
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>
  <form method="post" class="row g-3 border-top pt-3">
    <?= pcvc_csrf_input() ?>
    <input type="hidden" name="action" value="update_application">
    <input type="hidden" name="application_id" value="<?= (int) ($viewApp['id'] ?? 0) ?>">
    <div class="col-md-4">
      <label class="form-label">Status</label>
      <select class="form-select" name="status">
        <?php foreach ($statusLabels as $k => $lbl): ?>
        <option value="<?= xander_institution_h($k) ?>" <?= (($viewApp['status'] ?? '') === $k) ? 'selected' : '' ?>><?= xander_institution_h($lbl) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12">
      <label class="form-label">Internal review notes</label>
      <textarea class="form-control" name="internal_notes" rows="3"><?= xander_institution_h((string) ($viewApp['internal_notes'] ?? '')) ?></textarea>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-save">Save review</button>
    </div>
  </form>
</div>
<?php else: ?>
<div class="data-table-wrap panel p-0 overflow-hidden">
  <table class="table table-hover mb-0 inst-table">
    <thead><tr><th>Applicant</th><th>Scholarship</th><th>Date</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($applications)): ?>
      <tr>
        <td colspan="5" class="text-center py-5">
          <div style="font-size:2.5rem; color:#cbd5e1;"><i class="fas fa-inbox"></i></div>
          <h4 class="h6 fw-bold mt-2 mb-1">No applications here</h4>
          <p class="text-muted small mb-0">Applications matching this filter will appear here.</p>
        </td>
      </tr>
      <?php else: ?>
      <?php foreach ($applications as $a): ?>
      <tr>
        <td><strong><?= xander_institution_h((string) ($a['applicant_name'] ?? '')) ?></strong><br><small><?= xander_institution_h((string) ($a['applicant_email'] ?? '')) ?></small></td>
        <td><?= xander_institution_h((string) ($a['scholarship_title'] ?? '')) ?></td>
        <td><?= xander_institution_h(date('M j, Y', strtotime((string) ($a['created_at'] ?? 'now')))) ?></td>
        <td><span class="badge-status status-<?= xander_institution_h((string) ($a['status'] ?? 'new')) ?>"><?= xander_institution_h($statusLabels[(string) ($a['status'] ?? '')] ?? '') ?></span></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-primary" href="index.php?tab=applications&id=<?= (int) $a['id'] ?><?= $activeSection !== '' ? '&section=' . urlencode($activeSection) : '' ?>">Review</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
