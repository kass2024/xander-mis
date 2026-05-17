<?php
declare(strict_types=1);

require_once __DIR__ . '/institution_portal.php';
require_once __DIR__ . '/institution_portal_schema.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/urls.php';
require_once __DIR__ . '/../includes/company_branding.php';

function xander_institution_password_reset_log(string $message, bool $isError = false): void
{
    $prefix = $isError ? '[institution_password_reset][ERROR] ' : '[institution_password_reset] ';
    error_log($prefix . $message);

    $dir = __DIR__ . '/../logs';
    $file = $dir . '/institution_password_reset.log';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (is_dir($dir) && (is_writable($dir) || !file_exists($file))) {
        $line = date('c') . ' ' . ($isError ? 'ERROR ' : '') . $message . PHP_EOL;
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}

function xander_ensure_institution_password_reset_columns(mysqli $conn): void
{
    xander_institution_portal_ensure_schema($conn);

    foreach (['password_reset_token', 'password_reset_expires'] as $col) {
        $esc = $conn->real_escape_string($col);
        $r = @$conn->query("SHOW COLUMNS FROM `institution_portal_accounts` LIKE '{$esc}'");
        if ($r && $r->num_rows > 0) {
            continue;
        }
        if ($col === 'password_reset_token') {
            $sql = 'ALTER TABLE `institution_portal_accounts` ADD COLUMN `password_reset_token` VARCHAR(64) NULL DEFAULT NULL';
        } else {
            $sql = 'ALTER TABLE `institution_portal_accounts` ADD COLUMN `password_reset_expires` DATETIME NULL DEFAULT NULL';
        }
        if (!@$conn->query($sql)) {
            xander_institution_password_reset_log('Column migration failed: ' . $conn->error, true);
        }
    }
}

function xander_institution_public_origin(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

    return $scheme . '://' . $host;
}

/**
 * @return array{ok: bool, error_info: string}
 */
function xander_send_institution_password_reset_email(string $toEmail, string $toName, string $resetUrl): array
{
    try {
        $mail = app_mailer();
        if (method_exists($mail, 'setFrom')) {
            $mail->setFrom(PCVC_COMPANY_SUPPORT_EMAIL, PCVC_COMPANY_DISPLAY_NAME);
        }
        $mail->clearAddresses();
        $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Reset your institution portal password — ' . PCVC_COMPANY_DISPLAY_NAME;
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $safeName = htmlspecialchars($toName, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $mail->Body = '
      <div style="font-family:Arial,sans-serif;line-height:1.6;color:#111">
        <h2 style="margin:0 0 12px 0">Password reset</h2>
        <p>Hello' . ($toName !== '' ? ' <strong>' . $safeName . '</strong>' : '') . ',</p>
        <p>We received a request to reset the password for your institution portal account.</p>
        <p><a href="' . $safeUrl . '" style="display:inline-block;padding:12px 20px;background:#012F6B;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;">Reset password</a></p>
        <p>Or copy this link into your browser:<br><span style="word-break:break-all;">' . $safeUrl . '</span></p>
        <p>This link expires in one hour. If you did not request this, you can ignore this email.</p>
      </div>';
        $mail->AltBody = "Reset your institution portal password:\n{$resetUrl}\n\nThis link expires in one hour.";

        if (!$mail->send()) {
            $info = method_exists($mail, 'ErrorInfo') ? (string) $mail->ErrorInfo : 'send failed';
            xander_institution_password_reset_log('send() returned false. ErrorInfo: ' . $info, true);

            return ['ok' => false, 'error_info' => $info];
        }

        xander_institution_password_reset_log('Mail sent OK to ' . $toEmail);

        return ['ok' => true, 'error_info' => ''];
    } catch (Throwable $e) {
        xander_institution_password_reset_log('Throwable: ' . $e->getMessage(), true);

        return ['ok' => false, 'error_info' => $e->getMessage()];
    }
}

function xander_institution_clear_password_reset_token(mysqli $conn, int $accountId): void
{
    $st = $conn->prepare('UPDATE institution_portal_accounts SET password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?');
    if ($st) {
        $st->bind_param('i', $accountId);
        if (!$st->execute()) {
            xander_institution_password_reset_log('Failed to clear token for account id=' . $accountId . ': ' . $st->error, true);
        }
        $st->close();
    }
}

/**
 * @return array<string, mixed>|null
 */
function xander_institution_fetch_for_password_reset(mysqli $conn, string $email): ?array
{
    $email = xander_institution_email_norm($email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    $st = $conn->prepare('
        SELECT id, contact_name, email, status
        FROM institution_portal_accounts
        WHERE email = ?
        LIMIT 1
    ');
    if (!$st) {
        return null;
    }
    $st->bind_param('s', $email);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$row || ($row['status'] ?? '') !== 'active') {
        return null;
    }

    return $row;
}

/**
 * @return string sent | no_account | invalid_input | db_error | mail_failed
 */
function xander_institution_password_reset_request(mysqli $conn, string $email): string
{
    xander_ensure_institution_password_reset_columns($conn);

    $email = xander_institution_email_norm(trim($email));
    if ($email === '' || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'invalid_input';
    }

    $row = xander_institution_fetch_for_password_reset($conn, $email);
    if (!$row) {
        xander_institution_password_reset_log('No active account for email lookup.');

        return 'no_account';
    }

    $raw = bin2hex(random_bytes(32));
    $hash = hash('sha256', $raw);
    $expires = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('+1 hour')->format('Y-m-d H:i:s');
    $id = (int) $row['id'];

    $upd = $conn->prepare('UPDATE institution_portal_accounts SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?');
    if (!$upd) {
        xander_institution_password_reset_log('DB prepare failed: ' . $conn->error, true);

        return 'db_error';
    }
    $upd->bind_param('ssi', $hash, $expires, $id);
    if (!$upd->execute()) {
        xander_institution_password_reset_log('DB execute failed account_id=' . $id . ': ' . $upd->error, true);
        $upd->close();

        return 'db_error';
    }
    $upd->close();

    $resetUrl = xander_institution_public_origin() . pcvc_url('/institution-reset-password.php?t=' . rawurlencode($raw));
    $name = trim((string) ($row['contact_name'] ?? ''));
    $to = xander_institution_email_norm((string) ($row['email'] ?? ''));

    $send = xander_send_institution_password_reset_email($to, $name, $resetUrl);
    if (!$send['ok']) {
        xander_institution_clear_password_reset_token($conn, $id);
        xander_institution_password_reset_log('Token rolled back after mail failure for account_id=' . $id, true);

        return 'mail_failed';
    }

    xander_institution_password_reset_log('Password reset completed for account_id=' . $id);

    return 'sent';
}
