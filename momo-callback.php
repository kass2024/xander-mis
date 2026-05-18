<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/payment_config.php';

$receipt_pdf_available = file_exists(__DIR__ . '/generateReceiptPdf.php');
if ($receipt_pdf_available) {
    require_once __DIR__ . '/generateReceiptPdf.php';
}

$debug_mode = (string)($_GET['debug'] ?? '') === '1';
if ($debug_mode) {
    @ini_set('display_errors', '1');
    @ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

header('Content-Type: application/json; charset=utf-8');

function momo_json_response(int $code, array $payload): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

$secret = (string)($_GET['secret'] ?? '');
$momoCallbackSecret = xander_momo_callback_secret();
$hasValidSecret = ($momoCallbackSecret !== '' && $secret !== '' && hash_equals($momoCallbackSecret, $secret));

function stmt_fetch_assoc(mysqli_stmt $stmt): ?array
{
    $meta = $stmt->result_metadata();
    if (!$meta) {
        return null;
    }

    $row = [];
    $params = [];
    while ($field = $meta->fetch_field()) {
        $row[$field->name] = null;
        $params[] = &$row[$field->name];
    }

    if ($params) {
        call_user_func_array([$stmt, 'bind_result'], $params);
    }

    if ($stmt->fetch()) {
        return $row;
    }

    return null;
}

function momo_log(string $message, $data = null): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if ($data !== null) {
        $line .= ' :: ' . (is_array($data) || is_object($data) ? json_encode($data) : (string)$data);
    }
    @file_put_contents(__DIR__ . '/momo_callback.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// PHPMailer autoload (optional)
$phpmailer_available =
    file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')
    && file_exists(__DIR__ . '/PHPMailer/src/SMTP.php')
    && file_exists(__DIR__ . '/PHPMailer/src/Exception.php');

if ($phpmailer_available) {
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
}

function sendMomoReceiptEmail(string $toEmail, string $toName, int $amountRwf, string $reference, string $status): bool
{
    global $phpmailer_available;

    $toEmail = trim($toEmail);
    $toName = trim($toName);

    if ($toEmail === '' || !$phpmailer_available) {
        return false;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'xanderglobalscholars.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admission@xanderglobalscholars.com';
        $mail->Password   = 'Xander@2026';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->setFrom('admission@xanderglobalscholars.com', 'Xander Global Scholars');
        $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);

        $safeName = htmlspecialchars($toName !== '' ? $toName : $toEmail);
        $safeRef = htmlspecialchars($reference);
        $safeStatus = htmlspecialchars($status);
        $safeAmount = number_format((float)$amountRwf, 0, '.', ',');
        $safeDate = htmlspecialchars(date('F j, Y, g:i a'));

        $logoDataUri = '';
        $logoPath = __DIR__ . '/logo.png';
        if (is_file($logoPath)) {
            $logoBin = @file_get_contents($logoPath);
            if (is_string($logoBin) && $logoBin !== '') {
                $logoDataUri = 'data:image/png;base64,' . base64_encode($logoBin);
            }
        }

        $logoUrl = 'https://xanderglobalscholars.com/logo.png';

        $qrData = 'https://xanderglobalscholars.com | Receipt Ref: ' . (string)$reference;
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . rawurlencode($qrData);
        $qrDataUri = '';
        $qrBin = @file_get_contents($qrUrl);
        if (is_string($qrBin) && $qrBin !== '') {
            $qrDataUri = 'data:image/png;base64,' . base64_encode($qrBin);
        }

        $socialHtml = '<div style="margin-top:12px;text-align:center;">
            <a href="https://facebook.com/xanderglobalscholars" style="display:inline-block;margin:0 6px;text-decoration:none;" target="_blank" rel="noopener">
                <span style="display:inline-block;width:26px;height:26px;line-height:26px;border-radius:999px;background:#1e3a5f;color:#ffffff;font-size:12px;font-weight:700;text-align:center;">f</span>
            </a>
            <a href="https://instagram.com/xanderglobalscholars" style="display:inline-block;margin:0 6px;text-decoration:none;" target="_blank" rel="noopener">
                <span style="display:inline-block;width:26px;height:26px;line-height:26px;border-radius:999px;background:#1e3a5f;color:#ffffff;font-size:11px;font-weight:700;text-align:center;">IG</span>
            </a>
            <a href="https://twitter.com/xanderglobal" style="display:inline-block;margin:0 6px;text-decoration:none;" target="_blank" rel="noopener">
                <span style="display:inline-block;width:26px;height:26px;line-height:26px;border-radius:999px;background:#1e3a5f;color:#ffffff;font-size:12px;font-weight:700;text-align:center;">X</span>
            </a>
            <a href="https://linkedin.com/company/xander-global-scholars" style="display:inline-block;margin:0 6px;text-decoration:none;" target="_blank" rel="noopener">
                <span style="display:inline-block;width:26px;height:26px;line-height:26px;border-radius:999px;background:#1e3a5f;color:#ffffff;font-size:11px;font-weight:700;text-align:center;">in</span>
            </a>
        </div>';

        $mail->Subject = 'Payment Successful – Mobile Money Receipt | Xander Global Scholars';

        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"><title>Payment Receipt</title></head>
        <body style="margin:0;padding:0;background:#f0f2f5;font-family:Arial,Helvetica,sans-serif;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f0f2f5;">
                <tr><td align="center" style="padding:28px 14px;">
                    <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,0.05);overflow:hidden;border:4px solid #1e3a5f;">
                        <tr>
                            <td style="background:linear-gradient(135deg,#1e3a5f 0%, #0f2542 100%);padding:22px 24px;text-align:center;">
                                <div style="margin-bottom:10px;">
                                    <img src="' . ($logoDataUri !== '' ? $logoDataUri : $logoUrl) . '" alt="Xander Global Scholars" style="height:98px;max-width:300px;width:auto;display:block;margin:0 auto;background:#ffffff;padding:8px 12px;border-radius:12px;">
                                </div>
                                <div style="color:#ffffff;font-weight:800;font-size:18px;letter-spacing:.2px;">Xander Global Scholars</div>
                                <div style="color:#dbeafe;font-size:12px;margin-top:6px;">Official Payment Receipt</div>
                                <div style="color:#dbeafe;font-size:12px;margin-top:6px;">https://xanderglobalscholars.com</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:28px 24px;color:#111827;">
                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin:0 0 16px 0;">
                                    <tr>
                                        <td style="padding:10px 14px;background:#f8fafc;font-weight:700;color:#1e3a5f;width:40%;border-bottom:1px solid #e5e7eb;">Merchant</td>
                                        <td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">Xander Global Scholars</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:10px 14px;background:#f8fafc;font-weight:700;color:#1e3a5f;width:40%;border-bottom:1px solid #e5e7eb;">Website</td>
                                        <td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">https://xanderglobalscholars.com</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:10px 14px;background:#f8fafc;font-weight:700;color:#1e3a5f;width:40%;">Support Email</td>
                                        <td style="padding:10px 14px;">info@xanderglobalscolars.com</td>
                                    </tr>
                                </table>

                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px 0;">
                                    <tr>
                                        <td style="vertical-align:top;">
                                            <p style="margin:0 0 10px;font-size:16px;">Hi <strong style="color:#1e3a5f;">' . $safeName . '</strong>,</p>
                                            <p style="margin:0 0 16px;font-size:15px;line-height:1.5;color:#374151;"><strong>Success!</strong> The Mobile Money payment you sent has arrived at its destination and has been recorded by Xander Global Scholars.</p>
                                        </td>
                                        <td style="width:190px;vertical-align:top;text-align:right;">
                                            <div style="font-size:12px;color:#1e3a5f;font-weight:800;margin-bottom:8px;">Scan QR</div>
                                            <img src="' . ($qrDataUri !== '' ? $qrDataUri : $qrUrl) . '" alt="Receipt QR" style="width:150px;height:150px;border:2px solid #1e3a5f;border-radius:10px;background:#ffffff;">
                                            <div style="font-size:10px;color:#6b7280;margin-top:6px;">Ref: ' . $safeRef . '</div>
                                        </td>
                                    </tr>
                                </table>

                                <div style="margin:16px 0 8px;font-size:14px;font-weight:700;color:#1e3a5f;">Transfer checklist</div>

                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin:18px 0;">
                                    <tr>
                                        <td style="padding:10px 14px;background:#f8fafc;font-weight:600;color:#1e3a5f;width:40%;border-bottom:1px solid #e5e7eb;">Your reference number</td>
                                        <td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">' . $safeRef . '</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:10px 14px;background:#f8fafc;font-weight:600;color:#1e3a5f;border-bottom:1px solid #e5e7eb;">You sent</td>
                                        <td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">RWF ' . $safeAmount . '</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:10px 14px;background:#f8fafc;font-weight:600;color:#1e3a5f;border-bottom:1px solid #e5e7eb;">Payment method</td>
                                        <td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">MTN Mobile Money</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:10px 14px;background:#f8fafc;font-weight:600;color:#1e3a5f;border-bottom:1px solid #e5e7eb;">Status</td>
                                        <td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">' . $safeStatus . '</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:10px 14px;background:#f8fafc;font-weight:600;color:#1e3a5f;">Date</td>
                                        <td style="padding:10px 14px;">' . $safeDate . '</td>
                                    </tr>
                                </table>

                                <p style="margin:0 0 14px;font-size:14px;color:#6b7280;line-height:1.5;">We’re always looking for ways to improve our service. If you have feedback, please reply to this email.</p>

                                <p style="margin:0;font-size:14px;color:#6b7280;line-height:1.5;">Got a question? Email us at <a href="mailto:info@xanderglobalscolars.com" style="color:#1e3a5f;">info@xanderglobalscolars.com</a>.</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:16px 24px;background:#f8fafc;text-align:center;color:#6b7280;font-size:12px;">
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

        $mail->AltBody = "Hi {$toName},\n\nSuccess! The Mobile Money payment you sent has arrived at its destination and has been recorded by Xander Global Scholars.\n\nTransfer checklist\nYour reference number: {$reference}\nYou sent: RWF {$safeAmount}\nPayment method: MTN Mobile Money\nStatus: {$status}\nDate: " . date('Y-m-d H:i') . "\n\nQuestions? support@xanderglobalscholars.com\n\nXander Global Scholars";

        if (function_exists('generateReceiptPdf')) {
            try {
                generateReceiptPdf((string)$mail->Body, (string)$reference);
                $pdfPath = __DIR__ . '/receipts/' . $reference . '.pdf';
                if (is_file($pdfPath)) {
                    $mail->addAttachment($pdfPath, 'Receipt-' . $reference . '.pdf');
                } else {
                    momo_log('Receipt PDF not generated. Install Dompdf (composer require dompdf/dompdf) to enable PDF receipts.', [
                        'expected_pdf' => $pdfPath,
                        'reference' => $reference,
                    ]);
                }
            } catch (\Throwable $e) {
                momo_log('MoMo receipt PDF attach failed: ' . $e->getMessage());
            }
        }

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        momo_log('MoMo receipt email failed: ' . $e->getMessage());
        return false;
    }
}

$rawInput = file_get_contents('php://input');
$payload = null;
if (is_string($rawInput) && trim($rawInput) !== '') {
    $payload = json_decode($rawInput, true);
}

// Fallback to POST form-data
if (!is_array($payload)) {
    $payload = $_POST;
}

momo_log('Callback received', $payload);

// Extract reference and status from different possible shapes
$reference = (string)($payload['req_ref'] ?? $payload['reference'] ?? ($payload['data']['req_ref'] ?? ''));
$status = (string)($payload['status'] ?? ($payload['data']['status'] ?? ($payload['data']['message'] ?? '')));

$reference = trim($reference);
$status = trim($status);

if ($reference === '') {
    momo_json_response(422, ['success' => false, 'message' => 'Missing reference']);
}

// If secret is missing/invalid, verify with ItecPay to prevent spoofed callbacks
if (!$hasValidSecret) {
    if (!function_exists('curl_init')) {
        momo_log('Callback verify failed: PHP cURL extension not enabled');
        momo_json_response(500, ['success' => false, 'message' => 'Server verification error']);
    }

    $verifyPayload = json_encode([
        'action' => 'status_check',
        'req_ref' => $reference,
        'key' => xander_payment_require_momo_key(),
    ]);

    $verifyResp = '';
    $verifyErr = '';
    $verifyHttp = '';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => xander_momo_verify_api_url(),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $verifyPayload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);
    $verifyResp = (string)curl_exec($ch);
    $verifyErr = (string)curl_error($ch);
    $verifyHttp = (string)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($verifyErr !== '') {
        momo_log('Callback verify curl error', ['err' => $verifyErr, 'http' => $verifyHttp, 'ref' => $reference]);
        momo_json_response(500, ['success' => false, 'message' => 'Verification failed']);
    }

    $verifyData = json_decode($verifyResp, true);
    $providerStatus = strtoupper(trim((string)($verifyData['data']['status'] ?? '')));
    momo_log('Callback verify response', ['http' => $verifyHttp, 'status' => $providerStatus, 'ref' => $reference]);

    if ($providerStatus === '') {
        momo_json_response(400, ['success' => false, 'message' => 'Invalid verify response']);
    }

    // Override status based on authoritative verify response
    $status = $providerStatus;
}

// Decide paid vs not paid (best-effort, since provider formats can differ)
$statusLower = strtolower($status);
$isPaid = in_array($statusLower, ['paid', 'success', 'successful', 'completed', 'complete', '200'], true);

// Map provider status to DB enum values: pending/completed/failed/cancelled
$mappedStatus = 'pending';
if ($isPaid) {
    $mappedStatus = 'completed';
} elseif (str_contains($statusLower, 'cancel')) {
    $mappedStatus = 'cancelled';
} elseif (str_contains($statusLower, 'pend') || str_contains($statusLower, 'process') || str_contains($statusLower, 'wait')) {
    $mappedStatus = 'pending';
} else {
    $mappedStatus = 'failed';
}

try {
    // Load payment record
    $stmt = $conn->prepare("SELECT full_name, email, amount, transaction_status FROM payments WHERE momo_reference = ? OR transaction_id = ? LIMIT 1");
    $stmt->bind_param('ss', $reference, $reference);
    $stmt->execute();
    $payment = stmt_fetch_assoc($stmt);
    $stmt->close();

    if (!$payment) {
        momo_log('Payment not found for reference', $reference);
        momo_json_response(404, ['success' => false, 'message' => 'Payment not found']);
    }

    $previousStatus = strtolower((string)($payment['transaction_status'] ?? 'pending'));

    $stmt = $conn->prepare("UPDATE payments SET transaction_status = ? WHERE momo_reference = ? OR transaction_id = ?");
    $stmt->bind_param('sss', $mappedStatus, $reference, $reference);
    $stmt->execute();
    $stmt->close();

    // Send receipt email only when transitioned to completed
    $emailSent = false;
    if ($mappedStatus === 'completed' && $previousStatus !== 'completed') {
        $fullName = (string)($payment['full_name'] ?? '');
        $email = (string)($payment['email'] ?? '');
        $amount = (int)round((float)($payment['amount'] ?? 0));
        $emailSent = sendMomoReceiptEmail($email, $fullName, $amount, $reference, 'COMPLETED');
    }

    echo json_encode([
        'success' => true,
        'reference' => $reference,
        'transaction_status' => $mappedStatus,
        'email_sent' => $emailSent,
    ]);
} catch (\Throwable $e) {
    momo_log('Callback handler error: ' . $e->getMessage());
    momo_json_response(500, ['success' => false, 'message' => 'Server error']);
}
