<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/db.php';

$app = $conn->query("SELECT id FROM student_applications ORDER BY id DESC LIMIT 1")->fetch_assoc();
$pkg = $conn->query("SELECT id FROM fee_packages ORDER BY id ASC LIMIT 1")->fetch_assoc();
$item = $conn->query("SELECT id, amount FROM fee_items WHERE package_id = " . (int) $pkg['id'] . " LIMIT 1")->fetch_assoc();

$requestId = 'test-idem-' . time();
$payload = json_encode([
    'student_id' => (int) $app['id'],
    'table' => 'student_applications',
    'package_id' => (int) $pkg['id'],
    'payment_method' => 'Bank Transfer',
    'comment' => 'Idempotency test',
    'request_id' => $requestId,
    'items' => [(string) $item['id'] => 0.01],
]);

function postPayment(string $payload): array
{
    $ch = curl_init('http://127.0.0.1/Xander/record-payment.php');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body, 'json' => json_decode($body, true)];
}

$r1 = postPayment($payload);
$r2 = postPayment($payload);

echo "First:  HTTP {$r1['code']} " . ($r1['json']['receipt_no'] ?? '') . PHP_EOL;
echo "Second: HTTP {$r2['code']} duplicate=" . json_encode($r2['json']['duplicate'] ?? false) . ' receipt=' . ($r2['json']['receipt_no'] ?? '') . PHP_EOL;
