<?php
/** @var bool $isSigned */
/** @var array|null $student */
/** @var string $effectiveDate */
/** @var bool $burundiContractPdf */
$effectiveDate = $effectiveDate ?? '';
$student = $student ?? null;
$isSigned = !empty($isSigned);
$burundiContractPdf = !empty($burundiContractPdf);

if ($burundiContractPdf) {
    require_once __DIR__ . '/../includes/contract_pdf_helpers.php';
}
?>
<div class="burundi-contract-body">

<?php if ($burundiContractPdf): ?>
<p class="bc-intro">
This Agreement (&ldquo;Agreement&rdquo;) is made and entered into on
<strong><?= xander_pdf_esc(xander_pdf_date($effectiveDate)) ?></strong>
(&ldquo;Effective Date&rdquo;), by and between:
</p>
<?php else: ?>
<p class="bc-intro">
This Agreement (&ldquo;Agreement&rdquo;) is made and entered into on
<input type="date" id="effective_date" name="effective_date" value="<?= htmlspecialchars($effectiveDate, ENT_QUOTES, 'UTF-8') ?>" <?= $isSigned ? 'readonly' : '' ?> class="bc-inline-input">
(&ldquo;Effective Date&rdquo;), by and between:
</p>
<?php endif; ?>

<h3 class="bc-h3">1. COMPANY</h3>
<p>
<strong>Xander Tech LLC</strong>, an Arizona, Phoenix, USA-registered company<br>
Phone: +1 270 438 7305<br>
Email: <a href="mailto:info@xanderglobalscholars.com">info@xanderglobalscholars.com</a>
</p>
<p><strong>In partnership with</strong></p>
<p>
<strong>Recruitment Partner / Exclusive Agent for Burundi</strong><br>
Name: Jean Paul Manirakiza<br>
Company: <strong>HEERA 10 (SURL)</strong><br>
Country: Burundi<br>
Phone: +257 62 03 89 84<br>
Emails: mnpauls2@yahoo.fr, heera10office@gmail.com
</p>
<p><strong>Company Details:</strong></p>
<ul class="bc-list">
<li>Registration Number (RC): 0047301/23</li>
<li>NIF: 4002306605</li>
<li>Address: Avenue du Stade, Hotel Source du Nil, Bureau N 120, Rohero, Bujumbura Mairie, Burundi</li>
<li>Territory: Republic of Burundi</li>
</ul>
<p class="bc-and">AND</p>

<h3 class="bc-h3">2. CLIENT</h3>
<?php
$clientFullName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
$clientEmail = (string) ($prefillEmail ?? $student['email'] ?? '');
if ($burundiContractPdf):
    $clientTypes = ['Student', 'Professional', 'Job Seeker', 'Visitor Visa Applicant'];
    $selectedType = (string) ($clientType ?? '');
?>
<table class="pdf-client-table" cellpadding="6" cellspacing="0">
<tr><td class="pdf-label">Email</td><td class="pdf-value"><?= xander_pdf_esc($clientEmail) ?></td></tr>
<tr><td class="pdf-label">Full Name</td><td class="pdf-value"><?= xander_pdf_esc($clientFullName) ?></td></tr>
<tr><td class="pdf-label">Date of Birth</td><td class="pdf-value"><?= xander_pdf_esc(xander_pdf_date($student['dob'] ?? '')) ?></td></tr>
<tr><td class="pdf-label">Passport / National ID Number</td><td class="pdf-value"><?= xander_pdf_esc($student['passport_number'] ?? '') ?></td></tr>
<tr><td class="pdf-label">Nationality</td><td class="pdf-value"><?= xander_pdf_esc($student['nationality'] ?? '') ?></td></tr>
<tr><td class="pdf-label">Country of Residence</td><td class="pdf-value"><?= xander_pdf_esc($clientResidence ?? '') ?></td></tr>
<tr><td class="pdf-label">Current Address</td><td class="pdf-value"><?= xander_pdf_esc($clientAddress ?? '') ?></td></tr>
<tr><td class="pdf-label">Phone</td><td class="pdf-value"><?= xander_pdf_esc($student['phone_number'] ?? '') ?></td></tr>
<tr><td class="pdf-label">Client Type</td><td class="pdf-value"><?php
    foreach ($clientTypes as $t) {
        $mark = ($selectedType === $t) ? '☑' : '☐';
        echo xander_pdf_esc($mark . ' ' . $t) . ' &nbsp;&nbsp; ';
    }
?></td></tr>
</table>
<?php else: ?>
<div class="bc-client-grid">
<label class="bc-email-first">Email: <input type="email" id="student_email" name="student_email" autocomplete="email" required class="bc-input-email" value="<?= htmlspecialchars($clientEmail, ENT_QUOTES, 'UTF-8') ?>" <?= $isSigned ? 'readonly' : '' ?>></label>
<label>Full Name: <input type="text" id="student_name" name="student_name" autocomplete="name" required value="<?= htmlspecialchars($clientFullName, ENT_QUOTES, 'UTF-8') ?>" <?= $isSigned ? 'readonly' : '' ?>></label>
<label>Date of Birth: <input type="date" id="student_dob" name="student_dob" autocomplete="bday" required value="<?= htmlspecialchars((string)($student['dob'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $isSigned ? 'readonly' : '' ?>></label>
<label>Passport / National ID Number: <input type="text" id="student_passport" name="student_passport" autocomplete="off" value="<?= htmlspecialchars((string)($student['passport_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $isSigned ? 'readonly' : '' ?>></label>
<label>Nationality: <input type="text" id="student_nationality" name="student_nationality" autocomplete="country-name" required value="<?= htmlspecialchars((string)($student['nationality'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $isSigned ? 'readonly' : '' ?>></label>
<label>Country of Residence: <input type="text" id="client_residence" name="client_residence" autocomplete="country" required value="<?= htmlspecialchars((string)($clientResidence ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $isSigned ? 'readonly' : '' ?>></label>
<label>Current Address: <input type="text" id="client_address" name="client_address" autocomplete="street-address" required value="<?= htmlspecialchars((string)($clientAddress ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $isSigned ? 'readonly' : '' ?>></label>
<label>Phone: <input type="tel" id="student_phone" name="student_phone" autocomplete="tel" required value="<?= htmlspecialchars((string)($student['phone_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $isSigned ? 'readonly' : '' ?>></label>
</div>
<p class="bc-client-type"><strong>Client Type:</strong>
<?php
$types = ['Student', 'Professional', 'Job Seeker', 'Visitor Visa Applicant'];
$selectedType = $clientType ?? '';
foreach ($types as $t):
    $id = 'ctype_' . preg_replace('/\s+/', '_', strtolower($t));
?>
<label class="bc-check"><input type="radio" name="client_type" id="<?= $id ?>" value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedType === $t ? 'checked' : '' ?> <?= $isSigned ? 'disabled' : '' ?>> <?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?></label>
<?php endforeach; ?>
</p>
<?php endif; ?>
<p>(Hereinafter referred to as the &ldquo;Client,&rdquo; &ldquo;Student,&rdquo; or &ldquo;Applicant&rdquo;)</p>
<p>The Company and the Client shall collectively be referred to as the &ldquo;Parties.&rdquo;</p>

<h3 class="bc-h3">3. PURPOSE OF THIS AGREEMENT</h3>
<p>This Agreement governs the provision of international education consulting, employment placement assistance, immigration and visa support, admissions guidance, documentation support, relocation advisory, and related professional services provided by Xander Global Scholars / Xander Tech LLC.</p>
<p>This Agreement applies to Clients globally, including but not limited to Africa (Rwanda, Uganda, Kenya, Tanzania, Burundi, Ghana, Nigeria), Europe, the United States, Canada, Asia, and other jurisdictions.</p>

<h3 class="bc-h3">4. SCOPE OF SERVICES</h3>
<p>Subject to the service package selected by the Client, services may include, but are not limited to:</p>
<p><strong>A. Education &amp; Career Services</strong></p>
<ul class="bc-list">
<li>University, college, and professional program applications</li>
<li>Scholarships, fee waivers, and financial aid guidance</li>
<li>Education loan facilitation (where applicable)</li>
<li>Admission documentation support</li>
<li>Interview preparation</li>
<li>Pre-departure orientation</li>
<li>Accommodation guidance</li>
<li>Credit transfer assistance</li>
</ul>
<p><strong>B. Employment &amp; Immigration Services</strong></p>
<ul class="bc-list">
<li>Job opportunity referrals (EU &amp; international)</li>
<li>Employer connection facilitation</li>
<li>Work permit application support</li>
<li>Visa documentation preparation</li>
<li>Embassy application guidance</li>
<li>Immigration process coordination</li>
</ul>
<p><strong>No Guarantee Disclaimer</strong></p>
<p>The Client acknowledges and agrees that Xander Tech LLC does not guarantee: visa approval; admission; employment placement; loan approval; or processing timelines. All final decisions rest solely with universities, employers, embassies, immigration authorities, lenders, and other third-party entities.</p>

<h3 class="bc-h3">5. FEES &amp; PAYMENT TERMS</h3>
<?php
require_once __DIR__ . '/../includes/contract_fee_packages.php';
$selectedPackageCode = $selectedPackageCode ?? (string) ($contract['selected_package_code'] ?? '');
if (!empty($burundiFeesPdfOnly)) {
    renderContractFeePackagesPdf($selectedPackageCode);
} else {
    renderContractFeePackagesSection($isSigned, $selectedPackageCode);
}
?>

<h3 class="bc-h3">6. PROCESSING TIMELINE</h3>
<p>Estimated processing time is 2–4 months, depending on embassy workload, employer response, immigration authorities, and third-party institutions. The Company shall not be responsible for delays beyond its control.</p>

<h3 class="bc-h3">7. REFUND POLICY</h3>
<p>If the Job Seeker visa application is refused, the Client shall be entitled to a 30% refund of the total amount paid. The refund will be processed within 2–5 months from the date of the official refusal decision.</p>
<p>The remaining 70% is non-refundable as it covers services already rendered, including administrative processing, documentation handling, application support, government-related procedures, and professional time and services.</p>
<p><strong>N.B:</strong> All other services and fees paid upfront are strictly non-refundable.</p>

<h3 class="bc-h3">8. CLIENT RESPONSIBILITIES</h3>
<p>The Client agrees to: provide true, accurate, and complete information; submit only genuine and authentic documents; respond promptly to Company requests; attend all required interviews and appointments; comply with all immigration and employment laws. Any failure resulting from false, misleading, or delayed information shall be the sole responsibility of the Client.</p>

<h3 class="bc-h3">9. DATA COLLECTION &amp; CONSENT</h3>
<p>The Client authorizes the Company to collect, store, process, and use personal data for applications, admissions, employment placement, visa processing, loan facilitation, embassy communication, compliance and audits.</p>

<h3 class="bc-h3">10. CROSS-BORDER DATA TRANSFER</h3>
<p>The Client expressly consents that their data may be transferred, stored, and processed internationally, including but not limited to the USA, Europe, Canada, Asia, and partner countries.</p>

<h3 class="bc-h3">11. CONFIDENTIALITY &amp; DATA PROTECTION</h3>
<p>Xander Global Scholars applies reasonable safeguards to protect Client information. However, no system guarantees absolute security.</p>

<h3 class="bc-h3">12. FRAUD, MISREPRESENTATION &amp; LEGAL RESPONSIBILITY</h3>
<p>All documents and information submitted must be genuine, accurate, and lawful. If the Client submits false, forged, altered, or misleading documents: the Company bears no liability; the Client assumes full legal responsibility; services may be terminated immediately; no refund shall be issued. Fraud may result in civil, administrative, or criminal penalties under U.S., EU, UK, Canadian, and international laws.</p>

<h3 class="bc-h3">13. LIMITATION OF LIABILITY</h3>
<p>The Company shall not be liable for visa refusals, embassy decisions, employer withdrawal, admission rejection, loan refusal, delays by third parties, policy changes, deportation or bans, or financial losses.</p>

<h3 class="bc-h3">14. TERMINATION</h3>
<p>Either Party may terminate this Agreement in writing. If the Client terminates after processing has begun, no refund shall apply except as stated in Section 7.</p>

<h3 class="bc-h3">15. TESTIMONIAL &amp; MEDIA CONSENT</h3>
<p>The Client voluntarily consents to the use of testimonials (video, text, images) for educational and marketing purposes. No compensation shall be owed unless separately agreed in writing.</p>

<h3 class="bc-h3">16. WITHDRAWAL OF CONSENT</h3>
<p>The Client may withdraw consent in writing. However, already-rendered services remain payable and previously published media may not be retractable.</p>

<h3 class="bc-h3">17. GOVERNING LAW</h3>
<p>This Agreement shall be governed by the laws of the United States of America, with due consideration to international immigration and data protection principles.</p>

<h3 class="bc-h3">18. ENTIRE AGREEMENT</h3>
<p>This Agreement constitutes the entire understanding between the Parties and supersedes all prior agreements.</p>

</div>
