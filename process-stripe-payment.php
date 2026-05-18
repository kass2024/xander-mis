<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/payment_config.php';

header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

$student_id = $input['student_id'] ?? 0;
$package_id = $input['package_id'] ?? 0;
$items = $input['items'] ?? [];
$total_amount = $input['total_amount'] ?? 0;
$payment_method_id = $input['payment_method_id'] ?? '';

// Validate required fields
if (!$student_id || !$package_id || empty($items) || !$total_amount || !$payment_method_id) {
    echo json_encode(['error' => 'Missing required payment information']);
    exit();
}

$STRIPE_SECRET_KEY = xander_payment_require_stripe_keys()['secret'];

try {
    // Create payment intent
    $payment_intent_data = [
        'amount' => (int)($total_amount * 100), // Convert to cents
        "currency" => "eur",
        'payment_method' => $payment_method_id,
        'confirmation_method' => 'manual',
        'confirm' => true,
        'metadata' => [
            'student_id' => $student_id,
            'package_id' => $package_id,
            'source' => 'payment_portal'
        ]
    ];

    $ch = curl_init('https://api.stripe.com/v1/payment_intents');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payment_intent_data));
    curl_setopt($ch, CURLOPT_USERPWD, $STRIPE_SECRET_KEY . ':');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        throw new Exception('Stripe API error: ' . $response);
    }

    $payment_intent = json_decode($response, true);

    if (isset($payment_intent['error'])) {
        throw new Exception($payment_intent['error']['message']);
    }

    // Return client secret for frontend confirmation
    echo json_encode([
        'client_secret' => $payment_intent['client_secret'],
        'payment_id' => $payment_intent['id']
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
