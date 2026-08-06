<?php
declare(strict_types=1);
/**
 * End-to-end: sign a contract, then delete via admin-delete-contract.php (AJAX).
 */
require __DIR__ . '/../db.php';
require __DIR__ . '/../includes/contract_fee_repository.php';

// 1) Ensure draft exists
$row = $conn->query("SELECT id, contract_token FROM student_contracts WHERE status='draft' ORDER BY id DESC LIMIT 1")->fetch_assoc();
if (!$row) {
    $token = bin2hex(random_bytes(32));
    $stmt = $conn->prepare("INSERT INTO student_contracts (contract_token, status, created_at) VALUES (?, 'draft', NOW())");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->close();
} else {
    $token = $row['contract_token'];
}

// 2) Sign it
$code = 'p501';
$pkg = getPackageDetails($code);
$png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
$payload = [
    'token' => $token,
    'selected_package_label' => $pkg['title'] ?? $pkg['label'] ?? 'Test',
    'selected_package_code' => $code,
    'student_name' => 'Delete Test',
    'signed_date' => date('Y-m-d'),
    'signature' => 'data:image/png;base64,' . $png,
    'full_name' => 'Delete Test',
    'student_email' => 'delete.test@example.com',
    'student_dob' => '1990-01-01',
    'student_passport' => 'P999',
    'student_nationality' => 'Rwanda',
    'student_phone' => '+250700000000',
    'student_country' => 'Rwanda',
    'student_address' => 'Kigali',
    'client_type' => ['Student'],
];

$ch = curl_init('http://localhost/xander/submit-signature.php');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
]);
$signBody = curl_exec($ch);
curl_close($ch);
$signJson = json_decode((string) $signBody, true);
$contractId = (int) ($signJson['contract_id'] ?? 0);
echo "Sign: " . ($signJson['success'] ?? false ? 'OK' : 'FAIL') . " contract_id={$contractId}\n";
if (!$contractId) {
    echo $signBody . "\n";
    exit(1);
}

// 3) Admin delete via AJAX
session_start();
$_SESSION['admin_id'] = 1;
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

$post = http_build_query([
    'contract_id' => $contractId,
    'csrf_token' => $csrf,
    'ajax' => '1',
]);

$ch = curl_init('http://localhost/xander/admin-delete-contract.php');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded',
        'X-Requested-With: XMLHttpRequest',
        'Cookie: ' . session_name() . '=' . session_id(),
    ],
    CURLOPT_POSTFIELDS => $post,
    CURLOPT_RETURNTRANSFER => true,
]);
$delBody = curl_exec($ch);
$delCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Delete HTTP {$delCode}: {$delBody}\n";

$still = $conn->query("SELECT id FROM student_contracts WHERE id={$contractId}")->num_rows;
echo $still ? "FAIL: row still exists\n" : "OK: row removed\n";
