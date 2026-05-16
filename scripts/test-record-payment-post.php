<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/db.php';

$app = $conn->query("SELECT id FROM student_applications ORDER BY id DESC LIMIT 1")->fetch_assoc();
$pkg = $conn->query("SELECT id FROM fee_packages ORDER BY id ASC LIMIT 1")->fetch_assoc();
$item = $conn->query("SELECT id, name, amount FROM fee_items WHERE package_id = " . (int)$pkg['id'] . " LIMIT 1")->fetch_assoc();

if (!$app || !$pkg || !$item) {
    fwrite(STDERR, "Missing test data\n");
    exit(1);
}

// Dev-only: creates a real receipt. Do not run against production data casually.
$payload = json_encode([
    'student_id' => (int)$app['id'],
    'table' => 'student_applications',
    'package_id' => (int)$pkg['id'],
    'payment_method' => 'Bank Transfer',
    'comment' => 'CLI test',
    'request_id' => 'cli-test-' . uniqid('', true),
    'items' => [(string)$item['id'] => min(1.0, (float)$item['amount'])],
]);

$ch = curl_init('http://127.0.0.1/Xander/record-payment.php');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
]);
$body = curl_exec($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP $code\n$body\n";
