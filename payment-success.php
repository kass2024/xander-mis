<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/payment_config.php';
require_once __DIR__ . '/helpers/mailer.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// -------------------------------------------------------------------
// Helper function to send receipt email
// -------------------------------------------------------------------
function sendPaymentReceipt($paymentRecord, $student, $package, $items) {
    global $conn;
    try {
        $mail = app_mailer();

        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->setFrom('admission@xanderglobalscholars.com', 'Xander Global Scholars');
        $mail->addAddress($student['email'], trim($student['first_name'] . ' ' . $student['last_name']));

        $mail->Subject = 'Your Payment Receipt – Xander Global Scholars';

        // Build items list HTML
        $itemsHtml = '';
        foreach ($items as $itemId => $amount) {
            // Fetch item title from fee_items table (you may need to adjust)
            if (!isset($conn) || !($conn instanceof mysqli)) {
                throw new Exception('Database connection not available for receipt email.');
            }

            $itemQuery = $conn->prepare("SELECT title, code FROM fee_items WHERE id = ?");
            $itemQuery->bind_param('i', $itemId);
            $itemQuery->execute();
            $itemRes = $itemQuery->get_result()->fetch_assoc();
            $itemTitle = $itemRes ? $itemRes['title'] : 'Item #' . $itemId;
            $itemCode  = $itemRes ? $itemRes['code'] : '';
            $itemQuery->close();

            $itemsHtml .= '
            <tr>
                <td style="padding:10px; border-bottom:1px solid #e9ecef;">' . htmlspecialchars($itemTitle) . '<br><small style="color:#777;">Code: ' . htmlspecialchars($itemCode) . '</small></td>
                <td style="padding:10px; border-bottom:1px solid #e9ecef; text-align:right;">' . $paymentRecord['currency'] . ' ' . number_format($amount, 2) . '</td>
            </tr>';
        }

        $studentName = htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
        $paymentDate = date('F j, Y, g:i a', strtotime($paymentRecord['completed_at'] ?? $paymentRecord['created_at']));
        $total       = number_format($paymentRecord['total_amount'], 2);
        $currency    = $paymentRecord['currency'];

        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"><title>Payment Receipt</title></head>
        <body style="margin:0; padding:0; background-color:#f0f2f5; font-family: Arial, Helvetica, sans-serif;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0f2f5; width:100%;">
                <tr><td align="center" style="padding:30px 15px;">
                    <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,0.05);">
                        <!-- Header with Logo -->
                        <tr>
                            <td style="background: linear-gradient(135deg, #1e3a5f 0%, #0f2542 100%); padding:25px 30px; text-align:center; border-radius:12px 12px 0 0;">
                                <img src="https://xanderglobalscholars.com/logo.jpg" alt="Xander Global Scholars" style="max-width:200px; height:auto; display:block; margin:0 auto;">
                            </td>
                        </tr>
                        <!-- Main Content -->
                        <tr>
                            <td style="padding:35px 30px;">
                                <p style="font-size:16px; color:#333; margin:0 0 10px;">Dear <strong style="color:#1e3a5f;">' . $studentName . '</strong>,</p>
                                <p style="font-size:16px; color:#555; margin:0 0 25px;">Thank you for your payment. Your transaction has been completed successfully.</p>

                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0; border:1px solid #e9ecef; border-radius:8px;">
                                    <tr><td colspan="2" style="background-color:#f8fafc; padding:12px 20px; font-weight:600; color:#1e3a5f;">Payment Details</td></tr>
                                    <tr><td style="padding:12px 20px; border-top:1px solid #e9ecef;">Receipt No.</td><td style="padding:12px 20px; border-top:1px solid #e9ecef; font-weight:600;">' . $paymentRecord['id'] . '</td></tr>
                                    <tr><td style="padding:12px 20px; border-top:1px solid #e9ecef;">Date</td><td style="padding:12px 20px; border-top:1px solid #e9ecef;">' . $paymentDate . '</td></tr>
                                    <tr><td style="padding:12px 20px; border-top:1px solid #e9ecef;">Payment Method</td><td style="padding:12px 20px; border-top:1px solid #e9ecef;">' . ucfirst($paymentRecord['payment_method']) . '</td></tr>
                                    <tr><td style="padding:12px 20px; border-top:1px solid #e9ecef;">Package</td><td style="padding:12px 20px; border-top:1px solid #e9ecef;">' . htmlspecialchars($package['title']) . '</td></tr>
                                </table>

                                <h4 style="color:#1e3a5f; margin:30px 0 15px;">Items Paid</h4>
                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                                    <thead>
                                        <tr style="background-color:#f8fafc;">
                                            <th style="padding:12px 20px; text-align:left; border-bottom:2px solid #1e3a5f;">Description</th>
                                            <th style="padding:12px 20px; text-align:right; border-bottom:2px solid #1e3a5f;">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ' . $itemsHtml . '
                                        <tr>
                                            <td style="padding:15px 20px; text-align:right; font-weight:600;">Total Paid</td>
                                            <td style="padding:15px 20px; text-align:right; font-weight:700; color:#1e3a5f;">' . $currency . ' ' . $total . '</td>
                                        </tr>
                                    </tbody>
                                </table>

                                <p style="font-size:15px; color:#555; margin:30px 0 0;">If you have any questions, contact our support team at <a href="mailto:support@xanderglobalscholars.com" style="color:#1e3a5f;">support@xanderglobalscholars.com</a>.</p>
                                <p style="font-size:15px; color:#555; margin:20px 0 0;">Kind regards,<br><strong style="color:#1e3a5f;">Xander Global Scholars</strong></p>
                            </td>
                        </tr>
                        <!-- Footer with Social Media -->
                        <tr>
                            <td style="background-color:#f8fafc; padding:25px 30px; text-align:center; border-top:1px solid #e9ecef; border-radius:0 0 12px 12px;">
                                <p style="margin:0 0 15px; font-size:14px; color:#1e3a5f; font-weight:600; text-transform:uppercase;">Connect with us</p>
                                <div style="margin-bottom:10px;">
                                    <a href="https://facebook.com/xanderglobalscholars" style="color:#1e3a5f; text-decoration:none; margin:0 10px;">Facebook</a> |
                                    <a href="https://twitter.com/xanderglobal" style="color:#1e3a5f; text-decoration:none; margin:0 10px;">X (Twitter)</a> |
                                    <a href="https://linkedin.com/company/xander-global-scholars" style="color:#1e3a5f; text-decoration:none; margin:0 10px;">LinkedIn</a> |
                                    <a href="https://instagram.com/xanderglobalscholars" style="color:#1e3a5f; text-decoration:none; margin:0 10px;">Instagram</a>
                                </div>
                                <p style="margin:15px 0 0; font-size:12px; color:#777;">&copy; 2025 Xander Global Scholars. All rights reserved.</p>
                            </td>
                        </tr>
                    </table>
                </td></tr>
            </table>
        </body>
        </html>';

        // Plain text alternative
        $mail->AltBody = "Dear $studentName,\n\n"
                       . "Thank you for your payment. Receipt #{$paymentRecord['id']}\n"
                       . "Date: $paymentDate\n"
                       . "Total: $currency $total\n\n"
                       . "Items paid:\n" . print_r($items, true) . "\n\n"
                       . "Contact support@xanderglobalscholars.com if you have any questions.\n\n"
                       . "Kind regards,\nXander Global Scholars";

        $mail->send();
        return true;
    } catch (Exception $e) {
        $paymentId = is_array($paymentRecord) && isset($paymentRecord['id']) ? (string)$paymentRecord['id'] : 'unknown';
        $errInfo = isset($mail) ? (string)$mail->ErrorInfo : '';
        error_log("Receipt email failed for payment {$paymentId}: " . $e->getMessage() . ($errInfo !== '' ? ' | ' . $errInfo : ''));
        return false;
    }
}

// -------------------------------------------------------------------
// Main success handler
// -------------------------------------------------------------------
$payment_intent_id = $_GET['payment_intent'] ?? $_POST['payment_intent_id'] ?? '';

if (!$payment_intent_id) {
    die('Missing payment intent ID');
}

// 1. Retrieve payment record from database using stripe_payment_intent_id
$stmt = $conn->prepare("SELECT * FROM payments WHERE stripe_payment_intent_id = ?");
$stmt->bind_param('s', $payment_intent_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    die('Payment record not found');
}

// 2. Verify with Stripe that payment succeeded (optional but recommended)
$STRIPE_SECRET_KEY = xander_payment_require_stripe_keys()['secret'];

$ch = curl_init("https://api.stripe.com/v1/payment_intents/$payment_intent_id");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $STRIPE_SECRET_KEY . ':');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    die('Failed to verify payment with Stripe');
}

$intent = json_decode($response, true);
if ($intent['status'] !== 'succeeded') {
    die('Payment not successful');
}

// 3. Update payment record to completed
$update = $conn->prepare("UPDATE payments SET status = 'completed', completed_at = NOW() WHERE id = ?");
$update->bind_param('i', $payment['id']);
$update->execute();
$update->close();

// 4. Fetch student and package details
$student_stmt = $conn->prepare("SELECT first_name, last_name, email FROM student_applications WHERE id = ?");
$student_stmt->bind_param('i', $payment['student_id']);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();
$student_stmt->close();

$package_stmt = $conn->prepare("SELECT title FROM fee_packages WHERE id = ?");
$package_stmt->bind_param('i', $payment['package_id']);
$package_stmt->execute();
$package = $package_stmt->get_result()->fetch_assoc();
$package_stmt->close();

// 5. Decode items data
$items = json_decode($payment['items_data'], true);

// 6. Send receipt email
$emailSent = sendPaymentReceipt($payment, $student, $package, $items);

// 7. Redirect or show success message to user
if ($emailSent) {
    header("Location: payment-success-page.php?receipt_sent=1&payment_id=" . $payment['id']);
} else {
    header("Location: payment-success-page.php?receipt_error=1&payment_id=" . $payment['id']);
}
exit;