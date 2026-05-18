<?php
/** @var array $account @var string $contactName @var string $accountEmail */
?>
<div class="page-head mb-4">
  <h1>Account settings</h1>
  <p class="page-sub text-muted mb-0">Manage your institution portal login</p>
</div>
<div class="panel mb-4">
  <form method="post" class="row g-3">
    <?= pcvc_csrf_input() ?>
    <input type="hidden" name="action" value="update_account">
    <div class="col-md-6">
      <label class="form-label">Full name *</label>
      <input class="form-control" name="contact_name" required value="<?= xander_institution_h((string) ($account['contact_name'] ?? $contactName)) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Job title</label>
      <input class="form-control" name="contact_title" value="<?= xander_institution_h((string) ($account['contact_title'] ?? '')) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Email *</label>
      <input class="form-control" type="email" name="email" required value="<?= xander_institution_h($accountEmail) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Phone</label>
      <input class="form-control" name="phone" value="<?= xander_institution_h((string) ($account['phone'] ?? '')) ?>">
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-save">Save profile</button>
    </div>
  </form>
</div>
<div class="panel">
  <h4 class="h6 fw-bold mb-3">Change password</h4>
  <form method="post" class="row g-3">
    <?= pcvc_csrf_input() ?>
    <input type="hidden" name="action" value="change_password">
    <div class="col-md-4">
      <input class="form-control" type="password" name="current_password" required placeholder="Current password">
    </div>
    <div class="col-md-4">
      <input class="form-control" type="password" name="new_password" required minlength="8" placeholder="New password">
    </div>
    <div class="col-md-4">
      <input class="form-control" type="password" name="new_password_confirm" required minlength="8" placeholder="Confirm password">
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-outline-primary">Update password</button>
    </div>
  </form>
</div>
