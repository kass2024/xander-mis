<?php
declare(strict_types=1);

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/includes/contract_signature_schema.php";
require_once __DIR__ . "/includes/contract_package_map.php";

header("Content-Type: application/json");

/* =====================================================
   CONFIG
===================================================== */
$LOG_FILE = __DIR__ . "/logs/contract-signing.log";

/* =====================================================
   HELPERS
===================================================== */
function logMsg(string $msg, array $data = []): void {
    global $LOG_FILE;

    if (!is_dir(dirname($LOG_FILE))) {
        mkdir(dirname($LOG_FILE), 0777, true);
    }

    file_put_contents(
        $LOG_FILE,
        "[" . date("Y-m-d H:i:s") . "] {$msg} " . json_encode($data) . PHP_EOL,
        FILE_APPEND
    );
}

function respond(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function fail(string $message, int $code = 400, array $debug = []): void {
    logMsg("FAIL: {$message}", $debug);
    respond([
        "success" => false,
        "error"   => $message
    ], $code);
}

/* =====================================================
   1. READ INPUT
===================================================== */
$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

logMsg("RAW INPUT", ["raw" => $raw]);

if (!is_array($data)) {
    fail("Invalid JSON payload", 400);
}

/* =====================================================
   2. EXTRACT DATA
===================================================== */
/* --- CORE --- */
$token      = trim($data['token'] ?? '');
$name       = trim($data['student_name'] ?? '');
$signedDate = trim($data['signed_date'] ?? '');
$signature  = $data['signature'] ?? '';

/* --- ARTICLE 7 PACKAGE --- */
$pkgLabel = trim($data['selected_package_label'] ?? '');
$pkgCode  = trim($data['selected_package_code'] ?? '');

/* --- STUDENT --- */
$email       = trim($data['student_email'] ?? '');
$dob         = $data['student_dob'] ?? null;
$nationality = trim($data['student_nationality'] ?? '');
$passport    = trim($data['student_passport'] ?? '');
$phone       = trim($data['student_phone'] ?? '');

/* =====================================================
   3. HARD VALIDATION
===================================================== */
if (
    $token === '' ||
    $name === '' ||
    $signedDate === '' ||
    $email === '' ||
    $signature === '' ||
    $pkgLabel === '' ||
    $pkgCode === ''
) {
    fail("Missing required fields", 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail("Invalid email address", 400);
}

if (!str_starts_with($signature, 'data:image/png;base64,')) {
    fail("Invalid signature format", 400);
}

if (!getPackageDetails($pkgCode)) {
    fail("Invalid fee package selection", 400);
}

xander_ensure_contract_signature_columns($conn);

/* =====================================================
   4. LOAD CONTRACT (NO LOCK YET)
===================================================== */
$stmt = $conn->prepare("
    SELECT id, status
    FROM student_contracts_special
    WHERE contract_token = ?
    LIMIT 1
");
$stmt->bind_param("s", $token);
$stmt->execute();
$contract = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$contract) {
    fail("Contract not found", 404);
}

/* =====================================================
   5. ALREADY SIGNED = SUCCESS (IDEMPOTENT)
===================================================== */
if ($contract['status'] === 'signed') {
    respond([
        "success" => true,
        "status"  => "already_signed",
        "message" => "This contract has already been signed."
    ]);
}

$contractId = (int) $contract['id'];

/* =====================================================
   6. TRANSACTION
===================================================== */
$conn->begin_transaction();

try {

    /* =====================================================
       6.1 LOCK CONTRACT ROW
    ===================================================== */
    $stmt = $conn->prepare("
        SELECT id
        FROM student_contracts_special
        WHERE id = ?
        FOR UPDATE
    ");
    $stmt->bind_param("i", $contractId);
    $stmt->execute();
    $stmt->close();

    logMsg("Contract locked", ["contract_id" => $contractId]);

    /* =====================================================
       6.2 SAVE SIGNATURE (client snapshot — not student_applications)
    ===================================================== */
    $stmt = $conn->prepare("
        INSERT INTO student_signatures_special
        (contract_id, student_name, student_email, signed_date, signature_image,
         client_dob, client_nationality, client_passport, client_phone, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            student_name = VALUES(student_name),
            student_email = VALUES(student_email),
            signed_date = VALUES(signed_date),
            signature_image = VALUES(signature_image),
            client_dob = VALUES(client_dob),
            client_nationality = VALUES(client_nationality),
            client_passport = VALUES(client_passport),
            client_phone = VALUES(client_phone)
    ");
    $stmt->bind_param(
        "issssssss",
        $contractId,
        $name,
        $email,
        $signedDate,
        $signature,
        $dob,
        $nationality,
        $passport,
        $phone
    );
    $stmt->execute();
    $stmt->close();

    /* =====================================================
       6.3 FINALIZE CONTRACT
    ===================================================== */
    $stmt = $conn->prepare("
        UPDATE student_contracts_special SET
            status                 = 'signed',
            signed_at              = NOW(),
            selected_package_code  = ?,
            selected_package_label = ?
        WHERE id = ?
    ");
    $stmt->bind_param(
        "ssi",
        $pkgCode,
        $pkgLabel,
        $contractId
    );
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    logMsg("Contract finalized", ["contract_id" => $contractId]);

} catch (Throwable $e) {
    $conn->rollback();
    fail("Signing failed", 500, [
        "message" => $e->getMessage(),
        "line"    => $e->getLine()
    ]);
}

/* =====================================================
   7. GENERATE PDF (POST-COMMIT)
===================================================== */
require_once __DIR__ . "/generate-contract-pdf-special.php";

if (!function_exists('generateContractPDF')) {
    fail("PDF generator missing", 500);
}

$pdfPath = generateContractPDF($contractId);

if (!$pdfPath || !file_exists($pdfPath)) {
    fail("PDF generation failed", 500);
}

/* =====================================================
   8. SAVE PDF PATH
===================================================== */
$stmt = $conn->prepare("
    UPDATE student_contracts_special
    SET pdf_path = ?
    WHERE id = ?
");
$stmt->bind_param("si", $pdfPath, $contractId);
$stmt->execute();
$stmt->close();

/* =====================================================
   9. FINAL RESPONSE (ALWAYS JSON)
===================================================== */
respond([
    "success"      => true,
    "status"       => "signed",
    "contract_id"  => $contractId,
    "pdf_path"     => $pdfPath
]);
