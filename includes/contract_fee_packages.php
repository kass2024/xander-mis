<?php
declare(strict_types=1);

require_once __DIR__ . '/contract_fee_repository.php';

/**
 * Render Section 5 fee packages as selectable radios (standard + Burundi contracts).
 *
 * @param bool $isSigned
 * @param string $selectedCode e.g. p501
 */
function renderContractFeePackagesSection(bool $isSigned, string $selectedCode = ''): void
{
    $disabled = $isSigned ? 'disabled' : '';
    $selectedCode = trim($selectedCode);

    $sections = [
        'study'  => ['icon' => '🎓', 'label' => 'Study Services'],
        'visit'  => ['icon' => '🌍', 'label' => 'Visit Visa Services'],
        'credit' => ['icon' => '🔁', 'label' => 'Credit Transfer Services'],
        'asia'   => ['icon' => '🌏', 'label' => 'Asia Visit Visa Services (65% upfront / 35% later)'],
        'job'    => ['icon' => '💼', 'label' => 'Job Seeker Services'],
    ];

    $mk = static function (array $pkg) use ($disabled, $selectedCode): void {
        $id = $pkg['contract_code'];
        $checked = ($selectedCode === $id) ? 'checked' : '';
        $label = $pkg['label'];
        $details = [];
        foreach ($pkg['lines'] as $line) {
            $details[] = '• ' . $line;
        }
        $detailsHtml = implode('<br>', $details);

        echo '<div class="package-item">';
        echo '<label class="package-label">';
        echo '<input type="radio" name="package" value="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" ';
        echo 'onclick="showPkg(\'' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '\')" ';
        echo $disabled . ' ' . $checked . ' required> ';
        echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        echo '</label>';
        echo '<div id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" class="package-details" style="display:' . ($checked ? 'block' : 'none') . '">';
        echo $detailsHtml;
        echo '</div></div>';
    };

    ?>
<p class="bc-fee-intro">
The Client shall select <strong>one (1)</strong> applicable service package only.
Fees apply exclusively to the selected package.
</p>
<p>
All fees cover professional consulting, documentation support, administrative processing, and coordination services.
<strong>Government fees, embassy charges, biometric fees, tuition deposits, courier fees, legal fees, and third-party costs are paid separately and are non-refundable.</strong>
</p>
<?php

    $catalog = xander_contract_fee_catalog_list();
    $first = true;
    foreach ($sections as $key => $meta) {
        $group = array_filter($catalog, static fn(array $p): bool => $p['section'] === $key);
        if ($group === []) {
            continue;
        }
        if (!$first) {
            echo '<p class="bc-fee-divider">________________________________________</p>';
        }
        $first = false;
        echo '<p class="bc-fee-head">' . $meta['icon'] . ' ' . htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') . '</p>';
        foreach ($group as $pkg) {
            $mk($pkg);
        }
    }
    ?>
<p>⚠ Failure to pay may result in suspension or termination of services.</p>
<input type="hidden" id="selected_package_code" value="<?= htmlspecialchars($selectedCode, ENT_QUOTES, 'UTF-8') ?>">
<?php
    if ($isSigned && $selectedCode !== '') {
        $pkg = getPackageDetails($selectedCode);
        if ($pkg) {
            echo '<div class="bc-selected-pkg-summary"><strong>Selected package:</strong> ' . htmlspecialchars($pkg['title'], ENT_QUOTES, 'UTF-8') . '</div>';
        }
    }
}

/**
 * Section 5 for signed PDFs — selected package only.
 */
function renderContractFeePackagesPdf(string $code): void
{
    $pkg = getPackageDetails($code);
    if (!$pkg) {
        echo '<p><em>No fee package selected.</em></p>';
        return;
    }
    ?>
<p>
All fees cover professional consulting, documentation support, administrative processing, and coordination services.
<strong>Government fees, embassy charges, biometric fees, tuition deposits, courier fees, legal fees, and third-party costs are paid separately and are non-refundable.</strong>
</p>
<p>The Client selected the following service package:</p>
<p><strong><?= htmlspecialchars($pkg['title'], ENT_QUOTES, 'UTF-8') ?></strong></p>
<ul class="bc-list">
<?php foreach ($pkg['lines'] as $line): ?>
<li><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></li>
<?php endforeach; ?>
</ul>
<?php if (!empty($pkg['total'])): ?>
<p><strong>Total Package Fee: <?= htmlspecialchars($pkg['total'], ENT_QUOTES, 'UTF-8') ?></strong></p>
<?php endif; ?>
<p>Failure to pay may result in suspension or termination of services.</p>
<?php
}
