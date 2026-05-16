<?php
session_start();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once 'db.php';
require_once 'database.php';

// Ensure logs directory exists
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) mkdir($logDir, 0777, true);
$logFile = $logDir . '/commission_debug.log';
function logError($msg) {
    global $logFile;
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n", FILE_APPEND);
}

$response = ['status' => 'error', 'message' => 'Unknown error occurred'];

try {
    // --- 1. Check login ---
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) throw new Exception('Agent not logged in.');

    // --- 2. Validate required fields ---
    $required = ['first_name','last_name','email','phone','recruited_student_id','date','signature'];
    foreach ($required as $f) {
        if (empty($_POST[$f])) throw new Exception("Missing required field: $f");
    }

    // --- 3. Determine student source ---
    $studentKey = trim($_POST['recruited_student_id']);
    $prefix = substr($studentKey, 0, 2);
    $studentId = (int) substr($studentKey, 2);
    $recruited_name = '';
    $recruited_phone = '';

    if ($prefix === 's_') {
        $stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name, phone_number FROM student_applications WHERE id = ?");
        $stmt->bind_param("i", $studentId);
    } elseif ($prefix === 'a_') {
        $stmt = $conn2->prepare("SELECT name FROM applications WHERE id = ?");
        $stmt->bind_param("i", $studentId);
    } else {
        throw new Exception('Invalid student reference.');
    }

    if (!$stmt->execute()) throw new Exception("Student lookup failed: " . $stmt->error);
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $recruited_name = trim($row['name']);
        $recruited_phone = trim($row['phone_number'] ?? '');
    } else {
        throw new Exception('Student not found.');
    }
    $stmt->close();

    // --- 4. Collect form data safely ---
    $first_name      = $_POST['first_name'];
    $last_name       = $_POST['last_name'];
    $email           = $_POST['email'];
    $phone           = $_POST['phone'];
    $street_address  = $_POST['street_address'] ?? '';
    $address_line_2  = $_POST['address_line_2'] ?? '';
    $city            = $_POST['city'] ?? '';
    $state           = $_POST['state'] ?? '';
    $postal_code     = $_POST['postal_code'] ?? '';
    $country_applied = $_POST['country_applied'] ?? '';
    $loan_status     = $_POST['loan_status'] ?? '';
    $visa_status     = $_POST['visa_status'] ?? '';
    $contract_signed = $_POST['contract_signed'] ?? '';
    $comments        = $_POST['comments'] ?? '';
    $submission_date = $_POST['date'];
    $signature       = $_POST['signature'];

    // --- 5. Insert query ---
    $sql = "INSERT INTO commission_requests (
        user_id, first_name, last_name, email, phone,
        street_address, address_line_2, city, state, postal_code,
        recruited_name, recruited_phone, country_applied,
        loan_status, visa_status, contract_signed,
        comments, submission_date, signature, recruited_student_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

    // 🔧 Correct binding (20 vars, 20 type chars)
    $stmt->bind_param(
        "sssssssssssssssssssi",
        $userId,
        $first_name,
        $last_name,
        $email,
        $phone,
        $street_address,
        $address_line_2,
        $city,
        $state,
        $postal_code,
        $recruited_name,
        $recruited_phone,
        $country_applied,
        $loan_status,
        $visa_status,
        $contract_signed,
        $comments,
        $submission_date,
        $signature,
        $studentId
    );

    if (!$stmt->execute()) throw new Exception("Insert failed: " . $stmt->error);

    $response = ['status' => 'success', 'message' => '✅ Commission request submitted successfully.'];

} catch (Throwable $e) {
    logError($e->getMessage());
    $response = ['status' => 'error', 'message' => $e->getMessage()];
} finally {
    if (isset($stmt) && $stmt) $stmt->close();
    if (isset($conn) && $conn) $conn->close();
    if (isset($conn2) && $conn2) $conn2->close();
}

echo json_encode($response);
exit;
