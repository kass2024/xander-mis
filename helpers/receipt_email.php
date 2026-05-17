<?php
declare(strict_types=1);

require_once __DIR__ . '/mail_smtp.php';

/**
 * @return array{first_name: string, last_name: string, email: string}|null
 */
function xander_receipt_lookup_student(mysqli $conn, int $applicationId, string $sourceTable): ?array
{
    $sql = match ($sourceTable) {
        'malta_applications'  => 'SELECT name AS first_name, surname AS last_name, email FROM malta_applications WHERE id = ? LIMIT 1',
        'turkey_applications' => 'SELECT first_name, last_name, email FROM turkey_applications WHERE id = ? LIMIT 1',
        default               => 'SELECT first_name, last_name, email FROM student_applications WHERE id = ? LIMIT 1',
    };

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $applicationId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || trim((string) ($row['email'] ?? '')) === '') {
        return null;
    }

    return $row;
}

/**
 * Email the receipt PDF to the student. Returns ['ok' => true] or ['ok' => false, 'reason' => string].
 *
 * @param callable(string, mixed): void|null $log
 */
function xander_send_receipt_email(mysqli $conn, string $receiptNo, ?callable $log = null): array
{
    $logFn = $log ?? static function (): void {
    };

    $receiptNo = trim($receiptNo);
    if ($receiptNo === '') {
        $logFn('empty receipt_no');
        return ['ok' => false, 'reason' => 'receipt_no'];
    }

    $stmt = $conn->prepare(
        'SELECT r.receipt_no, r.application_id, r.source_table, r.total_amount, r.payment_method
         FROM payment_receipts r
         WHERE TRIM(r.receipt_no) = ?
         LIMIT 1'
    );
    if (!$stmt) {
        $logFn('sql prepare failed', $conn->error);
        return ['ok' => false, 'reason' => 'sql_prepare'];
    }

    $stmt->bind_param('s', $receiptNo);
    $stmt->execute();
    $receipt = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$receipt) {
        $logFn('receipt not found', $receiptNo);
        return ['ok' => false, 'reason' => 'not_found'];
    }

    $student = xander_receipt_lookup_student(
        $conn,
        (int) $receipt['application_id'],
        (string) ($receipt['source_table'] ?? 'student_applications')
    );

    if ($student === null) {
        $logFn('student email not found', $receipt['application_id'] ?? null);
        return ['ok' => false, 'reason' => 'email_missing'];
    }

    $pdfPath = dirname(__DIR__) . '/receipts/' . $receiptNo . '.pdf';
    if (!is_file($pdfPath)) {
        $logFn('pdf missing', $pdfPath);
        return ['ok' => false, 'reason' => 'pdf_missing'];
    }

    $studentName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
    $amount      = number_format((float) ($receipt['total_amount'] ?? 0), 2);
    $method      = htmlspecialchars((string) ($receipt['payment_method'] ?? ''), ENT_QUOTES, 'UTF-8');
    $safeName    = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');
    $safeReceipt = htmlspecialchars($receiptNo, ENT_QUOTES, 'UTF-8');

    try {
        $mail = xander_create_phpmailer_applicant_sender();
        $mail->isHTML(true);
        $mail->addAddress((string) $student['email'], $studentName);
        $mail->Subject = 'Payment Receipt – ' . $receiptNo;
        $mail->Body    = '
            <div style="font-family:Arial,sans-serif;color:#1e293b;max-width:560px;">
                <p>Dear <strong>' . $safeName . '</strong>,</p>
                <p>Thank you for your payment. Your official receipt is attached as a PDF.</p>
                <table cellpadding="8" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:8px;margin:16px 0;">
                    <tr><td style="color:#012F6B;font-weight:bold;">Receipt</td><td>' . $safeReceipt . '</td></tr>
                    <tr><td style="color:#012F6B;font-weight:bold;">Amount</td><td>' . $amount . '</td></tr>
                    <tr><td style="color:#012F6B;font-weight:bold;">Method</td><td>' . $method . '</td></tr>
                </table>
                <p style="color:#64748b;font-size:13px;">Please keep this receipt for your records.</p>
                <p>Xander Global Scholars — Finance Office</p>
            </div>';
        $mail->addAttachment($pdfPath, $receiptNo . '.pdf');
        $mail->send();

        $logFn('email sent', $student['email']);
        return ['ok' => true];
    } catch (Throwable $e) {
        $logFn('mail exception', $e->getMessage());
        return ['ok' => false, 'reason' => 'mail'];
    }
}
