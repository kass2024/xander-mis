<?php
declare(strict_types=1);
/**
 * End-to-end contract sign smoke test (local CLI).
 */
require 'C:/xampp/htdocs/Xander/db.php';
require 'C:/xampp/htdocs/Xander/includes/contract_fee_repository.php';
require 'C:/xampp/htdocs/Xander/includes/contract_pdf_helpers.php';

$row = $conn->query("SELECT id, contract_token FROM student_contracts WHERE status='draft' ORDER BY id DESC LIMIT 1")->fetch_assoc();
if (!$row) {
    echo "No draft contract\n";
    exit(1);
}

$token = $row['contract_token'];
$code = 'p501';
$pkg = getPackageDetails($code);
if (!$pkg) {
    echo "Package missing\n";
    exit(1);
}

// 1x1 PNG
$png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
$sig = 'data:image/png;base64,' . $png;

$payload = [
    'token' => $token,
    'selected_package_label' => $pkg['title'],
    'selected_package_code' => $code,
    'student_name' => 'Test Student',
    'signed_date' => date('Y-m-d'),
    'signature' => $sig,
    'full_name' => 'Test Student',
    'student_email' => 'test.contract@example.com',
    'student_dob' => '1990-01-01',
    'student_passport' => 'P123456',
    'student_nationality' => 'Rwanda',
    'student_phone' => '+250788000000',
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
    CURLOPT_TIMEOUT => 120,
]);
$body = curl_exec($ch);
$codeHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP {$codeHttp}\n{$body}\n";
