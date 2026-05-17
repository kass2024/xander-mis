<?php
declare(strict_types=1);

/** @var array<string,string> $docLabels */
/** @var array<string,mixed> $prefill */
/** @var bool $readonly */
/** @var bool $asyncDocs */

$readonly = !empty($readonly);
$prefill = $prefill ?? [];
$docLabels = $docLabels ?? [];
$asyncDocs = !empty($asyncDocs);

if (!empty($asyncDocs)): ?>
<p class="small text-muted mb-3">Files upload as soon as you pick them — submit is faster.</p>
<?php endif;

foreach ($docLabels as $key => $label):
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
