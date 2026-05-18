<?php
/** @var bool $isSigned */
/** @var string|null $studentSignatureImg */
/** @var string $contractToken */
/** @var string $sigStudentName */
/** @var string $sigSignedDate */
require_once __DIR__ . '/burundi_contract_assets.php';

$sigStudentName = trim((string) ($sigStudentName ?? ''));
$sigSignedDate = trim((string) ($sigSignedDate ?? ''));
$burundiAssets = xander_burundi_contract_paths();
$xanderSigSrc = xander_burundi_img_src($burundiAssets['signature'], false);
$xanderSigDate = date('F j, Y');
?>
<h3 class="bc-h3">19. SIGNATURES</h3>
<div class="bc-sig-grid">
  <div class="bc-sig-block">
    <p><strong>For Xander Tech LLC</strong></p>
    <p>Name: Jean de Dieu Hakizimana</p>
    <p>Title: Owner / Managing Director</p>
    <p>Stamp/Signature:</p>
    <div class="bc-xander-sig-wrap">
      <?php if ($xanderSigSrc !== ''): ?>
      <img src="<?= htmlspecialchars($xanderSigSrc, ENT_QUOTES, 'UTF-8') ?>" alt="Authorized signature" class="bc-xander-sig-img">
      <?php endif; ?>
    </div>
    <p>Date: <?= htmlspecialchars($xanderSigDate, ENT_QUOTES, 'UTF-8') ?></p>
  </div>
  <div class="bc-sig-block">
    <p><strong>For Recruitment Partner / Exclusive Agent for Burundi</strong></p>
    <p>Name: Jean Paul Manirakiza</p>
    <p>Company: HEERA 10 (SURL)</p>
    <p>Stamp/Signature: ______________________________</p>
    <p>Date: _________________________________</p>
  </div>
  <div class="bc-sig-block bc-sig-client">
    <p><strong>For Client</strong></p>
    <p>Full Name: <input type="text" id="sig_student_name" class="bc-inline-input" value="<?= htmlspecialchars($sigStudentName, ENT_QUOTES, 'UTF-8') ?>" <?= $isSigned ? 'readonly' : '' ?>></p>
    <p>Signature:</p>
    <div class="bc-sig-canvas-wrap">
      <?php if ($isSigned && !empty($studentSignatureImg)): ?>
        <img src="<?= htmlspecialchars($studentSignatureImg, ENT_QUOTES, 'UTF-8') ?>" alt="Signature" class="bc-sig-img">
      <?php else: ?>
        <canvas class="signature-canvas" aria-label="Draw your signature"></canvas>
      <?php endif; ?>
    </div>
    <p>Date: <input type="date" id="sig_signed_date" class="bc-inline-input" value="<?= htmlspecialchars($sigSignedDate, ENT_QUOTES, 'UTF-8') ?>" <?= $isSigned ? 'readonly' : '' ?>></p>
    <?php if (!$isSigned): ?>
    <div class="bc-sig-actions">
      <button type="button" id="clearSignature">Clear</button>
      <button type="button" id="signContract">Sign &amp; Submit</button>
      <input type="hidden" id="signatureData">
    </div>
    <div id="signatureProgress" class="bc-progress" style="display:none">
      <div class="bc-progress-bar"><div id="signatureProgressBar"></div></div>
      <div id="signatureProgressText">Submitting signature…</div>
    </div>
    <?php endif; ?>
  </div>
  <div class="bc-sig-block">
    <p><strong>For Notary</strong></p>
    <p>Full Name: ______________________________</p>
    <p>Stamp/Signature: ______________________________</p>
    <p>Date: _________________________________</p>
  </div>
</div>
<p class="footer-ref">Contract Reference: <?= htmlspecialchars($contractToken, ENT_QUOTES, 'UTF-8') ?></p>
