<?php
declare(strict_types=1);

header('Content-Type: application/json');
require_once 'db.php';

/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
*/
$LOG_FILE = __DIR__ . '/logs/air_reservation.log';

/*
|--------------------------------------------------------------------------
| LOGGER
|--------------------------------------------------------------------------
*/
function log_event(string $message, array $data = []): void
{
    global $LOG_FILE;

    if (!is_dir(dirname($LOG_FILE))) {
        mkdir(dirname($LOG_FILE), 0775, true);
    }

    $entry = "[" . date('Y-m-d H:i:s') . "] " . $message;

    if (!empty($data)) {
        $entry .= " | " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    file_put_contents($LOG_FILE, $entry . PHP_EOL, FILE_APPEND);
}

try {

    /* ------------------------------------------------------------------
     | REQUEST METHOD
     ------------------------------------------------------------------ */
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    log_event('Air reservation submission started');

    /* ------------------------------------------------------------------
     | COLLECT & SANITIZE INPUT (MATCHES FORM)
     ------------------------------------------------------------------ */
    $user_id              = trim($_POST['user_id'] ?? '');
    $full_name            = trim($_POST['full_name'] ?? '');
    $gender               = trim($_POST['gender'] ?? '');
    $email                = trim($_POST['email'] ?? '');

    $phone_area_code      = trim($_POST['phone_area_code'] ?? '');
    $phone_number         = trim($_POST['phone_number'] ?? '');

    $date_of_birth        = $_POST['date_of_birth'] ?? null;
    $nationality_text     = trim($_POST['nationality'] ?? '');

    $passport_number      = trim($_POST['passport_number'] ?? '');
    $passport_expiry      = $_POST['passport_expiry'] ?? null;

    $trip_type            = $_POST['trip_type'] ?? '';
    $departure_city       = trim($_POST['departure_city'] ?? '');
    $destination_city     = trim($_POST['destination_city'] ?? '');

    $departure_date       = $_POST['departure_date'] ?? null;
    $return_date          = !empty($_POST['return_date']) ? $_POST['return_date'] : null;

    $passengers           = max(1, (int)($_POST['passengers'] ?? 1));
    $cabin_class          = strtolower(trim($_POST['cabin_class'] ?? ''));
    $payment_method       = $_POST['payment_method'] ?? '';

    $special_requests     = trim($_POST['special_requests'] ?? '');

    /* ------------------------------------------------------------------
     | VALIDATION (STRICT BUT FAIR)
     ------------------------------------------------------------------ */
    if (
        $user_id === '' ||
        $full_name === '' ||
        $email === '' ||
        $phone_number === '' ||
        !$date_of_birth ||
        $passport_number === '' ||
        !$passport_expiry ||
        $trip_type === '' ||
        $departure_city === '' ||
        $destination_city === '' ||
        !$departure_date ||
        $cabin_class === '' ||
        $payment_method === ''
    ) {
        throw new Exception('Missing required fields');
    }

    /* ------------------------------------------------------------------
     | NORMALIZE ENUMS (DB SAFE)
     ------------------------------------------------------------------ */
    $allowedTripTypes  = ['one_way','round_trip','multi_city'];
    $allowedCabins     = ['economy','business','first'];
    $allowedPayments   = ['mobile_money','bank_transfer','cash'];

    if (!in_array($trip_type, $allowedTripTypes, true)) {
        throw new Exception('Invalid trip type');
    }

    if (!in_array($cabin_class, $allowedCabins, true)) {
        throw new Exception('Invalid cabin class');
    }

    if (!in_array($payment_method, $allowedPayments, true)) {
        throw new Exception('Invalid payment method');
    }

    /* ------------------------------------------------------------------
     | NATIONALITY (TEMP SAFE DEFAULT)
     | You can later map text → countries.id
     ------------------------------------------------------------------ */
    $nationality_id = 163; // Rwanda (safe default for now)

    /* ------------------------------------------------------------------
     | EMERGENCY CONTACT (AUTO-FILL)
     ------------------------------------------------------------------ */
    $emergency_full_name     = $full_name;
    $emergency_relationship = 'Self';
    $emergency_phone         = $phone_area_code . $phone_number;
    $emergency_email         = $email;

    /* ------------------------------------------------------------------
     | INSERT RESERVATION
     ------------------------------------------------------------------ */
    $stmt = $conn->prepare("
        INSERT INTO air_reservations (
            user_id,
            full_name,
            email,
            phone_area_code,
            phone_number,
            date_of_birth,
            nationality_id,
            passport_number,
            passport_expiry,
            trip_type,
            departure_city,
            destination_city,
            departure_date,
            return_date,
            passengers,
            cabin_class,
            special_requests,
            emergency_full_name,
            emergency_relationship,
            emergency_phone,
            emergency_email,
            payment_method
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param(
        "ssssssisssssississssss",
        $user_id,
        $full_name,
        $email,
        $phone_area_code,
        $phone_number,
        $date_of_birth,
        $nationality_id,
        $passport_number,
        $passport_expiry,
        $trip_type,
        $departure_city,
        $destination_city,
        $departure_date,
        $return_date,
        $passengers,
        $cabin_class,
        $special_requests,
        $emergency_full_name,
        $emergency_relationship,
        $emergency_phone,
        $emergency_email,
        $payment_method
    );

    if (!$stmt->execute()) {
        throw new Exception('Insert failed: ' . $stmt->error);
    }

    $reservation_id = $stmt->insert_id;
    $stmt->close();

    log_event('Reservation inserted', [
        'reservation_id' => $reservation_id,
        'user_id' => $user_id
    ]);

    /* ------------------------------------------------------------------
     | AIRLINES (OPTIONAL – FIRST ONE USED)
     ------------------------------------------------------------------ */
    if (!empty($_POST['preferred_airlines']) && is_array($_POST['preferred_airlines'])) {
        $airline_id = (int)$_POST['preferred_airlines'][0];

        if ($airline_id > 0) {
            $stmtAir = $conn->prepare("
                UPDATE air_reservations
                SET airline_id = ?
                WHERE id = ?
            ");
            $stmtAir->bind_param("ii", $airline_id, $reservation_id);
            $stmtAir->execute();
            $stmtAir->close();
        }
    }

    /* ------------------------------------------------------------------
     | SUCCESS
     ------------------------------------------------------------------ */
    log_event('Submission completed successfully');

    echo json_encode([
        'status'  => 'success',
        'user_id' => $user_id
    ]);

} catch (Exception $e) {

    log_event('ERROR', [
        'message' => $e->getMessage(),
        'post' => $_POST
    ]);

    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
