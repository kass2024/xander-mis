<?php
declare(strict_types=1);

/**
 * Section 5 important notice (Client Service Contract Aug 2026).
 */
function renderContractFeeImportantNotice(bool $isPdf = false): void
{
    $tag = $isPdf ? 'p' : 'div';
    ?>
<<?= $tag ?> class="bc-fee-notice">
<strong>IMPORTANT NOTICE</strong><br>
Note: School tuition fees, tuition deposits, accommodation fees, and other institutional charges are
separate and are not included in the upfront service fees. Government fees, embassy and visa
charges, biometric fees, courier fees, legal fees, and all other third-party costs are the applicant's
responsibility and must be paid separately. These fees may vary depending on the destination
country, institution, and embassy requirements. Any changes or additional costs will be
communicated to the applicant throughout the application process.
</<?= $tag ?>>
<<?= $tag ?> class="bc-fee-notice">
<strong>Important:</strong> Tuition fees, tuition deposits, and accommodation fees may be refundable, depending
on the institution's refund policy. Applicants are strongly advised to carefully read the terms and
conditions outlined in the Conditional Offer Letter and any other official documents issued by the
school before making any payments. Refund eligibility is determined solely by the institution's
policies and conditions.
</<?= $tag ?>>
<?php
}

/**
 * Section 5 legal notices (red text — Client Service Contract Aug 2026).
 */
function renderContractFeeLegalNotices(bool $isPdf = false): void
{
    $blocks = [
        [
            'title' => 'Third-Party Costs',
            'body'  => 'The Student acknowledges and agrees that all third-party costs associated with the application and enrollment process are the sole responsibility of the Student. These may include, but are not limited to, passport fees, police clearance certificates, translation and notarization costs, university application fees, tuition deposits, tuition fees, medical examinations, TB tests, visa and embassy fees, flight tickets, accommodation expenses, and any other government, institutional, or service-provider charges. Such fees are separate from XGS service fees and are non-refundable unless otherwise provided by the respective third-party provider.',
        ],
        [
            'title' => 'Student Withdrawal or Non-Payment',
            'body'  => 'If the Student fails to meet their payment obligations or voluntarily withdraws from the process, the Student shall be granted a five (5) calendar-day grace period to resolve the outstanding balance, during which a minimum payment of fifty percent (50%) of the cheque value may be accepted. If the outstanding amount remains unpaid after the grace period, Xander Global Scholars (XGS) reserves the right to present, deposit, or enforce the cheque and pursue all available legal remedies. All fees paid to XGS shall remain non-refundable.',
        ],
        [
            'title' => 'Change of School, Country, or Program',
            'body'  => 'Any request to change the selected institution, country, or academic program after the application process has commenced shall be subject to a non-refundable administrative fee of €300. The fee must be paid within three (3) business days of the change request. The Student shall also be responsible for any additional costs, application fees, tuition differences, or other charges arising from the requested change.',
        ],
    ];

    echo $isPdf ? '<div style="margin-top:12pt;">' : '<div class="bc-fee-legal-wrap">';

    foreach ($blocks as $block) {
        if ($isPdf) {
            echo '<div style="margin:0 0 10pt;padding:10pt 12pt;background:#fff5f5;border-left:3pt solid #dc2626;">';
            echo '<p style="margin:0 0 6pt;font-weight:bold;color:#b91c1c;font-size:11pt;">'
                . htmlspecialchars($block['title'], ENT_QUOTES, 'UTF-8') . '</p>';
            echo '<p style="margin:0;color:#dc2626;font-weight:600;font-size:10pt;line-height:1.55;">'
                . htmlspecialchars($block['body'], ENT_QUOTES, 'UTF-8') . '</p>';
            echo '</div>';
            continue;
        }

        echo '<div class="bc-fee-legal-block">';
        echo '<h4 class="bc-fee-legal-title">' . htmlspecialchars($block['title'], ENT_QUOTES, 'UTF-8') . '</h4>';
        echo '<p>' . htmlspecialchars($block['body'], ENT_QUOTES, 'UTF-8') . '</p>';
        echo '</div>';
    }

    echo '</div>';
}

/**
 * Job Seeker section intro (before country packages — PDF page 7).
 */
function renderContractJobSeekerIntroNote(bool $isPdf = false): void
{
    if ($isPdf) {
        echo '<p><strong>N.B.:</strong> Basic English communication is mandatory for all positions. '
            . 'Applicants must have at least a High School diploma or equivalent.</p>';
        return;
    }
    echo '<p class="bc-fee-section-note"><strong>N.B.:</strong> Basic English communication is mandatory for all job seeker positions. '
        . 'Applicants must have at least a High School diploma or equivalent.</p>';
}

/**
 * Closing notices after all packages: payment + air ticketing, then red legal blocks (PDF page 10).
 */
function renderContractFeeClosingNotices(bool $isPdf = false): void
{
    if ($isPdf) {
        echo '<p>Failure to pay required fees may result in suspension or termination of services.</p>';
        echo '<p><strong>N.B.:</strong> Air ticketing prices are determined by market rates and must be paid before ticket issuance.</p>';
    } else {
        echo '<div class="bc-fee-closing-notices">';
        echo '<p>Failure to pay required fees may result in suspension or termination of services.</p>';
        echo '<p><strong>N.B.:</strong> Air ticketing prices are determined by market rates and must be paid before ticket issuance.</p>';
        echo '</div>';
    }

    renderContractFeeLegalNotices($isPdf);
}
