<?php
declare(strict_types=1);

require_once __DIR__ . '/service_contract_rules.php';
require_once dirname(__DIR__) . '/contracts/service_clause_placeholders.php';
require_once __DIR__ . '/contract_fee_notice.php';

function xander_sc_e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Factual service block. Legal articles stay in the master contract.
 *
 * @param array<string, mixed> $contract hydrated row
 */
function xander_sc_render_service_facts(array $contract): void
{
    $service = (string) ($contract['service_type'] ?? '');
    $offering = is_array($contract['offering'] ?? null) ? $contract['offering'] : [];
    $country = is_array($contract['country'] ?? null) ? $contract['country'] : [];
    $facts = xander_sc_renderer_facts($service, $offering);
    $label = xander_sc_service_types()[$service] ?? $service;
    $created = (string) ($contract['created_at'] ?? '');
    $createdLabel = $created !== '' ? date('F j, Y', strtotime($created)) : '';
    ?>
<h2>Service details</h2>
<p><strong>Contract reference:</strong> <?= xander_sc_e($contract['reference'] ?? '') ?></p>
<p><strong>Contract date:</strong> <?= xander_sc_e($createdLabel) ?></p>
<p><strong>Service:</strong> <?= xander_sc_e($label) ?></p>
<p><strong>Destination country:</strong> <?= xander_sc_e($country['name'] ?? $contract['country_name'] ?? '') ?></p>
<?php if ($facts['show_study']): ?>
<p><strong>School:</strong> <?= xander_sc_e($offering['school_name'] ?? '') ?></p>
<p><strong>Program:</strong> <?= xander_sc_e($offering['program_name'] ?? '') ?></p>
<?php if (trim((string) ($offering['level'] ?? '')) !== ''): ?>
<p><strong>Study level:</strong> <?= xander_sc_e($offering['level']) ?></p>
<?php endif; ?>
<?php if (trim((string) ($offering['duration'] ?? '')) !== ''): ?>
<p><strong>Duration:</strong> <?= xander_sc_e($offering['duration']) ?></p>
<?php endif; ?>
<?php if (trim((string) ($offering['city'] ?? '')) !== ''): ?>
<p><strong>Campus / location:</strong> <?= xander_sc_e($offering['city']) ?></p>
<?php endif; ?>
<?php endif; ?>
<?php if ($facts['show_work']): ?>
<p><strong>Job:</strong> <?= xander_sc_e($offering['job_title'] ?? $offering['title'] ?? '') ?></p>
<?php if (trim((string) ($offering['employer'] ?? '')) !== ''): ?>
<p><strong>Employer:</strong> <?= xander_sc_e($offering['employer']) ?></p>
<?php endif; ?>
<?php if (trim((string) ($offering['category'] ?? '')) !== ''): ?>
<p><strong>Category:</strong> <?= xander_sc_e($offering['category']) ?></p>
<?php endif; ?>
<?php if (trim((string) ($offering['description'] ?? '')) !== ''): ?>
<p><strong>Description:</strong> <?= xander_sc_e($offering['description']) ?></p>
<?php endif; ?>
<?php endif; ?>
<?php if ($facts['show_visit']): ?>
<p><strong>Visit package:</strong> <?= xander_sc_e($offering['package_name'] ?? $offering['title'] ?? '') ?></p>
<?php if (trim((string) ($offering['description'] ?? '')) !== ''): ?>
<p><strong>Package details:</strong> <?= xander_sc_e($offering['description']) ?></p>
<?php endif; ?>
<?php endif; ?>
<?php
    $clauses = xander_service_specific_clauses();
    $extra = trim((string) ($clauses[$service] ?? ''));
    if ($extra !== '') {
        echo '<p>' . xander_sc_e($extra) . '</p>';
    }
}

/** @param array<string, mixed> $contract */
function xander_sc_render_locked_fee(array $contract): void
{
    $fee = is_array($contract['fee'] ?? null) ? $contract['fee'] : [];
    $label = (string) ($fee['fee_label'] ?? '');
    $formatted = (string) ($fee['formatted'] ?? '');
    $description = trim((string) ($fee['description'] ?? ''));
    $remainingFormatted = trim((string) ($fee['remaining_formatted'] ?? ''));
    if ($remainingFormatted === '' && is_numeric($fee['remaining_amount'] ?? null) && (float) $fee['remaining_amount'] > 0) {
        $remainingFormatted = xander_sc_format_money((float) $fee['remaining_amount'], (string) ($fee['remaining_currency'] ?? ''));
    }
    ?>
<div class="bc-fee-wrap" id="bcFeeWrap" data-readonly="1">
<?php renderContractFeeImportantNotice(false); ?>
<div class="bc-fee-intro-block">
<p class="bc-fee-intro">
All fees cover professional consulting, documentation support, administrative processing, and
coordination services. Government fees, embassy charges, biometric fees, tuition deposits, courier fees,
legal fees, and third-party costs are paid separately and are non-refundable.
</p>
<p class="bc-fee-intro">
The Client shall select <strong>one (1)</strong> applicable service package only.
Fees apply exclusively to the selected package.
</p>
</div>
<div class="bc-selected-pkg-summary"><strong>Agreed fee:</strong> <?= xander_sc_e($label) ?> — <?= xander_sc_e($formatted) ?> · <strong>Remaining balance (mandatory):</strong> <?= xander_sc_e($remainingFormatted !== '' ? $remainingFormatted : 'Required') ?></div>
<div class="package-item is-selected">
    <div class="package-item-head">
        <span class="package-label-text"><?= xander_sc_e($label) ?></span>
        <span class="package-price-badge"><?= xander_sc_e($formatted) ?></span>
    </div>
    <div class="package-details" style="display:block">
        <ul class="package-lines">
            <li>Fee type: <?= xander_sc_e($label) ?></li>
            <li>Amount: <?= xander_sc_e($formatted) ?></li>
            <li><strong>Remaining balance (mandatory):</strong> <?= xander_sc_e($remainingFormatted !== '' ? $remainingFormatted : 'Required') ?></li>
            <?php if ($description !== ''): ?>
            <li>Description: <?= xander_sc_e($description) ?></li>
            <?php endif; ?>
        </ul>
    </div>
</div>
<?php renderContractFeeClosingNotices(false); ?>
<input type="hidden" id="selected_package_code" value="locked">
<input type="hidden" id="selected_package_label" value="<?= xander_sc_e($label . ' ' . $formatted) ?>">
</div>
<?php
}
