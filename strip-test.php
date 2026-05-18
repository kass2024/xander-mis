<?php

require_once __DIR__ . '/helpers/payment_config.php';
$secretKey = xander_payment_require_stripe_keys()['secret'];

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/balance");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $secretKey . ":");
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Stripe LIVE key works\n";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
} else {
    echo "❌ Stripe error\n";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}
