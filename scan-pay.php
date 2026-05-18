<?php
$pageTitle = 'Scan & Pay - Xander Global Scholars';
include 'header.php';
require_once __DIR__ . '/db.php';

// Fetch packages from database
$packages = $conn->query("SELECT * FROM fee_packages ORDER BY title ASC");

// Fetch fee items for each package
$fee_items = [];
$fee_items_result = $conn->query("SELECT fi.*, p.title as package_title FROM fee_items fi LEFT JOIN fee_packages p ON fi.package_id = p.id ORDER BY fi.package_id, fi.id");
while ($item = $fee_items_result->fetch_assoc()) {
    if (!isset($fee_items[$item['package_id']])) {
        $fee_items[$item['package_id']] = [];
    }
    $fee_items[$item['package_id']][] = $item;
}

// Cache packages data for JS
$packages_data = [];
if ($packages) {
    while ($package = $packages->fetch_assoc()) {
        $packages_data[$package['id']] = $package;
    }
}

// Base URL (QR must contain absolute URL for customer phones)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
$scheme = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $scheme . '://' . $host;

?>

<div class="page-hero">
    <h1>Scan & Pay</h1>
    <p>Generate a QR code for customers to scan and pay securely</p>
</div>

<div class="page-content">
    <div class="payment-section">
        <h2>1) Select Package & Fee Items</h2>
        <p>Choose the items, then generate a QR code that opens the payment page on a phone.</p>

        <div class="form-grid">
            <div class="form-group">
                <label for="package-select">Select Package</label>
                <select id="package-select" required onchange="loadFeeItems(this.value)">
                    <option value="">Choose a package...</option>
                    <?php foreach ($packages_data as $pid => $p): ?>
                        <option value="<?= (int)$pid ?>">
                            <?= htmlspecialchars($p['title'] ?? '') ?> - <?= htmlspecialchars($p['currency'] ?? 'USD') ?> <?= number_format((float)($p['total_expected'] ?? 0), 2) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="payment-method">Payment Method</label>
                <select id="payment-method" required>
                    <option value="stripe">Credit Card (Stripe)</option>
                </select>
                <div class="help-text">This generates a link to your existing <code>stripe-payment.php</code> flow.</div>
            </div>
        </div>

        <div id="fee-items-section" style="display:none;">
            <h3>Fee Items</h3>
            <div id="fee-items-list"></div>

            <div class="total-section">
                <h3>Total Amount: <span id="total-amount">0.00</span></h3>
            </div>

            <button type="button" class="payment-btn" id="generate-btn" onclick="generateQr()" disabled>
                Generate QR Code
            </button>
        </div>
    </div>

    <div class="payment-section" id="qr-section" style="display:none;">
        <h2>2) Customer QR Code</h2>
        <p>Customer scans this QR code to open the payment page on their phone.</p>

        <div class="qr-wrap">
            <div class="qr-card">
                <img id="qr-image" alt="Scan to Pay" />
                <div class="qr-meta">
                    <div><strong>Link:</strong></div>
                    <div class="qr-link" id="pay-link"></div>
                    <div class="qr-actions">
                        <button type="button" class="secondary-btn" onclick="copyPayLink()">Copy Link</button>
                        <a class="secondary-btn" id="open-link" href="#" target="_blank" rel="noopener">Open Link</a>
                    </div>
                </div>
            </div>
            <div class="qr-note">
                <strong>Tip:</strong> Print this page or show the QR on-screen at your office/desk.
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
const feeItemsData = <?= json_encode($fee_items) ?>;
const packagesData = <?= json_encode($packages_data) ?>;
const baseUrl = <?= json_encode($baseUrl) ?>;

function loadFeeItems(packageId) {
    const feeItemsSection = document.getElementById('fee-items-section');
    const feeItemsList = document.getElementById('fee-items-list');
    const generateBtn = document.getElementById('generate-btn');

    document.getElementById('qr-section').style.display = 'none';

    if (!packageId) {
        feeItemsSection.style.display = 'none';
        generateBtn.disabled = true;
        return;
    }

    const items = feeItemsData[packageId] || [];

    if (items.length === 0) {
        feeItemsList.innerHTML = '<p>No fee items available for this package.</p>';
        feeItemsSection.style.display = 'block';
        generateBtn.disabled = true;
        return;
    }

    let html = '';
    items.forEach(item => {
        const amount = parseFloat(item.total_expected || item.amount || 0);
        const cur = item.currency || (packagesData[packageId] ? packagesData[packageId].currency : 'USD') || 'USD';
        html += `
            <div class="fee-item">
                <div class="fee-info">
                    <h4>${escapeHtml(item.title || item.name || '')}</h4>
                    <small>Code: ${escapeHtml(item.code || 'N/A')}</small>
                </div>
                <div class="fee-amount">
                    <input type="checkbox" id="fee_${item.id}" name="selected_items" value="${item.id}" data-amount="${amount}" data-currency="${escapeHtml(cur)}" onchange="calculateTotal()">
                    <label for="fee_${item.id}">${escapeHtml(cur)} ${amount.toFixed(2)}</label>
                </div>
            </div>
        `;
    });

    feeItemsList.innerHTML = html;
    feeItemsSection.style.display = 'block';
    calculateTotal();
}

function calculateTotal() {
    const checkboxes = document.querySelectorAll('input[name="selected_items"]:checked');
    let total = 0;
    checkboxes.forEach(cb => {
        total += parseFloat(cb.getAttribute('data-amount')) || 0;
    });

    document.getElementById('total-amount').textContent = total.toFixed(2);
    document.getElementById('generate-btn').disabled = (checkboxes.length === 0);
}

function generateQr() {
    const packageId = document.getElementById('package-select').value;
    const method = document.getElementById('payment-method').value;

    const checkboxes = document.querySelectorAll('input[name="selected_items"]:checked');
    if (!packageId || checkboxes.length === 0) return;

    // Build items JSON in the same structure used elsewhere: { item_id: amount }
    const items = {};
    checkboxes.forEach(cb => {
        const itemId = cb.value;
        const amount = parseFloat(cb.getAttribute('data-amount')) || 0;
        if (amount > 0) items[itemId] = amount;
    });

    const itemsJson = JSON.stringify(items);

    const payUrl = `${baseUrl}/stripe-payment.php?package_id=${encodeURIComponent(packageId)}&payment_method=${encodeURIComponent(method)}&items=${encodeURIComponent(itemsJson)}`;

    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=${encodeURIComponent(payUrl)}`;

    document.getElementById('qr-image').src = qrUrl;
    document.getElementById('pay-link').textContent = payUrl;

    const openLink = document.getElementById('open-link');
    openLink.href = payUrl;

    document.getElementById('qr-section').style.display = 'block';
    window.scrollTo({ top: document.getElementById('qr-section').offsetTop - 20, behavior: 'smooth' });
}

function copyPayLink() {
    const txt = document.getElementById('pay-link').textContent || '';
    if (!txt) return;

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(txt);
        return;
    }

    const ta = document.createElement('textarea');
    ta.value = txt;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
}

function escapeHtml(str) {
    return String(str)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
</script>

<style>
.payment-section {
    background: var(--card);
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 18px;
}

.form-group {
    margin-bottom: 10px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--text);
}

.form-group select {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--border);
    border-radius: 8px;
    font-size: 1rem;
    background: white;
}

.help-text {
    margin-top: 8px;
    color: var(--text-light);
    font-size: 0.9rem;
}

.fee-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border: 1px solid var(--border);
    border-radius: 8px;
    margin-bottom: 10px;
    background: var(--bg);
}

.fee-info h4 {
    margin: 0 0 5px 0;
    color: var(--text);
}

.fee-info small {
    color: var(--text-light);
}

.fee-amount {
    display: flex;
    align-items: center;
    gap: 10px;
}

.fee-amount input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.fee-amount label {
    font-weight: 600;
    color: var(--primary);
    cursor: pointer;
}

.total-section {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 18px;
    border-radius: 10px;
    text-align: center;
    margin-top: 16px;
}

.total-section h3 {
    margin: 0;
    font-size: 1.35rem;
}

.payment-btn {
    width: 100%;
    background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
    color: white;
    border: none;
    padding: 16px 20px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1.05rem;
    cursor: pointer;
    transition: var(--transition);
    margin-top: 18px;
}

.payment-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.qr-wrap {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}

.qr-card {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 18px;
    align-items: start;
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 16px;
    background: var(--bg);
}

.qr-card img {
    width: 320px;
    height: 320px;
    border-radius: 12px;
    border: 2px solid var(--border);
    background: #fff;
}

.qr-meta {
    word-break: break-word;
    color: var(--text);
}

.qr-link {
    margin-top: 8px;
    padding: 10px 12px;
    border-radius: 10px;
    background: #ffffff;
    border: 1px dashed var(--border);
    font-size: 0.95rem;
}

.qr-actions {
    margin-top: 12px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.secondary-btn {
    display: inline-block;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: #ffffff;
    color: var(--text);
    cursor: pointer;
    text-decoration: none;
    font-weight: 600;
}

.qr-note {
    color: var(--text-light);
    font-size: 0.95rem;
}

@media (max-width: 820px) {
    .qr-card {
        grid-template-columns: 1fr;
    }

    .qr-card img {
        width: 100%;
        height: auto;
        aspect-ratio: 1 / 1;
    }
}
</style>
