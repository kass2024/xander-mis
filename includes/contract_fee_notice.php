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
