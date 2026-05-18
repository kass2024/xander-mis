<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/payment_config.php';

header('Content-Type: application/json; charset=utf-8');

// Get POST data
$rawInput = file_get_contents('php://input');
if ($rawInput === false || trim($rawInput) === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Empty request body']);
    exit;
}

$data = json_decode($rawInput, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

// Extract data
$amount = (int)($data['amount'] ?? 0);
$currency = $data['currency'] ?? 'USD';
$email = $data['email'] ?? '';
$name = $data['name'] ?? '';

// Validate
if ($amount <= 0 || empty($email) || empty($name)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing required fields']);
    exit;
}

$stripeSecretKey = xander_payment_require_stripe_keys()['secret'];

// Create payment intent
$payload = http_build_query([
    'amount' => $amount,
    'currency' => $currency,
    'payment_method_types[]' => 'card',
    'description' => "Payment from {$name} ({$email})",
    'receipt_email' => $email
]);

$ch = curl_init('https://api.stripe.com/v1/payment_intents');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_USERPWD, $stripeSecretKey . ':');
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Payment gateway error']);
    curl_close($ch);
    exit;
}

curl_close($ch);

$paymentIntent = json_decode($response, true);

if (!isset($paymentIntent['client_secret'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to create payment intent']);
    exit;
}

echo json_encode([
    'success' => true,
    'client_secret' => $paymentIntent['client_secret'],
    'payment_intent_id' => $paymentIntent['id']
]);
?>
