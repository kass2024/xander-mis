<?php
declare(strict_types=1);

require_once __DIR__ . '/student_portal_accounts.php';
require_once __DIR__ . '/urls.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/../includes/company_branding.php';

/**
 * Create/refresh portal account and email login details (study, job, or other applicants).
 */
function xander_send_student_portal_access_email(
    mysqli $conn,
    string $email,
    string $studentName = '',
    string $introHtml = ''
): bool {
    $email = pcvc_student_email_norm($email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $studentName = trim($studentName) !== '' ? trim($studentName) : 'Applicant';

    try {
        pcvc_student_portal_ensure_account_for_email($conn, $email);
    } catch (Throwable $e) {
        error_log('[portal_access_email] ensure account: ' . $e->getMessage());
    }

    if ($introHtml === '') {
        $introHtml = '<p>Thank you for submitting your application. Your account is ready so you can track status and upload required materials securely.</p>';
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $loginUrl = $scheme . '://' . $host . pcvc_url('/student-login.php') . '?email=' . rawurlencode($email);
    $defaultPw = PCVC_STUDENT_DEFAULT_PASSWORD;

    $body = '
      <div style="font-family:Arial,sans-serif;line-height:1.6;color:#111">
        <h2 style="margin:0 0 12px 0">My Account — Portal Access</h2>
        <p>Hello <strong>' . htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') . '</strong>,</p>
        ' . $introHtml . '
        <p style="margin:16px 0">
          <a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '" style="
            display:inline-block;background:#3661B9;color:#fff;text-decoration:none;
            padding:10px 14px;border-radius:8px;font-weight:700;
          ">Open My Account</a>
        </p>
        <p><strong>Login details</strong></p>
        <ul>
          <li>Email: <strong>' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</strong></li>
          <li>Default password: <strong>' . htmlspecialchars($defaultPw, ENT_QUOTES, 'UTF-8') . '</strong></li>
        </ul>
        <p>If the button does not work, copy this link:<br>
        <a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '</a></p>
        <p style="margin-top:18px">Thank you,<br>' . htmlspecialchars(PCVC_COMPANY_DISPLAY_NAME, ENT_QUOTES, 'UTF-8') . '</p>
      </div>
    ';
    $body = str_replace(['<div', '</div>'], ['<div', '</div>'], $body);
    $body = str_replace('</div>', '</div>', $body);

    try {
        $mail = app_mailer();
        if (method_exists($mail, 'setFrom')) {
            $mail->setFrom(PCVC_COMPANY_SUPPORT_EMAIL, PCVC_COMPANY_DISPLAY_NAME);
        }
        $mail->clearAddresses();
        $mail->addAddress($email, $studentName);
        $mail->Subject = 'Your My Account portal access — ' . PCVC_COMPANY_DISPLAY_NAME;
        $mail->isHTML(true);
        $mail->Body = $body;
        $mail->AltBody = "My Account portal access\n\nLogin: {$loginUrl}\nEmail: {$email}\nPassword: {$defaultPw}\n";
        $mail->send();

        return true;
    } catch (Throwable $e) {
        error_log('[portal_access_email] send failed: ' . $e->getMessage());

        return false;
    }
}

/**
 * True if email exists on any application table that may use the student portal.
 */
function xander_portal_email_has_application_source(mysqli $conn, string $email): bool
{
    $email = pcvc_student_email_norm($email);
    if ($email === '') {
        return false;
    }

    $queries = [
        'SELECT 1 FROM student_applications WHERE LOWER(TRIM(email)) = ? LIMIT 1',
        'SELECT 1 FROM job_applications WHERE LOWER(TRIM(email)) = ? LIMIT 1',
        'SELECT 1 FROM credit_transfer_applications WHERE LOWER(TRIM(email)) = ? LIMIT 1',
        'SELECT 1 FROM master_loan_applications WHERE LOWER(TRIM(email)) = ? LIMIT 1',
    ];

    foreach ($queries as $sql) {
        $st = $conn->prepare($sql);
        if (!$st) {
            continue;
        }
        $st->bind_param('s', $email);
        $st->execute();
        $found = (bool) $st->get_result()->fetch_row();
        $st->close();
        if ($found) {
            return true;
        }
    }

    return false;
}
