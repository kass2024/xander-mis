<?php

declare(strict_types=1);



require_once __DIR__ . '/contract_fee_repository.php';

require_once __DIR__ . '/contract_fee_notice.php';



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

        'study'  => ['icon' => '🎓', 'label' => 'Study Services (Self-Sponsored)'],

        'credit' => ['icon' => '🔁', 'label' => 'Credit Transfer Services'],

        'visit'  => ['icon' => '🌍', 'label' => 'Visit Visa Services Worldwide'],

        'job'    => ['icon' => '💼', 'label' => 'Job Seeker Services'],

    ];



    $extractPrice = static function (string $label): string {

        if (preg_match('/–\s*(€[\d,]+)\s*$/u', $label, $m)) {

            return $m[1];

        }

        if (preg_match('/-\s*(€[\d,]+)\s*$/u', $label, $m)) {

            return $m[1];

        }

        return '';

    };



    $mk = static function (array $pkg) use ($disabled, $selectedCode, $extractPrice, $isSigned): void {

        $id = $pkg['contract_code'];
        $inputId = 'pkg_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $id);

        $checked = ($selectedCode === $id) ? 'checked' : '';

        $label = $pkg['label'];

        $price = $extractPrice($label);

        $titleOnly = $price !== '' ? preg_replace('/\s*[–-]\s*€[\d,]+\s*$/u', '', $label) : $label;

        $tag = $isSigned ? 'div' : 'label';

        echo '<' . $tag . ' class="package-item" data-package-code="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" data-package-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">';

        echo '<div class="package-item-head">';

        echo '<input type="radio" class="package-radio" id="' . htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') . '" name="package" value="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" ';

        echo 'data-package-code="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" ';

        echo 'data-package-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '" ';

        echo $disabled . ' ' . $checked . '> ';

        echo '<span class="package-label-text">' . htmlspecialchars($titleOnly, ENT_QUOTES, 'UTF-8') . '</span>';

        if ($price !== '') {

            echo '<span class="package-price-badge">' . htmlspecialchars($price, ENT_QUOTES, 'UTF-8') . '</span>';

        }

        echo '</div>';

        echo '<div id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" class="package-details" style="display:' . ($checked ? 'block' : 'none') . '">';

        echo '<ul class="package-lines">';

        if ($id === 'p544') {

            echo '<li>Additional Fee: €250</li>';
            echo '<li>Payable upfront</li>';
            echo '<li>Non-refundable</li>';

        } else {

            foreach ($pkg['lines'] as $line) {

                echo '<li>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</li>';

            }

        }

        echo '</ul></div></' . $tag . '>';

    };



    echo '<div class="bc-fee-wrap" id="bcFeeWrap" data-readonly="' . ($isSigned ? '1' : '0') . '">';

    renderContractFeeImportantNotice(false);

    if (!$isSigned) {

        echo '<p class="bc-fee-select-hint">Click any package card below to select it and view the fee breakdown.</p>';

    } else {

        echo '<p class="bc-fee-select-hint bc-fee-select-hint--locked">Contract signed — selected package is locked. Click a card to view its details.</p>';

    }

    ?>

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

<?php



    $catalog = xander_contract_fee_catalog_list();
    $expeditedCode = 'p544';

    foreach ($sections as $key => $meta) {

        $group = array_filter($catalog, static fn(array $p): bool => $p['section'] === $key);

        if ($group === []) {

            continue;

        }

        $expedited = null;

        if ($key === 'job') {

            foreach ($group as $idx => $pkg) {

                if (($pkg['contract_code'] ?? '') === $expeditedCode) {

                    $expedited = $pkg;

                    unset($group[$idx]);

                    break;

                }

            }

            $group = array_values($group);

        }

        $packageCount = count($group) + ($expedited !== null ? 1 : 0);

        echo '<section class="bc-fee-section">';

        echo '<header class="bc-fee-section-head">';

        echo '<span class="bc-fee-section-icon" aria-hidden="true">' . $meta['icon'] . '</span>';

        echo '<h3 class="bc-fee-section-title">' . htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') . '</h3>';

        echo '<span class="bc-fee-section-count">' . $packageCount . ' packages</span>';

        echo '</header>';

        echo '<div class="bc-fee-section-body">';

        if ($key === 'job') {

            renderContractJobSeekerIntroNote(false);

        }

        foreach ($group as $pkg) {

            $mk($pkg);

        }

        if ($expedited !== null) {

            echo '<div class="bc-fee-expedited-wrap">';

            $mk($expedited);

            echo '</div>';

        }

        echo '</div></section>';

    }

    ?>

<?php renderContractFeeClosingNotices(false); ?>

<input type="hidden" id="selected_package_code" name="selected_package_code" value="<?= htmlspecialchars($selectedCode, ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" id="selected_package_label" name="selected_package_label" value="">

<?php

    if ($isSigned && $selectedCode !== '') {

        $pkg = getPackageDetails($selectedCode);

        if ($pkg) {

            echo '<div class="bc-selected-pkg-summary"><strong>Selected package:</strong> ' . htmlspecialchars($pkg['title'], ENT_QUOTES, 'UTF-8') . '</div>';

        }

    }

    echo '</div>';

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

    renderContractFeeImportantNotice(true);

    ?>

<p>All fees cover professional consulting, documentation support, administrative processing, and coordination services.</p>

<p>The Client selected the following service package:</p>

<p><strong><?= htmlspecialchars($pkg['title'], ENT_QUOTES, 'UTF-8') ?></strong></p>

<ul class="bc-list">

<?php if ($code === 'p544'): ?>

<li>Additional Fee: €250</li>
<li>Payable upfront</li>
<li>Non-refundable</li>

<?php else: foreach ($pkg['lines'] as $line): ?>

<li><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></li>

<?php endforeach; endif; ?>

</ul>

<?php if (!empty($pkg['total'])): ?>

<p><strong>Total Package Fee: <?= htmlspecialchars($pkg['total'], ENT_QUOTES, 'UTF-8') ?></strong></p>

<?php endif; ?>

<?php renderContractFeeClosingNotices(true); ?>

<?php

}


