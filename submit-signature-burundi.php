<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/burundi_contract_db.php';
require_once __DIR__ . '/includes/contract_signature_schema.php';
require_once __DIR__ . '/includes/contract_package_map.php';

header('Content-Type: application/json');

xander_ensure_burundi_contract_tables($conn);
xander_ensure_contract_signature_columns($conn);

function respond(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function fail(string $message, int $code = 400): void
{
    respond(['success' => false, 'error' => $message], $code);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    fail('Invalid JSON payload');
}

$token       = trim((string) ($data['token'] ?? ''));
$name        = trim((string) ($data['student_name'] ?? ''));
$signedDate  = trim((string) ($data['signed_date'] ?? ''));
$signature   = $data['signature'] ?? '';
$email       = trim((string) ($data['student_email'] ?? ''));
$dob         = $data['student_dob'] ?? null;
$nationality = trim((string) ($data['student_nationality'] ?? ''));
$passport    = trim((string) ($data['student_passport'] ?? ''));
$phone       = trim((string) ($data['student_phone'] ?? ''));
$residence   = trim((string) ($data['client_residence'] ?? ''));
$address     = trim((string) ($data['client_address'] ?? ''));
$clientType  = trim((string) ($data['client_type'] ?? ''));
$pkgCode     = trim((string) ($data['selected_package_code'] ?? ''));
$effective   = trim((string) ($data['effective_date'] ?? ''));
$effectiveDb = ($effective !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $effective)) ? $effective : null;

if ($token === '' || $name === '' || $signedDate === '' || $email === '' || $signature === '' || $clientType === '' || $pkgCode === '') {
    fail('Missing required fields');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('Invalid email address');
}
if (!str_starts_with($signature, 'data:image/png;base64,')) {
    fail('Invalid signature format');
}

$pkg = getPackageDetails($pkgCode);
if (!$pkg) {
    fail('Invalid fee package selection');
}
$pkgLabel = (string) $pkg['title'];

$stmt = $conn->prepare('SELECT id, status FROM student_contracts_burundi WHERE contract_token = ? LIMIT 1');
$stmt->bind_param('s', $token);
$stmt->execute();
$contract = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$contract) {
    fail('Contract not found', 404);
}
if ($contract['status'] === 'signed') {
    respond(['success' => true, 'status' => 'already_signed', 'message' => 'This contract has already been signed.']);
}

$contractId = (int) $contract['id'];
$conn->begin_transaction();

try {
    $stmt = $conn->prepare('SELECT id FROM student_contracts_burundi WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $contractId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare(
        'INSERT INTO student_signatures_burundi (
            contract_id, student_name, student_email, signed_date, signature_image,
            client_residence, client_address, client_dob, client_nationality, client_passport, client_phone, client_type, effective_date, created_at
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())
        ON DUPLICATE KEY UPDATE
            student_name = VALUES(student_name),
            student_email = VALUES(student_email),
            signed_date = VALUES(signed_date),
            signature_image = VALUES(signature_image),
            client_residence = VALUES(client_residence),
            client_address = VALUES(client_address),
            client_dob = VALUES(client_dob),
            client_nationality = VALUES(client_nationality),
            client_passport = VALUES(client_passport),
            client_phone = VALUES(client_phone),
            client_type = VALUES(client_type),
            effective_date = VALUES(effective_date)'
    );
    $stmt->bind_param(
        'issssssssssss',
        $contractId,
        $name,
        $email,
        $signedDate,
        $signature,
        $residence,
        $address,
        $dob,
        $nationality,
        $passport,
        $phone,
        $clientType,
        $effectiveDb
    );
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare(
        "UPDATE student_contracts_burundi SET status='signed', signed_at=NOW(), selected_package_code=?, selected_package_label=? WHERE id=?"
    );
    $stmt->bind_param('ssi', $pkgCode, $pkgLabel, $contractId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    fail('Signing failed: ' . $e->getMessage(), 500);
}

require_once __DIR__ . '/generate-contract-pdf-burundi.php';
if (!function_exists('generateBurundiContractPDF')) {
    fail('PDF generator missing', 500);
}

$pdfPath = generateBurundiContractPDF($contractId);
if (!$pdfPath || !is_file($pdfPath)) {
    fail('PDF generation failed', 500);
}

$stmt = $conn->prepare('UPDATE student_contracts_burundi SET pdf_path = ? WHERE id = ?');
$stmt->bind_param('si', $pdfPath, $contractId);
$stmt->execute();
$stmt->close();

respond([
    'success' => true,
    'status'  => 'signed',
    'message' => 'Burundi contract signed successfully.',
    'contract_id' => $contractId,
]);
