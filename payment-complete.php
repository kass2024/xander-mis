<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

// Handle form submission for student registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_student'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $area_code = trim($_POST['area_code'] ?? '');
    
    if ($first_name && $last_name && $email && $phone_number) {
        require_once __DIR__ . '/helpers/application_spam_guard.php';
        $spamVerdict = pcvc_spam_check_post([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'area_code' => $area_code,
            'phone_number' => $phone_number,
        ]);
        if ($spamVerdict['is_spam']) {
            header('Location: payment-complete.php?error=' . urlencode('Registration blocked. Please use your real name and a valid personal email.'));
            exit();
        }

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM student_applications WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($existing) {
            // Email already exists, redirect with error
            header("Location: payment-complete.php?error=" . urlencode('Email already registered. Please search for your existing account.'));
            exit();
        }
        
        // Insert new student
        $stmt = $conn->prepare("INSERT INTO student_applications (user_id, first_name, last_name, email, area_code, phone_number) VALUES (?, ?, ?, ?, ?, ?)");
        $user_id = 'STU_' . time();
        $stmt->bind_param('sssss', $user_id, $first_name, $last_name, $email, $area_code, $phone_number);
        $stmt->execute();
        $student_id = $stmt->insert_id;
        $stmt->close();
        
        // Redirect to payment page with student_id parameter
        header("Location: payment-complete.php?student_id=$student_id&registered=1");
        exit();
    }
}

// Handle form submission for student selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_student'])) {
    $student_id = (int)($_POST['student_id'] ?? 0);
    
    if ($student_id > 0) {
        // Redirect to payment page with student_id parameter
        header("Location: payment-complete.php?student_id=$student_id");
        exit();
    }
}

// Handle form submission for payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $student_id = (int)($_GET['student_id'] ?? 0);
    
    if ($student_id === 0) {
        header("Location: payment-complete.php");
        exit();
    }
    
    $package_id = (int)($_POST['package_id'] ?? 0);
    $selected_items = $_POST['selected_items'] ?? [];
    $payment_method = $_POST['payment_method'] ?? 'stripe';
    
    if ($package_id > 0 && !empty($selected_items)) {
        // Redirect to stripe payment with selected items and student info
        $items_json = json_encode($selected_items);
        $total = 0;
        foreach ($selected_items as $item_id => $amount) {
            $total += (float)$amount;
        }
        
        header("Location: stripe-payment.php?student_id=$student_id&package_id=$package_id&payment_method=$payment_method&items=" . urlencode($items_json));
        exit();
    }
}

// Get student data from URL parameter instead of session
$selected_student = null;
$registration_error = isset($_GET['error']) ? (string) $_GET['error'] : null;
$registered_new = isset($_GET['registered']) && (string) $_GET['registered'] !== '';

if (isset($_GET['student_id'])) {
    $student_id = (int)$_GET['student_id'];
    $stmt = $conn->prepare("SELECT * FROM student_applications WHERE id = ?");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $selected_student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch packages from database (using fee_packages table)
$packages = $conn->query("SELECT * FROM fee_packages ORDER BY display_order ASC, id ASC");

// Fetch fee items for each package (using fee_items table)
$fee_items = [];
$fee_items_result = $conn->query("SELECT fi.*, p.title as package_title FROM fee_items fi LEFT JOIN fee_packages p ON fi.package_id = p.id ORDER BY fi.package_id, fi.id");
while ($item = $fee_items_result->fetch_assoc()) {
    if (!isset($fee_items[$item['package_id']])) {
        $fee_items[$item['package_id']] = [];
    }
    $fee_items[$item['package_id']][] = $item;
}

// Calculate totals
$total_expected_sum = 0;
$total_amount_sum = 0;
$packages_summary = [];

while ($package = $packages->fetch_assoc()) {
    $packages_summary[$package['id']] = [
        'title' => $package['title'],
        'currency' => $package['currency'],
        'total_expected' => $package['total_expected'],
        'total_amount' => $package['total_amount'],
        'payment_items' => $fee_items[$package['id']] ?? []
    ];
    $total_expected_sum += $package['total_expected'];
    $total_amount_sum += $package['total_amount'];
}

$pageTitle = 'Payment - Xander Global Scholars';
include 'header.php';
?>

<div class="page-hero">
    <h1>Secure Payment Portal</h1>
    <p>Identify yourself and select package to complete payment securely</p>
</div>

<div class="page-content">
    <?php if (!$selected_student): ?>
        <!-- Student Selection Section -->
        <div class="payment-section">
            <h2>Student Selection</h2>
            <p>Search for existing student or register new student to continue with payment:</p>
            
            <?php if ($registration_error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($registration_error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$selected_student && !$registered_new): ?>
                <!-- Live Search Form -->
                <div class="selection-tabs">
                    <button type="button" class="tab-btn active" onclick="showTab('search')">
                        <i class="fas fa-search"></i> Search Existing Student
                    </button>
                    <button type="button" class="tab-btn" onclick="showTab('register')">
                        <i class="fas fa-user-plus"></i> Register New Student
                    </button>
                </div>
                
                <!-- Search Tab -->
                <div id="search-tab" class="tab-content active">
                    <form method="POST" id="student-search-form">
                        <input type="hidden" name="select_student" value="1">
                        <input type="hidden" name="student_id" id="selected-student-id" value="">
                        
                        <div class="form-group">
                            <label for="search-input">Search Student (Name or Email):</label>
                            <div class="search-container">
                                <input type="text" id="search-input" required placeholder="Type student name or email..." autocomplete="off">
                                <div id="search-results" class="search-results"></div>
                            </div>
                        </div>
                        
                        <button type="submit" class="payment-btn" id="select-btn" disabled>
                            <i class="fas fa-user-check"></i> Select Student & Continue
                        </button>
                    </form>
                </div>
                
                <!-- Registration Tab -->
                <div id="register-tab" class="tab-content">
                    <form method="POST" id="registration-form">
                        <input type="hidden" name="register_student" value="1">
                        
                        <div class="registration-notice">
                            <h3>New Student Registration</h3>
                            <p>Please fill in your details to create a new student account:</p>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name:</label>
                                <input type="text" name="first_name" id="first_name" required placeholder="Enter your first name">
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name">Last Name:</label>
                                <input type="text" name="last_name" id="last_name" required placeholder="Enter your last name">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address:</label>
                                <input type="email" name="email" id="reg-email" required placeholder="Enter your email address">
                            </div>
                            
                            <div class="form-group">
                                <label for="area_code">Area Code:</label>
                                <input type="text" name="area_code" id="area_code" placeholder="+123" maxlength="10">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone_number">Phone Number:</label>
                            <input type="tel" name="phone_number" id="phone_number" required placeholder="1234567890">
                        </div>
                        
                        <button type="submit" class="payment-btn">
                            <i class="fas fa-user-plus"></i> Register & Continue to Payment
                        </button>
                    </form>
                </div>
            <?php elseif ($registered_new): ?>
                <!-- Registration Success Message -->
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <strong>Registration Successful!</strong> Your account has been created and you can now proceed with payment.
                </div>
                
                <!-- Package Selection Section -->
                <div class="payment-section">
                    <h2>Select Package & Fee Items</h2>
                    <p>Choose a package and select fee items you want to pay for:</p>
                    
                    <form method="POST" id="payment-form">
                        <input type="hidden" name="process_payment" value="1">
                        
                        <!-- Package Selection -->
                        <div class="form-group">
                            <label for="package-select">Select Package:</label>
                            <select name="package_id" id="package-select" required onchange="loadFeeItems(this.value)">
                                <option value="">Choose a package...</option>
                                <?php foreach ($packages_summary as $package_id => $package): ?>
                                <option value="<?= $package_id ?>"><?= htmlspecialchars($package['title']) ?> - <?= $package['currency'] ?> <?= number_format($package['total_expected'], 2) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Fee Items Section -->
                        <div id="fee-items-section" style="display: none;">
                            <h3>Available Fee Items</h3>
                            <div id="fee-items-list"></div>
                            
                            <!-- Total Amount -->
                            <div class="total-section">
                                <h3>Total Amount: <span id="total-amount">$0.00</span></h3>
                            </div>
                        </div>
                        
                        <!-- Payment Method -->
                        <div class="form-group">
                            <label for="payment-method">Payment Method:</label>
                            <select name="payment_method" id="payment-method" required>
                                <option value="stripe">Credit Card (Stripe)</option>
                                <option value="bank">Bank Transfer</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="payment-btn" id="submit-btn" disabled>
                            <i class="fas fa-credit-card"></i> Process Payment
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Selected Student Info Display -->
        <div class="student-info-section">
            <div class="student-card">
                <div class="student-avatar">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="student-details">
                    <h3><?= htmlspecialchars(trim(($selected_student['first_name'] ?? '') . ' ' . ($selected_student['last_name'] ?? ''))) ?></h3>
                    <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($selected_student['email']) ?></p>
                    <?php if ($selected_student['phone_number']): ?>
                        <p><i class="fas fa-phone"></i> <?= htmlspecialchars($selected_student['area_code'] . ' ' . $selected_student['phone_number']) ?></p>
                    <?php endif; ?>
                    <p><i class="fas fa-id-badge"></i> Student ID: <?= htmlspecialchars($selected_student['user_id']) ?></p>
                </div>
                <div class="student-actions">
                    <a href="clear-student.php" class="change-student-btn">
                        <i class="fas fa-exchange-alt"></i> Change Student
                    </a>
                </div>
            </div>
            
            <!-- Package Selection Section -->
            <div class="payment-section">
                <h2>Select Package & Fee Items</h2>
                <p>Choose a package and select fee items you want to pay for:</p>
                
                <form method="POST" id="payment-form">
                    <input type="hidden" name="process_payment" value="1">
                    
                    <!-- Package Selection -->
                    <div class="form-group">
                        <label for="package-select">Select Package:</label>
                            <select name="package_id" id="package-select" required onchange="loadFeeItems(this.value)">
                                <option value="">Choose a package...</option>
                                <?php foreach ($packages_summary as $package_id => $package): ?>
                                <option value="<?= $package_id ?>"><?= htmlspecialchars($package['title']) ?> - <?= $package['currency'] ?> <?= number_format($package['total_expected'], 2) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    
                    <!-- Fee Items Section -->
                    <div id="fee-items-section" style="display: none;">
                        <h3>Available Fee Items</h3>
                        <div id="fee-items-list"></div>
                        
                        <!-- Total Amount -->
                        <div class="total-section">
                            <h3>Total Amount: <span id="total-amount">$0.00</span></h3>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="form-group">
                        <label for="payment-method">Payment Method:</label>
                            <select name="payment_method" id="payment-method" required>
                                <option value="stripe">Credit Card (Stripe)</option>
                                <option value="bank">Bank Transfer</option>
                            </select>
                        </div>
                    
                    <button type="submit" class="payment-btn" id="submit-btn" disabled>
                        <i class="fas fa-credit-card"></i> Process Payment
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Quick Actions Section -->
    <div class="payment-section">
        <h2>Payment Management</h2>
        <p>Additional payment options and tools:</p>
        
        <div class="action-grid">
            <a href="strip-test.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Test Connection</h3>
                <p>Verify Stripe API connectivity</p>
            </a>
            
            <a href="stripe-payment.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h3>Quick Payment</h3>
                <p>Make a direct payment</p>
            </a>
            
            <a href="Stripe-check_transactions.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h3>View Transactions</h3>
                <p>Check payment history</p>
            </a>
            
            <a href="student-manage.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Student Management</h3>
                <p>Manage packages and assignments</p>
            </a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
// Fee items data for JavaScript
const feeItemsData = <?= json_encode($fee_items) ?>;
const packagesData = <?= json_encode($packages_summary) ?>;

// Tab switching functionality
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Set active button
    if (tabName === 'search') {
        document.querySelector('.tab-btn').classList.add('active');
    } else {
        document.querySelectorAll('.tab-btn')[1].classList.add('active');
    }
}

// Live Search Functionality
let searchTimeout;
const searchInput = document.getElementById('search-input');
const searchResults = document.getElementById('search-results');
const selectBtn = document.getElementById('select-btn');
const selectedStudentId = document.getElementById('selected-student-id');

if (searchInput && searchResults) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            searchResults.innerHTML = '';
            selectBtn.disabled = true;
            selectedStudentId.value = '';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            fetch(`search-students.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(students => {
                    displaySearchResults(students);
                })
                .catch(error => {
                    console.error('Search error:', error);
                    searchResults.innerHTML = '';
                });
        }, 300);
    });
    
    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-container')) {
            searchResults.innerHTML = '';
        }
    });
}

function displaySearchResults(students) {
    if (students.length === 0) {
        searchResults.innerHTML = '<div class="search-no-results">No students found. Try registering as a new student.</div>';
        selectBtn.disabled = true;
        selectedStudentId.value = '';
        return;
    }
    
    let html = '<div class="search-results-list">';
    students.forEach(student => {
        const fullName = `${student.first_name} ${student.last_name}`;
        const phone = student.area_code && student.phone_number ? 
            `${student.area_code} ${student.phone_number}` : 'No phone';
        
        html += `
            <div class="search-result-item" onclick="selectStudent(${student.id}, '${student.email}', '${student.first_name}', '${student.last_name}')">
                <div class="result-info">
                    <div class="result-name">${fullName}</div>
                    <div class="result-email">${student.email}</div>
                    <div class="result-phone">${phone}</div>
                    <div class="result-id">ID: ${student.user_id}</div>
                    <div class="result-package">Package: ${student.package_name}</div>
                </div>
                <div class="result-select">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    searchResults.innerHTML = html;
}

function selectStudent(studentId, email, firstName, lastName) {
    searchInput.value = `${firstName} ${lastName} (${email})`;
    selectedStudentId.value = studentId;
    searchResults.innerHTML = '';
    selectBtn.disabled = false;
    
    // Show loading indicator
    selectBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Selecting...';
    selectBtn.disabled = true;
    
    // Auto-submit form using direct form submission for reliable session handling
    const form = document.getElementById('student-search-form');
    
    // Set the hidden field and submit form directly
    selectedStudentId.value = studentId;
    
    // Use setTimeout to allow UI update before submission
    setTimeout(() => {
        form.submit();
    }, 100);
}

function loadFeeItems(packageId) {
    const feeItemsSection = document.getElementById('fee-items-section');
    const feeItemsList = document.getElementById('fee-items-list');
    const submitBtn = document.getElementById('submit-btn');
    
    if (!packageId) {
        feeItemsSection.style.display = 'none';
        submitBtn.disabled = true;
        return;
    }
    
    const items = feeItemsData[packageId] || [];
    
    if (items.length === 0) {
        feeItemsList.innerHTML = '<p>No fee items available for this package.</p>';
        feeItemsSection.style.display = 'block';
        submitBtn.disabled = true;
        return;
    }
    
    let html = '';
    
    items.forEach((item, index) => {
        const amount = parseFloat(item.total_expected || item.amount || 0);
        html += `
            <div class="fee-item">
                <div class="fee-info">
                    <h4>${item.title || item.name}</h4>
                    <small>Code: ${item.code || 'N/A'}</small>
                    ${item.refundable ? '<span class="refundable-tag">Refundable</span>' : ''}
                </div>
                <div class="fee-amount">
                    <input type="checkbox" 
                           id="fee_${item.id}" 
                           name="selected_items[${item.id}]" 
                           value="${amount}"
                           onchange="calculateTotal()">
                    <label for="fee_${item.id}">
                        ${item.currency || 'USD'} ${amount.toFixed(2)}
                    </label>
                </div>
            </div>
        `;
    });
    
    feeItemsList.innerHTML = html;
    feeItemsSection.style.display = 'block';
    calculateTotal();
}

function calculateTotal() {
    const checkboxes = document.querySelectorAll('input[name^="selected_items"]:checked');
    let total = 0;
    
    checkboxes.forEach(checkbox => {
        total += parseFloat(checkbox.value) || 0;
    });
    
    document.getElementById('total-amount').textContent = `$${total.toFixed(2)}`;
    document.getElementById('submit-btn').disabled = total === 0;
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

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--text);
}

.form-group input, .form-group select {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--border);
    border-radius: 8px;
    font-size: 1rem;
    background: white;
}

.selection-tabs {
    display: flex;
    gap: 0;
    margin-bottom: 25px;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid var(--border);
}

.tab-btn {
    flex: 1;
    background: var(--bg);
    border: none;
    padding: 15px 20px;
    cursor: pointer;
    transition: var(--transition);
    font-weight: 600;
    color: var(--text-light);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.tab-btn:hover {
    background: var(--border);
    color: var(--text);
}

.tab-btn.active {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.search-container {
    position: relative;
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 2px solid var(--border);
    border-top: none;
    border-radius: 0 0 8px 8px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: var(--shadow-md);
}

.search-results-list {
    padding: 0;
}

.search-result-item {
    padding: 15px;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 15px;
}

.search-result-item:hover {
    background: var(--bg);
}

.search-result-item:last-child {
    border-bottom: none;
}

.result-info {
    flex: 1;
}

.result-name {
    font-weight: 600;
    color: var(--text);
    margin-bottom: 5px;
}

.result-email {
    color: var(--primary);
    font-size: 0.9rem;
    margin-bottom: 3px;
}

.result-phone {
    color: var(--text-light);
    font-size: 0.85rem;
    margin-bottom: 3px;
}

.result-id {
    color: var(--text-light);
    font-size: 0.8rem;
    font-family: monospace;
}

.result-select {
    color: var(--primary);
    font-size: 1.2rem;
}

.search-no-results {
    padding: 20px;
    text-align: center;
    color: var(--text-light);
    background: var(--bg);
}

.error-message {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-left: 4px solid #f59e0b;
}

.error-message i {
    font-size: 1.2rem;
}

.success-message {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-left: 4px solid #34d399;
}

.success-message i {
    font-size: 1.2rem;
}

.student-info-section {
    margin-bottom: 30px;
}

.student-card {
    background: linear-gradient(135deg, var(--card) 0%, var(--bg) 100%);
    border: 2px solid var(--border);
    border-radius: 15px;
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: var(--shadow-md);
}

.student-avatar {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    flex-shrink: 0;
}

.student-details {
    flex: 1;
}

.student-details h3 {
    margin: 0 0 10px 0;
    color: var(--text);
    font-size: 1.5rem;
}

.student-details p {
    margin: 5px 0;
    color: var(--text-light);
    display: flex;
    align-items: center;
    gap: 10px;
}

.student-details i {
    width: 20px;
    text-align: center;
}

.student-actions {
    width: 100%;
}

.change-student-btn {
    margin: 0 auto;
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

.refundable-tag {
    background: #10b981;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    margin-left: 10px;
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
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    margin-top: 20px;
}

.total-section h3 {
    margin: 0;
    font-size: 1.5rem;
}

.payment-btn {
    width: 100%;
    background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
    color: white;
    border: none;
    padding: 18px 24px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1.1rem;
    cursor: pointer;
    transition: var(--transition);
    margin-top: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.payment-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(255, 140, 66, 0.3);
}

.payment-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.action-card {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    text-decoration: none;
    color: var(--text);
    transition: var(--transition);
    display: block;
}

.action-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: var(--accent);
}

.action-icon {
    width: 50px;
    height: 50px;
    background: var(--accent);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    margin-bottom: 15px;
}

.action-card h3 {
    margin: 0 0 8px 0;
    color: var(--text);
}

.action-card p {
    margin: 0;
    color: var(--text-light);
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .student-card {
        flex-direction: column;
        text-align: center;
    }
    
    .student-actions {
        width: 100%;
    }
    
    .change-student-btn {
        margin: 0 auto;
    }
}
</style>
