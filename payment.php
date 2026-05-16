<?php
$debug_mode = (string)($_GET['debug'] ?? '') === '1';
if ($debug_mode) {
    @ini_set('display_errors', '1');
    @ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

if ($debug_mode && (string)($_GET['ping'] ?? '') === '1') {
    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo "payment.php ping ok\n";
    echo "php_version=" . PHP_VERSION . "\n";
    exit;
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && strpos((string)$haystack, (string)$needle) !== false;
    }
}

$__app_error_log_dir = is_writable(__DIR__) ? __DIR__ : (string)sys_get_temp_dir();
$__app_error_log = rtrim($__app_error_log_dir, '/\\') . '/payment_fatal.log';
@ini_set('log_errors', '1');
@ini_set('error_log', $__app_error_log);

register_shutdown_function(function () use ($debug_mode, $__app_error_log) {
    $err = error_get_last();
    if (!$err) return;
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    if (!in_array((int)($err['type'] ?? 0), $fatalTypes, true)) return;

    $msg = '[FATAL] ' . ($err['message'] ?? 'Unknown error')
        . ' in ' . ($err['file'] ?? 'unknown')
        . ':' . ($err['line'] ?? '0');
    error_log($msg);

    if ($debug_mode) {
        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8');
        }
        echo "\n\n" . $msg . "\nLogged to: " . $__app_error_log . "\n";
    }
});

if (!file_exists(__DIR__ . '/db.php')) {
    die('Database configuration file (db.php) is missing.');
}
require_once __DIR__ . '/db.php';

if (!class_exists('mysqli')) {
    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo $debug_mode
        ? "Server error: PHP mysqli extension is not enabled. Enable mysqli in PHP."
        : 'Server error. Please contact support.';
    exit;
}

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_errno) {
    $msg = 'Server error: database connection failed.';
    if ($debug_mode) {
        $detail = '';
        if (isset($conn) && $conn instanceof mysqli) {
            $detail = (string)$conn->connect_error;
        } elseif (function_exists('mysqli_connect_error')) {
            $detail = (string)mysqli_connect_error();
        }
        if ($detail !== '') {
            $msg .= ' ' . $detail;
        }
    }
    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo $debug_mode ? $msg : 'Server error. Please contact support.';
    exit;
}

$receipt_pdf_available = file_exists(__DIR__ . '/generateReceiptPdf.php');
if ($receipt_pdf_available) {
    require_once __DIR__ . '/generateReceiptPdf.php';
}

function generateUUID() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function stmt_fetch_assoc(mysqli_stmt $stmt) {
    $meta = $stmt->result_metadata();
    if (!$meta) return null;
    $row = [];
    $params = [];
    while ($field = $meta->fetch_field()) {
        $row[$field->name] = null;
        $params[] = &$row[$field->name];
    }
    if ($params) {
        call_user_func_array([$stmt, 'bind_result'], $params);
    }
    return $stmt->fetch() ? $row : null;
}

// =====================================================
// PHPMailer Autoload (adjust paths if needed)
// =====================================================
$phpmailer_available =
    file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')
    && file_exists(__DIR__ . '/PHPMailer/src/SMTP.php')
    && file_exists(__DIR__ . '/PHPMailer/src/Exception.php');

if ($phpmailer_available) {
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
}

$momo_mode = (string)($_GET['momo'] ?? '') === '1';
$momo_success = false;
$momo_error = '';
$momo_response_data = null;
$momo_api_status = null;
$momo_api_message = '';

$momo_reference = trim((string)($_GET['ref'] ?? ''));
$momo_db_status = '';
$momo_student_id = (int)($_GET['student_id'] ?? 0);
$momo_verify_debug = [
    'curl_error' => '',
    'http_code' => '',
    'provider_status' => '',
    'raw_response' => '',
];
$momo_base_url = 'payment.php?momo=1'
    . ($momo_reference !== '' ? '&ref=' . urlencode($momo_reference) : '')
    . ($momo_student_id > 0 ? '&student_id=' . $momo_student_id : '')
    . ($debug_mode ? '&debug=1' : '');
if ($momo_mode && $momo_reference !== '') {
    $stmt = $conn->prepare("SELECT transaction_status FROM payments WHERE momo_reference = ? OR transaction_id = ? LIMIT 1");
    if (!$stmt) {
        error_log('MoMo status read prepare failed: ' . mysqli_error($conn));
    } else {
        $stmt->bind_param('ss', $momo_reference, $momo_reference);
        $stmt->execute();
        $row = stmt_fetch_assoc($stmt);
        $stmt->close();
        $momo_db_status = (string)($row['transaction_status'] ?? '');
    }

    if ($momo_db_status === '') {
        $momo_db_status = 'pending';
    }

    // Verify against ItecPay
    $verifyPayload = json_encode([
        'action' => 'status_check',
        'req_ref' => $momo_reference,
        'key' => 'eGx562IiN7y31CmZCnYgFWahrSBi/BbXSz5+6ElZvoCBtf4sYdZ2NipQ8UlKoZ0v9vH6wPtAHEjvNANLqYy3yw==',
    ]);

    $verifyResp = null;
    $verifyCurlErr = '';
    $verifyHttpCode = '';
    if (!function_exists('curl_init')) {
        $verifyCurlErr = 'PHP cURL extension is not enabled.';
        error_log('MoMo verify failed: ' . $verifyCurlErr);
    } else {
        $ch = curl_init();
        $opts = [
            CURLOPT_URL => 'https://pay.itecpay.rw/api2/verify',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $verifyPayload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ];
        curl_setopt_array($ch, $opts);
        $verifyResp = curl_exec($ch);
        $verifyCurlErr = curl_error($ch);
        $verifyHttpCode = (string)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($verifyCurlErr && stripos($verifyCurlErr, 'ssl') !== false) {
            $ch = curl_init();
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
            $opts[CURLOPT_SSL_VERIFYHOST] = false;
            curl_setopt_array($ch, $opts);
            $verifyResp = curl_exec($ch);
            $verifyCurlErr = curl_error($ch);
            $verifyHttpCode = (string)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }
    }

    if ($debug_mode) {
        $momo_verify_debug['curl_error'] = (string)$verifyCurlErr;
        $momo_verify_debug['http_code'] = (string)$verifyHttpCode;
        $momo_verify_debug['raw_response'] = is_string($verifyResp) ? trim($verifyResp) : '';
    }

    if (!$verifyCurlErr && is_string($verifyResp) && trim($verifyResp) !== '') {
        $verifyData = json_decode($verifyResp, true);
        $providerStatus = strtoupper(trim((string)($verifyData['data']['status'] ?? '')));
        if ($debug_mode) {
            $momo_verify_debug['provider_status'] = (string)$providerStatus;
        }
        if ($providerStatus !== '') {
            $mapped = 'pending';
            if ($providerStatus === 'SUCCESSFUL' || $providerStatus === 'SUCCESS') {
                $mapped = 'completed';
            } elseif ($providerStatus === 'PENDING') {
                $mapped = 'pending';
            } elseif ($providerStatus === 'CANCELLED' || $providerStatus === 'CANCELED') {
                $mapped = 'cancelled';
            } else {
                $mapped = 'failed';
            }

            if ($mapped !== '' && strtolower($momo_db_status) !== $mapped) {
                $stmt = $conn->prepare("UPDATE payments SET transaction_status = ? WHERE momo_reference = ? OR transaction_id = ?");
                if (!$stmt) {
                    error_log('MoMo status update prepare failed: ' . mysqli_error($conn));
                } else {
                    $stmt->bind_param('sss', $mapped, $momo_reference, $momo_reference);
                    $stmt->execute();
                    $stmt->close();
                }
                $momo_db_status = $mapped;

                if ($mapped === 'completed') {
                    $stmt = $conn->prepare("SELECT full_name, email, amount, transaction_status FROM payments WHERE momo_reference = ? OR transaction_id = ? LIMIT 1");
                    if (!$stmt) {
                        error_log('MoMo payment read prepare failed: ' . mysqli_error($conn));
                    } else {
                        $stmt->bind_param('ss', $momo_reference, $momo_reference);
                        $stmt->execute();
                        $p = stmt_fetch_assoc($stmt);
                        $stmt->close();
                        if ($p) {
                            sendMomoReceiptEmail(
                                (string)($p['email'] ?? ''),
                                (string)($p['full_name'] ?? ''),
                                (int)round((float)($p['amount'] ?? 0)),
                                $momo_reference,
                                'COMPLETED'
                            );
                        }
                    }
                }
            }
        }
    }
}

$fx_to_rwf = [
    'RWF' => 1.0,
    'USD' => 1450.82,
    'EUR' => 1686.50,
];

$momo_amount_max_rwf = 500000;

if ($momo_mode && isset($_POST['btn'])) {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $phone = preg_replace('/\D+/', '', $phone);

    $amount = (int)round((float)($_POST['amount'] ?? 0));
    $selected_network = 'MTN';
    $payment_method = (string)($_POST['payment_method'] ?? 'momo');
    $req_ref = generateUUID();

    if ($amount > $momo_amount_max_rwf) {
        $momo_error = "Maximum allowed amount for MTN Mobile Money is {$momo_amount_max_rwf} RWF. Please reduce the amount or pay by card.";
    }

    if ($momo_error === '') {
        if (!function_exists('curl_init')) {
            $momo_error = 'Server configuration error: PHP cURL extension is not enabled. Please enable cURL on the server.';
        }
    }

    if ($momo_error === '') {
        $payload = json_encode([
            "amount"  => $amount,
            "phone"   => $phone,
            "key"     => "eGx562IiN7y31CmZCnYgFWahrSBi/BbXSz5+6ElZvoCBtf4sYdZ2NipQ8UlKoZ0v9vH6wPtAHEjvNANLqYy3yw==",
            "req_ref" => $req_ref,
            "note"    => "Payment from $name",
            "message" => "MoMo payment for $name",
            "callback_url" => "https://xanderglobalscholars.com/momo-callback.php?secret=" . urlencode('MOMO_CB_9fA8kKx_2026_SECURE'),
            "return_url" => "https://xanderglobalscholars.com/payment.php?momo=1"
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://pay.itecpay.rw/api2/pay',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);

        $response = curl_exec($curl);
        $curl_error = curl_error($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($curl_error) {
            $momo_error = "Connection error: $curl_error";
        } else {
            $momo_response_data = json_decode((string)$response, true);

            $momo_api_status  = $momo_response_data['status'] ?? $http_code;
            $momo_api_message = $momo_response_data['data']['message']
                            ?? $momo_response_data['message']
                            ?? 'No message returned.';

            if ((string)$momo_api_status === '200' || (string)$momo_api_status === 'success') {
                $momo_success = true;

                $req_ref_saved = mysqli_real_escape_string($conn, (string)($momo_response_data['data']['req_ref'] ?? $req_ref));
                $network       = mysqli_real_escape_string($conn, (string)($momo_response_data['data']['network'] ?? $selected_network));
                $name_e        = mysqli_real_escape_string($conn, $name);
                $email_e       = mysqli_real_escape_string($conn, $email);
                $phone_e       = mysqli_real_escape_string($conn, $phone);
                $method_e      = mysqli_real_escape_string($conn, $payment_method);
                $ip_e          = mysqli_real_escape_string($conn, (string)($_SERVER['REMOTE_ADDR'] ?? ''));
                $ua_e          = mysqli_real_escape_string($conn, (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));

                $query = mysqli_query(
                    $conn,
                    "INSERT INTO payments
                        (full_name, email, phone_number, amount, payment_method,
                         transaction_status, transaction_id, momo_reference,
                         network_provider, payment_date, ip_address, user_agent)
                     VALUES
                        ('$name_e', '$email_e', '$phone_e', $amount, '$method_e',
                         'pending', '$req_ref_saved', '$req_ref_saved', '$network', NOW(), '$ip_e', '$ua_e')"
                );

                if (!$query) {
                    $momo_error = 'Database error: ' . mysqli_error($conn);
                    $momo_success = false;
                } else {
                    $momo_reference = (string)$req_ref_saved;
                    $momo_db_status = 'pending';
                    $emailSent = sendMomoThankYouEmail($email, $name, $amount, $req_ref_saved);
                    if (!$emailSent && $debug_mode) {
                        $momo_error = 'Payment initiated, but the thank-you email was not sent. Check server error_log for details.';
                    }
                }
            } else {
                $rawMsg = (string)$momo_api_message;
                $rawLower = strtolower($rawMsg);

                if ($rawLower !== '' && (
                    str_contains($rawLower, 'insufficient')
                    || str_contains($rawLower, 'not enough')
                    || str_contains($rawLower, 'low balance')
                    || str_contains($rawLower, 'no funds')
                    || str_contains($rawLower, 'insufficient funds')
                )) {
                    $momo_error = 'Payment failed: insufficient balance in your Mobile Money account. Please top up and try again.';
                    if ($debug_mode) {
                        $momo_error .= ' Gateway message: ' . $rawMsg;
                    }
                } else {
                    $momo_error = $rawMsg !== '' ? $rawMsg : 'Payment failed. Please try again.';
                    if ($debug_mode && $rawMsg === '') {
                        $momo_error .= ' (No message returned by payment gateway.)';
                    }
                }
            }
        }
    }
}

// =====================================================
// Country codes list (full)
// =====================================================
$country_codes = [
    ['code' => '93', 'name' => 'Afghanistan (+93)'],
    ['code' => '355', 'name' => 'Albania (+355)'],
    ['code' => '213', 'name' => 'Algeria (+213)'],
    ['code' => '1', 'name' => 'United States/Canada (+1)'],
    ['code' => '54', 'name' => 'Argentina (+54)'],
    ['code' => '374', 'name' => 'Armenia (+374)'],
    ['code' => '61', 'name' => 'Australia (+61)'],
    ['code' => '43', 'name' => 'Austria (+43)'],
    ['code' => '994', 'name' => 'Azerbaijan (+994)'],
    ['code' => '973', 'name' => 'Bahrain (+973)'],
    ['code' => '880', 'name' => 'Bangladesh (+880)'],
    ['code' => '375', 'name' => 'Belarus (+375)'],
    ['code' => '32', 'name' => 'Belgium (+32)'],
    ['code' => '501', 'name' => 'Belize (+501)'],
    ['code' => '229', 'name' => 'Benin (+229)'],
    ['code' => '975', 'name' => 'Bhutan (+975)'],
    ['code' => '591', 'name' => 'Bolivia (+591)'],
    ['code' => '387', 'name' => 'Bosnia and Herzegovina (+387)'],
    ['code' => '267', 'name' => 'Botswana (+267)'],
    ['code' => '55', 'name' => 'Brazil (+55)'],
    ['code' => '673', 'name' => 'Brunei (+673)'],
    ['code' => '359', 'name' => 'Bulgaria (+359)'],
    ['code' => '226', 'name' => 'Burkina Faso (+226)'],
    ['code' => '257', 'name' => 'Burundi (+257)'],
    ['code' => '238', 'name' => 'Cabo Verde (+238)'],
    ['code' => '855', 'name' => 'Cambodia (+855)'],
    ['code' => '237', 'name' => 'Cameroon (+237)'],
    ['code' => '236', 'name' => 'Central African Republic (+236)'],
    ['code' => '235', 'name' => 'Chad (+235)'],
    ['code' => '56', 'name' => 'Chile (+56)'],
    ['code' => '86', 'name' => 'China (+86)'],
    ['code' => '57', 'name' => 'Colombia (+57)'],
    ['code' => '269', 'name' => 'Comoros (+269)'],
    ['code' => '242', 'name' => 'Congo (+242)'],
    ['code' => '506', 'name' => 'Costa Rica (+506)'],
    ['code' => '225', 'name' => 'Côte d\'Ivoire (+225)'],
    ['code' => '385', 'name' => 'Croatia (+385)'],
    ['code' => '53', 'name' => 'Cuba (+53)'],
    ['code' => '357', 'name' => 'Cyprus (+357)'],
    ['code' => '420', 'name' => 'Czech Republic (+420)'],
    ['code' => '45', 'name' => 'Denmark (+45)'],
    ['code' => '253', 'name' => 'Djibouti (+253)'],
    ['code' => '1', 'name' => 'Dominica (+1)'],
    ['code' => '1', 'name' => 'Dominican Republic (+1)'],
    ['code' => '593', 'name' => 'Ecuador (+593)'],
    ['code' => '20', 'name' => 'Egypt (+20)'],
    ['code' => '503', 'name' => 'El Salvador (+503)'],
    ['code' => '240', 'name' => 'Equatorial Guinea (+240)'],
    ['code' => '291', 'name' => 'Eritrea (+291)'],
    ['code' => '372', 'name' => 'Estonia (+372)'],
    ['code' => '268', 'name' => 'Eswatini (+268)'],
    ['code' => '251', 'name' => 'Ethiopia (+251)'],
    ['code' => '679', 'name' => 'Fiji (+679)'],
    ['code' => '358', 'name' => 'Finland (+358)'],
    ['code' => '33', 'name' => 'France (+33)'],
    ['code' => '241', 'name' => 'Gabon (+241)'],
    ['code' => '220', 'name' => 'Gambia (+220)'],
    ['code' => '995', 'name' => 'Georgia (+995)'],
    ['code' => '49', 'name' => 'Germany (+49)'],
    ['code' => '233', 'name' => 'Ghana (+233)'],
    ['code' => '30', 'name' => 'Greece (+30)'],
    ['code' => '1', 'name' => 'Grenada (+1)'],
    ['code' => '502', 'name' => 'Guatemala (+502)'],
    ['code' => '224', 'name' => 'Guinea (+224)'],
    ['code' => '245', 'name' => 'Guinea-Bissau (+245)'],
    ['code' => '592', 'name' => 'Guyana (+592)'],
    ['code' => '509', 'name' => 'Haiti (+509)'],
    ['code' => '504', 'name' => 'Honduras (+504)'],
    ['code' => '36', 'name' => 'Hungary (+36)'],
    ['code' => '354', 'name' => 'Iceland (+354)'],
    ['code' => '91', 'name' => 'India (+91)'],
    ['code' => '62', 'name' => 'Indonesia (+62)'],
    ['code' => '98', 'name' => 'Iran (+98)'],
    ['code' => '964', 'name' => 'Iraq (+964)'],
    ['code' => '353', 'name' => 'Ireland (+353)'],
    ['code' => '972', 'name' => 'Israel (+972)'],
    ['code' => '39', 'name' => 'Italy (+39)'],
    ['code' => '1', 'name' => 'Jamaica (+1)'],
    ['code' => '81', 'name' => 'Japan (+81)'],
    ['code' => '962', 'name' => 'Jordan (+962)'],
    ['code' => '7', 'name' => 'Kazakhstan (+7)'],
    ['code' => '254', 'name' => 'Kenya (+254)'],
    ['code' => '686', 'name' => 'Kiribati (+686)'],
    ['code' => '383', 'name' => 'Kosovo (+383)'],
    ['code' => '965', 'name' => 'Kuwait (+965)'],
    ['code' => '996', 'name' => 'Kyrgyzstan (+996)'],
    ['code' => '856', 'name' => 'Laos (+856)'],
    ['code' => '371', 'name' => 'Latvia (+371)'],
    ['code' => '961', 'name' => 'Lebanon (+961)'],
    ['code' => '266', 'name' => 'Lesotho (+266)'],
    ['code' => '231', 'name' => 'Liberia (+231)'],
    ['code' => '218', 'name' => 'Libya (+218)'],
    ['code' => '423', 'name' => 'Liechtenstein (+423)'],
    ['code' => '370', 'name' => 'Lithuania (+370)'],
    ['code' => '352', 'name' => 'Luxembourg (+352)'],
    ['code' => '261', 'name' => 'Madagascar (+261)'],
    ['code' => '265', 'name' => 'Malawi (+265)'],
    ['code' => '60', 'name' => 'Malaysia (+60)'],
    ['code' => '960', 'name' => 'Maldives (+960)'],
    ['code' => '223', 'name' => 'Mali (+223)'],
    ['code' => '356', 'name' => 'Malta (+356)'],
    ['code' => '692', 'name' => 'Marshall Islands (+692)'],
    ['code' => '222', 'name' => 'Mauritania (+222)'],
    ['code' => '230', 'name' => 'Mauritius (+230)'],
    ['code' => '52', 'name' => 'Mexico (+52)'],
    ['code' => '691', 'name' => 'Micronesia (+691)'],
    ['code' => '373', 'name' => 'Moldova (+373)'],
    ['code' => '377', 'name' => 'Monaco (+377)'],
    ['code' => '976', 'name' => 'Mongolia (+976)'],
    ['code' => '382', 'name' => 'Montenegro (+382)'],
    ['code' => '212', 'name' => 'Morocco (+212)'],
    ['code' => '258', 'name' => 'Mozambique (+258)'],
    ['code' => '95', 'name' => 'Myanmar (+95)'],
    ['code' => '264', 'name' => 'Namibia (+264)'],
    ['code' => '674', 'name' => 'Nauru (+674)'],
    ['code' => '977', 'name' => 'Nepal (+977)'],
    ['code' => '31', 'name' => 'Netherlands (+31)'],
    ['code' => '64', 'name' => 'New Zealand (+64)'],
    ['code' => '505', 'name' => 'Nicaragua (+505)'],
    ['code' => '227', 'name' => 'Niger (+227)'],
    ['code' => '234', 'name' => 'Nigeria (+234)'],
    ['code' => '389', 'name' => 'North Macedonia (+389)'],
    ['code' => '47', 'name' => 'Norway (+47)'],
    ['code' => '968', 'name' => 'Oman (+968)'],
    ['code' => '92', 'name' => 'Pakistan (+92)'],
    ['code' => '680', 'name' => 'Palau (+680)'],
    ['code' => '970', 'name' => 'Palestine (+970)'],
    ['code' => '507', 'name' => 'Panama (+507)'],
    ['code' => '675', 'name' => 'Papua New Guinea (+675)'],
    ['code' => '595', 'name' => 'Paraguay (+595)'],
    ['code' => '51', 'name' => 'Peru (+51)'],
    ['code' => '63', 'name' => 'Philippines (+63)'],
    ['code' => '48', 'name' => 'Poland (+48)'],
    ['code' => '351', 'name' => 'Portugal (+351)'],
    ['code' => '1', 'name' => 'Puerto Rico (+1)'],
    ['code' => '974', 'name' => 'Qatar (+974)'],
    ['code' => '40', 'name' => 'Romania (+40)'],
    ['code' => '7', 'name' => 'Russia (+7)'],
    ['code' => '250', 'name' => 'Rwanda (+250)'],
    ['code' => '1', 'name' => 'Saint Kitts and Nevis (+1)'],
    ['code' => '1', 'name' => 'Saint Lucia (+1)'],
    ['code' => '1', 'name' => 'Saint Vincent and the Grenadines (+1)'],
    ['code' => '685', 'name' => 'Samoa (+685)'],
    ['code' => '378', 'name' => 'San Marino (+378)'],
    ['code' => '239', 'name' => 'São Tomé and Príncipe (+239)'],
    ['code' => '966', 'name' => 'Saudi Arabia (+966)'],
    ['code' => '221', 'name' => 'Senegal (+221)'],
    ['code' => '381', 'name' => 'Serbia (+381)'],
    ['code' => '248', 'name' => 'Seychelles (+248)'],
    ['code' => '232', 'name' => 'Sierra Leone (+232)'],
    ['code' => '65', 'name' => 'Singapore (+65)'],
    ['code' => '421', 'name' => 'Slovakia (+421)'],
    ['code' => '386', 'name' => 'Slovenia (+386)'],
    ['code' => '677', 'name' => 'Solomon Islands (+677)'],
    ['code' => '252', 'name' => 'Somalia (+252)'],
    ['code' => '27', 'name' => 'South Africa (+27)'],
    ['code' => '82', 'name' => 'South Korea (+82)'],
    ['code' => '211', 'name' => 'South Sudan (+211)'],
    ['code' => '34', 'name' => 'Spain (+34)'],
    ['code' => '94', 'name' => 'Sri Lanka (+94)'],
    ['code' => '249', 'name' => 'Sudan (+249)'],
    ['code' => '597', 'name' => 'Suriname (+597)'],
    ['code' => '46', 'name' => 'Sweden (+46)'],
    ['code' => '41', 'name' => 'Switzerland (+41)'],
    ['code' => '963', 'name' => 'Syria (+963)'],
    ['code' => '886', 'name' => 'Taiwan (+886)'],
    ['code' => '992', 'name' => 'Tajikistan (+992)'],
    ['code' => '255', 'name' => 'Tanzania (+255)'],
    ['code' => '66', 'name' => 'Thailand (+66)'],
    ['code' => '670', 'name' => 'Timor-Leste (+670)'],
    ['code' => '228', 'name' => 'Togo (+228)'],
    ['code' => '690', 'name' => 'Tokelau (+690)'],
    ['code' => '676', 'name' => 'Tonga (+676)'],
    ['code' => '1', 'name' => 'Trinidad and Tobago (+1)'],
    ['code' => '216', 'name' => 'Tunisia (+216)'],
    ['code' => '90', 'name' => 'Turkey (+90)'],
    ['code' => '993', 'name' => 'Turkmenistan (+993)'],
    ['code' => '688', 'name' => 'Tuvalu (+688)'],
    ['code' => '256', 'name' => 'Uganda (+256)'],
    ['code' => '380', 'name' => 'Ukraine (+380)'],
    ['code' => '971', 'name' => 'United Arab Emirates (+971)'],
    ['code' => '44', 'name' => 'United Kingdom (+44)'],
    ['code' => '598', 'name' => 'Uruguay (+598)'],
    ['code' => '998', 'name' => 'Uzbekistan (+998)'],
    ['code' => '678', 'name' => 'Vanuatu (+678)'],
    ['code' => '379', 'name' => 'Vatican City (+379)'],
    ['code' => '58', 'name' => 'Venezuela (+58)'],
    ['code' => '84', 'name' => 'Vietnam (+84)'],
    ['code' => '681', 'name' => 'Wallis and Futuna (+681)'],
    ['code' => '212', 'name' => 'Western Sahara (+212)'],
    ['code' => '967', 'name' => 'Yemen (+967)'],
    ['code' => '260', 'name' => 'Zambia (+260)'],
    ['code' => '263', 'name' => 'Zimbabwe (+263)']
];

usort($country_codes, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

// =====================================================
// Email helper functions (full)
// =====================================================
function sendRegistrationConfirmation($studentId, $firstName, $lastName, $email, $areaCode, $phone) {
    global $phpmailer_available;
    if (!$phpmailer_available) return false;
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'xanderglobalscholars.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admissions@xanderglobalscholars.com';
        $mail->Password   = 'Xander2026$';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->setFrom('admissions@xanderglobalscholars.com', 'Xander Global Scholars');
        $mail->addAddress($email, trim($firstName . ' ' . $lastName));
        $mail->Subject = 'Welcome to Xander Global Scholars – Registration Confirmed';

        $studentName = htmlspecialchars($firstName . ' ' . $lastName);
        $phoneFull   = htmlspecialchars(trim($areaCode . ' ' . $phone));
        $emailSafe   = htmlspecialchars($email);
        $studentId   = htmlspecialchars($studentId);
        $paymentLink = "https://xanderglobalscholars.com/payment.php?student_id=" . urlencode($studentId);

        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Registration Confirmation</title>
        </head>
        <body style="margin:0; padding:0; background-color:#f0f2f5; font-family: \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0f2f5; width:100%;">
                <tr>
                    <td align="center" style="padding:30px 15px;">
                        <!-- Main Container -->
                        <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,0.05);">
                            <!-- Header with Logo -->
                            <tr>
                                <td style="background: linear-gradient(135deg, #1e3a5f 0%, #0f2542 100%); padding:25px 30px; text-align:center; border-radius:12px 12px 0 0;">
                                    <img src="https://xanderglobalscholars.com/logo.png" alt="Xander Global Scholars" style="max-width:200px; height:auto; display:block; margin:0 auto;">
                                </td>
                            </tr>
                            <!-- Main Content -->
                            <tr>
                                <td style="padding:35px 30px;">
                                    <p style="font-size:16px; color:#333333; margin:0 0 20px 0;">Dear <strong style="color:#1e3a5f;">' . $studentName . '</strong>,</p>
                                    <p style="font-size:16px; color:#555555; margin:0 0 20px 0; line-height:1.5;">Thank you for registering with <strong>Xander Global Scholars</strong>. Your account has been successfully created and you can now proceed to complete your payment.</p>
                                    
                                    <!-- Customer Details Table -->
                                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:25px 0; border:1px solid #e9ecef; border-radius:8px; overflow:hidden;">
                                        <tr>
                                            <td style="background-color:#f8fafc; padding:12px 20px; border-bottom:1px solid #e9ecef; width:35%; font-weight:600; color:#1e3a5f;">Customer ID</td>
                                            <td style="padding:12px 20px; border-bottom:1px solid #e9ecef; background-color:#ffffff;">' . $studentId . '</td>
                                        </tr>
                                        <tr>
                                            <td style="background-color:#f8fafc; padding:12px 20px; border-bottom:1px solid #e9ecef; font-weight:600; color:#1e3a5f;">Email</td>
                                            <td style="padding:12px 20px; border-bottom:1px solid #e9ecef; background-color:#ffffff;">' . $emailSafe . '</td>
                                        </tr>
                                        <tr>
                                            <td style="background-color:#f8fafc; padding:12px 20px; border-bottom:1px solid #e9ecef; font-weight:600; color:#1e3a5f;">Phone</td>
                                            <td style="padding:12px 20px; border-bottom:1px solid #e9ecef; background-color:#ffffff;">' . $phoneFull . '</td>
                                        </tr>
                                    </table>

                                    <!-- Call to Action Button -->
                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td align="center" style="padding:20px 0 30px;">
                                                <a href="' . $paymentLink . '" style="display:inline-block; background: linear-gradient(135deg, #1e3a5f 0%, #0f2542 100%); color:#ffffff; text-decoration:none; padding:16px 32px; border-radius:50px; font-size:16px; font-weight:600; letter-spacing:0.5px; box-shadow:0 4px 10px rgba(0,0,0,0.2);">
                                                    Complete Your Payment →
                                                </a>
                                            </td>
                                        </tr>
                                    </table>

                                    <p style="font-size:15px; color:#555555; margin:0 0 5px; line-height:1.5;">If you have any questions, our support team is here to help:</p>
                                    <p style="font-size:15px; margin:0 0 20px;"><a href="mailto:support@xanderglobalscholars.com" style="color:#1e3a5f; text-decoration:none; font-weight:500;">support@xanderglobalscholars.com</a></p>

                                    <p style="font-size:15px; color:#555555; margin:20px 0 0;">Kind regards,<br><strong style="color:#1e3a5f;">Xander Global Scholars</strong><br><span style="font-size:13px; color:#777;">Admissions Team</span></p>
                                </td>
                            </tr>
                            <!-- Footer with Social Media -->
                            <tr>
                                <td style="background-color:#f8fafc; padding:25px 30px; text-align:center; border-top:1px solid #e9ecef; border-radius:0 0 12px 12px;">
                                    <p style="margin:0 0 15px; font-size:14px; color:#1e3a5f; font-weight:600; text-transform:uppercase; letter-spacing:1px;">Connect with us</p>
                                    <div style="margin-bottom:10px;">
                                        <a href="https://facebook.com/xanderglobalscholars" style="color:#1e3a5f; text-decoration:none; margin:0 10px; font-size:14px;">Facebook</a> |
                                        <a href="https://twitter.com/xanderglobal" style="color:#1e3a5f; text-decoration:none; margin:0 10px; font-size:14px;">X (Twitter)</a> |
                                        <a href="https://linkedin.com/company/xander-global-scholars" style="color:#1e3a5f; text-decoration:none; margin:0 10px; font-size:14px;">LinkedIn</a> |
                                        <a href="https://instagram.com/xanderglobalscholars" style="color:#1e3a5f; text-decoration:none; margin:0 10px; font-size:14px;">Instagram</a>
                                    </div>
                                    <p style="margin:15px 0 0; font-size:12px; color:#777;">&copy; 2025 Xander Global Scholars. All rights reserved.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';

        $mail->AltBody = "Dear $studentName,\n\n"
                       . "Thank you for registering with Xander Global Scholars. Your account has been successfully created.\n\n"
                       . "Customer ID: $studentId\n"
                       . "Email: $email\n"
                       . "Phone: $phoneFull\n\n"
                       . "You can complete your payment by visiting the following link:\n"
                       . "$paymentLink\n\n"
                       . "If you have any questions, contact us at support@xanderglobalscholars.com\n\n"
                       . "Kind regards,\nXander Global Scholars Admissions Team";

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        error_log("Registration email failed for student {$studentId}: " . $e->getMessage());
        return false;
    }
}

function sendMomoThankYouEmail($toEmail, $toName, $amountRwf, $reference) {
    global $phpmailer_available;
    if (!$phpmailer_available || !trim($toEmail)) return false;
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = 'xanderglobalscholars.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admissions@xanderglobalscholars.com';
        $mail->Password   = 'Xander2026$';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->setFrom('admissions@xanderglobalscholars.com', 'Xander Global Scholars');
        $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);

        $logoPath = __DIR__ . '/logo.png';
        $logoCid = '';
        if (is_file($logoPath)) {
            $logoCid = 'xgs_momo_logo';
            $mail->addEmbeddedImage($logoPath, $logoCid, 'logo.png');
        }

        $infoEmail = 'info@xanderglobalscloars.com';
        $socialHtml = '<div style="margin-top:14px;text-align:center;">
            <a href="https://facebook.com/xanderglobalscholars" style="display:inline-block;margin:0 6px;text-decoration:none;" target="_blank" rel="noopener">
                <span style="display:inline-block;width:28px;height:28px;line-height:28px;border-radius:999px;background:#1e3a5f;color:#ffffff;font-size:12px;font-weight:800;text-align:center;">f</span>
            </a>
            <a href="https://instagram.com/xanderglobalscholars" style="display:inline-block;margin:0 6px;text-decoration:none;" target="_blank" rel="noopener">
                <span style="display:inline-block;width:28px;height:28px;line-height:28px;border-radius:999px;background:#1e3a5f;color:#ffffff;font-size:11px;font-weight:800;text-align:center;">IG</span>
            </a>
            <a href="https://twitter.com/xanderglobal" style="display:inline-block;margin:0 6px;text-decoration:none;" target="_blank" rel="noopener">
                <span style="display:inline-block;width:28px;height:28px;line-height:28px;border-radius:999px;background:#1e3a5f;color:#ffffff;font-size:12px;font-weight:800;text-align:center;">X</span>
            </a>
            <a href="https://linkedin.com/company/xander-global-scholars" style="display:inline-block;margin:0 6px;text-decoration:none;" target="_blank" rel="noopener">
                <span style="display:inline-block;width:28px;height:28px;line-height:28px;border-radius:999px;background:#1e3a5f;color:#ffffff;font-size:11px;font-weight:800;text-align:center;">in</span>
            </a>
        </div>';

        $safeName = htmlspecialchars($toName !== '' ? $toName : $toEmail);
        $safeRef = htmlspecialchars((string)$reference);
        $safeAmount = number_format((float)$amountRwf, 0, '.', ',');

        $logoHtml = $logoCid !== ''
            ? '<img src="cid:' . $logoCid . '" alt="Xander Global Scholars" style="height:88px;display:block;margin:0 auto;" />'
            : '<div style="font-weight:800;font-size:18px;">Xander Global Scholars</div>';

        $mail->Subject = 'MTN Mobile Money Payment Request – Xander Global Scholars';

        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"><title>MoMo Payment</title></head>
        <body style="margin:0;padding:0;background:#f0f2f5;font-family:Arial,Helvetica,sans-serif;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f0f2f5;">
                <tr><td align="center" style="padding:28px 14px;">
                    <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,0.05);overflow:hidden;">
                        <tr>
                            <td style="background:linear-gradient(135deg,#1e3a5f 0%, #0f2542 100%);padding:22px 24px;text-align:center;">
                                <div style="margin-bottom:10px;display:inline-block;background:#ffffff;padding:10px 14px;border-radius:14px;">
                                    ' . $logoHtml . '
                                </div>
                                <div style="color:#ffffff;font-weight:800;font-size:18px;letter-spacing:.2px;">Xander Global Scholars</div>
                                <div style="color:#dbeafe;font-size:12px;margin-top:6px;">Secure Payment Request</div>
                                <div style="color:#dbeafe;font-size:12px;margin-top:6px;">https://xanderglobalscholars.com</div>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding:28px 24px;color:#111827;">
                                <p style="margin:0 0 12px;font-size:16px;">Dear <strong style="color:#1e3a5f;">' . $safeName . '</strong>,</p>
                                <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#374151;">We are pleased to inform you that a <strong>Mobile Money (MTN MoMo)</strong> payment request has been initiated for your transaction with <strong>Xander Global Scholars</strong>.</p>
                                <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#374151;">To complete your payment, please <strong>approve the prompt</strong> that will appear on your phone. Once approved, your payment will be recorded and you will receive an official receipt by email.</p>
                                <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#6b7280;">If you did not request this payment, please do not approve it and contact us immediately at <a href="mailto:' . $infoEmail . '" style="color:#1e3a5f;">' . $infoEmail . '</a>.</p>
                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin:18px 0;">
                                    <tr><td style="padding:10px 14px;background:#f8fafc;font-weight:600;color:#1e3a5f;width:40%;border-bottom:1px solid #e5e7eb;">Amount</td><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">RWF ' . $safeAmount . '</td></tr>
                                    <tr><td style="padding:10px 14px;background:#f8fafc;font-weight:600;color:#1e3a5f;border-bottom:1px solid #e5e7eb;">Reference</td><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">' . $safeRef . '</td></tr>
                                    <tr><td style="padding:10px 14px;background:#f8fafc;font-weight:600;color:#1e3a5f;">Payment method</td><td style="padding:10px 14px;">MTN Mobile Money</td></tr>
                                </table>
                                <p style="margin:0;font-size:14px;color:#6b7280;line-height:1.6;">Thank you for choosing Xander Global Scholars. We appreciate your trust and look forward to supporting your journey.</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:18px 24px;background:#f8fafc;text-align:center;color:#6b7280;font-size:12px;">
                                <div style="font-weight:700;color:#1e3a5f;">Connect with us</div>
                                ' . $socialHtml . '
                                <div style="margin-top:12px;">&copy; ' . date('Y') . ' Xander Global Scholars</div>
                            </td>
                        </tr>
                    </table>
                </td></tr>
            </table>
        </body>
        </html>';

        $mail->AltBody = "Dear {$toName},\n\nThank you for choosing MTN Mobile Money. Your payment request has been initiated.\nAmount: RWF {$safeAmount}\nReference: {$reference}\n\nPlease approve the prompt on your phone.\n\nXander Global Scholars";

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        error_log('MoMo thank-you email failed: ' . $e->getMessage());
        return false;
    }
}

function sendMomoReceiptEmail($toEmail, $toName, $amountRwf, $reference, $statusLabel) {
    global $phpmailer_available;
    if (!$phpmailer_available || !trim($toEmail)) return false;
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = 'xanderglobalscholars.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admissions@xanderglobalscholars.com';
        $mail->Password   = 'Xander2026$';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->setFrom('admissions@xanderglobalscholars.com', 'Xander Global Scholars');
        $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);

        $logoPath = __DIR__ . '/logo.png';
        $logoCid = '';
        if (is_file($logoPath)) {
            $logoCid = 'xgs_momo_logo';
            $mail->addEmbeddedImage($logoPath, $logoCid, 'logo.png');
        }

        $logoUrl = 'https://xanderglobalscholars.com/logo.png';
        $logoSrcEmail = $logoUrl;
        $infoEmail = 'info@xanderglobalscloars.com';

        $logoDataUri = '';
        if (is_file($logoPath)) {
            $logoBin = @file_get_contents($logoPath);
            if (is_string($logoBin) && $logoBin !== '') {
                $logoDataUri = 'data:image/png;base64,' . base64_encode($logoBin);
            }
        }

        $logoSrcPdf = ($logoDataUri !== '') ? $logoDataUri : $logoUrl;

        $socialHtml = '<div style="margin-top:14px;text-align:center;">
            <a href="https://facebook.com/xanderglobalscholars" style="display:inline-block;margin:0 6px;text-decoration:none;" target="_blank" rel="noopener">
                <span style="display:inline-block;width:28px;height:28px;line-height:28px;border-radius:999px;background:#1e3a5f;color:#ffffff;font-size:12px;font-weight:800;text-align:center;">f</span>
            </a>
            <a href="https://instagram.com/xanderglobalscholars" style="display:inline-block;margin:0 6px;text-decoration:none;" target="_blank" rel="noopener">
                <span style="display:inline-block;width:28px;height:28px;line-height:28px;border-radius:999px;background:#1e3a5f;color:#ffffff;font-size:11px;font-weight:800;text-align:center;">IG</span>
            </a>
            <a href="https://twitter.com/xanderglobal" style="display:inline-block;margin:0 6px;text-decoration:none;" target="_blank" rel="noopener">
                <span style="display:inline-block;width:28px;height:28px;line-height:28px;border-radius:999px;background:#1e3a5f;color:#ffffff;font-size:12px;font-weight:800;text-align:center;">X</span>
            </a>
            <a href="https://linkedin.com/company/xander-global-scholars" style="display:inline-block;margin:0 6px;text-decoration:none;" target="_blank" rel="noopener">
                <span style="display:inline-block;width:28px;height:28px;line-height:28px;border-radius:999px;background:#1e3a5f;color:#ffffff;font-size:11px;font-weight:800;text-align:center;">in</span>
            </a>
        </div>';

        $safeName = htmlspecialchars($toName !== '' ? $toName : $toEmail);
        $safeRef = htmlspecialchars((string)$reference);
        $safeStatus = htmlspecialchars((string)$statusLabel);
        $safeAmount = number_format((float)$amountRwf, 0, '.', ',');
        $safeDate = htmlspecialchars(date('F j, Y, g:i a'));

        $logoHtml = $logoCid !== ''
            ? '<img src="cid:' . $logoCid . '" alt="Xander Global Scholars" style="height:92px;display:block;margin:0 auto;" />'
            : '<div style="font-weight:800;font-size:18px;">Xander Global Scholars</div>';

        $qrData = 'https://xanderglobalscholars.com | Receipt Ref: ' . (string)$reference;
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . rawurlencode($qrData);
        $qrDataUri = '';
        $qrCtx = stream_context_create([
            'http' => [
                'timeout' => 8,
                'follow_location' => 1,
                'user_agent' => 'XanderReceiptBot/1.0',
            ],
            'https' => [
                'timeout' => 8,
                'follow_location' => 1,
                'user_agent' => 'XanderReceiptBot/1.0',
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        $qrBin = @file_get_contents($qrUrl, false, $qrCtx);
        if (is_string($qrBin) && $qrBin !== '') {
            $qrDataUri = 'data:image/png;base64,' . base64_encode($qrBin);
        }

        $mail->Subject = 'Payment Successful – Mobile Money Receipt | Xander Global Scholars';

        $emailBody = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:0;">
            <div style="max-width:680px;margin:0 auto;padding:24px;">
                <div style="background:#ffffff;border-radius:14px;overflow:hidden;border:2px solid #0f2542;">
                    <div style="height:6px;background:#ff8c42;"></div>
                    <div style="background:#0f2542;color:#ffffff;padding:18px 20px 16px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                            <tr>
                                <td style="vertical-align:middle;">
                                    <div style="display:inline-block;background:#ffffff;border-radius:12px;padding:10px 12px;border:1px solid rgba(255,255,255,0.25);">
                                        ' . $logoHtml . '
                                    </div>
                                </td>
                                <td style="vertical-align:middle;text-align:right;font-size:12px;opacity:.95;">
                                    <div style="font-weight:800;">Mobile Money Receipt</div>
                                    <div>Receipt No: ' . $safeRef . '</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div style="padding:22px;color:#111827;">
                        <p style="margin:0 0 12px;">Dear <strong>' . $safeName . '</strong>,</p>
                        <p style="margin:0 0 12px;">Thank you for completing your payment with <strong>Xander Global Scholars</strong>. This email is a confirmation that your Mobile Money (MTN MoMo) payment has been received successfully.</p>

                        <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;margin:14px 0 14px;">
                            <table style="width:100%;border-collapse:collapse;">
                                <tr><td style="padding:7px 0;color:#6b7280;">Reference Number</td><td style="padding:7px 0;color:#111827;font-weight:700;">' . $safeRef . '</td></tr>
                                <tr><td style="padding:7px 0;color:#6b7280;">Amount Paid</td><td style="padding:7px 0;color:#0f2542;font-weight:800;">RWF ' . $safeAmount . '</td></tr>
                                <tr><td style="padding:7px 0;color:#6b7280;">Payment Method</td><td style="padding:7px 0;color:#111827;font-weight:700;">MTN Mobile Money</td></tr>
                                <tr><td style="padding:7px 0;color:#6b7280;">Payment Date</td><td style="padding:7px 0;color:#111827;font-weight:700;">' . $safeDate . '</td></tr>
                                <tr><td style="padding:7px 0;color:#6b7280;">Status</td><td style="padding:7px 0;color:#111827;font-weight:700;">' . $safeStatus . '</td></tr>
                            </table>
                        </div>

                        <p style="margin:0 0 12px;">A professional receipt is attached to this email in PDF format. Please keep it for your records.</p>
                        <p style="margin:0 0 12px;color:#6b7280;font-size:13px;">If you did not authorize this transaction, please contact us immediately.</p>

                        <div style="border-top:1px solid #e5e7eb;margin-top:16px;padding-top:12px;color:#6b7280;font-size:12px;">
                            <div style="font-weight:800;color:#111827;">Xander Global Scholars</div>
                            <div>Email: admission@xanderglobalscholars.com</div>
                            <div>Website: xanderglobalscholars.com</div>
                            <div style="margin-top:10px;font-weight:700;color:#0f2542;">Connect with us</div>
                            ' . $socialHtml . '
                        </div>
                    </div>
                </div>
                <div style="text-align:center;color:#94a3b8;font-size:11px;margin-top:12px;">This is an automated message. Please do not share your payment details with anyone.</div>
            </div>
        </body></html>';

        $pdfBody = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page { margin: 22mm 16mm 22mm 16mm; }
                body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: #111827; }
                .frame { border: 3px solid #1e3a5f; border-radius: 14px; padding: 16px; }
                .header { text-align: center; padding-bottom: 12px; border-bottom: 1px solid #e9ecef; }
                .logoWrap { display: inline-block; background: #ffffff; padding: 14px 18px; border-radius: 16px; }
                .logo { height: 120px; width: auto; display: block; margin: 0 auto; }
                .title { font-size: 16px; font-weight: 800; color: #1e3a5f; margin: 0; }
                .sub { font-size: 11px; color: #6b7280; margin-top: 6px; }
                .section { margin-top: 14px; }
                table { width: 100%; border-collapse: collapse; }
                td { padding: 9px 10px; border: 1px solid #e5e7eb; }
                td.k { width: 38%; background: #f8fafc; font-weight: 700; color: #1e3a5f; }
                .footer { margin-top: 16px; padding-top: 12px; border-top: 1px solid #e5e7eb; text-align: center; }
                .qr { width: 120px; height: 120px; border: 2px solid #1e3a5f; border-radius: 10px; background: #ffffff; display:block; margin: 0 auto; }
                .muted { color: #6b7280; font-size: 10px; margin-top: 6px; }
            </style>
        </head>
        <body>
            <div class="frame">
                <div class="header">
                    <div class="logoWrap"><img class="logo" src="' . $logoSrcPdf . '" alt="Xander Global Scholars"></div>
                    <h1 style="margin:12px 0 0;font-size:20px;font-weight:800;color:#1e3a5f;">Payment Receipt</h1>
                    <p class="sub">Xander Global Scholars | https://xanderglobalscholars.com</p>
                    <p style="margin:10px 0 0;font-size:12px;line-height:1.6;color:#374151;">
                        <strong>Success!</strong> Your MTN Mobile Money payment has been received and recorded by Xander Global Scholars.
                    </p>
                </div>

                <div class="section">
                    <p style="margin:0 0 8px;">Customer: <strong>' . $safeName . '</strong></p>
                    <table>
                        <tr><td class="k">Receipt Reference</td><td>' . $safeRef . '</td></tr>
                        <tr><td class="k">Amount</td><td>RWF ' . $safeAmount . '</td></tr>
                        <tr><td class="k">Payment Method</td><td>MTN Mobile Money</td></tr>
                        <tr><td class="k">Status</td><td>' . $safeStatus . '</td></tr>
                        <tr><td class="k">Date</td><td>' . $safeDate . '</td></tr>
                        <tr><td class="k">Information Email</td><td>' . $infoEmail . '</td></tr>
                    </table>
                </div>

                <div class="footer">
                    <img class="qr" src="' . ($qrDataUri !== '' ? $qrDataUri : $qrUrl) . '" alt="Receipt QR">
                    <div class="muted">Verify / reference: ' . $safeRef . '</div>
                    <div class="muted">&copy; ' . date('Y') . ' Xander Global Scholars</div>
                </div>
            </div>
        </body>
        </html>';

        $mail->Body = $emailBody;

        $mail->AltBody = "Dear {$toName},\n\nThank you for your payment to Xander Global Scholars.\n\nReceipt No: {$reference}\nAmount Paid: RWF {$safeAmount}\nPayment Method: MTN Mobile Money\nStatus: {$statusLabel}\nPayment Date: {$safeDate}\n\nA receipt is attached (PDF).\n\nXander Global Scholars\nEmail: admission@xanderglobalscholars.com\nWebsite: xanderglobalscholars.com\n";

        if (function_exists('generateReceiptPdf')) {
            try {
                generateReceiptPdf((string)$pdfBody, (string)$reference);
                $pdfPath = __DIR__ . '/receipts/' . $reference . '.pdf';
                if (is_file($pdfPath)) {
                    $mail->addAttachment($pdfPath, 'Receipt-' . $reference . '.pdf');
                } else {
                    error_log('Receipt PDF not generated. Expected: ' . $pdfPath);
                }
            } catch (\Throwable $e) {
                error_log('MoMo receipt PDF attach failed: ' . $e->getMessage());
            }
        }

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        error_log('MoMo receipt email failed: ' . $e->getMessage());
        return false;
    }
}

// Initialize variables
$selected_student = null;
$registration_error = $_GET['error'] ?? null;
$registered_new = $_GET['registered'] ?? null;
$success_message = $_GET['success'] ?? null;

// Get selected student data
if (isset($_GET['student_id'])) {
    $student_id = (int)$_GET['student_id'];
    $stmt = $conn->prepare("SELECT * FROM student_applications WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $selected_student = stmt_fetch_assoc($stmt);
        $stmt->close();
    } else {
        error_log('Failed to prepare student select: ' . $conn->error);
    }
}

// Handle form submission for student registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_student'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $area_code = trim($_POST['area_code'] ?? '');
    
    if ($first_name && $last_name && $email && $phone_number && $area_code) {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM student_applications WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $existing = stmt_fetch_assoc($stmt);
        $stmt->close();
        
        if ($existing) {
            header("Location: payment.php?error=" . urlencode('Email already registered. Please search for your existing account.'));
            exit();
        }
        
        // Insert new student
        $stmt = $conn->prepare("INSERT INTO student_applications (user_id, first_name, last_name, email, area_code, phone_number) VALUES (?, ?, ?, ?, ?, ?)");
        $user_id = 'STU_' . time() . '_' . rand(1000, 9999);
        $stmt->bind_param('ssssss', $user_id, $first_name, $last_name, $email, $area_code, $phone_number);
        
        if ($stmt->execute()) {
            $student_id = $stmt->insert_id;
            $stmt->close();

            $emailSent = sendRegistrationConfirmation(
                $student_id,
                $first_name,
                $last_name,
                $email,
                $area_code,
                $phone_number
            );

            $emailFlag = $emailSent ? '&email_sent=1' : '&email_sent=0';
            header("Location: payment.php?student_id=$student_id&registered=1{$emailFlag}");
            exit();
        } else {
            header("Location: payment.php?error=" . urlencode('Registration failed. Please try again.'));
            exit();
        }
    } else {
        header("Location: payment.php?error=" . urlencode('All fields are required, including country code.'));
        exit();
    }
}

// Handle form submission for student selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_student'])) {
    $student_id = (int)($_POST['student_id'] ?? 0);
    if ($student_id > 0) {
        header("Location: payment.php?student_id=$student_id");
        exit();
    }
}

// Handle form submission for payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    try {
        $student_id = (int)($_GET['student_id'] ?? 0);
        if ($student_id === 0) {
            header("Location: payment.php?error=" . urlencode('Please select or register a student first.'));
            exit();
        }

        $package_id = (int)($_POST['package_id'] ?? 0);
        $selected_items = $_POST['selected_items'] ?? [];
        $item_amounts = $_POST['item_amount'] ?? [];
        $payment_method = (string)($_POST['payment_method'] ?? 'stripe');

        if ($package_id > 0 && !empty($selected_items)) {
            $items_data = [];
            $total = 0;

            foreach ($selected_items as $item_id) {
                $amount = isset($item_amounts[$item_id]) ? (float)$item_amounts[$item_id] : 0;
                if ($amount > 0) {
                    $items_data[$item_id] = $amount;
                    $total += $amount;
                }
            }

            if ($total > 0) {
                $items_json = json_encode($items_data);

                $package_currency = 'USD';
                $stmt = $conn->prepare("SELECT currency FROM fee_packages WHERE id = ? LIMIT 1");
                if (!$stmt) {
                    $msg = 'Database error while reading package currency.';
                    if ($debug_mode) $msg .= ' ' . mysqli_error($conn);
                    header("Location: payment.php?student_id=$student_id&error=" . urlencode($msg));
                    exit();
                }
                $stmt->bind_param('i', $package_id);
                $stmt->execute();
                $pkgRow = stmt_fetch_assoc($stmt);
                $stmt->close();

                if (!$pkgRow) {
                    header("Location: payment.php?student_id=$student_id&error=" . urlencode('Selected package not found. Please choose another package.'));
                    exit();
                }

                if (!empty($pkgRow['currency'])) {
                    $package_currency = (string)$pkgRow['currency'];
                }

                if ($payment_method === 'momo') {
                    $stmt = $conn->prepare("SELECT first_name, last_name, email, area_code, phone_number FROM student_applications WHERE id = ? LIMIT 1");
                    if (!$stmt) {
                        $msg = 'Database error while reading student details.';
                        if ($debug_mode) $msg .= ' ' . mysqli_error($conn);
                        header("Location: payment.php?student_id=$student_id&error=" . urlencode($msg));
                        exit();
                    }
                    $stmt->bind_param('i', $student_id);
                    $stmt->execute();
                    $student = stmt_fetch_assoc($stmt);
                    $stmt->close();

                    if (!$student) {
                        header("Location: payment.php?student_id=$student_id&error=" . urlencode('Student not found. Please re-select the student and try again.'));
                        exit();
                    }

                    $fullName = trim((string)($student['first_name'] ?? '') . ' ' . (string)($student['last_name'] ?? ''));
                    $email = trim((string)($student['email'] ?? ''));
                    $phone = trim((string)($student['phone_number'] ?? ''));

                    $src_currency = strtoupper(trim((string)$package_currency));
                    $src_amount = (float)$total;
                    if (!isset($fx_to_rwf[$src_currency])) {
                        header("Location: payment.php?student_id=$student_id&error=" . urlencode('Unsupported currency for MoMo conversion. Please choose another package or pay by card.'));
                        exit();
                    }

                    $amount_rwf = (int)round($src_amount * (float)$fx_to_rwf[$src_currency]);
                    if ($amount_rwf < 1) {
                        header("Location: payment.php?student_id=$student_id&error=" . urlencode('Invalid total amount for MoMo payment.'));
                        exit();
                    }

                    if ($amount_rwf > $momo_amount_max_rwf) {
                        header("Location: payment.php?student_id=$student_id&error=" . urlencode("Maximum allowed amount for MTN Mobile Money is {$momo_amount_max_rwf} RWF. Please reduce the amount or pay by card."));
                        exit();
                    }

                    header(
                        "Location: payment.php?student_id=$student_id&momo=1"
                        . "&name=" . urlencode($fullName)
                        . "&email=" . urlencode($email)
                        . "&phone=" . urlencode($phone)
                        . "&amount=" . urlencode((string)$amount_rwf)
                        . "&src_currency=" . urlencode($src_currency)
                        . "&src_amount=" . urlencode((string)$src_amount)
                        . ($debug_mode ? "&debug=1" : '')
                    );
                    exit();
                }

                header("Location: stripe-payment.php?student_id=$student_id&package_id=$package_id&payment_method=$payment_method&currency=$package_currency&items=" . urlencode($items_json));
                exit();
            }

            header("Location: payment.php?student_id=$student_id&error=" . urlencode('Please select at least one item with amount greater than 0.'));
            exit();
        }

        header("Location: payment.php?student_id=$student_id&error=" . urlencode('Please select a package and at least one item.'));
        exit();
    } catch (\Throwable $e) {
        error_log('process_payment failed: ' . $e->getMessage());
        $student_id = (int)($_GET['student_id'] ?? 0);
        $msg = 'Payment failed due to a server error. Please try again.';
        if ($debug_mode) $msg .= ' ' . $e->getMessage();
        $redir = $student_id > 0 ? "payment.php?student_id=$student_id&error=" : 'payment.php?error=';
        header('Location: ' . $redir . urlencode($msg));
        exit();
    }
}

// Fetch packages from database
$packages_result = null;
$fee_items = [];
$packages_summary = [];

if (!$momo_mode) {
    $packages_result = $conn->query("SELECT * FROM fee_packages ORDER BY title ASC");
    if (!$packages_result) {
        error_log('Failed to fetch packages: ' . $conn->error);
        $registration_error = $debug_mode
            ? ('Database error while loading packages. ' . $conn->error)
            : 'Database error while loading packages.';
    } else {
        $fee_items_result = $conn->query("SELECT fi.*, p.title as package_title, p.currency as package_currency, p.total_amount as package_total FROM fee_items fi LEFT JOIN fee_packages p ON fi.package_id = p.id ORDER BY fi.package_id, fi.id");
        if ($fee_items_result) {
            while ($item = $fee_items_result->fetch_assoc()) {
                if (!isset($fee_items[$item['package_id']])) {
                    $fee_items[$item['package_id']] = [];
                }
                $fee_items[$item['package_id']][] = $item;
            }
        } else {
            error_log('Failed to fetch fee items: ' . $conn->error);
        }

        $packages_result->data_seek(0);
        while ($package = $packages_result->fetch_assoc()) {
            $packages_summary[$package['id']] = [
                'title' => $package['title'],
                'currency' => $package['currency'],
                'total_expected' => $package['total_expected'],
                'total_amount' => $package['total_amount'],
                'payment_items' => $fee_items[$package['id']] ?? []
            ];
        }
    }
}

$pageTitle = 'Secure Payment Portal - Xander Global Scholars';
include 'header.php';
?>

<?php if (isset($_GET['momo']) && $_GET['momo'] === '1'): ?>
<?php
$prefillName = (string)($_GET['name'] ?? '');
$prefillEmail = (string)($_GET['email'] ?? '');
$prefillPhone = (string)($_GET['phone'] ?? '');
$prefillAmount = (string)($_GET['amount'] ?? '');
$prefillSrcCurrency = strtoupper(trim((string)($_GET['src_currency'] ?? '')));
$prefillSrcAmount = (string)($_GET['src_amount'] ?? '');
?>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<style>
        .momo-page, .momo-page * , .momo-page *::before, .momo-page *::after { box-sizing: border-box; }
        .momo-page {
            margin: 0;
            padding: 0;
        }
        .momo-page {
            --mtn-yellow: #ffcc00;
            --mtn-yellow-dk: #f5c400;
            --mtn-black: #0b0f14;
            --mtn-black-2: #111827;
            --mtn-yellow-lt: #fff7cc;
            --text:     #111827; --muted:    #6b7280;
            --border:   #e5e7eb; --bg:       #f0fdf4; --white:    #ffffff;
            --red:      #dc2626; --red-lt:   #fef2f2;
            --amber:    #92400e; --amber-lt: #fffbeb;
        }
        .momo-page {
            font-family: 'DM Sans', sans-serif;
            background: #f6f7fb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .momo-page .card {
            background: var(--white); border-radius: 20px;
            box-shadow: 0 4px 32px rgba(0,0,0,.08);
            width: 100%; max-width: 460px; overflow: hidden;
        }
        .momo-page .card-header { background: var(--mtn-yellow); padding: 22px 28px 18px; color: var(--mtn-black); border-bottom: 1px solid rgba(0,0,0,.06); }
        .momo-page .card-header .logo { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .momo-page .card-header .brand { font-family: 'Syne', sans-serif; font-size: 1.05rem; font-weight: 800; letter-spacing: .2px; }
        .momo-page .card-header .logo-img { width: 34px; height: 34px; object-fit: contain; display:block; }
        .momo-page .card-header .badge { margin-left: auto; font-size: 12px; font-weight: 700; padding: 6px 10px; border-radius: 999px; background: rgba(11,15,20,.08); color: var(--mtn-black); }
        .momo-page .card-header h1 { font-family: 'Syne', sans-serif; font-size: 1.5rem; font-weight: 700; }
        .momo-page .card-header p  { font-size: .875rem; opacity: .85; margin-top: 4px; }
        .momo-page .card-body { padding: 28px 32px 32px; }
        .momo-page .alert {
            display: flex; align-items: flex-start; gap: 10px;
            border-radius: 10px; padding: 12px 14px;
            font-size: .875rem; margin-bottom: 16px;
        }
        .momo-page .alert-success { background: #ecfdf5; color: #065f46; }
        .momo-page .alert-error { background: var(--red-lt);   color: var(--red); }
        .momo-page .alert-debug { background: var(--amber-lt); color: var(--amber); }
        .momo-page .alert svg   { flex-shrink: 0; margin-top: 2px; }
        .momo-page .alert strong { display: block; font-weight: 600; margin-bottom: 6px; }
        .momo-page .debug-table { width: 100%; border-collapse: collapse; font-size: .78rem; margin-top: 4px; }
        .momo-page .debug-table td { padding: 4px 6px; vertical-align: top; border-bottom: 1px solid rgba(146,64,14,.12); }
        .momo-page .debug-table td:first-child { font-weight: 600; white-space: nowrap; width: 38%; color: var(--amber); }
        .momo-page .debug-table td:last-child  { word-break: break-all; }
        .momo-page .field { margin-bottom: 16px; }
        .momo-page label { display: block; font-size: .8125rem; font-weight: 500; color: var(--text); margin-bottom: 5px; }
        .momo-page input[type="text"], .momo-page input[type="email"], .momo-page input[type="tel"], .momo-page input[type="number"] {
            width: 100%; padding: 10px 14px;
            border: 1.5px solid var(--border); border-radius: 10px;
            font-family: inherit; font-size: .9375rem; color: var(--text);
            background: var(--white); transition: border-color .2s, box-shadow .2s; outline: none;
        }
        .momo-page input:focus { border-color: var(--mtn-yellow-dk); box-shadow: 0 0 0 3px rgba(255,204,0,.22); }
        .momo-page input::placeholder { color: #9ca3af; }
        .momo-page .method-tile {
            display: flex; align-items: center; gap: 12px; padding: 12px 14px;
            border: 1.5px solid rgba(11,15,20,.18); border-radius: 12px;
            background: #fffaf0; cursor: pointer;
        }
        .momo-page .method-tile input[type="radio"] { width: 16px; height: 16px; accent-color: var(--mtn-black); flex-shrink: 0; }
        .momo-page .method-name { font-weight: 600; font-size: .9375rem; color: var(--text); }
        .momo-page .method-sub  { font-size: .8125rem; color: var(--muted); margin-top: 1px; }
        .momo-page .method-hint { font-size: .75rem; color: var(--muted); margin-top: 6px; }
        .momo-page .divider { height: 1px; background: var(--border); margin: 20px 0; }
        .momo-page .btn-pay {
            width: 100%; background: var(--mtn-black); color: var(--mtn-yellow);
            font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700;
            letter-spacing: .02em; padding: 13px; border: none; border-radius: 12px;
            cursor: pointer; transition: background .2s, transform .1s, box-shadow .2s;
            box-shadow: 0 10px 24px rgba(11,15,20,.18);
        }
        .momo-page .btn-pay:hover  { background: #000000; box-shadow: 0 14px 34px rgba(11,15,20,.24); }
        .momo-page .btn-pay:active { transform: scale(.98); }
        .momo-page .actions {
            padding: 0 32px 28px;
            display: flex;
            justify-content: center;
        }
        .momo-page .back-link {
            display: block; text-align: center; margin-top: 16px;
            font-size: .8125rem; color: var(--muted); text-decoration: none; transition: color .2s;
        }
        .momo-page .back-link:hover { color: var(--text); }
        .momo-page .success-body { text-align: center; padding: 40px 32px; }
        .momo-page .success-icon {
            width: 64px; height: 64px; background: var(--mtn-yellow-lt); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;
        }
        .momo-page .success-body h2 { font-family: 'Syne', sans-serif; font-size: 1.25rem; font-weight: 700; color: var(--text); margin-bottom: 8px; }
        .momo-page .success-body p  { font-size: .9rem; color: var(--muted); line-height: 1.5; }
    </style>

<div class="momo-page">
    <div class="card" style="margin: 40px auto;">

        <div class="card-header">
            <div class="logo">
                <img class="logo-img" src="momo.png" alt="MTN MoMo">
                <span class="brand">MTN MoMo</span>
                <span class="badge">Secure Payment</span>
            </div>
            <h1>Payment Details</h1>
            <p>Fill in your details to pay via Mobile Money.</p>
        </div>

        <?php if ($momo_success): ?>
        <div class="success-body">
            <div class="success-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#0b0f14" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <h2>Payment Initiated!</h2>
            <p>A payment prompt has been sent to <strong><?= htmlspecialchars((string)($_POST['phone'] ?? $prefillPhone)) ?></strong>. Please approve it on your phone.</p>
            <?php if ($momo_reference !== ''): ?>
                <div class="alert alert-debug" style="margin-top:14px;">
                    <div style="width:100%">
                        <strong>Reference:</strong> <?= htmlspecialchars($momo_reference) ?><br>
                        <strong>Status:</strong> <span id="momoStatusText"><?= htmlspecialchars($momo_db_status !== '' ? strtoupper($momo_db_status) : 'PENDING') ?></span><br>
                        <a id="momoRefreshLink" href="<?= htmlspecialchars($momo_base_url) ?>" style="color:#1e3a5f; font-weight:600; text-decoration:underline;">Refreshing automatically...</a>
                        <?php if ($debug_mode): ?>
                            <div style="margin-top:10px; font-size:12px; line-height:1.4;">
                                <strong>Verify debug</strong><br>
                                http_code: <?= htmlspecialchars((string)($momo_verify_debug['http_code'] ?? '')) ?><br>
                                provider_status: <?= htmlspecialchars((string)($momo_verify_debug['provider_status'] ?? '')) ?><br>
                                curl_error: <?= htmlspecialchars((string)($momo_verify_debug['curl_error'] ?? '')) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <script>
                (function () {
                    var ref = <?= json_encode($momo_reference) ?>;
                    var status = <?= json_encode(strtolower((string)$momo_db_status)) ?>;
                    if (!ref) return;

                    var isTerminal = (status === 'completed' || status === 'failed' || status === 'cancelled');
                    if (isTerminal) {
                        var link = document.getElementById('momoRefreshLink');
                        if (link) link.textContent = 'Check status';
                        return;
                    }

                    var seconds = 1;
                    var linkEl = document.getElementById('momoRefreshLink');
                    var tick = function () {
                        if (linkEl) linkEl.textContent = 'Refreshing automatically in ' + seconds + 's...';
                        seconds -= 1;
                        if (seconds < 0) {
                            window.location.href = <?= json_encode('payment.php?momo=1') ?>
                                + '&ref=' + encodeURIComponent(ref)
                                + <?= json_encode($momo_student_id > 0 ? '&student_id=' . $momo_student_id : '') ?>
                                + <?= json_encode($debug_mode ? '&debug=1' : '') ?>;
                        } else {
                            window.setTimeout(tick, 1000);
                        }
                    };
                    tick();
                })();
                </script>
            <?php endif; ?>
        </div>
        <div class="actions">
            <a href="payment.php?student_id=<?= (int)($_GET['student_id'] ?? 0) ?>" class="back-link" style="margin-top:0;">← Back to Payment</a>
        </div>

        <?php else: ?>
        <div class="card-body">

            <?php $momo_is_completed = ($momo_reference !== '' && strtolower((string)$momo_db_status) === 'completed'); ?>

            <?php if ($momo_is_completed): ?>
                <div class="success-body" style="padding-top:30px;">
                    <div class="success-icon" style="background: #ecfdf5;">
                        <div style="font-family: 'Syne', sans-serif; font-size: 34px; font-weight: 900; line-height: 1; color: #065f46;">V</div>
                    </div>
                    <h2 style="margin-top:10px;">Payment Completed.</h2>
                    <p>Thank you. A receipt email will be sent.</p>
                </div>
            <?php else: ?>

            <?php if ($momo_reference !== ''): ?>
                <?php if (strtolower($momo_db_status) === 'completed'): ?>
                    <div class="alert alert-success">
                        <div><strong>Payment Completed.</strong> Thank you. A receipt email will be sent.</div>
                    </div>
                <?php elseif (strtolower($momo_db_status) === 'cancelled'): ?>
                    <div class="alert alert-error">
                        <div><strong>Payment Cancelled.</strong> You cancelled the payment on your phone.</div>
                    </div>
                <?php elseif (strtolower($momo_db_status) === 'failed'): ?>
                    <div class="alert alert-error">
                        <div><strong>Payment Failed.</strong> Please try again.</div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-debug">
                        <div><strong>Payment Pending.</strong> Please approve the prompt on your phone. This page will refresh automatically.</div>
                        <?php if ($debug_mode): ?>
                            <div style="margin-top:10px; font-size:12px; line-height:1.4;">
                                <strong>Verify debug</strong><br>
                                http_code: <?= htmlspecialchars((string)($momo_verify_debug['http_code'] ?? '')) ?><br>
                                provider_status: <?= htmlspecialchars((string)($momo_verify_debug['provider_status'] ?? '')) ?><br>
                                curl_error: <?= htmlspecialchars((string)($momo_verify_debug['curl_error'] ?? '')) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <script>
                    (function () {
                        var ref = <?= json_encode($momo_reference) ?>;
                        var status = <?= json_encode(strtolower((string)$momo_db_status)) ?>;
                        if (!ref) return;
                        var isTerminal = (status === 'completed' || status === 'failed' || status === 'cancelled');
                        if (isTerminal) return;
                        window.setTimeout(function () {
                            window.location.href = <?= json_encode('payment.php?momo=1') ?>
                                + '&ref=' + encodeURIComponent(ref)
                                + <?= json_encode($momo_student_id > 0 ? '&student_id=' . $momo_student_id : '') ?>
                                + <?= json_encode($debug_mode ? '&debug=1' : '') ?>;
                        }, 1000);
                    })();
                    </script>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($momo_error): ?>
            <div class="alert alert-error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <div><?= htmlspecialchars((string)$momo_error) ?></div>
            </div>

            <?php if ($momo_response_data): ?>
            <div class="alert alert-debug">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                </svg>
                <div style="width:100%">
                    <strong>API Response Details</strong>
                    <table class="debug-table">
                        <tr>
                            <td>status</td>
                            <td><?= htmlspecialchars((string)($momo_response_data['status'] ?? '—')) ?></td>
                        </tr>
                        <?php
                        $flat = [];
                        array_walk_recursive($momo_response_data, function($val, $key) use (&$flat) {
                            $flat[$key] = $val;
                        });
                        foreach ($flat as $k => $v):
                            if ($k === 'status') continue;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$k) ?></td>
                            <td><?= htmlspecialchars((string)$v) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if (!$momo_is_completed): ?>
            <form method="POST" novalidate>
                <input type="hidden" name="network" value="MTN">
                <div class="field">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars((string)($_POST['name'] ?? $prefillName)) ?>" placeholder="Enter Your Firstname" required>
                </div>
                <div class="field">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars((string)($_POST['email'] ?? $prefillEmail)) ?>" placeholder="Enter Your Email" required>
                </div>
                <div class="field">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars((string)($_POST['phone'] ?? $prefillPhone)) ?>" placeholder="Enter Your Phone Number To Pay" inputmode="numeric" pattern="[0-9]*" required>
                </div>
                <div class="field">
                    <label for="amount">Amount (RWF)</label>
                    <input type="number" id="amount" name="amount" value="<?= htmlspecialchars((string)($_POST['amount'] ?? $prefillAmount)) ?>" min="1" step="1" placeholder="Enter Amount in RWF" required>
                </div>
                <div class="divider"></div>
                <div class="field">
                    <label>Payment Method</label>
                    <label class="method-tile">
                        <input type="radio" name="payment_method" value="momo" checked>
                        <div>
                            <div class="method-name">Mobile Money (MoMo)</div>
                            <div class="method-sub">MTN Mobile Money only</div>
                        </div>
                    </label>
                    <p class="method-hint">A payment prompt will be sent to your phone.</p>
                </div>
                <button type="submit" name="btn" class="btn-pay">Pay with MoMo</button>
            </form>

            <script>
            (function() {
                var phoneInput = document.getElementById('phone');
                if (!phoneInput) return;
                phoneInput.addEventListener('input', function(e) {
                    var v = (e.target.value || '').replace(/\D+/g, '');
                    if (v.length > 15) v = v.slice(0, 15);
                    e.target.value = v;
                });
            })();
            </script>
            <?php endif; ?>
            <?php endif; ?>

        </div>
        <div class="actions">
            <a href="payment.php?student_id=<?= (int)($_GET['student_id'] ?? 0) ?>" class="back-link" style="margin-top:0;">← Back to payment</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; exit; ?>
<?php endif; ?>

<div class="page-hero">
    <div class="hero-content">
        <h1><i class="fas fa-shield-alt"></i> Secure Payment Portal</h1>
        <p class="hero-subtitle">Complete your payment in three simple steps</p>
        <div class="progress-steps">
            <div class="step <?= $selected_student ? 'completed active' : '' ?>">
                <div class="step-icon">1</div>
                <div class="step-text">Customer Verification</div>
            </div>
            <div class="step <?= $selected_student ? 'active' : '' ?>">
                <div class="step-icon">2</div>
                <div class="step-text">Package Selection</div>
            </div>
            <div class="step">
                <div class="step-icon">3</div>
                <div class="step-text">Payment Processing</div>
            </div>
        </div>
    </div>
</div>

<div class="page-content">
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <div class="alert-content">
                <strong>Success!</strong> <?= htmlspecialchars($success_message) ?>
            </div>
            <button class="alert-close" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>
    
    <?php if ($registration_error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <div class="alert-content">
                <strong>Attention Required!</strong> <?= htmlspecialchars($registration_error) ?>
            </div>
            <button class="alert-close" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>
    
    <div class="dashboard-container">
        <?php if (!$selected_student): ?>
            <!-- Student Verification Section -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-user-graduate"></i> Customer Verification</h2>
                    <p class="card-subtitle">To proceed with your payment, you can either create a new account or search for an existing one.</p>
                </div>
                
                <div class="card-body">
                    <!-- Mode Toggle Buttons (styled consistently) -->
                    <div class="customer-mode-toggle">
                        <button type="button" id="mode-create" class="mode-btn active">
                            <i class="fas fa-user-plus"></i>
                            <span>Create as New Customer</span>
                        </button>
                        <button type="button" id="mode-search" class="mode-btn">
                            <i class="fas fa-search"></i>
                            <span>Search Existing Customer</span>
                        </button>
                    </div>

                    <!-- Registration / Create Section (default visible) -->
                    <div id="create-section" class="create-section">
                        <div class="divider">
                            <span>OR</span>
                        </div>
                        
                        <div class="create-prompt">
                            <div class="prompt-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="prompt-content">
                                <h4>Create as New Customer</h4>
                                <p>New here? Fill in your details to create an account and continue.</p>
                            </div>
                        </div>
                        
                        <form method="POST" id="registration-form" class="registration-form">
                            <input type="hidden" name="register_student" value="1">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="first_name">
                                        <i class="fas fa-user"></i> First Name
                                        <span class="required">*</span>
                                    </label>
                                    <input type="text" 
                                           name="first_name" 
                                           id="first_name" 
                                           required 
                                           placeholder="Enter first name"
                                           class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="last_name">
                                        <i class="fas fa-user"></i> Last Name
                                        <span class="required">*</span>
                                    </label>
                                    <input type="text" 
                                           name="last_name" 
                                           id="last_name" 
                                           required 
                                           placeholder="Enter last name"
                                           class="form-control">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">
                                    <i class="fas fa-envelope"></i> Email Address
                                    <span class="required">*</span>
                                </label>
                                <input type="email" 
                                       name="email" 
                                       id="reg-email" 
                                       required 
                                       placeholder="Enter Your Email"
                                       class="form-control">
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="area_code">
                                        <i class="fas fa-phone"></i> Country Code
                                        <span class="required">*</span>
                                    </label>
                                    <div class="select-wrapper">
                                        <select name="area_code" id="area_code" required class="form-control">
                                            <option value="">-- Select your country code --</option>
                                            <?php foreach ($country_codes as $country): ?>
                                                <option value="<?= htmlspecialchars($country['code']) ?>">
                                                    <?= htmlspecialchars($country['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <i class="fas fa-chevron-down select-icon"></i>
                                    </div>
                                    <small class="form-help">Choose your country dial code</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone_number">
                                        <i class="fas fa-mobile-alt"></i> Phone Number
                                        <span class="required">*</span>
                                    </label>
                                    <input type="tel" 
                                           name="phone_number" 
                                           id="phone_number" 
                                           required 
                                           placeholder="Enter Your Phone Number"
                                           class="form-control">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-user-plus"></i> Create & Continue
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Live Search Section (hidden until Search tab is clicked) -->
                    <div class="search-section" id="search-section" style="display: none;">
                        <h3>
                            <i class="fas fa-search"></i>
                            Type Your Email, Phone Number, or Full Name to Proceed
                        </h3>

                        <p class="section-description">
                            Start typing to search for an existing account using your name, email, or phone number.
                            If you are not registered, please switch to "Create as New Customer".
                        </p>

                        <form method="POST" id="student-search-form" class="search-form">
                            <input type="hidden" name="select_student" value="1">
                            <input type="hidden" name="student_id" id="selected-student-id" value="">
                            
                            <div class="form-group">
                                <div class="search-container">
                                    <div class="input-with-icon">
                                        <i class="fas fa-search input-icon"></i>
                                        <input type="text" 
                                               id="search-input" 
                                               placeholder="Type student name, email, or phone..." 
                                               autocomplete="off"
                                               class="search-input">
                                    </div>
                                    <div id="search-results" class="search-results"></div>
                                </div>
                                <div class="input-hint">
                                    <i class="fas fa-info-circle"></i> Search by name, email, or phone number
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary" id="select-btn" disabled>
                                    <i class="fas fa-user-check"></i> Select Student
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Student Information & Package Selection -->
            <div class="dashboard-grid">
                <!-- Student Info Card -->
                <div class="student-card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-circle"></i> Student Information</h3>
                        <a href="payment.php" class="btn-change">
                            <i class="fas fa-exchange-alt"></i> Change Student
                        </a>
                    </div>
                    
                    <div class="student-info">
                        <div class="student-avatar">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        
                        <div class="student-details">
                            <h4><?= htmlspecialchars($selected_student['first_name'] . ' ' . $selected_student['last_name']) ?></h4>
                            <div class="detail-item">
                                <i class="fas fa-envelope"></i>
                                <span><?= htmlspecialchars($selected_student['email']) ?></span>
                            </div>
                            <?php if ($selected_student['phone_number']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?= htmlspecialchars($selected_student['area_code'] . ' ' . $selected_student['phone_number']) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="detail-item">
                                <i class="fas fa-id-card"></i>
                                <span>ID: <?= htmlspecialchars($selected_student['user_id']) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($registered_new): ?>
                        <div class="registration-success">
                            <i class="fas fa-check-circle"></i>
                            <span>New customer registered successfully!</span>
                            <?php if (isset($_GET['email_sent']) && $_GET['email_sent'] == 1): ?>
                                <br><small><i class="fas fa-envelope"></i> A confirmation email has been sent to your inbox.</small>
                            <?php elseif (isset($_GET['email_sent']) && $_GET['email_sent'] == 0): ?>
                                <br><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Registration complete, but the confirmation email could not be sent. Please contact support.</small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Package Selection Card -->
                <div class="package-card">
                    <div class="card-header">
                        <h3><i class="fas fa-box-open"></i> Package & Fee Selection</h3>
                        <p class="card-subtitle">Select your package and customize fee items</p>
                    </div>
                    
                    <div class="card-body">
                        <form method="POST" id="payment-form" class="payment-form">
                            <input type="hidden" name="process_payment" value="1">
                            
                            <!-- Package Selection -->
                            <div class="form-group">
                                <label for="package-select">
                                    <i class="fas fa-cube"></i> Select Package
                                    <span class="required">*</span>
                                </label>
                                <div class="select-wrapper">
                                    <select name="package_id" id="package-select" required onchange="loadFeeItems(this.value)">
                                        <option value="">-- Choose a package --</option>
                                        <?php foreach ($packages_summary as $package_id => $package): ?>
                                            <option value="<?= $package_id ?>">
                                                <?= htmlspecialchars($package['title']) ?> 
                                                - <?= $package['currency'] ?> <?= number_format($package['total_amount'], 2) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="fas fa-chevron-down select-icon"></i>
                                </div>
                                <small class="form-help">Choose the package that best fits your needs</small>
                            </div>
                            
                            <!-- Package Info (shown when package is selected) -->
                            <div id="package-info" style="display: none;" class="package-info-card">
                                <div class="package-info-header">
                                    <h4><i class="fas fa-info-circle"></i> Package Details</h4>
                                </div>
                                <div class="package-info-body">
                                    <div class="info-row">
                                        <span class="info-label">Package:</span>
                                        <span id="package-title" class="info-value"></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Currency:</span>
                                        <span id="package-currency" class="info-value"></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Package Total:</span>
                                        <span id="package-total" class="info-value amount"></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Remaining:</span>
                                        <span id="package-remaining" class="info-value amount"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Fee Items Section -->
                            <div id="fee-items-section" style="display: none;">
                                <div class="section-header">
                                    <h4><i class="fas fa-list-alt"></i> Available Fee Items</h4>
                                    <p class="section-description">Select items and adjust amounts (cannot exceed original price)</p>
                                </div>
                                
                                <div id="fee-items-list" class="fee-items-grid"></div>
                                
                                <!-- Selected Items Summary -->
                                <div id="selected-summary" class="selected-summary" style="display: none;">
                                    <div class="summary-header">
                                        <h4><i class="fas fa-check-circle"></i> Selected Items</h4>
                                    </div>
                                    <div id="selected-items-list" class="selected-items-list"></div>
                                </div>
                                
                                <!-- Enhanced Payment Summary -->
                                <div class="payment-summary-enhanced">
                                    <div class="summary-header">
                                        <h3><i class="fas fa-calculator"></i> Payment Summary</h3>
                                        <div class="summary-badge">
                                            <i class="fas fa-shield-alt"></i>
                                            <span>Secure Checkout</span>
                                        </div>
                                    </div>
                                    
                                    <div class="summary-content">
                                        <div class="summary-section">
                                            <h4><i class="fas fa-shopping-cart"></i> Order Details</h4>
                                            <div class="items-list" id="selected-items-list">
                                                <!-- Items will be populated by JavaScript -->
                                            </div>
                                        </div>
                                        
                                        <div class="summary-section">
                                            <div class="total-amount-display">
                                                <h4><i class="fas fa-calculator"></i> Total Amount</h4>
                                                <div class="total-amount-value" id="total-amount">€0.00</div>
                                            </div>
                                            <div class="remaining-amount-display">
                                                <small id="remaining-amount">Remaining from package: €0.00</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Payment Method -->
                                <div class="payment-methods">
                                    <h4><i class="fas fa-credit-card"></i> Payment Method</h4>
                                    <div class="method-options">
                                        <label class="method-option active">
                                            <input type="radio" name="payment_method" value="stripe" checked>
                                            <div class="method-content">
                                                <div class="method-icon">
                                                    <i class="fab fa-cc-stripe"></i>
                                                </div>
                                                <div class="method-info">
                                                    <h5>Credit/Debit Card</h5>
                                                    <p>Pay securely with Stripe</p>
                                                </div>
                                            </div>
                                            <div class="method-check">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </label>
                                        
                                        <label class="method-option">
                                            <input type="radio" name="payment_method" value="momo">
                                            <div class="method-content">
                                                <div class="method-icon">
                                                    <i class="fas fa-mobile-alt"></i>
                                                </div>
                                                <div class="method-info">
                                                    <h5>Mobile Money (MoMo)</h5>
                                                    <p>Pay using Mobile Money prompt</p>
                                                </div>
                                            </div>
                                            <div class="method-check">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </label>
                                        
                                      
                                    </div>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary btn-pay" id="submit-btn" disabled>
                                        <i class="fas fa-lock"></i>
                                        <span>Proceed to Secure Payment</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                    <p class="security-note">
                                        <i class="fas fa-shield-alt"></i>
                                        Your payment is secured with 256-bit SSL encryption
                                    </p>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Fee items data for JavaScript
const feeItemsData = <?= json_encode($fee_items) ?>;
const packagesData = <?= json_encode($packages_summary) ?>;

// Mode Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const modeCreate = document.getElementById('mode-create');
    const modeSearch = document.getElementById('mode-search');
    const createSection = document.getElementById('create-section');
    const searchSection = document.getElementById('search-section');

    if (modeCreate && modeSearch && createSection && searchSection) {
        // Function to switch mode
        function setMode(mode) {
            if (mode === 'create') {
                modeCreate.classList.add('active');
                modeSearch.classList.remove('active');
                createSection.style.display = 'block';
                searchSection.style.display = 'none';
            } else {
                modeSearch.classList.add('active');
                modeCreate.classList.remove('active');
                searchSection.style.display = 'block';
                createSection.style.display = 'none';
                // Optional: focus search input
                document.getElementById('search-input')?.focus();
            }
        }

        modeCreate.addEventListener('click', () => setMode('create'));
        modeSearch.addEventListener('click', () => setMode('search'));
    }

    // Live Search Functionality
    let searchTimeout;
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');
    const selectBtn = document.getElementById('select-btn');
    const selectedStudentId = document.getElementById('selected-student-id');

    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchResults.innerHTML = '';
                selectBtn.disabled = true;
                selectedStudentId.value = '';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                searchResults.innerHTML = '<div class="search-loading"><i class="fas fa-spinner fa-spin"></i> Searching student database...</div>';
                
                fetch(`search-students.php?q=${encodeURIComponent(query)}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network error');
                        return response.json();
                    })
                    .then(students => {
                        displaySearchResults(students);
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        searchResults.innerHTML = '<div class="search-no-results"><i class="fas fa-exclamation-circle"></i> Error loading results. Please try again.</div>';
                    });
            }, 300);
        });
        
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-container')) {
                searchResults.innerHTML = '';
            }
        });
    }

    function displaySearchResults(students) {
        if (!students || students.length === 0) {
            searchResults.innerHTML = '<div class="search-no-results"><i class="fas fa-search"></i> No students found matching your search.</div>';
            selectBtn.disabled = true;
            selectedStudentId.value = '';
            return;
        }
        
        let html = '<div class="search-results-list">';
        students.forEach(student => {
            const fullName = `${student.first_name} ${student.last_name}`;
            const phone = student.area_code && student.phone_number ? 
                `${student.area_code} ${student.phone_number}` : 'No phone number';
            
            const safeName = escapeHtml(fullName);
            const safeEmail = escapeHtml(student.email);
            const safePhone = escapeHtml(phone);
            
            html += `
                <div class="search-result-item" onclick="selectStudent(${student.id}, '${safeEmail}', '${student.first_name}', '${student.last_name}')">
                    <div class="result-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="result-info">
                        <div class="result-name">${safeName}</div>
                        <div class="result-email"><i class="fas fa-envelope"></i> ${safeEmail}</div>
                        <div class="result-phone"><i class="fas fa-phone"></i> ${safePhone}</div>
                        <div class="result-id"><i class="fas fa-id-card"></i> ${student.user_id}</div>
                    </div>
                    <div class="result-select">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        searchResults.innerHTML = html;
    }

    window.selectStudent = function(studentId, email, firstName, lastName) {
        searchInput.value = `${firstName} ${lastName} (${email})`;
        selectedStudentId.value = studentId;
        searchResults.innerHTML = '';
        selectBtn.disabled = false;
        
        selectBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Selecting...';
        selectBtn.disabled = true;
        
        const form = document.getElementById('student-search-form');
        setTimeout(() => {
            form.submit();
        }, 100);
    };

    // Payment method selection
    const methodOptions = document.querySelectorAll('.method-option');
    methodOptions.forEach(option => {
        option.addEventListener('click', function() {
            methodOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Initialize first method as selected
    if (methodOptions.length > 0) {
        methodOptions[0].classList.add('active');
    }
    
    // Form validation for registration
    const registrationForm = document.getElementById('registration-form');
    if (registrationForm) {
        registrationForm.addEventListener('submit', function(e) {
            const phoneInput = document.getElementById('phone_number');
            const phoneValue = phoneInput.value.trim();
            
            if (!/^\d{10,15}$/.test(phoneValue)) {
                e.preventDefault();
                showMessage('Please enter a valid phone number (10-15 digits, numbers only).', 'warning');
                phoneInput.focus();
                return false;
            }
            
            // Area code is now a dropdown, so it will always be valid if selected.
            // We only need to ensure it's not empty (already handled by 'required' attribute)
            
            return true;
        });
    }
    
    // Phone number formatting
    const phoneInput = document.getElementById('phone_number');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 15) value = value.substr(0, 15);
            e.target.value = value;
        });
    }
    
    // (Removed area code formatting listener)
    
    // Amount input validation for fee items
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('amount-input') && !e.target.disabled) {
            const value = parseFloat(e.target.value);
            const max = parseFloat(e.target.max);
            
            if (isNaN(value) || value < 0) {
                e.target.value = '0.00';
            } else if (value > max) {
                e.target.value = max.toFixed(2);
                showMessage('Cannot exceed original amount', 'warning');
            }
        }
    });
});

// Fee items functions (loadFeeItems, toggleFeeItem, updateAmount, etc.)
function loadFeeItems(packageId) {
    const feeItemsSection = document.getElementById('fee-items-section');
    const feeItemsList = document.getElementById('fee-items-list');
    const submitBtn = document.getElementById('submit-btn');
    const packageSelect = document.getElementById('package-select');
    const packageInfo = document.getElementById('package-info');
    const selectedSummary = document.getElementById('selected-summary');
    
    if (!packageId) {
        feeItemsSection.style.display = 'none';
        packageInfo.style.display = 'none';
        selectedSummary.style.display = 'none';
        submitBtn.disabled = true;
        return;
    }
    
    const package = packagesData[packageId];
    if (!package) {
        feeItemsSection.style.display = 'none';
        packageInfo.style.display = 'none';
        selectedSummary.style.display = 'none';
        submitBtn.disabled = true;
        return;
    }
    
    // Show package info
    document.getElementById('package-title').textContent = package.title;
    document.getElementById('package-currency').textContent = package.currency;
    document.getElementById('package-total').textContent = `${package.currency} ${parseFloat(package.total_amount).toFixed(2)}`;
    packageInfo.style.display = 'block';
    
    const items = feeItemsData[packageId] || [];
    const currency = package.currency || 'USD';
    const packageTotal = parseFloat(package.total_amount) || 0;
    
    if (items.length === 0) {
        feeItemsList.innerHTML = '<div class="no-items"><i class="fas fa-box-open"></i> No fee items available for this package.</div>';
        feeItemsSection.style.display = 'block';
        selectedSummary.style.display = 'none';
        submitBtn.disabled = true;
        updateTotals(0, 0, currency);
        return;
    }
    
    let html = '';
    
    items.forEach((item) => {
        const amount = parseFloat(item.total_expected || item.amount || 0);
        const itemCurrency = item.package_currency || currency;
        const pkgTotal = packageTotal;
        const percentage = pkgTotal > 0 ? (amount / pkgTotal) * 100 : 0;

        const itemId = escapeHtml(item.id.toString());
        const rawTitle = (item.title || item.name || '');
        const titleWithoutPercent = rawTitle.replace(/\(\s*\d+\s*%\s*\)/, '').trim();
        const itemTitle = escapeHtml(titleWithoutPercent);
        const itemCode = escapeHtml(item.code || 'N/A');
        const description = item.description ? escapeHtml(item.description) : '';
        
        html += `
            <div class="fee-item-card" data-original-amount="${amount}" data-package-total="${pkgTotal}">
                <div class="fee-item-header">
                    <div class="custom-checkbox">
                        <input type="checkbox" 
                               id="fee_${itemId}" 
                               name="selected_items[]" 
                               value="${itemId}"
                               data-original-amount="${amount}"
                               data-currency="${itemCurrency}"
                               onchange="toggleFeeItem(this, ${itemId})">
                        <label for="fee_${itemId}">
                            <div class="checkbox-box">
                                <i class="fas fa-check"></i>
                            </div>
                        </label>
                    </div>
                    <div class="fee-item-info">
                        <h5>${itemTitle}</h5>
                        ${description ? `<p class="fee-description">${description}</p>` : ''}
                        <div class="fee-item-meta">
                            <span class="fee-code"><i class="fas fa-hashtag"></i> ${itemCode}</span>
                            ${item.refundable ? '<span class="refundable-badge"><i class="fas fa-undo"></i> Refundable</span>' : ''}
                        </div>
                    </div>
                </div>
                
                <div class="fee-item-amount">
                    <div class="amount-control">
                        <div class="amount-input-group">
                            <span class="currency-label">${itemCurrency}</span>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   max="${amount}"
                                   class="amount-input" 
                                   id="amount_${itemId}" 
                                   name="item_amount[${itemId}]" 
                                   value="${amount.toFixed(2)}"
                                   disabled
                                   oninput="updateAmount(${itemId}, this.value)"
                                   placeholder="0.00">
                        </div>
                        <div class="amount-hint">
                            <small>
                                Max: ${itemCurrency} ${amount.toFixed(2)}
                                · <span class="amount-percentage" id="percent_${itemId}">${percentage.toFixed(2)}%</span> of package
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    feeItemsList.innerHTML = html;
    feeItemsSection.style.display = 'block';
    selectedSummary.style.display = 'none';
    
    setTimeout(() => {
        feeItemsSection.style.opacity = '1';
        feeItemsSection.style.transform = 'translateY(0)';
    }, 10);
    
    updateSelectedItemsList();
    calculateTotal();
}

function toggleFeeItem(checkbox, itemId) {
    const amountInput = document.getElementById(`amount_${itemId}`);
    const feeItemCard = amountInput.closest('.fee-item-card');
    const selectedSummary = document.getElementById('selected-summary');
    
    if (checkbox.checked) {
        amountInput.disabled = false;
        feeItemCard.classList.add('selected');
        selectedSummary.style.display = 'block';
    } else {
        amountInput.disabled = true;
        feeItemCard.classList.remove('selected');
        // Reset to original amount
        const originalAmount = checkbox.getAttribute('data-original-amount');
        amountInput.value = parseFloat(originalAmount).toFixed(2);
    }
    
    updateSelectedItemsList();
    calculateTotal();
}

function updateAmount(itemId, value) {
    const checkbox = document.getElementById(`fee_${itemId}`);
    const originalAmount = parseFloat(checkbox.getAttribute('data-original-amount'));
    const input = document.getElementById(`amount_${itemId}`);
    const feeItemCard = input.closest('.fee-item-card');
    const pkgTotalAttr = feeItemCard ? feeItemCard.getAttribute('data-package-total') : null;
    const pkgTotal = pkgTotalAttr ? parseFloat(pkgTotalAttr) : 0;
    
    // Ensure value doesn't exceed original amount
    let newValue = parseFloat(value) || 0;
    
    if (newValue > originalAmount) {
        newValue = originalAmount;
        input.value = newValue.toFixed(2);
        showMessage('Cannot exceed original amount', 'warning');
    }
    
    if (newValue < 0) {
        newValue = 0;
        input.value = newValue.toFixed(2);
    }

    // Update percentage display for this item based on current value
    if (pkgTotal > 0) {
        const percentSpan = document.getElementById(`percent_${itemId}`);
        if (percentSpan) {
            const pct = (newValue / pkgTotal) * 100;
            percentSpan.textContent = `${pct.toFixed(2)}%`;
        }
    }

    updateSelectedItemsList();
    calculateTotal();
}

function updateSelectedItemsList() {
    const checkboxes = document.querySelectorAll('input[name="selected_items[]"]:checked');
    const selectedList = document.getElementById('selected-items-list');
    const selectedSummary = document.getElementById('selected-summary');
    
    if (checkboxes.length === 0) {
        selectedSummary.style.display = 'none';
        return;
    }
    
    let html = '';
    checkboxes.forEach(checkbox => {
        const itemId = checkbox.value;
        const amountInput = document.getElementById(`amount_${itemId}`);
        const amount = parseFloat(amountInput.value) || 0;
        const currency = checkbox.getAttribute('data-currency') || '$';
        const feeItemCard = checkbox.closest('.fee-item-card');
        const itemTitle = feeItemCard.querySelector('.fee-item-info h5').textContent;
        
        html += `
            <div class="selected-item">
                <div class="selected-item-info">
                    <span class="selected-item-name">${itemTitle}</span>
                    <span class="selected-item-amount">${currency} ${amount.toFixed(2)}</span>
                </div>
            </div>
        `;
    });
    
    selectedList.innerHTML = html;
    selectedSummary.style.display = 'block';
}

function calculateTotal() {
    const checkboxes = document.querySelectorAll('input[name="selected_items[]"]:checked');
    let total = 0;

    // Recalculate percentages for ALL fee items based on their current values
    document.querySelectorAll('.fee-item-card').forEach(card => {
        const input = card.querySelector('.amount-input');
        if (!input) return;

        const pkgTotalAttr = card.getAttribute('data-package-total');
        const pkgTotal = pkgTotalAttr ? parseFloat(pkgTotalAttr) : 0;
        const currentVal = parseFloat(input.value) || 0;
        const percentSpan = card.querySelector('.amount-percentage');

        if (percentSpan && pkgTotal > 0) {
            const pct = (currentVal / pkgTotal) * 100;
            percentSpan.textContent = `${pct.toFixed(2)}%`;
        }
    });

    // Sum only selected items for the total
    checkboxes.forEach(checkbox => {
        const itemId = checkbox.value;
        const amountInput = document.getElementById(`amount_${itemId}`);
        const amount = parseFloat(amountInput.value) || 0;
        total += amount;
    });
    
    updateTotal(total);
}

function updateTotal(total) {
    let currency = '$';
    const firstChecked = document.querySelector('input[name="selected_items[]"]:checked');
    if (firstChecked) {
        currency = firstChecked.getAttribute('data-currency') || '$';
    }

    // Derive package total from any fee-item-card (all cards for the same package share the same total)
    let packageTotal = 0;
    const anyCard = document.querySelector('.fee-item-card');
    if (anyCard) {
        const pkgAttr = anyCard.getAttribute('data-package-total');
        if (pkgAttr) {
            packageTotal = parseFloat(pkgAttr) || 0;
        }
    }

    // Clamp remaining to 0 to avoid negative when user pays more than package total
    let remaining = packageTotal > 0 ? packageTotal - total : 0;
    if (remaining < 0) remaining = 0;

    // Update Total line
    const totalEl = document.getElementById('total-amount');
    if (totalEl) {
        totalEl.textContent = `${currency} ${total.toFixed(2)}`;
    }

    // Update Remaining line in payment summary (right box)
    const remainingEl = document.getElementById('remaining-amount');
    if (remainingEl) {
        if (packageTotal > 0) {
            remainingEl.textContent = `Remaining from package: ${currency} ${remaining.toFixed(2)}`;
        } else {
            remainingEl.textContent = 'Remaining from package: N/A';
        }
    }

    // Update Remaining line in Package Details card (blue box)
    const pkgRemainingEl = document.getElementById('package-remaining');
    if (pkgRemainingEl) {
        if (packageTotal > 0) {
            pkgRemainingEl.textContent = `${currency} ${remaining.toFixed(2)}`;
        } else {
            pkgRemainingEl.textContent = 'N/A';
        }
    }

    // Update button label and disabled state
    const submitBtn = document.getElementById('submit-btn');
    if (submitBtn) {
        submitBtn.disabled = total === 0;
        if (total > 0) {
            submitBtn.querySelector('span').textContent = `Pay ${currency} ${total.toFixed(2)}`;
        } else {
            submitBtn.querySelector('span').textContent = 'Proceed to Secure Payment';
        }
    }
}

function showMessage(message, type) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `alert alert-${type}`;
    messageDiv.innerHTML = `
        <i class="fas fa-${type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
        <div class="alert-content">${message}</div>
        <button class="alert-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    const pageContent = document.querySelector('.page-content');
    pageContent.insertBefore(messageDiv, pageContent.firstChild);
    
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.remove();
        }
    }, 5000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
<style>
/* Professional Design Variables */
:root {
    --primary: #1e3a5f;
    --primary-dark: #0f2542;
    --secondary: #7209b7;
    --success: #4cc9f0;
    --success-dark: #3a86ff;
    --danger: #f72585;
    --warning: #f8961e;
    --info: #06d6a0;
    --light: #f8f9fa;
    --dark: #212529;
    --gray: #6c757d;
    --gray-light: #e9ecef;
    --border: #dee2e6;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    --radius: 12px;
    --radius-sm: 8px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Base Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    background: #f8fafc;
    color: var(--dark);
    line-height: 1.6;
}

/* Page Hero */
.page-hero {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 40px 20px;
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}

.hero-content {
    max-width: 1200px;
    margin: 0 auto;
    text-align: center;
}

.hero-content h1 {
    font-size: clamp(1.8rem, 4vw, 2.8rem);
    font-weight: 700;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
}

.hero-subtitle {
    font-size: clamp(1rem, 2vw, 1.2rem);
    opacity: 0.9;
    margin-bottom: 30px;
    font-weight: 300;
}

/* Progress Steps */
.progress-steps {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: clamp(20px, 4vw, 40px);
    margin-top: 30px;
    position: relative;
    flex-wrap: wrap;
}

.progress-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    width: 60%;
    height: 3px;
    background: rgba(255, 255, 255, 0.3);
    z-index: 1;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    z-index: 2;
    position: relative;
    min-width: 100px;
}

.step-icon {
    width: 44px;
    height: 44px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.1rem;
    transition: var(--transition);
}

.step-text {
    font-size: 0.9rem;
    font-weight: 500;
    opacity: 0.7;
    transition: var(--transition);
    text-align: center;
}

.step.active .step-icon {
    background: white;
    color: var(--primary);
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.step.active .step-text {
    opacity: 1;
    font-weight: 600;
}

.step.completed .step-icon {
    background: var(--success);
    color: white;
}

/* Alerts */
.alert {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 16px 20px;
    border-radius: var(--radius-sm);
    margin-bottom: 30px;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border-left: 4px solid var(--success);
    color: #065f46;
}

.alert-error {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border-left: 4px solid var(--danger);
    color: #7f1d1d;
}

.alert-warning {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-left: 4px solid var(--warning);
    color: #92400e;
}

.alert-info {
    background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
    border-left: 4px solid var(--primary);
    color: #0369a1;
}

.alert i {
    font-size: 1.3rem;
}

.alert-content {
    flex: 1;
    font-size: 0.95rem;
}

.alert-content strong {
    display: block;
    margin-bottom: 4px;
}

.alert-close {
    background: none;
    border: none;
    color: inherit;
    cursor: pointer;
    opacity: 0.7;
    transition: var(--transition);
    padding: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.alert-close:hover {
    opacity: 1;
}

/* Dashboard Container */
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Dashboard Card */
.dashboard-card {
    background: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
    margin-bottom: 30px;
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card-header {
    padding: 25px 30px;
    border-bottom: 1px solid var(--border);
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.card-header h2 {
    margin: 0 0 8px 0;
    color: var(--dark);
    font-size: clamp(1.3rem, 3vw, 1.5rem);
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.card-subtitle {
    margin: 0;
    color: var(--gray);
    font-size: 0.95rem;
}

.card-body {
    padding: 30px;
}

@media (max-width: 768px) {
    .card-header,
    .card-body {
        padding: 20px;
    }
}

/* Mode Toggle Buttons */
.customer-mode-toggle {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.mode-btn {
    flex: 1 1 200px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 16px 24px;
    border: 2px solid var(--border);
    border-radius: var(--radius-sm);
    background: white;
    color: var(--dark);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: var(--shadow);
}

.mode-btn i {
    font-size: 1.2rem;
    color: var(--primary);
    transition: var(--transition);
}

.mode-btn:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.mode-btn.active {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-color: var(--primary-dark);
    color: white;
}

.mode-btn.active i {
    color: white;
}

/* Search Section */
.search-section {
    margin-bottom: 0;
}

.search-section h3 {
    margin: 0 0 10px 0;
    color: var(--dark);
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-description {
    margin: 0 0 25px 0;
    color: var(--gray);
    font-size: 0.95rem;
}

/* Search Form */
.search-form {
    position: relative;
}

.search-container {
    position: relative;
}

.input-with-icon {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray);
    font-size: 1.1rem;
}

.search-input {
    width: 100%;
    padding: 16px 20px 16px 55px;
    border: 2px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 1rem;
    transition: var(--transition);
    background: white;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

.input-hint {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 8px;
    color: var(--gray);
    font-size: 0.85rem;
    font-style: italic;
}

.input-hint i {
    color: var(--primary);
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 2px solid var(--border);
    border-top: none;
    border-radius: 0 0 var(--radius-sm) var(--radius-sm);
    max-height: 400px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: var(--shadow-lg);
    margin-top: -2px;
}

.search-loading {
    padding: 30px;
    text-align: center;
    color: var(--gray);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
}

.search-no-results {
    padding: 30px;
    text-align: center;
    color: var(--gray);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
    background: var(--light);
}

.search-no-results i {
    font-size: 2rem;
    color: var(--gray);
}

.search-results-list {
    padding: 10px 0;
}

.search-result-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    transition: var(--transition);
    background: white;
}

.search-result-item:hover {
    background: #f8fafc;
    transform: translateX(5px);
}

.search-result-item:last-child {
    border-bottom: none;
}

.result-avatar {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.result-info {
    flex: 1;
    min-width: 0;
}

.result-name {
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 6px;
    font-size: 1.05rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.result-email, .result-phone, .result-id {
    font-size: 0.9rem;
    color: var(--gray);
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 3px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.result-select {
    color: var(--primary);
    opacity: 0.7;
    transition: var(--transition);
    flex-shrink: 0;
}

.search-result-item:hover .result-select {
    opacity: 1;
    transform: translateX(5px);
}

/* Divider */
.divider {
    display: flex;
    align-items: center;
    margin: 40px 0 30px;
    position: relative;
}

.divider::before, .divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border);
}

.divider span {
    padding: 0 20px;
    color: var(--primary);
    font-weight: 600;
    font-size: 1rem;
    background: white;
    z-index: 1;
}

/* Create Prompt (formerly Register Prompt) */
.create-prompt {
    display: flex;
    align-items: center;
    gap: 20px;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 1px solid var(--primary);
    border-radius: var(--radius-sm);
    padding: 25px;
    margin-bottom: 30px;
}

.prompt-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.prompt-content {
    flex: 1;
    min-width: 0;
}

.prompt-content h4 {
    margin: 0 0 8px 0;
    color: var(--dark);
    font-size: 1.2rem;
}

.prompt-content p {
    margin: 0;
    color: var(--gray);
    font-size: 0.95rem;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Registration Form */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    font-weight: 500;
    color: var(--dark);
    font-size: 0.95rem;
}

.form-group label i {
    color: var(--primary);
    width: 16px;
}

.required {
    color: var(--danger);
    font-weight: 600;
}

.form-control {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 1rem;
    transition: var(--transition);
    background: white;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

.input-group {
    display: flex;
}

.input-group-text {
    padding: 0 15px;
    background: var(--gray-light);
    border: 2px solid var(--border);
    border-right: none;
    border-radius: var(--radius-sm) 0 0 var(--radius-sm);
    display: flex;
    align-items: center;
    color: var(--gray);
    font-weight: 500;
}

.input-group .form-control {
    border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 28px;
    border: none;
    border-radius: var(--radius-sm);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    text-align: center;
    white-space: nowrap;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    width: 100%;
    padding: 16px;
    font-size: 1.1rem;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(67, 97, 238, 0.3);
}

.btn-success {
    background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
    color: white;
    width: 100%;
    padding: 16px;
    font-size: 1.1rem;
}

.btn-success:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(76, 201, 240, 0.3);
}

.btn-change {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: var(--gray-light);
    color: var(--dark);
    border-radius: var(--radius-sm);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: var(--transition);
    white-space: nowrap;
}

.btn-change:hover {
    background: var(--border);
    transform: translateY(-1px);
}

.btn-pay {
    width: 100%;
    padding: 20px;
    font-size: 1.1rem;
    border-radius: var(--radius);
    box-shadow: 0 4px 15px rgba(67, 97, 238, 0.2);
}

.btn-pay:hover:not(:disabled) {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

.form-actions {
    margin-top: 30px;
}

.security-note {
    text-align: center;
    margin-top: 15px;
    color: var(--gray);
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
}

.security-note i {
    color: var(--success);
}

/* Dashboard Grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

@media (max-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

/* Student Card */
.student-card {
    background: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    height: fit-content;
}

.student-card .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.student-card .card-header h3 {
    margin: 0;
    font-size: 1.2rem;
}

.student-info {
    padding: 25px;
}

.student-avatar {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    margin: 0 auto 25px;
}

.student-details {
    text-align: center;
}

.student-details h4 {
    margin: 0 0 20px 0;
    color: var(--dark);
    font-size: 1.3rem;
    word-break: break-word;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
    text-align: left;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-item i {
    width: 20px;
    color: var(--primary);
    flex-shrink: 0;
}

.detail-item span {
    flex: 1;
    color: var(--gray);
    font-size: 0.95rem;
    word-break: break-word;
}

.registration-success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    padding: 15px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #065f46;
    font-weight: 500;
    border-top: 1px solid rgba(6, 95, 70, 0.1);
    flex-wrap: wrap;
}

.registration-success i {
    font-size: 1.2rem;
    flex-shrink: 0;
}

.registration-success span {
    flex: 1;
    word-break: break-word;
}

.registration-success small {
    width: 100%;
    margin-left: 28px;
    font-size: 0.85rem;
    opacity: 0.9;
}

/* Package Card */
.package-card {
    background: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.package-card .card-body {
    padding: 30px;
}

/* Select Wrapper */
.select-wrapper {
    position: relative;
}

.select-wrapper select {
    width: 100%;
    padding: 14px 45px 14px 16px;
    border: 2px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 1rem;
    appearance: none;
    background: white;
    cursor: pointer;
    transition: var(--transition);
}

.select-wrapper select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

.select-icon {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray);
    pointer-events: none;
}

.form-help {
    display: block;
    margin-top: 8px;
    color: var(--gray);
    font-size: 0.85rem;
}

/* Package Info Card */
.package-info-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 1px solid var(--primary);
    border-radius: var(--radius-sm);
    padding: 20px;
    margin: 20px 0;
}

.package-info-header {
    margin-bottom: 15px;
}

.package-info-header h4 {
    margin: 0;
    color: var(--dark);
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.package-info-body {
    display: grid;
    gap: 12px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(67, 97, 238, 0.1);
}

.info-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.info-label {
    font-weight: 500;
    color: var(--gray);
}

.info-value {
    color: var(--dark);
    font-weight: 500;
}

.info-value.amount {
    font-weight: 700;
    color: var(--primary);
}

/* Fee Items */
.section-header {
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border);
}

.section-header h4 {
    margin: 0 0 8px 0;
    color: var(--dark);
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.fee-items-grid {
    display: grid;
    gap: 15px;
    margin-bottom: 30px;
}

.fee-item-card {
    background: white;
    border: 2px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: var(--transition);
    flex-wrap: wrap;
    gap: 20px;
}

.fee-item-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(67, 97, 238, 0.1);
}

.fee-item-card.selected {
    border-color: var(--primary);
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.fee-item-header {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    flex: 1;
    min-width: 0;
}

.custom-checkbox {
    position: relative;
    flex-shrink: 0;
}

.custom-checkbox input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

.checkbox-box {
    width: 24px;
    height: 24px;
    border: 2px solid var(--border);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
    background: white;
    margin-top: 2px;
}

.checkbox-box i {
    color: white;
    font-size: 0.8rem;
    opacity: 0;
    transition: var(--transition);
}

.custom-checkbox input:checked + label .checkbox-box {
    background: var(--primary);
    border-color: var(--primary);
}

.custom-checkbox input:checked + label .checkbox-box i {
    opacity: 1;
}

.fee-item-info {
    flex: 1;
    min-width: 0;
}

.fee-item-info h5 {
    margin: 0 0 8px 0;
    color: var(--dark);
    font-size: 1rem;
    font-weight: 600;
    word-break: break-word;
}

.fee-description {
    margin: 0 0 8px 0;
    color: var(--gray);
    font-size: 0.9rem;
    line-height: 1.4;
    word-break: break-word;
}

.fee-item-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.fee-code {
    color: var(--gray);
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.refundable-badge {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 5px;
}

.fee-item-amount {
    text-align: right;
    flex-shrink: 0;
}

.amount-control {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 5px;
}

.amount-input-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.currency-label {
    font-weight: 600;
    color: var(--dark);
    font-size: 1rem;
}

.amount-input {
    width: 120px;
    padding: 10px 12px;
    border: 2px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 1rem;
    text-align: right;
    font-weight: 500;
    transition: var(--transition);
    background: white;
}

.amount-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

.amount-input:disabled {
    background: var(--gray-light);
    color: var(--gray);
    cursor: not-allowed;
}

.amount-hint small {
    color: var(--gray);
    font-size: 0.8rem;
    font-style: italic;
}

.no-items {
    text-align: center;
    padding: 60px 20px;
    color: var(--gray);
    background: var(--gray-light);
    border-radius: var(--radius-sm);
    border: 2px dashed var(--border);
}

.no-items i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

/* Selected Summary */
.selected-summary {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: var(--radius-sm);
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid var(--border);
}

.selected-summary .summary-header {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border);
}

.selected-summary .summary-header h4 {
    margin: 0;
    font-size: 1.1rem;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 10px;
}

.selected-items-list {
    display: grid;
    gap: 12px;
}

.selected-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: white;
    border-radius: 6px;
    border: 1px solid var(--border);
}

.selected-item-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    gap: 15px;
}

.selected-item-name {
    color: var(--dark);
    font-weight: 500;
    flex: 1;
    word-break: break-word;
}

.selected-item-amount {
    color: var(--primary);
    font-weight: 600;
    font-size: 1rem;
    flex-shrink: 0;
}

/* Payment Summary */
.payment-summary-enhanced {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    border-radius: var(--radius);
    padding: 25px;
    margin: 30px 0;
    border: 1px solid var(--primary);
}

.payment-summary-enhanced .summary-header {
    margin-bottom: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    padding-bottom: 15px;
}

.payment-summary-enhanced .summary-header h3 {
    margin: 0;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 10px;
    color: white;
}

.summary-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.2);
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    margin-left: 10px;
}

.summary-content {
    display: grid;
    gap: 20px;
}

.summary-section h4 {
    margin: 0 0 10px 0;
    font-size: 1rem;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 8px;
}

.total-amount-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-top: 2px solid rgba(255,255,255,0.2);
    border-bottom: 2px solid rgba(255,255,255,0.2);
}

.total-amount-value {
    font-size: 1.8rem;
    font-weight: 700;
}

.remaining-amount-display {
    text-align: right;
    opacity: 0.8;
}

/* Payment Methods */
.payment-methods {
    margin-bottom: 30px;
}

.payment-methods h4 {
    margin: 0 0 20px 0;
    color: var(--dark);
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.method-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.method-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    border: 2px solid var(--border);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: var(--transition);
    background: white;
    position: relative;
}

.method-option input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.method-option.active,
.method-option.selected {
    border-color: var(--primary);
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.method-content {
    display: flex;
    align-items: center;
    gap: 15px;
    flex: 1;
    min-width: 0;
}

.method-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.method-info {
    flex: 1;
    min-width: 0;
}

.method-info h5 {
    margin: 0 0 5px 0;
    color: var(--dark);
    font-size: 1rem;
    word-break: break-word;
}

.method-info p {
    margin: 0;
    color: var(--gray);
    font-size: 0.9rem;
    word-break: break-word;
}

.method-check {
    color: var(--primary);
    font-size: 1.2rem;
    opacity: 0;
    transition: var(--transition);
    flex-shrink: 0;
}

.method-option.active .method-check,
.method-option.selected .method-check {
    opacity: 1;
}

/* Animation for fee items section */
#fee-items-section {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.5s ease, transform 0.5s ease;
}

/* Responsive Design for Mobile */
@media (max-width: 768px) {
    .page-hero {
        padding: 30px 15px;
    }
    
    .progress-steps::before {
        display: none;
    }
    
    .progress-steps {
        gap: 20px;
    }
    
    .step {
        min-width: 80px;
    }
    
    .step-icon {
        width: 36px;
        height: 36px;
        font-size: 0.9rem;
    }
    
    .step-text {
        font-size: 0.8rem;
    }
    
    .alert {
        padding: 12px 15px;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .alert-content {
        font-size: 0.9rem;
    }
    
    .dashboard-container {
        padding: 0 15px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .search-results {
        position: static;
        margin-top: 10px;
        border: 2px solid var(--border);
        border-radius: var(--radius-sm);
    }
    
    .create-prompt {
        flex-direction: column;
        text-align: center;
        padding: 20px;
        gap: 15px;
    }
    
    .prompt-icon {
        width: 50px;
        height: 50px;
        font-size: 1.3rem;
    }
    
    .fee-item-card {
        flex-direction: column;
        align-items: stretch;
        gap: 20px;
    }
    
    .fee-item-amount {
        text-align: left;
        width: 100%;
    }
    
    .amount-control {
        align-items: stretch;
    }
    
    .amount-input-group {
        width: 100%;
    }
    
    .amount-input {
        width: 100%;
    }
    
    .selected-item-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .method-options {
        grid-template-columns: 1fr;
    }
    
    .btn {
        padding: 12px 20px;
        font-size: 0.95rem;
    }
    
    .btn-pay {
        padding: 16px;
        font-size: 1rem;
    }
}

/* Extra Small Devices */
@media (max-width: 480px) {
    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .card-header h3 {
        font-size: 1.1rem;
    }
    
    .btn-change {
        width: 100%;
        justify-content: center;
    }
    
    .student-avatar {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .student-details h4 {
        font-size: 1.1rem;
    }
    
    .detail-item {
        font-size: 0.9rem;
    }
    
    .package-card .card-body {
        padding: 20px;
    }
    
    .payment-summary-enhanced {
        padding: 20px;
    }
    
    .total-amount-value {
        font-size: 1.5rem;
    }
    
    .method-content {
        gap: 10px;
    }
    
    .method-icon {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
    }
    
    .method-info h5 {
        font-size: 0.95rem;
    }
    
    .method-info p {
        font-size: 0.85rem;
    }
}

/* Print Styles */
@media print {
    .page-hero,
    .quick-actions,
    .btn-change,
    .alert-close,
    .security-note,
    .customer-mode-toggle {
        display: none;
    }
    
    .dashboard-card,
    .student-card,
    .package-card {
        box-shadow: none;
        border: 1px solid #ddd;
    }
    
    .btn {
        display: none;
    }
    
    .form-group select,
    .form-group input:not([type="checkbox"]):not([type="radio"]) {
        border: 1px solid #ddd;
        background: white;
    }
}
</style>

<?php
include 'footer.php';
?>