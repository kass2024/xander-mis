<?php
/** @var int $universityId @var array $instProfile */
$instProfile = $instProfile ?? xander_institution_default_profile($universityId);
?>
<div class="page-head d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
  <div>
    <h1>Homepage listing</h1>
    <p class="page-sub text-muted mb-0">Publish scholarship and loan opportunities on the public website</p>
  </div>
</div>

<div class="panel mb-4">
  <form method="post" class="row g-3">
    <?= pcvc_csrf_input() ?>
    <input type="hidden" name="action" value="save_institution_profile">
    <div class="col-12">
      <h3 class="h6 fw-bold mb-1"><i class="fas fa-globe me-1 text-primary"></i> Visibility</h3>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="homepage_published" value="1" id="hpPub"
          <?= !empty($instProfile['homepage_published']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="hpPub">Show our institution opportunities on the public homepage</label>
      </div>
      <p class="text-muted small mb-0 mt-1">Scholarships marked “Publish on homepage” in the Scholarships tab appear automatically. Use the loan section below for education loan programs.</p>
    </div>

    <div class="col-12 border-top pt-3 mt-1">
      <h3 class="h6 fw-bold"><i class="fas fa-hand-holding-dollar me-1 text-success"></i> Education loan program</h3>
    </div>
    <div class="col-md-8">
      <label class="form-label">Loan program name *</label>
      <input class="form-control" name="loan_program_name" required
        value="<?= xander_institution_h((string) ($instProfile['loan_program_name'] ?? '')) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Partner / bank name</label>
      <input class="form-control" name="loan_institution_name"
        value="<?= xander_institution_h((string) ($instProfile['loan_institution_name'] ?? '')) ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Summary *</label>
      <textarea class="form-control" name="loan_summary" rows="3" required><?= xander_institution_h((string) ($instProfile['loan_summary'] ?? '')) ?></textarea>
    </div>
    <div class="col-md-6">
      <label class="form-label">Coverage</label>
      <input class="form-control" name="loan_coverage" placeholder="e.g. Up to 100% tuition"
        value="<?= xander_institution_h((string) ($instProfile['loan_coverage'] ?? '')) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Rates / notes</label>
      <input class="form-control" name="loan_rates_notes"
        value="<?= xander_institution_h((string) ($instProfile['loan_rates_notes'] ?? '')) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Eligibility</label>
      <textarea class="form-control" name="loan_eligibility" rows="2"><?= xander_institution_h((string) ($instProfile['loan_eligibility'] ?? '')) ?></textarea>
    </div>
    <div class="col-md-6">
      <label class="form-label">Contact email</label>
      <input class="form-control" type="email" name="loan_contact_email"
        value="<?= xander_institution_h((string) ($instProfile['loan_contact_email'] ?? '')) ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Apply URL (optional)</label>
      <input class="form-control" name="loan_apply_url" placeholder="https://"
        value="<?= xander_institution_h((string) ($instProfile['loan_apply_url'] ?? '')) ?>">
    </div>

    <div class="col-12 d-flex gap-2 pt-2 flex-wrap">
      <button type="submit" class="btn btn-save"><i class="fas fa-upload me-1"></i> Publish to website</button>
      <a href="../index.php#opportunities" target="_blank" rel="noopener" class="btn btn-outline-primary">Preview homepage</a>
    </div>
  </form>
</div>
