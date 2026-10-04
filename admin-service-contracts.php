<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/service_contract_lib.php';
require_once __DIR__ . '/helpers/csrf.php';
require_once __DIR__ . '/helpers/currencies.php';

if (!xander_sc_staff_authorized(isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 0)) {
    header('Location: admin-login.php');
    exit;
}

$adminId = (int) $_SESSION['admin_id'];
$csrf = pcvc_csrf_token();
$wizard = isset($_GET['wizard']);
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (($_POST['action'] ?? '') === 'cancel')) {
    if (!pcvc_csrf_validate_post()) {
        header('Location: ' . $basePath . '/admin-service-contracts.php?error=' . rawurlencode('Invalid security token.'));
        exit;
    }
    $cancelled = xander_sc_cancel($conn, (int) ($_POST['id'] ?? 0), $adminId);
    $flag = $cancelled ? 'cancelled=1' : 'error=' . rawurlencode('This contract cannot be cancelled.');
    header('Location: ' . $basePath . '/admin-service-contracts.php?' . $flag);
    exit;
}

$statusFilter = (string) ($_GET['status'] ?? 'all');
$serviceFilter = (string) ($_GET['service'] ?? 'all');
$search = trim((string) ($_GET['q'] ?? ''));
$rows = $wizard ? [] : xander_sc_list($conn, $statusFilter, $serviceFilter, $search);
$currencies = xander_payment_currency_options();

function sc_h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $wizard ? 'Generate Contract Link' : 'Service Contracts' ?> | Xander Global Scholars</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= sc_h($basePath) ?>/assets/css/contract-modern.css?v=20261004b">
<link rel="stylesheet" href="<?= sc_h($basePath) ?>/assets/css/service-contract-wizard.css?v=20261004e">
</head>
<body class="xgs-contract-body">
<main class="sc-shell">
<div class="sc-card">
<?php if ($wizard): ?>
    <a class="sc-btn sc-btn-ghost" href="<?= sc_h($basePath) ?>/admin-service-contracts.php">← Contract list</a>
    <h1>Generate Contract Link</h1>
    <p class="sc-lead">The public link is created only after you confirm the review.</p>
    <ol class="sc-steps">
        <li data-step-index="0">1 Service</li>
        <li data-step-index="1">2 Destination</li>
        <li data-step-index="2">3 Program</li>
        <li data-step-index="3">4 Customer</li>
        <li data-step-index="4">5 Payment</li>
        <li data-step-index="5">6 Review</li>
    </ol>
    <div id="sc-trail" class="sc-trail" hidden></div>
    <div id="sc-error" class="sc-error" hidden></div>
    <div id="sc-wizard">
        <section class="sc-panel is-active" data-step="0">
            <div class="sc-choice-grid">
                <label class="sc-choice"><input type="radio" name="service" value="study"> <strong>Study</strong><span>School or study program</span></label>
                <label class="sc-choice"><input type="radio" name="service" value="work"> <strong>Work / Job</strong><span>Available job</span></label>
                <label class="sc-choice"><input type="radio" name="service" value="visit"> <strong>Visit</strong><span>Visit visa package</span></label>
            </div>
        </section>
        <section class="sc-panel" data-step="1">
            <div class="sc-field sc-country-finder">
                <label for="sc-country-search">Find a country</label>
                <div class="sc-country-search-box">
                    <svg class="sc-country-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <input id="sc-country-search" type="text" role="combobox" aria-autocomplete="list" aria-expanded="true" aria-controls="sc-countries" placeholder="Type France, UAE, Korea…" autocomplete="off" spellcheck="false">
                    <button type="button" id="sc-country-clear" class="sc-country-clear" hidden aria-label="Clear country search">&times;</button>
                </div>
                <p id="sc-country-meta" class="sc-country-meta"></p>
                <p id="sc-country-selected" class="sc-country-selected" hidden></p>
            </div>
            <div id="sc-countries" class="sc-country-list" role="listbox" aria-label="Countries"></div>
        </section>
        <section class="sc-panel" data-step="2">
            <h2 id="sc-offering-title">Select School or Study Program</h2>
            <div id="sc-offerings"></div>
        </section>
        <section class="sc-panel" data-step="3">
            <div class="sc-field">
                <label for="sc-customer-search">Find an existing customer</label>
                <input id="sc-customer-search" type="search" placeholder="Name or email" autocomplete="off">
            </div>
            <div id="sc-customer-results"></div>
            <div class="sc-grid-2">
                <div class="sc-field"><label for="sc-first">First name</label><input id="sc-first" required></div>
                <div class="sc-field"><label for="sc-last">Last name</label><input id="sc-last" required></div>
            </div>
            <div class="sc-grid-2">
                <div class="sc-field"><label for="sc-email">Email</label><input id="sc-email" type="email" required></div>
                <div class="sc-field"><label for="sc-phone">Phone number</label><input id="sc-phone" type="tel" required></div>
            </div>
            <div class="sc-grid-2">
                <div class="sc-field"><label for="sc-dob">Date of birth</label><input id="sc-dob" type="date" required></div>
                <div class="sc-field"><label for="sc-nationality">Nationality</label><input id="sc-nationality" required></div>
            </div>
            <div class="sc-field"><label for="sc-passport">Passport number</label><input id="sc-passport" autocomplete="off"></div>
            <div class="sc-field"><label for="sc-address">Address</label><input id="sc-address"></div>
            <div class="sc-field"><label for="sc-residence">Country of residence</label><input id="sc-residence"></div>
        </section>
        <section class="sc-panel" data-step="4">
            <div class="sc-field">
                <label for="sc-fee-type">Select Fee Type</label>
                <select id="sc-fee-type">
                    <option value="">Select fee type</option>
                    <option value="upfront">Upfront Fee</option>
                    <option value="commitment">Commitment Fee</option>
                    <option value="service">Service Fee</option>
                    <option value="promotion">Promotion</option>
                    <option value="other">Other Fee</option>
                </select>
            </div>
            <div class="sc-field" id="sc-other-kind-wrap" hidden>
                <label for="sc-other-kind">Promotion type <span class="sc-required">Required</span></label>
                <select id="sc-other-kind">
                    <option value="">Select promotion type</option>
                    <option value="promotion">Promotion</option>
                    <option value="standard">Standard other fee</option>
                </select>
            </div>
            <div class="sc-field" id="sc-fee-desc-wrap" hidden>
                <label for="sc-fee-desc">Fee Description</label>
                <input id="sc-fee-desc" maxlength="255">
            </div>
            <div class="sc-grid-2">
                <div class="sc-field"><label for="sc-amount">Amount</label><input id="sc-amount" type="number" min="0" step="0.01" value="0"></div>
                <div class="sc-field">
                    <label for="sc-currency">Currency</label>
                    <select id="sc-currency">
                        <option value="">Select currency</option>
                        <?php foreach ($currencies as $currency): ?>
                        <option value="<?= sc_h($currency['code']) ?>"><?= sc_h($currency['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="sc-grid-2">
                <div class="sc-field"><label for="sc-remaining">Remaining fees <span class="sc-required">Required</span></label><input id="sc-remaining" type="number" min="0.01" step="0.01" required></div>
                <div class="sc-field">
                    <label for="sc-remaining-currency">Remaining currency</label>
                    <select id="sc-remaining-currency">
                        <option value="">Select currency</option>
                        <?php foreach ($currencies as $currency): ?>
                        <option value="<?= sc_h($currency['code']) ?>"><?= sc_h($currency['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <p class="sc-note">Remaining fees are required on every contract. An upfront fee, a Promotion fee, or an Other Fee marked as Promotion may be 0, and Continue stays available. Study contracts do not show Pay Here; the customer can sign without that button.</p>
        </section>
        <section class="sc-panel" data-step="5">
            <h2>Full contract</h2>
            <iframe id="sc-contract-preview" class="sc-preview" title="Full contract"></iframe>
            <div id="sc-review" class="sc-review"></div>
            <div class="sc-prepared">
                <button type="button" class="sc-btn sc-btn-primary" id="sc-prepared-btn">Type staff first and last name</button>
                <div id="sc-prepared-fields" class="sc-grid-2" hidden>
                    <div class="sc-field"><label for="sc-staff-first">Staff first name <span class="sc-required">Required</span></label><input id="sc-staff-first" autocomplete="given-name" required></div>
                    <div class="sc-field"><label for="sc-staff-last">Staff last name <span class="sc-required">Required</span></label><input id="sc-staff-last" autocomplete="family-name" required></div>
                </div>
                <p class="sc-note">Required on every contract. This name is shown at the end of every page as Prepared by.</p>
            </div>
        </section>
        <section class="sc-panel" data-step="6">
            <div class="sc-note">Contract created successfully</div>
            <p><strong>Contract reference:</strong> <span id="sc-reference"></span></p>
            <div class="sc-field">
                <label for="sc-link">Contract link</label>
                <input id="sc-link" class="sc-link" readonly>
            </div>
            <div class="sc-actions">
                <button type="button" class="sc-btn sc-btn-primary" id="sc-copy">Copy Link</button>
                <a class="sc-btn sc-btn-ghost" id="sc-view" href="#" target="_blank" rel="noopener">View Contract</a>
                <a class="sc-btn sc-btn-ghost" href="<?= sc_h($basePath) ?>/admin-service-contracts.php?wizard=1">Create Another Contract</a>
            </div>
            <p id="sc-copied" hidden>Link copied.</p>
        </section>
        <div class="sc-actions">
            <button type="button" class="sc-btn sc-btn-ghost" id="sc-back">Back</button>
            <button type="button" class="sc-btn sc-btn-primary" id="sc-next" disabled>Continue</button>
        </div>
    </div>
    <script>
    window.SC_WIZARD = {
        api: <?= json_encode($basePath . '/api/service-contracts.php') ?>,
        csrf: <?= json_encode($csrf) ?>,
        staffName: <?= json_encode((string) ($_SESSION['name'] ?? '')) ?>,
        staffId: <?= (int) $adminId ?>,
        preview: <?= json_encode($basePath . '/student-contract.php') ?>
    };
    </script>
    <script src="<?= sc_h($basePath) ?>/assets/js/service-contract-wizard.js?v=20261004c"></script>
<?php else: ?>
    <div class="sc-toolbar">
        <div>
            <h1>Service contracts</h1>
            <p class="sc-lead">Generated study, work, and visit agreements.</p>
        </div>
        <a class="sc-btn sc-btn-primary" href="<?= sc_h($basePath) ?>/admin-service-contracts.php?wizard=1">Generate Contract Link</a>
    </div>
    <?php if (!empty($_GET['cancelled'])): ?>
        <div class="sc-note">Contract cancelled.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
        <div class="sc-error"><?= sc_h($_GET['error']) ?></div>
    <?php endif; ?>
    <form class="sc-filters" method="get">
        <select name="status">
            <?php
            $statusOptions = [
                'all' => 'All',
                'pending_signature' => 'Pending',
                'viewed' => 'Viewed',
                'signed' => 'Signed',
                'expired' => 'Expired',
                'cancelled' => 'Cancelled',
            ];
            foreach ($statusOptions as $value => $label): ?>
            <option value="<?= sc_h($value) ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= sc_h($label) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="service">
            <?php foreach (['all' => 'All services', 'study' => 'Study', 'work' => 'Work', 'visit' => 'Visit'] as $value => $label): ?>
            <option value="<?= sc_h($value) ?>" <?= $serviceFilter === $value ? 'selected' : '' ?>><?= sc_h($label) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="search" name="q" value="<?= sc_h($search) ?>" placeholder="Customer name, email, or reference">
        <button class="sc-btn sc-btn-ghost" type="submit">Filter</button>
    </form>
    <div class="sc-table-wrap">
    <table class="xgs-admin-table">
        <thead>
        <tr>
            <th>Reference</th>
            <th>Customer</th>
            <th>Service</th>
            <th>Country</th>
            <th>Program / Job / Package</th>
            <th>Fee</th>
            <th>Currency</th>
            <th>Created by</th>
            <th>Created</th>
            <th>Status</th>
            <th>Signed</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($rows === []): ?>
        <tr><td colspan="12">No contracts match this filter.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row):
            $token = (string) $row['public_token'];
            $url = xander_sc_public_url($token);
            $status = (string) $row['status'];
            $canCancel = in_array($status, ['draft', 'pending_signature', 'viewed'], true);
            $feeLabel = xander_sc_fee_types()[(string) $row['fee_type']] ?? (string) $row['fee_type'];
        ?>
        <tr>
            <td><?= sc_h($row['reference']) ?></td>
            <td><?= sc_h($row['customer_name']) ?><br><?= sc_h($row['customer_email']) ?></td>
            <td><?= sc_h(xander_sc_service_types()[(string) $row['service_type']] ?? $row['service_type']) ?></td>
            <td><?= sc_h($row['country_name']) ?></td>
            <td><?= sc_h($row['offering_title']) ?></td>
            <td><?= sc_h($feeLabel) ?> <?= sc_h(number_format((float) $row['amount'], 2, '.', ',')) ?></td>
            <td><?= sc_h($row['currency']) ?></td>
            <td><?= sc_h($row['staff_name']) ?></td>
            <td><?= sc_h($row['created_at']) ?></td>
            <td><span class="xgs-badge <?= $status === 'signed' ? 'signed' : ($status === 'cancelled' || $status === 'expired' ? 'danger' : 'pending') ?>"><?= sc_h(xander_sc_statuses()[$status] ?? $status) ?></span></td>
            <td><?= sc_h($row['signed_at'] ?: '—') ?></td>
            <td>
                <a href="<?= sc_h($url) ?>" target="_blank" rel="noopener">View Contract</a>
                <button type="button" class="sc-btn sc-btn-ghost" data-copy="<?= sc_h($url) ?>">Copy Link</button>
                <?php if ($canCancel): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Cancel this unsigned contract?');">
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= sc_h($csrf) ?>">
                    <button class="sc-btn sc-btn-danger" type="submit">Cancel Contract</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <script>
    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const value = button.getAttribute('data-copy') || '';
            try { await navigator.clipboard.writeText(value); }
            catch (e) { window.prompt('Copy this link', value); }
            button.textContent = 'Copied';
            setTimeout(() => { button.textContent = 'Copy Link'; }, 1500);
        });
    });
    </script>
<?php endif; ?>
</div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
