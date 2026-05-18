<?php
/** @var array $account @var string $contactName @var string $accountEmail */
?>
<div class="page-head mb-4">
  <h1><i class="fas fa-user-gear me-2" style="color:var(--gold);"></i>Account Settings</h1>
  <p class="page-sub text-muted mb-0">Manage your institution portal login and contact details</p>
</div>

<div class="panel mb-4">
  <div class="panel-head">
    <div class="icon profile"><i class="fas fa-id-card"></i></div>
    <div>
      <h3 class="h5 fw-bold mb-1">Contact Information</h3>
      <p class="text-muted small mb-0">This is how your institution will be reached for application updates</p>
    </div>
  </div>
  <form method="post" class="row g-3">
    <?= pcvc_csrf_input() ?>
    <input type="hidden" name="action" value="update_account">
    <div class="col-md-6">
      <label class="form-label">Full name *</label>
      <input class="form-control" name="contact_name" required value="<?= xander_institution_h((string) ($account['contact_name'] ?? $contactName)) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Job title</label>
      <input class="form-control" name="contact_title" value="<?= xander_institution_h((string) ($account['contact_title'] ?? '')) ?>" placeholder="e.g. Admissions Director">
    </div>
    <div class="col-md-6">
      <label class="form-label">Email *</label>
      <input class="form-control" type="email" name="email" required value="<?= xander_institution_h($accountEmail) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Phone</label>
      <input class="form-control" name="phone" value="<?= xander_institution_h((string) ($account['phone'] ?? '')) ?>" placeholder="+1 555 123 4567">
    </div>
    <div class="col-12 d-flex gap-2 align-items-center pt-2">
      <button type="submit" class="btn btn-save"><i class="fas fa-save me-1"></i> Save profile</button>
      <span class="text-muted small"><i class="fas fa-shield-halved me-1"></i> Your changes are encrypted and saved securely.</span>
    </div>
  </form>
</div>

<div class="panel">
  <div class="panel-head">
    <div class="icon" style="background:linear-gradient(135deg,#fee2e2,#fecaca);color:#991b1b;width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;"><i class="fas fa-key"></i></div>
    <div>
      <h3 class="h5 fw-bold mb-1">Security & Password</h3>
      <p class="text-muted small mb-0">Use at least 8 characters with a mix of letters, numbers, and symbols.</p>
    </div>
  </div>
  <form method="post" class="row g-3">
    <?= pcvc_csrf_input() ?>
    <input type="hidden" name="action" value="change_password">
    <div class="col-md-4">
      <label class="form-label">Current password</label>
      <input class="form-control" type="password" name="current_password" required placeholder="••••••••">
    </div>
    <div class="col-md-4">
      <label class="form-label">New password</label>
      <input class="form-control" type="password" name="new_password" required minlength="8" placeholder="At least 8 characters">
    </div>
    <div class="col-md-4">
      <label class="form-label">Confirm new password</label>
      <input class="form-control" type="password" name="new_password_confirm" required minlength="8" placeholder="Re-enter new password">
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-outline-primary" style="border-radius:12px;padding:11px 22px;font-weight:700;">
        <i class="fas fa-rotate me-1"></i> Update password
      </button>
    </div>
  </form>
</div>
