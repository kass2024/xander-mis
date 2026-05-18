<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/payment_config.php';

$stripePublicKey = xander_stripe_public_key();
if ($stripePublicKey === '') {
    die('Payment is not configured. Set STRIPE_PUBLIC_KEY in .env.');
}

$pageTitle = 'International Payment Portal - Xander Global Scholars';
include 'header.php';

// Initialize variables for form data
$firstName = $lastName = $email = $phone = $description = $amount = '';
$paymentMethod = 'stripe';
$currency = 'USD';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = htmlspecialchars($_POST['first_name'] ?? '');
    $lastName = htmlspecialchars($_POST['last_name'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $phone = htmlspecialchars($_POST['phone'] ?? '');
    $description = htmlspecialchars($_POST['description'] ?? '');
    $amount = htmlspecialchars($_POST['amount'] ?? '');
    $currency = htmlspecialchars($_POST['currency'] ?? 'USD');
    
    // Auto-select payment method based on currency
    if ($currency === 'RWF') {
        $paymentMethod = 'momo';
    } else {
        $paymentMethod = 'stripe';
    }
    
    $stripePaymentIntentId = htmlspecialchars($_POST['stripe_payment_intent_id'] ?? '');
    
    // Validate and process
    if (!empty($firstName) && !empty($lastName) && !empty($email) && !empty($amount)) {
        // Store payment in database
        try {
            $stmt = $pdo->prepare("INSERT INTO payments (
                first_name, last_name, email, phone, description, 
                amount, currency, payment_method, status, stripe_payment_intent_id, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $status = !empty($stripePaymentIntentId) ? 'completed' : 'pending';
            
            $stmt->execute([
                $firstName, $lastName, $email, $phone, $description,
                $amount, $currency, $paymentMethod, $status, $stripePaymentIntentId
            ]);
            
            $paymentId = $pdo->lastInsertId();
            $success = true;
            $transactionId = 'TX' . str_pad($paymentId, 8, '0', STR_PAD_LEFT);
            
        } catch (PDOException $e) {
            $error = "Payment submission failed. Please try again.";
        }
    } else {
        $error = "Please fill all required fields.";
    }
}
?>

<div class="page-hero">
    <div class="hero-content">
        <div class="hero-icon-container">
            <i class="fas fa-globe-europe"></i>
        </div>
        <h1>International Payment Portal</h1>
        <p class="hero-subtitle">Secure multi-currency payment processing for global transactions</p>
    </div>
</div>

<div class="page-content">
    <div class="dashboard-container">
        <?php if (isset($success) && $success): ?>
        <div class="payment-success">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3>Payment Submitted Successfully!</h3>
            <div class="success-details">
                <p><strong>Transaction ID:</strong> <?php echo $transactionId; ?></p>
                <p><strong>Amount:</strong> <?php echo $currency . ' ' . number_format($amount, 2); ?></p>
                <p><strong>Method:</strong> <?php echo strtoupper($paymentMethod); ?></p>
                <p>You will receive a confirmation email shortly.</p>
            </div>
            <div class="success-actions">
                <a href="other-payments.php" class="btn btn-primary">
                    <i class="fas fa-receipt"></i> Make Another Payment
                </a>
                <a href="dashboard.php" class="btn btn-outline">
                    <i class="fas fa-tachometer-alt"></i> Return to Dashboard
                </a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <!-- Payment Form -->
        <div class="payment-form-section">
            <div class="payment-stepper">
                <div class="step active">
                    <span class="step-number">1</span>
                    <span class="step-label">Personal Info</span>
                </div>
                <div class="step">
                    <span class="step-number">2</span>
                    <span class="step-label">Payment Details</span>
                </div>
                <div class="step">
                    <span class="step-number">3</span>
                    <span class="step-label">Review & Pay</span>
                </div>
            </div>

            <form id="paymentForm" method="POST" action="" class="payment-form">
                <!-- Personal Information -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fas fa-user-tie"></i>
                        <h3>Personal Information</h3>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">
                                First Name <span class="required">*</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" id="first_name" name="first_name" 
                                       value="<?php echo $firstName; ?>" required
                                       placeholder="Enter your first name">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">
                                Last Name <span class="required">*</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" id="last_name" name="last_name" 
                                       value="<?php echo $lastName; ?>" required
                                       placeholder="Enter your last name">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">
                                Email Address <span class="required">*</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo $email; ?>" required
                                       placeholder="your.email@example.com">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">
                                Phone Number <span class="required">*</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-phone"></i>
                                <input type="tel" id="phone" name="phone" 
                                       value="<?php echo $phone; ?>" required
                                       placeholder="+1 (555) 123-4567">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fas fa-money-check-alt"></i>
                        <h3>Payment Details</h3>
                    </div>
                    
                    <div class="currency-selection">
                        <label>Select Currency <span class="required">*</span></label>
                        <div class="currency-options">
                            <div class="currency-option <?php echo $currency === 'USD' ? 'selected' : ''; ?>">
                                <input type="radio" id="currency_usd" name="currency" 
                                       value="USD" <?php echo $currency === 'USD' ? 'checked' : ''; ?>>
                                <label for="currency_usd">
                                    <span class="currency-flag">🇺🇸</span>
                                    <span class="currency-code">USD</span>
                                    <span class="currency-name">US Dollar</span>
                                </label>
                            </div>
                            
                            <div class="currency-option <?php echo $currency === 'EUR' ? 'selected' : ''; ?>">
                                <input type="radio" id="currency_eur" name="currency" 
                                       value="EUR" <?php echo $currency === 'EUR' ? 'checked' : ''; ?>>
                                <label for="currency_eur">
                                    <span class="currency-flag">🇪🇺</span>
                                    <span class="currency-code">EUR</span>
                                    <span class="currency-name">Euro</span>
                                </label>
                            </div>
                            
                            <div class="currency-option <?php echo $currency === 'RWF' ? 'selected' : ''; ?>">
                                <input type="radio" id="currency_rwf" name="currency" 
                                       value="RWF" <?php echo $currency === 'RWF' ? 'checked' : ''; ?>>
                                <label for="currency_rwf">
                                    <span class="currency-flag">🇷🇼</span>
                                    <span class="currency-code">RWF</span>
                                    <span class="currency-name">Rwandan Franc</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="amount-section">
                        <div class="form-group amount-group">
                            <label for="amount">
                                Amount <span class="required">*</span>
                            </label>
                            <div class="amount-input-container">
                                <div class="currency-display" id="currencySymbol">$</div>
                                <input type="number" id="amount" name="amount" 
                                       value="<?php echo $amount; ?>" 
                                       min="1" step="0.01" required
                                       placeholder="0.00">
                                <div class="currency-text" id="currencyText">USD</div>
                            </div>
                            <div class="amount-hint">
                                Minimum amount: <span id="minAmount">$1.00</span>
                            </div>
                        </div>
                        
                        <div class="form-group description-group">
                            <label for="description">
                                <i class="fas fa-edit"></i> Payment Description (Optional)
                            </label>
                            <div class="description-container">
                                <textarea id="description" name="description" 
                                          rows="4" placeholder="Enter any additional notes about this payment (e.g., student name, invoice number, purpose of payment, etc.)"><?php echo $description; ?></textarea>
                                <div class="description-footer">
                                    <span class="char-count">
                                        <span id="charCount">0</span>/500 characters
                                    </span>
                                    <span class="hint-text">
                                        <i class="fas fa-info-circle"></i> Optional field for reference
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method (Automatically selected based on currency) -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fas fa-credit-card"></i>
                        <h3>Payment Method</h3>
                        <div class="payment-method-info" id="paymentMethodInfo">
                            <i class="fas fa-info-circle"></i>
                            <span>Payment method is automatically selected based on your chosen currency</span>
                        </div>
                    </div>
                    
                    <input type="hidden" name="payment_method" id="payment_method" value="stripe">
                    
                    <!-- Dynamic Payment Methods -->
                    <div class="payment-methods-dynamic">
                        <!-- Stripe Method (for USD/EUR) -->
                        <div id="stripeMethod" class="payment-method-dynamic">
                            <div class="method-header">
                                <i class="fab fa-cc-stripe"></i>
                                <div class="method-title">
                                    <h4>Credit/Debit Card</h4>
                                    <p class="method-subtitle">For USD and EUR payments</p>
                                </div>
                                <span class="method-badge">Secure</span>
                            </div>
                            <p class="method-description">
                                Pay instantly with Visa, Mastercard, American Express, or Discover
                            </p>
                            <div class="method-icons">
                                <i class="fab fa-cc-visa"></i>
                                <i class="fab fa-cc-mastercard"></i>
                                <i class="fab fa-cc-amex"></i>
                                <i class="fab fa-cc-discover"></i>
                            </div>
                            
                            <div class="payment-details">
                                <div class="card-form">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Card Number</label>
                                            <div class="stripe-input" id="card-number-element"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Expiry Date</label>
                                            <div class="stripe-input" id="card-expiry-element"></div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>CVC</label>
                                            <div class="stripe-input" id="card-cvc-element"></div>
                                        </div>
                                    </div>
                                    
                                    <div id="card-errors" class="stripe-errors" role="alert"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- MoMo Method (for RWF) -->
                        <div id="momoMethod" class="payment-method-dynamic" style="display: none;">
                            <div class="method-header">
                                <i class="fas fa-mobile-alt"></i>
                                <div class="method-title">
                                    <h4>Mobile Money</h4>
                                    <p class="method-subtitle">For RWF payments only</p>
                                </div>
                                <span class="method-badge">Popular</span>
                            </div>
                            <p class="method-description">
                                Pay via MTN Mobile Money (Rwanda)
                            </p>
                            <div class="method-icons">
                                <i class="fas fa-sim-card"></i>
                                <i class="fas fa-qrcode"></i>
                            </div>
                            
                            <div class="payment-details">
                                <div class="momo-form">
                                    <div class="form-group">
                                        <label for="momo_phone">MTN Mobile Number <span class="required">*</span></label>
                                        <div class="phone-input-group">
                                            <span class="country-code">+250</span>
                                            <input type="tel" id="momo_phone" name="momo_phone" 
                                                   placeholder="78XXXXXXX" pattern="[0-9]{9}" required>
                                        </div>
                                        <div class="hint-text">
                                            Enter your 9-digit MTN Rwanda mobile number
                                        </div>
                                    </div>
                                    
                                    <div class="momo-instructions">
                                        <h5><i class="fas fa-info-circle"></i> Payment Instructions:</h5>
                                        <ol>
                                            <li>Enter your MTN Rwanda mobile number above</li>
                                            <li>Click "Pay with MoMo" to submit payment</li>
                                            <li>You will receive a USSD prompt on your phone</li>
                                            <li>Enter your MoMo PIN to authorize payment</li>
                                            <li>Wait for confirmation SMS message</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms & Submit -->
                <div class="form-section terms-section">
                    <div class="terms-agreement">
                        <div class="checkbox-group">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms">
                                I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and 
                                <a href="privacy.php" target="_blank">Privacy Policy</a>. I authorize Xander Global Scholars 
                                to process this payment. All payments are non-refundable unless otherwise stated.
                            </label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="receipt" name="receipt" checked>
                            <label for="receipt">
                                Email me a receipt for this transaction
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">
                            <i class="fas fa-redo"></i> Clear Form
                        </button>
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                            <i class="fas fa-lock"></i>
                            <span id="submitText">Pay with Card</span>
                            <span id="amountDisplay"> - <span id="currencySymbolDisplay">$</span><span id="amountValue">0.00</span></span>
                        </button>
                    </div>
                    
                    <div class="security-notice">
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <strong>Secure Payment Guaranteed</strong>
                            <p>Your payment is protected with 256-bit SSL encryption and PCI DSS compliance.</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Stripe.js Library -->
<script src="https://js.stripe.com/v3/"></script>

<script>
// Initialize Stripe with your publishable key
const stripe = Stripe(<?= json_encode($stripePublicKey, JSON_UNESCAPED_UNICODE) ?>);
const elements = stripe.elements();

// Create card elements
const cardNumber = elements.create('cardNumber', {
    style: {
        base: {
            fontSize: '16px',
            color: '#32325d',
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            '::placeholder': {
                color: '#aab7c4'
            }
        }
    }
});

const cardExpiry = elements.create('cardExpiry', {
    style: {
        base: {
            fontSize: '16px',
            color: '#32325d',
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            '::placeholder': {
                color: '#aab7c4'
            }
        }
    }
});

const cardCvc = elements.create('cardCvc', {
    style: {
        base: {
            fontSize: '16px',
            color: '#32325d',
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            '::placeholder': {
                color: '#aab7c4'
            }
        }
    }
});

// Mount card elements
cardNumber.mount('#card-number-element');
cardExpiry.mount('#card-expiry-element');
cardCvc.mount('#card-cvc-element');

// Handle real-time validation errors
cardNumber.addEventListener('change', function(event) {
    const displayError = document.getElementById('card-errors');
    if (event.error) {
        displayError.textContent = event.error.message;
        displayError.style.display = 'block';
    } else {
        displayError.style.display = 'none';
    }
});

// Currency handling
const currencyData = {
    'USD': { symbol: '$', name: 'US Dollar', minAmount: 1, paymentMethod: 'stripe' },
    'EUR': { symbol: '€', name: 'Euro', minAmount: 1, paymentMethod: 'stripe' },
    'RWF': { symbol: 'FRW', name: 'Rwandan Franc', minAmount: 100, paymentMethod: 'momo' }
};

// Function to update payment method based on currency
function updatePaymentMethodByCurrency(currency) {
    const data = currencyData[currency];
    const paymentMethod = data.paymentMethod;
    
    // Update hidden payment method field
    document.getElementById('payment_method').value = paymentMethod;
    
    // Show/hide payment methods
    if (paymentMethod === 'stripe') {
        document.getElementById('stripeMethod').style.display = 'block';
        document.getElementById('momoMethod').style.display = 'none';
        document.getElementById('submitText').textContent = 'Pay with Card';
        
        // Enable Stripe elements
        cardNumber.mount('#card-number-element');
        cardExpiry.mount('#card-expiry-element');
        cardCvc.mount('#card-cvc-element');
        
        // Hide MoMo phone field requirement
        document.getElementById('momo_phone').required = false;
    } else {
        document.getElementById('stripeMethod').style.display = 'none';
        document.getElementById('momoMethod').style.display = 'block';
        document.getElementById('submitText').textContent = 'Pay with MoMo';
        
        // Unmount Stripe elements to prevent conflicts
        cardNumber.unmount();
        cardExpiry.unmount();
        cardCvc.unmount();
        
        // Show MoMo phone field requirement
        document.getElementById('momo_phone').required = true;
    }
}

document.querySelectorAll('input[name="currency"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const currency = this.value;
        const data = currencyData[currency];
        
        // Update currency display
        document.getElementById('currencySymbol').textContent = data.symbol;
        document.getElementById('currencyText').textContent = currency;
        document.getElementById('currencySymbolDisplay').textContent = data.symbol;
        document.getElementById('minAmount').textContent = data.symbol + data.minAmount.toFixed(2);
        
        // Update amount field placeholder
        document.getElementById('amount').placeholder = data.symbol + '0.00';
        
        // Update payment method based on currency
        updatePaymentMethodByCurrency(currency);
    });
});

// Real-time amount display
document.getElementById('amount').addEventListener('input', function() {
    const amount = parseFloat(this.value) || 0;
    const currency = document.querySelector('input[name="currency"]:checked').value;
    const symbol = currencyData[currency].symbol;
    
    document.getElementById('amountValue').textContent = amount.toFixed(2);
    document.getElementById('amountDisplay').style.display = amount > 0 ? 'inline' : 'none';
});

// Character counter for description
document.getElementById('description').addEventListener('input', function() {
    const charCount = this.value.length;
    const maxLength = 500;
    const charCountElement = document.getElementById('charCount');
    
    charCountElement.textContent = charCount;
    
    if (charCount > maxLength) {
        this.value = this.value.substring(0, maxLength);
        charCountElement.textContent = maxLength;
        charCountElement.style.color = '#e74c3c';
    } else if (charCount > 450) {
        charCountElement.style.color = '#f39c12';
    } else {
        charCountElement.style.color = '#27ae60';
    }
});

// Form submission
document.getElementById('paymentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const originalContent = submitBtn.innerHTML;
    
    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    const currency = document.querySelector('input[name="currency"]:checked').value;
    const paymentMethod = document.getElementById('payment_method').value;
    const amount = document.getElementById('amount').value;
    
    // Validate amount
    if (amount < currencyData[currency].minAmount) {
        showNotification(`Minimum amount is ${currencyData[currency].symbol}${currencyData[currency].minAmount.toFixed(2)}`, 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalContent;
        return;
    }
    
    if (paymentMethod === 'stripe') {
        // Create payment intent first
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating payment...';
        
        try {
            const response = await fetch('create-payment-intent.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    amount: Math.round(parseFloat(amount) * 100), // Convert to cents
                    currency: currency.toLowerCase(),
                    email: document.getElementById('email').value,
                    name: document.getElementById('first_name').value + ' ' + document.getElementById('last_name').value
                })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to create payment intent');
            }
            
            // Now confirm the payment
            submitBtn.innerHTML = '<i class="fas fa-lock fa-spin"></i> Processing payment...';
            
            const { error, paymentIntent } = await stripe.confirmCardPayment(data.client_secret, {
                payment_method: {
                    type: 'card',
                    card: cardNumber,
                    billing_details: {
                        name: document.getElementById('first_name').value + ' ' + document.getElementById('last_name').value,
                        email: document.getElementById('email').value,
                        phone: document.getElementById('phone').value
                    }
                }
            });
            
            if (error) {
                const errorElement = document.getElementById('card-errors');
                errorElement.textContent = error.message;
                errorElement.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalContent;
                return;
            }
            
            // Payment successful - submit form with payment intent
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'stripe_payment_intent_id';
            hiddenInput.value = paymentIntent.id;
            this.appendChild(hiddenInput);
            
            // Submit form
            this.submit();
            
        } catch (error) {
            const errorElement = document.getElementById('card-errors');
            errorElement.textContent = error.message;
            errorElement.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalContent;
            return;
        }
    } else if (paymentMethod === 'momo') {
        // Validate MoMo number
        const momoPhone = document.getElementById('momo_phone').value;
        if (!/^\d{9}$/.test(momoPhone)) {
            showNotification('Please enter a valid 9-digit MTN Rwanda mobile number', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalContent;
            return;
        }
        
        // Show MoMo payment simulation
        submitBtn.innerHTML = '<i class="fas fa-mobile-alt fa-spin"></i> Initiating MoMo payment...';
        
        // Simulate MoMo payment processing
        setTimeout(() => {
            this.submit();
        }, 2000);
    }
});

function resetForm() {
    if (confirm('Are you sure you want to clear the form? All entered data will be lost.')) {
        document.getElementById('paymentForm').reset();
        cardNumber.clear();
        cardExpiry.clear();
        cardCvc.clear();
        document.getElementById('card-errors').style.display = 'none';
        document.getElementById('amountValue').textContent = '0.00';
        document.getElementById('amountDisplay').style.display = 'none';
        document.getElementById('charCount').textContent = '0';
        document.getElementById('charCount').style.color = '#27ae60';
        
        // Reset to default currency and payment method
        document.getElementById('currency_usd').checked = true;
        const data = currencyData['USD'];
        document.getElementById('currencySymbol').textContent = data.symbol;
        document.getElementById('currencyText').textContent = 'USD';
        document.getElementById('currencySymbolDisplay').textContent = data.symbol;
        document.getElementById('minAmount').textContent = data.symbol + data.minAmount.toFixed(2);
        
        // Reset payment method to stripe
        document.getElementById('payment_method').value = 'stripe';
        document.getElementById('stripeMethod').style.display = 'block';
        document.getElementById('momoMethod').style.display = 'none';
        document.getElementById('submitText').textContent = 'Pay with Card';
        
        // Remount stripe elements
        cardNumber.mount('#card-number-element');
        cardExpiry.mount('#card-expiry-element');
        cardCvc.mount('#card-cvc-element');
        
        // Reset MoMo phone requirement
        document.getElementById('momo_phone').required = false;
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const selectedCurrency = document.querySelector('input[name="currency"]:checked').value;
    const data = currencyData[selectedCurrency];
    document.getElementById('currencySymbolDisplay').textContent = data.symbol;
    
    // Initialize payment method based on initial currency
    updatePaymentMethodByCurrency(selectedCurrency);
    
    // Initialize character count
    const description = document.getElementById('description');
    document.getElementById('charCount').textContent = description.value.length;
});
</script>

<style>
/* Professional Design System */
:root {
    --primary-color: #2c3e50;
    --secondary-color: #3498db;
    --accent-color: #9b59b6;
    --success-color: #27ae60;
    --warning-color: #f39c12;
    --danger-color: #e74c3c;
    --light-gray: #f8f9fa;
    --medium-gray: #e9ecef;
    --dark-gray: #343a40;
    --border-radius: 12px;
    --box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-hero {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: white;
    padding: 80px 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.hero-icon-container {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 2.5rem;
}

.page-hero h1 {
    font-size: 2.5rem;
    font-weight: 600;
    margin-bottom: 15px;
}

.hero-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

/* Payment Stepper */
.payment-stepper {
    display: flex;
    justify-content: space-between;
    max-width: 600px;
    margin: 0 auto 40px;
    position: relative;
}

.payment-stepper::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 50px;
    right: 50px;
    height: 2px;
    background: var(--medium-gray);
    z-index: 1;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
}

.step-number {
    width: 40px;
    height: 40px;
    background: white;
    border: 2px solid var(--medium-gray);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: var(--dark-gray);
    margin-bottom: 10px;
    transition: var(--transition);
}

.step.active .step-number {
    background: var(--secondary-color);
    border-color: var(--secondary-color);
    color: white;
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}

.step-label {
    font-size: 0.9rem;
    color: var(--dark-gray);
    font-weight: 500;
}

/* Form Sections */
.form-section {
    background: white;
    border-radius: var(--border-radius);
    padding: 30px;
    margin-bottom: 20px;
    box-shadow: var(--box-shadow);
    border: 1px solid var(--medium-gray);
}

.section-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--light-gray);
    position: relative;
}

.section-header i {
    color: var(--secondary-color);
    font-size: 1.5rem;
}

.section-header h3 {
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--primary-color);
    margin: 0;
}

.payment-method-info {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: auto;
    font-size: 0.9rem;
    color: var(--secondary-color);
    background: #e3f2fd;
    padding: 6px 12px;
    border-radius: 20px;
}

/* Form Layout */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.amount-section {
    margin-top: 20px;
}

.description-group {
    margin-top: 20px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--dark-gray);
    font-size: 0.95rem;
}

.form-group .required {
    color: var(--danger-color);
}

.input-with-icon {
    position: relative;
}

.input-with-icon i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--dark-gray);
    opacity: 0.6;
}

.input-with-icon input {
    width: 100%;
    padding: 14px 14px 14px 45px;
    border: 2px solid var(--medium-gray);
    border-radius: 8px;
    font-size: 1rem;
    transition: var(--transition);
    background: var(--light-gray);
}

.input-with-icon input:focus {
    outline: none;
    border-color: var(--secondary-color);
    background: white;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

/* Currency Selection */
.currency-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 10px;
}

.currency-option {
    position: relative;
}

.currency-option input[type="radio"] {
    display: none;
}

.currency-option label {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 18px 20px;
    border: 2px solid var(--medium-gray);
    border-radius: 8px;
    cursor: pointer;
    transition: var(--transition);
    background: white;
}

.currency-option:hover label {
    border-color: var(--secondary-color);
    transform: translateY(-2px);
}

.currency-option.selected label {
    border-color: var(--secondary-color);
    background: linear-gradient(135deg, #f0f8ff 0%, #e3f2fd 100%);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.15);
}

.currency-flag {
    font-size: 1.8rem;
}

.currency-code {
    font-weight: 600;
    color: var(--primary-color);
    font-size: 1.1rem;
}

.currency-name {
    color: var(--dark-gray);
    font-size: 0.9rem;
}

/* Amount Input */
.amount-input-container {
    display: flex;
    align-items: center;
    border: 2px solid var(--medium-gray);
    border-radius: 8px;
    overflow: hidden;
    background: white;
}

.currency-display {
    padding: 0 20px;
    font-weight: 600;
    color: var(--primary-color);
    background: var(--light-gray);
    height: 100%;
    display: flex;
    align-items: center;
}

.amount-input-container input {
    flex: 1;
    border: none;
    padding: 15px;
    font-size: 1.1rem;
    font-weight: 500;
    background: transparent;
}

.amount-input-container input:focus {
    outline: none;
}

.currency-text {
    padding: 0 20px;
    font-weight: 600;
    color: var(--primary-color);
    background: var(--light-gray);
    height: 100%;
    display: flex;
    align-items: center;
}

.amount-hint {
    margin-top: 8px;
    font-size: 0.85rem;
    color: var(--dark-gray);
    opacity: 0.7;
}

/* Description Textarea */
.description-container {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid var(--medium-gray);
    background: white;
    transition: var(--transition);
}

.description-container:focus-within {
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.description-container textarea {
    width: 100%;
    padding: 16px;
    border: none;
    font-size: 1rem;
    line-height: 1.5;
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
    background: transparent;
}

.description-container textarea:focus {
    outline: none;
}

.description-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 16px;
    background: var(--light-gray);
    border-top: 1px solid var(--medium-gray);
    font-size: 0.85rem;
}

.char-count {
    color: var(--success-color);
    font-weight: 500;
}

.hint-text {
    color: var(--dark-gray);
    opacity: 0.7;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Dynamic Payment Methods */
.payment-methods-dynamic {
    margin-top: 20px;
}

.payment-method-dynamic {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    border: 2px solid #e0e7ff;
    transition: var(--transition);
}

.method-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.method-header i {
    font-size: 2.5rem;
    color: var(--secondary-color);
}

.method-title {
    flex: 1;
}

.method-title h4 {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--primary-color);
    margin: 0 0 5px 0;
}

.method-subtitle {
    font-size: 0.85rem;
    color: var(--dark-gray);
    opacity: 0.8;
    margin: 0;
}

.method-badge {
    background: var(--accent-color);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.method-description {
    color: var(--dark-gray);
    font-size: 0.95rem;
    margin-bottom: 20px;
    line-height: 1.5;
    padding-left: 40px;
}

.method-icons {
    display: flex;
    gap: 15px;
    font-size: 2rem;
    color: var(--dark-gray);
    opacity: 0.7;
    padding-left: 40px;
    margin-bottom: 25px;
}

/* Payment Details */
.payment-details {
    margin-top: 25px;
    padding-top: 25px;
    border-top: 2px solid var(--light-gray);
}

.stripe-input {
    padding: 15px;
    border: 2px solid var(--medium-gray);
    border-radius: 8px;
    background: white;
    transition: var(--transition);
}

.stripe-input:focus-within {
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.stripe-errors {
    color: var(--danger-color);
    font-size: 0.9rem;
    margin-top: 15px;
    padding: 15px;
    background: #fee;
    border-radius: 8px;
    border: 1px solid #fcc;
    display: none;
}

/* MoMo Form */
.phone-input-group {
    display: flex;
}

.country-code {
    background: var(--primary-color);
    color: white;
    padding: 15px 20px;
    border: 2px solid var(--primary-color);
    border-radius: 8px 0 0 8px;
    font-weight: 600;
}

.phone-input-group input {
    flex: 1;
    border: 2px solid var(--medium-gray);
    border-left: none;
    border-radius: 0 8px 8px 0;
    padding: 15px;
    font-size: 1rem;
}

.momo-instructions {
    background: #f8f9ff;
    border: 1px solid #e0e7ff;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.momo-instructions h5 {
    color: var(--primary-color);
    font-size: 1rem;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.momo-instructions ol {
    padding-left: 20px;
    color: var(--dark-gray);
    line-height: 1.8;
}

.momo-instructions li {
    margin-bottom: 8px;
}

/* Terms Section */
.terms-section {
    background: linear-gradient(135deg, #f8f9ff 0%, #f1f5ff 100%);
}

.terms-agreement {
    margin-bottom: 30px;
}

.checkbox-group {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 15px;
}

.checkbox-group input[type="checkbox"] {
    margin-top: 5px;
    transform: scale(1.2);
}

.checkbox-group label {
    font-size: 0.95rem;
    color: var(--dark-gray);
    line-height: 1.6;
}

.checkbox-group a {
    color: var(--secondary-color);
    text-decoration: none;
    font-weight: 500;
}

.checkbox-group a:hover {
    text-decoration: underline;
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 1px solid var(--medium-gray);
}

.btn {
    padding: 14px 28px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
    border: none;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-secondary {
    background: white;
    color: var(--dark-gray);
    border: 2px solid var(--medium-gray);
}

.btn-secondary:hover {
    background: var(--light-gray);
    transform: translateY(-2px);
}

.btn-primary {
    background: linear-gradient(135deg, var(--secondary-color) 0%, #2980b9 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
}

.btn-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.btn-lg {
    padding: 16px 40px;
    font-size: 1.1rem;
}

.btn-outline {
    background: transparent;
    color: var(--secondary-color);
    border: 2px solid var(--secondary-color);
}

.btn-outline:hover {
    background: var(--secondary-color);
    color: white;
}

/* Security Notice */
.security-notice {
    display: flex;
    align-items: center;
    gap: 15px;
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-top: 30px;
    border: 1px solid #d4edda;
    background-color: #d4edda;
}

.security-notice i {
    font-size: 2rem;
    color: var(--success-color);
}

.security-notice strong {
    color: var(--primary-color);
    display: block;
    margin-bottom: 5px;
}

.security-notice p {
    color: var(--dark-gray);
    font-size: 0.9rem;
    margin: 0;
    opacity: 0.8;
}

/* Success Message */
.payment-success {
    text-align: center;
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border-radius: 20px;
    padding: 60px 40px;
    margin-bottom: 40px;
    border: 2px solid #28a745;
    box-shadow: 0 15px 35px rgba(40, 167, 69, 0.2);
}

.success-icon {
    font-size: 5rem;
    color: #28a745;
    margin-bottom: 30px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.success-details {
    background: white;
    border-radius: 12px;
    padding: 25px;
    max-width: 500px;
    margin: 30px auto;
    text-align: left;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.success-details p {
    margin-bottom: 12px;
    color: var(--dark-gray);
    font-size: 1rem;
}

.success-details strong {
    color: var(--primary-color);
    min-width: 140px;
    display: inline-block;
}

.success-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 30px;
}

/* Alert */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert-danger {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

/* Notification */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    padding: 16px 24px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 1001;
    animation: slideIn 0.4s ease;
    border-left: 4px solid var(--secondary-color);
}

.notification.success {
    border-left-color: var(--success-color);
    color: #22543d;
}

.notification.error {
    border-left-color: var(--danger-color);
    color: #742a2a;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-hero {
        padding: 50px 20px;
    }
    
    .page-hero h1 {
        font-size: 2rem;
    }
    
    .form-section {
        padding: 20px;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .payment-method-info {
        margin-left: 0;
        width: 100%;
        justify-content: center;
    }
    
    .payment-stepper {
        flex-direction: column;
        gap: 30px;
        align-items: flex-start;
    }
    
    .payment-stepper::before {
        display: none;
    }
    
    .step {
        flex-direction: row;
        gap: 15px;
        width: 100%;
    }
    
    .step-number {
        margin-bottom: 0;
    }
    
    .form-actions {
        flex-direction: column;
        gap: 15px;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .currency-options {
        grid-template-columns: 1fr;
    }
    
    .method-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .method-description, .method-icons {
        padding-left: 0;
    }
    
    .success-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .page-hero h1 {
        font-size: 1.7rem;
    }
    
    .hero-icon-container {
        width: 60px;
        height: 60px;
        font-size: 2rem;
    }
    
    .form-section {
        padding: 15px;
    }
    
    .description-footer {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-section {
    animation: fadeIn 0.5s ease-out;
}

.form-section:nth-child(2) {
    animation-delay: 0.1s;
}

.form-section:nth-child(3) {
    animation-delay: 0.2s;
}

.form-section:nth-child(4) {
    animation-delay: 0.3s;
}
</style>