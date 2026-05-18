<?php
declare(strict_types=1);

/** @var string $contractToken */
/** @var string $clientName */
/** @var string $clientSignedDate */
/** @var string $studentSignatureImg */

require_once __DIR__ . '/contract_pdf_helpers.php';
require_once __DIR__ . '/burundi_contract_assets.php';

$clientName = trim((string) ($clientName ?? ''));
$clientSignedDate = xander_pdf_date($clientSignedDate ?? '');
$sigImg = (string) ($studentSignatureImg ?? '');
$burundiAssets = xander_burundi_contract_paths();
$xanderSigB64 = xander_burundi_img_src($burundiAssets['signature'], true);
$xanderSigDate = date('F j, Y');
?>
<h3 class="bc-h3">19. SIGNATURES</h3>
<table class="pdf-sig-table" cellpadding="0" cellspacing="0">
<tr>
  <td class="pdf-sig-cell">
    <p class="pdf-sig-title">For Xander Tech LLC</p>
    <p>Name: Jean de Dieu Hakizimana</p>
    <p>Title: Owner / Managing Director</p>
    <p>Stamp/Signature:</p>
    <?php if ($xanderSigB64 !== ''): ?>
    <img src="<?= $xanderSigB64 ?>" alt="Authorized signature" class="pdf-sig-img pdf-xander-sig-img">
    <?php else: ?>
    <p class="pdf-sig-line">_________________________</p>
    <?php endif; ?>
    <p>Date: <?= xander_pdf_esc($xanderSigDate) ?></p>
  </td>
  <td class="pdf-sig-cell">
    <p class="pdf-sig-title">For Recruitment Partner / Exclusive Agent for Burundi</p>
    <p>Name: Jean Paul Manirakiza</p>
    <p>Company: HEERA 10 (SURL)</p>
    <p>Signature: _________________________</p>
    <p>Date: _________________________</p>
  </td>
</tr>
<tr>
  <td class="pdf-sig-cell pdf-sig-client">
    <p class="pdf-sig-title">For Client</p>
    <p><strong>Full Name:</strong> <?= xander_pdf_esc($clientName !== '' ? $clientName : '____________') ?></p>
    <p><strong>Signature:</strong></p>
    <?php if ($sigImg !== '' && str_starts_with($sigImg, 'data:image')): ?>
    <img src="<?= $sigImg ?>" alt="Client signature" class="pdf-sig-img">
    <?php else: ?>
    <p class="pdf-sig-line">_________________________</p>
    <?php endif; ?>
    <p><strong>Date signed:</strong> <?= xander_pdf_esc($clientSignedDate) ?></p>
  </td>
  <td class="pdf-sig-cell">
    <p class="pdf-sig-title">For Notary</p>
    <p>Full Name: _________________________</p>
    <p>Signature: _________________________</p>
    <p>Date: _________________________</p>
  </td>
</tr>
</table>
<p class="pdf-contract-ref">Contract Reference: <?= xander_pdf_esc($contractToken ?? '') ?></p>
