<?php
/***********************
 * CONFIGURATION
 ***********************/
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/payment_config.php';
require_once __DIR__ . '/helpers/mailer.php';

if (file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php') && file_exists(__DIR__ . '/PHPMailer/src/SMTP.php') && file_exists(__DIR__ . '/PHPMailer/src/Exception.php')) {
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
}

if (file_exists(__DIR__ . '/generateReceiptPdf.php')) {
    require_once __DIR__ . '/generateReceiptPdf.php';
}

function fileToDataUri(string $path): string
{
    if (!is_file($path)) {
        return '';
    }
    $data = @file_get_contents($path);
    if ($data === false) {
        return '';
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = 'application/octet-stream';
    if ($ext === 'png') {
        $mime = 'image/png';
    } elseif ($ext === 'jpg' || $ext === 'jpeg') {
        $mime = 'image/jpeg';
    } elseif ($ext === 'gif') {
        $mime = 'image/gif';
    } elseif ($ext === 'svg') {
        $mime = 'image/svg+xml';
    }
    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

function urlToDataUri(string $url, string $mime = 'image/png'): string
{
    if ($url === '') {
        return '';
    }
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 8,
            'follow_location' => 1,
            'user_agent' => 'XanderReceiptBot/1.0'
        ],
        'https' => [
            'timeout' => 8,
            'follow_location' => 1,
            'user_agent' => 'XanderReceiptBot/1.0'
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false) {
        return '';
    }
    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

function buildStripeReceiptHtml(array $p): string
{
    $studentName = htmlspecialchars((string)($p['student_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $studentEmail = htmlspecialchars((string)($p['student_email'] ?? ''), ENT_QUOTES, 'UTF-8');
    $studentPhone = htmlspecialchars((string)($p['student_phone'] ?? ''), ENT_QUOTES, 'UTF-8');
    $amount = htmlspecialchars(number_format((float)($p['amount'] ?? 0), 2, '.', ''), ENT_QUOTES, 'UTF-8');
    $currency = htmlspecialchars((string)($p['currency'] ?? 'EUR'), ENT_QUOTES, 'UTF-8');
    $transactionId = htmlspecialchars((string)($p['transaction_id'] ?? ''), ENT_QUOTES, 'UTF-8');
    $receiptNo = htmlspecialchars((string)($p['receipt_no'] ?? ''), ENT_QUOTES, 'UTF-8');
    $dateStr = htmlspecialchars((string)($p['date'] ?? date('Y-m-d H:i')), ENT_QUOTES, 'UTF-8');
    $packageTitle = htmlspecialchars((string)($p['package_title'] ?? ''), ENT_QUOTES, 'UTF-8');

    $logoPath = __DIR__ . '/logo.png';
    $logoDataUri = fileToDataUri($logoPath);
    $logoImg = $logoDataUri !== ''
        ? '<img src="' . $logoDataUri . '" alt="Xander Global Scholars" style="height:82px;" />'
        : '<div style="font-weight:800;font-size:18px;color:#0f2542;">Xander Global Scholars</div>';

    $verifyUrl = 'https://xanderglobalscholars.com/?payment=receipt&tx=' . rawurlencode((string)$transactionId) . '&receipt=' . rawurlencode((string)$receiptNo);
    $qrValue = $verifyUrl . "\nTX: " . (string)$transactionId . "\nReceipt: " . (string)$receiptNo;
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode($qrValue);
    $qrDataUri = urlToDataUri($qrUrl, 'image/png');
    $qrSrc = $qrDataUri !== '' ? $qrDataUri : $qrUrl;

    $fbIcon = urlToDataUri('https://www.google.com/s2/favicons?sz=64&domain_url=https://facebook.com', 'image/png');
    $igIcon = urlToDataUri('https://www.google.com/s2/favicons?sz=64&domain_url=https://instagram.com', 'image/png');
    $xIcon  = urlToDataUri('https://www.google.com/s2/favicons?sz=64&domain_url=https://twitter.com', 'image/png');
    $inIcon = urlToDataUri('https://www.google.com/s2/favicons?sz=64&domain_url=https://linkedin.com', 'image/png');

    $socialHtml = '<div class="social-icons">
        <a href="https://facebook.com/xanderglobalscholars" target="_blank" rel="noopener" title="Facebook"><img alt="Facebook" src="' . htmlspecialchars($fbIcon, ENT_QUOTES, 'UTF-8') . '" /></a>
        <a href="https://instagram.com/xanderglobalscholars" target="_blank" rel="noopener" title="Instagram"><img alt="Instagram" src="' . htmlspecialchars($igIcon, ENT_QUOTES, 'UTF-8') . '" /></a>
        <a href="https://twitter.com/xanderglobal" target="_blank" rel="noopener" title="X"><img alt="X" src="' . htmlspecialchars($xIcon, ENT_QUOTES, 'UTF-8') . '" /></a>
        <a href="https://linkedin.com/company/xander-global-scholars" target="_blank" rel="noopener" title="LinkedIn"><img alt="LinkedIn" src="' . htmlspecialchars($inIcon, ENT_QUOTES, 'UTF-8') . '" /></a>
    </div>';

    return '<!DOCTYPE html><html><head><meta charset="UTF-8">
        <style>
            @page { margin: 22mm 16mm 22mm 16mm; }
            body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #111827; font-size: 12px; }
            a { color: #0f2542; text-decoration: underline; font-weight: 700; }
            .wrap { border: 2px solid #0f2542; border-radius: 14px; overflow: hidden; }
            .accent { height: 6px; background: #ff8c42; }
            .header { background: #0f2542; color: #fff; padding: 18px 18px; }
            .header-inner { display: table; width: 100%; }
            .header-left, .header-right { display: table-cell; vertical-align: middle; }
            .header-right { text-align: right; font-size: 12px; }
            .logo-card { display: inline-block; background: #ffffff; border-radius: 14px; padding: 10px 14px; border: 1px solid rgba(255,255,255,0.35); }
            .content { padding: 18px; }
            .title { font-size: 18px; font-weight: 800; margin: 0 0 4px; }
            .sub { color: #6b7280; margin: 0 0 14px; }
            .grid { display: table; width: 100%; margin-top: 10px; }
            .col { display: table-cell; vertical-align: top; }
            .box { border: 1px solid #cbd5e1; border-left: 4px solid #ff8c42; border-radius: 12px; padding: 12px; }
            .label { color: #6b7280; font-size: 11px; margin-bottom: 4px; }
            .value { font-weight: 700; font-size: 12px; }
            table.meta { width: 100%; border-collapse: collapse; margin-top: 12px; }
            table.meta td { padding: 8px 0; border-bottom: 1px dashed #e5e7eb; }
            table.meta td:first-child { color: #6b7280; width: 42%; }
            .total { font-size: 16px; font-weight: 800; color: #0f2542; }
            .footer { margin-top: 18px; padding-top: 14px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 10px; }
            .social-icons { margin-top: 8px; }
            .social-icons a { text-decoration: none; margin-right: 8px; }
            .social-icons img { width: 18px; height: 18px; border-radius: 999px; border: 1px solid #e5e7eb; }
            .qr-card { display:inline-block;border:1px solid #cbd5e1;border-radius:12px;padding:10px;background:#ffffff; }
            .qr-card img { border: 1px solid #e5e7eb; border-radius: 12px; }
        </style>
        </head><body>
        <div class="wrap">
            <div class="accent"></div>
            <div class="header">
                <div class="header-inner">
                    <div class="header-left"><div class="logo-card">' . $logoImg . '</div></div>
                    <div class="header-right">
                        <div style="font-weight:800;">Payment Receipt</div>
                        <div>Receipt No: ' . $receiptNo . '</div>
                        <div>Date: ' . $dateStr . '</div>
                    </div>
                </div>
            </div>
            <div class="content">
                <div class="title">Thank you for your payment</div>
                <div class="sub">This receipt confirms a successful Stripe payment to Xander Global Scholars.</div>

                <div class="grid" style="margin-bottom:12px;">
                    <div class="col" style="width: 100%;">
                        <div class="box">
                            <div class="label">Billed To</div>
                            <div class="value">' . ($studentName !== '' ? $studentName : '-') . '</div>
                            <div style="margin-top:6px;color:#374151;">' . ($studentEmail !== '' ? $studentEmail : '-') . '</div>
                            <div style="margin-top:3px;color:#374151;">' . ($studentPhone !== '' ? $studentPhone : '-') . '</div>
                        </div>
                    </div>
                </div>

                <div class="box">
                    <div class="label">Payment Details</div>
                    <table class="meta">
                        <tr><td>Transaction ID</td><td class="value">' . ($transactionId !== '' ? $transactionId : '-') . '</td></tr>
                        <tr><td>Payment Method</td><td class="value">Stripe (Card)</td></tr>
                        <tr><td>Description</td><td class="value">' . ($packageTitle !== '' ? $packageTitle : 'Fee Payment') . '</td></tr>
                        <tr><td>Amount Paid</td><td class="total">' . $currency . ' ' . $amount . '</td></tr>
                        <tr><td>Status</td><td class="value">Completed</td></tr>
                    </table>
                </div>

                <div class="footer">
                    <div style="display:table;width:100%;">
                        <div style="display:table-cell;vertical-align:top;">
                            <div style="font-weight:900;color:#111827;font-size:11px;">Xander Global Scholars</div>
                            <div style="margin-top:4px;">Website: xanderglobalscholars.com</div>
                            <div>Admissions: admissions@xanderglobalscholars.com</div>
                            <div style="margin-top:8px;font-weight:800;color:#0f2542;font-size:11px;">Connect with us</div>
                            ' . $socialHtml . '
                            <div style="margin-top:10px;">This receipt is generated electronically and is valid without a signature.</div>
                        </div>

                        <div style="display:table-cell;vertical-align:top;text-align:right;white-space:nowrap;">
                            <div class="qr-card">
                                <div style="color:#6b7280;font-size:11px;margin-bottom:6px;text-align:left;font-weight:800;">Scan to verify</div>
                                <img src="' . htmlspecialchars($qrSrc, ENT_QUOTES, 'UTF-8') . '" width="120" height="120" alt="QR" style="display:block;" />
                                <div style="margin-top:6px;color:#6b7280;font-size:9px;text-align:left;">TX: ' . ($transactionId !== '' ? $transactionId : '-') . '</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </body></html>';
}

function sendStripePaymentEmail(string $toEmail, string $toName, string $transactionId, float $amount, string $currency = 'EUR', string $studentPhone = '', string $packageTitle = ''): bool
{
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        return false;
    }

    try {
        $mail = app_mailer();
        $receiptNo = $transactionId !== '' ? $transactionId : ('RCP-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)));
        $receiptHtml = buildStripeReceiptHtml([
            'student_name' => $toName,
            'student_email' => $toEmail,
            'student_phone' => $studentPhone,
            'amount' => $amount,
            'currency' => $currency,
            'transaction_id' => $transactionId,
            'receipt_no' => $receiptNo,
            'date' => date('Y-m-d H:i'),
            'package_title' => $packageTitle,
        ]);

        if (function_exists('generateReceiptPdf')) {
            generateReceiptPdf($receiptHtml, $receiptNo);
        }

        $receiptPdfPath = __DIR__ . '/receipts/' . $receiptNo . '.pdf';
        if (!is_file($receiptPdfPath)) {
            logEmailMessage('Stripe receipt PDF missing (dompdf/vendor not available?)', ['receipt_no' => $receiptNo, 'path' => $receiptPdfPath]);
        }

        $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);

        $safeName = htmlspecialchars($toName !== '' ? $toName : $toEmail, ENT_QUOTES, 'UTF-8');
        $safeTx = htmlspecialchars($transactionId, ENT_QUOTES, 'UTF-8');
        $safeCur = htmlspecialchars($currency, ENT_QUOTES, 'UTF-8');
        $safeAmt = htmlspecialchars(number_format($amount, 2, '.', ''), ENT_QUOTES, 'UTF-8');

        if (is_file($receiptPdfPath)) {
            $mail->addAttachment($receiptPdfPath, 'Receipt-' . $receiptNo . '.pdf');
        }

        $mail->Subject = 'Payment Successful – Stripe Confirmation | Xander Global Scholars';

        $logoUrl = 'https://xanderglobalscholars.com/logo.png';
        $logoHtml = '<img src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="Xander Global Scholars" style="height:64px;display:block;" />';

        $fbIconUrl = 'https://www.google.com/s2/favicons?sz=64&domain_url=https://facebook.com';
        $igIconUrl = 'https://www.google.com/s2/favicons?sz=64&domain_url=https://instagram.com';
        $xIconUrl  = 'https://www.google.com/s2/favicons?sz=64&domain_url=https://twitter.com';
        $inIconUrl = 'https://www.google.com/s2/favicons?sz=64&domain_url=https://linkedin.com';

        $socialHtml = '<div style="margin-top:14px;text-align:center;">
            <a href="https://facebook.com/xanderglobalscholars" style="display:inline-block;margin:0 6px;text-decoration:none;" target="_blank" rel="noopener">
                <img src="' . htmlspecialchars($fbIconUrl, ENT_QUOTES, 'UTF-8') . '" alt="Facebook" width="28" height="28" style="display:inline-block;border-radius:999px;border:1px solid #e5e7eb;background:#ffffff;" />
            </a>
            <a href="https://instagram.com/xanderglobalscholars" style="display:inline-block;margin:0 6px;text-decoration:none;" target="_blank" rel="noopener">
                <img src="' . htmlspecialchars($igIconUrl, ENT_QUOTES, 'UTF-8') . '" alt="Instagram" width="28" height="28" style="display:inline-block;border-radius:999px;border:1px solid #e5e7eb;background:#ffffff;" />
            </a>
            <a href="https://twitter.com/xanderglobal" style="display:inline-block;margin:0 6px;text-decoration:none;" target="_blank" rel="noopener">
                <img src="' . htmlspecialchars($xIconUrl, ENT_QUOTES, 'UTF-8') . '" alt="X" width="28" height="28" style="display:inline-block;border-radius:999px;border:1px solid #e5e7eb;background:#ffffff;" />
            </a>
            <a href="https://linkedin.com/company/xander-global-scholars" style="display:inline-block;margin:0 6px;text-decoration:none;" target="_blank" rel="noopener">
                <img src="' . htmlspecialchars($inIconUrl, ENT_QUOTES, 'UTF-8') . '" alt="LinkedIn" width="28" height="28" style="display:inline-block;border-radius:999px;border:1px solid #e5e7eb;background:#ffffff;" />
            </a>
        </div>';

        $mail->Body = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:0;">
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
                                    <div style="font-weight:800;">Stripe Payment Confirmation</div>
                                    <div>Receipt No: ' . htmlspecialchars($receiptNo, ENT_QUOTES, 'UTF-8') . '</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div style="padding:22px;color:#111827;">
                        <p style="margin:0 0 12px;">Dear <strong>' . $safeName . '</strong>,</p>
                        <p style="margin:0 0 12px;">Thank you for completing your payment with <strong>Xander Global Scholars</strong>. This email is a confirmation that your payment has been received successfully.</p>

                        <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;margin:14px 0 14px;">
                            <table style="width:100%;border-collapse:collapse;">
                                <tr><td style="padding:7px 0;color:#6b7280;">Transaction ID</td><td style="padding:7px 0;color:#111827;font-weight:700;">' . $safeTx . '</td></tr>
                                <tr><td style="padding:7px 0;color:#6b7280;">Amount Paid</td><td style="padding:7px 0;color:#0f2542;font-weight:800;">' . $safeCur . ' ' . $safeAmt . '</td></tr>
                                <tr><td style="padding:7px 0;color:#6b7280;">Payment Date</td><td style="padding:7px 0;color:#111827;font-weight:700;">' . htmlspecialchars(date('Y-m-d H:i'), ENT_QUOTES, 'UTF-8') . '</td></tr>
                            </table>
                        </div>
                        <p style="margin:0 0 12px;">A professional receipt is attached to this email in PDF format. Please keep it for your records.</p>
                        <p style="margin:0 0 12px;color:#6b7280;font-size:13px;">If you did not authorize this transaction, please contact us immediately.</p>
                        <div style="border-top:1px solid #e5e7eb;margin-top:16px;padding-top:12px;color:#6b7280;font-size:12px;">
                            <div style="font-weight:800;color:#111827;">Xander Global Scholars</div>
                            <div>Email: admissions@xanderglobalscholars.com</div>
                            <div>Website: xanderglobalscholars.com</div>
                            <div style="margin-top:10px;font-weight:700;color:#0f2542;">Connect with us</div>
                            ' . $socialHtml . '
                        </div>
                    </div>
                </div>
                <div style="text-align:center;color:#94a3b8;font-size:11px;margin-top:12px;">This is an automated message. Please do not share your payment details with anyone.</div>
            </div>
        </body></html>';

        $mail->AltBody = "Dear {$toName},\n\nThank you for your payment to Xander Global Scholars.\n\nTransaction ID: {$transactionId}\nReceipt No: {$receiptNo}\nAmount Paid: {$currency} " . number_format($amount, 2, '.', '') . "\nPayment Date: " . date('Y-m-d H:i') . "\n" . ($packageTitle !== '' ? ("Description: {$packageTitle}\n") : "") . "\nA receipt is attached (PDF).\n\nXander Global Scholars\nEmail: admissions@xanderglobalscholars.com\nWebsite: xanderglobalscholars.com\n";

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        logEmailMessage('Stripe email send failed', ['to' => $toEmail, 'err' => $e->getMessage()]);
        return false;
    }
}

// Log file for email debugging (optional, kept for consistency)
define('EMAIL_LOG', __DIR__ . '/payment_email.log');

/**
 * Simple logging function
 */
function logEmailMessage($msg, $data = null) {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    if ($data !== null) {
        $line .= ' :: ' . (is_array($data) || is_object($data) ? json_encode($data) : $data);
    }
    $ok = @file_put_contents(EMAIL_LOG, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    if ($ok === false) {
        error_log($line);
    }
}

function stmt_fetch_assoc(mysqli_stmt $stmt) {
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

$__stripeKeys = xander_payment_require_stripe_keys();
$STRIPE_SECRET_KEY = $__stripeKeys['secret'];
$STRIPE_PUBLIC_KEY = $__stripeKeys['public'];

// Get payment parameters from URL or POST
$student_id = (int)($_GET['student_id'] ?? $_POST['student_id'] ?? 0);
$source_table = $_GET['table'] ?? $_POST['table'] ?? '';
$package_id = (int)($_GET['package_id'] ?? $_POST['package_id'] ?? 0);
$payment_method = $_GET['payment_method'] ?? $_POST['payment_method'] ?? 'stripe';
$items_param = $_GET['items'] ?? $_POST['items'] ?? '';
$currency = strtoupper(trim((string)($_GET['currency'] ?? $_POST['currency'] ?? 'EUR')));
$other_service_name = trim((string)($_GET['other_service_name'] ?? $_POST['other_service_name'] ?? ''));

// Parse items from JSON string
$items = [];
if ($items_param) {
    $items = json_decode($items_param, true) ?: [];
}

// Calculate total amount
$total_amount = 0;
foreach ($items as $item_id => $amount) {
    $total_amount += (float)$amount;
}

// Convert to cents for Stripe
$AMOUNT = (int)($total_amount * 100);

$isRecordRequest = (
    ($_SERVER['REQUEST_METHOD'] === 'POST')
    && isset($_GET['action'])
    && $_GET['action'] === 'record'
);

if (!$isRecordRequest && $AMOUNT <= 0) {
    die("Invalid payment amount. Please select at least one item to pay.");
}

// Get application details for description
$app_details = null;
if ($student_id > 0) {
    $stmt = $conn->prepare("SELECT first_name, last_name, email, phone_number FROM student_applications WHERE id = ?");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $app_details = stmt_fetch_assoc($stmt);
    $stmt->close();
}

// Get package details
$package_details = null;
if ($package_id > 0) {
    $stmt = $conn->prepare("SELECT title FROM fee_packages WHERE id = ?");
    $stmt->bind_param('i', $package_id);
    $stmt->execute();
    $package_details = stmt_fetch_assoc($stmt);
    $stmt->close();
}

$studentLabel = trim(($app_details['first_name'] ?? 'Student') . ' ' . ($app_details['last_name'] ?? ''));
if ($other_service_name === '' && is_array($items)) {
    $otherLabels = [];
    foreach (array_keys($items) as $itemKey) {
        $k = (string) $itemKey;
        if (str_starts_with($k, 'other:')) {
            $otherLabels[] = substr($k, 6);
        }
    }
    if (!empty($otherLabels)) {
        $other_service_name = implode(', ', $otherLabels);
    }
}
if ($package_id > 0 && !empty($package_details['title'])) {
    $description = 'Payment for ' . $package_details['title'] . ' - ' . $studentLabel;
} elseif ($other_service_name !== '') {
    $description = 'Payment: ' . $other_service_name . ' - ' . $studentLabel;
} else {
    $description = 'Custom payment - ' . $studentLabel;
}

// -------------------------------------------------------------------
// HANDLE PAYMENT RECORDING (POST request) – ONLY INSERT INTO payments TABLE
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'record') {
    header('Content-Type: application/json; charset=utf-8');
    
    $rawInput = file_get_contents('php://input');
    if ($rawInput === false || trim($rawInput) === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Empty request body']);
        exit;
    }
    
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
        exit;
    }
    
    // Sanitize input
    $applicationId = (int) ($data['student_id'] ?? 0);
    $sourceTable   = (string) ($data['table'] ?? $source_table);
    $sourceTable = trim($sourceTable);
    if ($sourceTable === '') {
        $sourceTable = 'student_applications';
    }
    $packageId     = (int) ($data['package_id'] ?? $package_id);
    $method        = trim((string) ($data['payment_method'] ?? 'stripe'));
    $comment       = trim((string) ($data['comment'] ?? ''));
    $items         = $data['items'] ?? [];
    $stripePaymentIntentId = trim((string)($data['stripe_payment_intent_id'] ?? ''));
    $currency      = strtoupper(trim((string) ($data['currency'] ?? ($_GET['currency'] ?? 'EUR'))));
    $otherServiceNameRecord = trim((string)($data['other_service_name'] ?? $other_service_name));
    
    // Validation
    $allowedTables = [
        'student_applications',
        'malta_applications',
        'turkey_applications'
    ];
    
    $isCustomOther = false;
    if ($packageId === 0 && is_array($items)) {
        foreach (array_keys($items) as $itemKey) {
            $k = (string) $itemKey;
            if ($k === 'other' || str_starts_with($k, 'other:')) {
                $isCustomOther = true;
                break;
            }
        }
    }
    if (
        $applicationId <= 0 ||
        ($packageId <= 0 && !$isCustomOther) ||
        $method === '' ||
        !in_array($sourceTable, $allowedTables, true) ||
        !is_array($items) ||
        empty($items)
    ) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid or missing required fields']);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Load student details for the payments table
        $studentStmt = $conn->prepare("SELECT first_name, last_name, email, phone_number FROM {$sourceTable} WHERE id = ? LIMIT 1");
        if (!$studentStmt) {
            throw new RuntimeException('Failed to prepare student query: ' . $conn->error);
        }
        $studentStmt->bind_param('i', $applicationId);
        $studentStmt->execute();
        $student = stmt_fetch_assoc($studentStmt);
        $studentStmt->close();

        if (!$student) {
            throw new RuntimeException('Student not found for application id ' . $applicationId);
        }

        // Calculate total amount from items
        $totalRecorded = 0.0;
        foreach ($items as $feeItemId => $amount) {
            $totalRecorded += round((float) $amount, 2);
        }
        
        // Prepare data for payments table
        $fullName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
        $email = trim($student['email'] ?? '');
        $phone = trim($student['phone_number'] ?? ''); // will be empty string if not set
        $amount = (float) $totalRecorded;
        $paymentMethod = 'stripe';
        $transactionStatus = 'completed';
        $transactionId = $stripePaymentIntentId ?: $comment; // fallback to comment if no intent id
        $networkProvider = 'other'; // because enum does not include 'stripe'
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Insert into payments table (must include phone_number)
        $ins = $conn->prepare(
            'INSERT INTO payments 
                (full_name, email, phone_number, amount, payment_method, transaction_status, transaction_id, network_provider, payment_date, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)'
        );
        if (!$ins) {
            throw new RuntimeException('Failed to prepare payments insert: ' . $conn->error);
        }
        
        $ins->bind_param('sssdssssss', 
            $fullName, 
            $email, 
            $phone, 
            $amount, 
            $paymentMethod, 
            $transactionStatus, 
            $transactionId, 
            $networkProvider, 
            $ip, 
            $ua
        );
        
        if (!$ins->execute()) {
            throw new RuntimeException('Failed to insert into payments table: ' . $ins->error);
        }
        $ins->close();
        
        // Commit transaction
        $conn->commit();

        $emailSent = false;
        if ($email !== '') {
            $pkgTitle = '';
            if ($packageId > 0) {
                $pkgStmt = $conn->prepare('SELECT title FROM fee_packages WHERE id = ? LIMIT 1');
                if ($pkgStmt) {
                    $pkgStmt->bind_param('i', $packageId);
                    $pkgStmt->execute();
                    $pkg = stmt_fetch_assoc($pkgStmt);
                    $pkgStmt->close();
                    if (is_array($pkg) && isset($pkg['title'])) {
                        $pkgTitle = (string)$pkg['title'];
                    }
                }
            } elseif ($otherServiceNameRecord !== '') {
                $pkgTitle = $otherServiceNameRecord;
            }

            $emailSent = sendStripePaymentEmail(
                $email,
                $fullName,
                (string)$transactionId,
                (float)$amount,
                (string)$currency,
                (string)$phone,
                (string)$pkgTitle
            );
        }
        
        // Return success
        echo json_encode([
            'success'     => true,
            'message'     => 'Payment recorded successfully in payments table',
            'transaction_id' => $transactionId,
            'amount'      => number_format($amount, 2, '.', ''),
            'email_sent'  => $emailSent
        ]);
        
    } catch (Throwable $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Payment recording failed',
            'error'   => $e->getMessage()
        ]);
    }
    exit;
}

/***********************
 * CREATE PAYMENT INTENT
 ***********************/
$data = http_build_query([
    "amount" => $AMOUNT,
    "currency" => strtolower($currency ?: 'eur'),
    "payment_method_types[]" => "card",
    "description" => $description,
    "metadata[student_id]" => $student_id,
    "metadata[source_table]" => $source_table,
    "metadata[package_id]" => $package_id,
    "metadata[payment_method]" => $payment_method
]);

$ch = curl_init("https://api.stripe.com/v1/payment_intents");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_USERPWD, $STRIPE_SECRET_KEY . ":");
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$host = (string)($_SERVER['HTTP_HOST'] ?? '');
$isLocalhost = (
    $host === 'localhost'
    || strpos($host, 'localhost:') === 0
    || $host === '127.0.0.1'
    || strpos($host, '127.0.0.1:') === 0
);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $isLocalhost ? false : true);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    die("Connection error: " . curl_error($ch));
}
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$paymentIntent = json_decode($response, true);

if (!isset($paymentIntent['client_secret'])) {
    $errMsg = '';
    if (is_array($paymentIntent) && isset($paymentIntent['error']['message'])) {
        $errMsg = (string)$paymentIntent['error']['message'];
    }
    error_log("Stripe API Error (HTTP {$httpCode}): " . $response);
    die("Stripe error (HTTP {$httpCode})" . ($errMsg !== '' ? (": " . $errMsg) : '') . ". Response: " . $response);
}

$clientSecret = $paymentIntent['client_secret'];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Secure Payment - Xander Global Scholars</title>
    <script src="https://js.stripe.com/v3/"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1e3a5f;
            --primary-dark: #0f2542;
            --accent: #ff8c42;
            --accent-dark: #e6732f;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        
        .payment-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .payment-card {
            background: var(--card);
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .payment-header h1 {
            color: var(--primary);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .payment-details {
            background: var(--bg);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        
        .detail-row:last-child {
            border-top: 2px solid var(--border);
            padding-top: 15px;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .card-element {
            border: 2px solid var(--border);
            padding: 16px;
            border-radius: 12px;
            background: white;
            transition: var(--transition);
        }
        
        .card-element:focus-within {
            border-color: var(--accent);
        }
        
        .payment-button {
            width: 100%;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            color: white;
            border: none;
            padding: 18px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .payment-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 140, 66, 0.3);
        }
        
        .payment-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .result {
            margin-top: 20px;
            padding: 16px;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
        }
        
        .result.success {
            background: #10b981;
            color: white;
        }
        
        .result.error {
            background: #ef4444;
            color: white;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link a:hover {
            color: var(--primary);
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .currency-note {
            text-align: center;
            color: var(--text-light);
            font-size: 0.9rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>

<div class="payment-container">
    <div class="payment-card">
        <div class="payment-header">
            <h1>Secure Payment</h1>
            <p>Complete your payment securely with Stripe</p>
            <div class="currency-note">
                <i class="fas fa-euro-sign"></i> All amounts are in Euros (EUR)
            </div>
        </div>
        
        <?php if ($app_details && $package_details): ?>
        <div class="payment-details">
            <div class="detail-row">
                <span>Student:</span>
                <strong><?= htmlspecialchars($app_details['first_name'] . ' ' . $app_details['last_name']) ?></strong>
            </div>
            <div class="detail-row">
                <span>Email:</span>
                <strong><?= htmlspecialchars($app_details['email']) ?></strong>
            </div>
            <div class="detail-row">
                <span>Package:</span>
                <strong><?= htmlspecialchars($package_details['title']) ?></strong>
            </div>
            <div class="detail-row">
                <span>Application ID:</span>
                <strong>#<?= $student_id ?></strong>
            </div>
            <div class="detail-row">
                <span>Total Amount:</span>
                <strong>€<?= number_format($total_amount, 2) ?> EUR</strong>
            </div>
        </div>
        <?php endif; ?>
        
        <form id="payment-form">
            <div id="card-element" class="card-element"></div>
            <button id="payBtn" class="payment-button" type="submit">
                <i class="fas fa-lock"></i>
                <span>Pay €<?= number_format($total_amount, 2) ?> EUR</span>
            </button>
        </form>
        
        <div id="result" class="result" style="display: none;"></div>
        
        <div class="back-link">
            <a href="payment.php">
                <i class="fas fa-arrow-left"></i> Back to Payment Portal
            </a>
        </div>
    </div>
</div>

<script>
const stripe = Stripe("<?= $STRIPE_PUBLIC_KEY ?>");
const clientSecret = "<?= $clientSecret ?>";
const elements = stripe.elements();
const card = elements.create("card", {
    style: {
        base: {
            fontSize: '16px',
            color: '#1e293b',
            '::placeholder': {
                color: '#64748b'
            }
        }
    }
});
card.mount("#card-element");

const paymentForm = document.getElementById("payment-form");
const payBtn = document.getElementById("payBtn");
const resultDiv = document.getElementById("result");

paymentForm.onsubmit = async (e) => {
    e.preventDefault();
    
    // Show loading state
    payBtn.disabled = true;
    payBtn.innerHTML = '<div class="loading"></div> Processing...';
    resultDiv.style.display = 'none';
    
    try {
        const { paymentIntent, error } = await stripe.confirmCardPayment(
            clientSecret,
            { 
                payment_method: { 
                    card: card,
                    billing_details: {
                        name: "<?= htmlspecialchars($app_details['first_name'] . ' ' . $app_details['last_name'] ?? '') ?>",
                        email: "<?= htmlspecialchars($app_details['email'] ?? '') ?>"
                    }
                } 
            }
        );

        if (error) {
            // If the payment intent is already succeeded, retrieve it and record
            if (error.code === 'payment_intent_unexpected_state') {
                const piResult = await stripe.retrievePaymentIntent(clientSecret);
                const pi = piResult && piResult.paymentIntent ? piResult.paymentIntent : null;
                if (pi && (pi.status === 'succeeded' || pi.status === 'processing')) {
                    // Record the payment in our database
                    const recordResult = await recordPayment(pi);

                    if (recordResult && recordResult.success) {
                        resultDiv.className = 'result success';
                        resultDiv.textContent = `✅ Payment successful . The Receipt was Sent on Your Email Please check!!. Transaction ID: ${pi.id}`;
                        resultDiv.style.display = 'block';
                        
                        setTimeout(() => {
                            window.location.href = 'payment.php?success=1&payment_id=' + encodeURIComponent(pi.id);
                        }, 3000);
                        return;
                    } else {
                        throw new Error(recordResult?.message || 'Failed to record payment');
                    }
                }
            }

            // If we get here, it's a real error
            throw error;
        }

        // Payment successful – record it
        const recordResult = await recordPayment(paymentIntent);
        
        if (recordResult && recordResult.success) {
            resultDiv.className = 'result success';
            resultDiv.textContent = `✅ Payment Successful. Receipt was sent on Your email Please Check your Email. Transaction ID: ${paymentIntent.id}`;
            resultDiv.style.display = 'block';
        } else {
            throw new Error(recordResult?.message || 'Payment recorded failed');
        }
        
        // Redirect after delay
        setTimeout(() => {
            window.location.href = 'payment.php?success=1&payment_id=' + encodeURIComponent(paymentIntent.id);
        }, 3000);
        
    } catch (error) {
        console.error('Stripe payment error:', error);
        let msg = '';

        if (error && typeof error === 'object') {
            if (typeof error.message === 'string' && error.message.trim() !== '') {
                msg = error.message;
            } else if (error.error && typeof error.error.message === 'string') {
                msg = error.error.message;
            }

            const code = (typeof error.code === 'string' && error.code) ? error.code : '';
            const decline = (typeof error.decline_code === 'string' && error.decline_code) ? error.decline_code : '';

            if (code || decline) {
                msg += (msg ? ' | ' : '') + (code ? ('code: ' + code) : '') + (code && decline ? ', ' : '') + (decline ? ('decline: ' + decline) : '');
            }

            if (!msg && error.payment_intent && error.payment_intent.last_payment_error && typeof error.payment_intent.last_payment_error.message === 'string') {
                msg = error.payment_intent.last_payment_error.message;
            }
        }

        if (!msg) {
            msg = 'A processing error occurred.';
        }

        resultDiv.className = 'result error';
        resultDiv.textContent = '❌ ' + msg;
        resultDiv.style.display = 'block';
        
        // Reset button
        payBtn.disabled = false;
        payBtn.innerHTML = `<i class="fas fa-lock"></i> <span>Pay €<?= number_format($total_amount, 2) ?> EUR</span>`;
    }
};

async function recordPayment(paymentIntent) {
    const items = <?= json_encode($items) ?>;
    
    const response = await fetch('stripe-payment.php?action=record', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            student_id: <?= $student_id ?>,
            table: <?= json_encode((string)$source_table) ?>,
            package_id: <?= $package_id ?>,
            payment_method: 'stripe',
            comment: `Stripe Payment ID: ${paymentIntent.id}`,
            items: items,
            stripe_payment_intent_id: paymentIntent.id,
            amount: <?= $total_amount ?>,
            currency: '<?= $currency ?>',
            other_service_name: <?= json_encode($other_service_name) ?>
        })
    });
    
    const text = await response.text();
    let json = null;
    try { json = JSON.parse(text); } catch (e) { json = null; }

    if (json === null) {
        throw new Error('Server returned invalid JSON while recording payment: ' + text);
    }

    if (!response.ok) {
        const msg = (json && (json.message || json.error)) ? (json.message || json.error) : text;
        throw new Error(msg);
    }
    return json;
}
</script>

</body>
</html>