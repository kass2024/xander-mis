<?php
/**
 * Staff / agent registration: default password, welcome email with login link.
 */
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/mail_smtp.php';

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

/** Initial password for self-registered admins (plain); user should change after approval. */
const REGISTER_STAFF_DEFAULT_PASSWORD = 'Xander@2026';

/**
 * Absolute URL to admin login (same folder as this script).
 */
function register_staff_admin_login_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/register_staff.php';
    $dir = dirname(str_replace('\\', '/', (string) $script));
    if ($dir === '/' || $dir === '.') {
        $path = '';
    } else {
        $path = rtrim($dir, '/');
    }

    return $scheme . '://' . $host . $path . '/admin-login.php';
}

/**
 * @return array{ok: bool, error_info: string}
 */
function register_staff_send_welcome_email(
    string $toEmail,
    string $toName,
    string $loginUrl,
    string $username,
    string $defaultPassword
): array {
    $mail = null;
    try {
        $mail = xander_create_phpmailer();
        $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Your Xander admin account — login details';

        $safeName = htmlspecialchars($toName !== '' ? $toName : $username, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $safeUser = htmlspecialchars($username, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $safePass = htmlspecialchars($defaultPassword, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $safeUrl = htmlspecialchars($loginUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $mail->Body = '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f1f5f9;padding:24px;font-family:Inter,Segoe UI,sans-serif;">'
            . '<tr><td align="center">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" width="560" style="background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">'
            . '<tr><td style="height:4px;background:linear-gradient(90deg,#1e3a5f 0%,#ff8c42 50%,#1e3a5f 100%);"></td></tr>'
            . '<tr><td style="padding:28px 32px;">'
            . '<p style="margin:0 0 12px;font-size:16px;color:#1e293b;">Hello ' . $safeName . ',</p>'
            . '<p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">Thank you for registering with <strong>Xander Global Scholars</strong>. Your account is <strong>pending approval</strong>. When an administrator activates it, you can sign in with the details below.</p>'
            . '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:16px 0;width:100%;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;">'
            . '<tr><td style="padding:16px 18px;font-size:14px;color:#1e293b;">'
            . '<strong>Login URL</strong><br><a href="' . $safeUrl . '" style="color:#1e3a5f;word-break:break-all;">' . $safeUrl . '</a></td></tr>'
            . '<tr><td style="padding:0 18px 16px;font-size:14px;color:#1e293b;">'
            . '<strong>Username</strong><br><span style="font-family:ui-monospace,monospace;">' . $safeUser . '</span></td></tr>'
            . '<tr><td style="padding:0 18px 18px;font-size:14px;color:#1e293b;">'
            . '<strong>Temporary password</strong><br><span style="font-family:ui-monospace,monospace;">' . $safePass . '</span></td></tr>'
            . '</table>'
            . '<p style="margin:16px 0 0;font-size:13px;color:#64748b;">Please change your password after your first successful login. If you did not request this account, you can ignore this message.</p>'
            . '<p style="margin:20px 0 0;"><a href="' . $safeUrl . '" style="display:inline-block;padding:12px 22px;background:#1e3a5f;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">Go to admin login</a></p>'
            . '</td></tr></table>'
            . '<p style="margin:16px 0 0;font-size:12px;color:#94a3b8;">© ' . date('Y') . ' Xander Global Scholars</p>'
            . '</td></tr></table>';

        $mail->AltBody = "Hello,\n\n"
            . "Your Xander admin registration was received. Your account is pending approval.\n\n"
            . "Login: {$loginUrl}\n"
            . "Username: {$username}\n"
            . "Temporary password: {$defaultPassword}\n\n"
            . "Change your password after first login.\n";

        if (!$mail->send()) {
            return ['ok' => false, 'error_info' => $mail->ErrorInfo];
        }
        return ['ok' => true, 'error_info' => ''];
    } catch (MailException $e) {
        $info = ($mail instanceof PHPMailer) ? $mail->ErrorInfo : '';
        return ['ok' => false, 'error_info' => $info !== '' ? $info : $e->getMessage()];
    } catch (Throwable $e) {
        return ['ok' => false, 'error_info' => $e->getMessage()];
    }
}

$first_name   = trim((string) ($_POST['first_name'] ?? ''));
$last_name    = trim((string) ($_POST['last_name'] ?? ''));
$username     = trim((string) ($_POST['username'] ?? ''));
$phone_number = trim((string) ($_POST['phone_number'] ?? ''));
$phone_e164   = trim((string) ($_POST['phone_e164'] ?? ''));
$email        = trim((string) ($_POST['email'] ?? ''));
$role         = trim((string) ($_POST['role'] ?? 'agent'));

if ($role === '') {
    $role = 'agent';
}

// Prefer the validated E.164 number coming from the client (intl-tel-input)
if ($phone_e164 !== '') {
    $phone_number = $phone_e164;
}

if ($first_name === '' || $last_name === '' || $username === '' || $phone_number === '' || $email === '') {
    header('Location: register.php?error=invalid');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: register.php?error=invalid');
    exit;
}

// Phone: must contain 7–15 digits (ITU E.164 allows up to 15)
$phoneDigits = preg_replace('/\D+/', '', $phone_number);
if (strlen((string) $phoneDigits) < 7 || strlen((string) $phoneDigits) > 15) {
    header('Location: register.php?error=invalid');
    exit;
}

$full_name      = $first_name . ' ' . $last_name;
$created_at     = date('Y-m-d H:i:s');
$password_hash  = password_hash(REGISTER_STAFF_DEFAULT_PASSWORD, PASSWORD_DEFAULT);
$status         = 'pending';

$checkUsername = $conn->prepare('SELECT id FROM admins WHERE username = ?');
$checkUsername->bind_param('s', $username);
$checkUsername->execute();
$checkUsername->store_result();
if ($checkUsername->num_rows > 0) {
    $checkUsername->close();
    $conn->close();
    header('Location: register.php?error=username_taken');
    exit;
}
$checkUsername->close();

$checkEmail = $conn->prepare('SELECT id FROM admins WHERE email = ?');
$checkEmail->bind_param('s', $email);
$checkEmail->execute();
$checkEmail->store_result();
if ($checkEmail->num_rows > 0) {
    $checkEmail->close();
    $conn->close();
    header('Location: register.php?error=email_taken');
    exit;
}
$checkEmail->close();

$sql = 'INSERT INTO admins 
(username, first_name, last_name, email, phone_number, password_hash, full_name, created_at, role, status)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $conn->close();
    header('Location: register.php?error=system');
    exit;
}

$stmt->bind_param(
    'ssssssssss',
    $username,
    $first_name,
    $last_name,
    $email,
    $phone_number,
    $password_hash,
    $full_name,
    $created_at,
    $role,
    $status
);

if (!$stmt->execute()) {
    $stmt->close();
    $conn->close();
    header('Location: register.php?error=system');
    exit;
}

$newAdminId = (int) $conn->insert_id;
$stmt->close();

// New admins start with N/A (no menu access). A superadmin must grant access
// explicitly via Menu Access. Existing admins are unaffected.
require_once __DIR__ . '/helpers/admin_menu_permissions.php';
xander_admin_menu_init_empty_for_admin($conn, $newAdminId, $newAdminId);

$loginUrl = register_staff_admin_login_url();
$send     = register_staff_send_welcome_email($email, $full_name, $loginUrl, $username, REGISTER_STAFF_DEFAULT_PASSWORD);

$conn->close();

if (!$send['ok']) {
    error_log('[register_staff] Welcome email failed: ' . $send['error_info']);
    header('Location: register.php?success=1&email_failed=1');
    exit;
}

header('Location: register.php?success=1');
exit;
