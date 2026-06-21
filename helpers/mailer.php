<?php
declare(strict_types=1);

require_once __DIR__ . '/env_load.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Improve deliverability: correct EHLO hostname and envelope sender.
 */
function xander_smtp_apply_deliverability(PHPMailer $mail, string $fromEmail): void
{
    $domain = substr(strrchr($fromEmail, '@'), 1) ?: 'xanderglobalscholars.com';
    $mail->Hostname = $domain;
    $mail->Sender = $fromEmail;
    $mail->XMailer = 'Xander-Global-Scholars';
}

/**
 * Resolve SMTP password from .env / server env.
 */
function xander_smtp_password(): string
{
    xander_load_env_file();

    $password = xander_env_get('SMTP_PASSWORD');
    if ($password !== '') {
        return $password;
    }

    $password = xander_env_get_from_dotenv_file('SMTP_PASSWORD');
    if ($password !== '') {
        return $password;
    }

    $fromGetenv = getenv('SMTP_PASSWORD');
    if ($fromGetenv !== false && trim((string) $fromGetenv) !== '') {
        return trim((string) $fromGetenv);
    }

    return '';
}

/**
 * SMTP host — cPanel typically uses mail.yourdomain.com.
 */
function xander_smtp_host(): string
{
    xander_load_env_file();
    $host = trim(xander_env_get('SMTP_HOST'));

    return $host !== '' ? $host : 'mail.xanderglobalscholars.com';
}

/**
 * Apply port + encryption from .env (SMTP_PORT, SMTP_SECURE: ssl|tls|none|auto).
 *
 * @return array{port:int,secure:string}
 */
function xander_smtp_transport_settings(): array
{
    xander_load_env_file();

    $portStr = trim(xander_env_get('SMTP_PORT'));
    $port = $portStr !== '' ? (int) $portStr : 0;
    $secureMode = strtolower(trim(xander_env_get('SMTP_SECURE')));

    if ($secureMode === 'tls' || $secureMode === 'starttls') {
        return ['port' => $port > 0 ? $port : 587, 'secure' => PHPMailer::ENCRYPTION_STARTTLS];
    }
    if (in_array($secureMode, ['none', 'off', 'plain'], true)) {
        return ['port' => $port > 0 ? $port : 25, 'secure' => ''];
    }
    if ($secureMode === 'ssl' || $secureMode === 'smtps') {
        return ['port' => $port > 0 ? $port : 465, 'secure' => PHPMailer::ENCRYPTION_SMTPS];
    }

    // auto: infer from port when set, else default SSL/465 (cPanel standard)
    if ($port === 587) {
        return ['port' => 587, 'secure' => PHPMailer::ENCRYPTION_STARTTLS];
    }
    if ($port === 25 || $port === 2525) {
        return ['port' => $port, 'secure' => ''];
    }

    return ['port' => $port > 0 ? $port : 465, 'secure' => PHPMailer::ENCRYPTION_SMTPS];
}

/**
 * SSL options — disable strict verify on local XAMPP unless SMTP_SSL_VERIFY=1.
 */
function xander_smtp_apply_ssl_options(PHPMailer $mail): void
{
    if ($mail->SMTPSecure === '') {
        return;
    }

    $verify = xander_env_is_true('SMTP_SSL_VERIFY');
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => $verify,
            'verify_peer_name' => $verify,
            'allow_self_signed' => !$verify,
        ],
    ];
}

/**
 * Best-effort error detail from PHPMailer after a failed send.
 */
function xander_mailer_error_detail(?PHPMailer $mail, Throwable $e): string
{
    $parts = [];
    $msg = trim($e->getMessage());
    if ($msg !== '') {
        $parts[] = $msg;
    }
    if ($mail instanceof PHPMailer) {
        $info = trim((string) $mail->ErrorInfo);
        if ($info !== '' && !in_array($info, $parts, true)) {
            $parts[] = $info;
        }
    }

    return implode(' — ', $parts);
}

/**
 * Central SMTP settings for PHPMailer across Xander.
 * Credentials are read from project-root .env (SMTP_* keys).
 */
function app_mailer(?string $fromNameOverride = null): PHPMailer
{
    xander_load_env_file();

    $username = xander_env_get('SMTP_USERNAME') ?: 'admissions@xanderglobalscholars.com';
    $password = xander_smtp_password();
    $transport = xander_smtp_transport_settings();

    $fromEmail = xander_env_get('SMTP_FROM_EMAIL') ?: $username;
    $fromName = $fromNameOverride
        ?? (xander_env_get('SMTP_FROM_NAME') ?: 'Xander Global Scholars');

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = xander_smtp_host();
    $mail->SMTPAuth = true;
    $mail->Username = $username;
    $mail->Password = $password;
    $mail->SMTPSecure = $transport['secure'];
    $mail->Port = $transport['port'];
    if ($transport['secure'] === '') {
        $mail->SMTPAutoTLS = false;
    }
    xander_smtp_apply_ssl_options($mail);
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 30;
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->isHTML(true);
    $mail->setFrom($fromEmail, $fromName);
    xander_smtp_apply_deliverability($mail, $fromEmail);

    return $mail;
}

/**
 * Applicant-facing mail uses the same working SMTP account as all other outbound mail.
 */
function app_applicant_mailer(): PHPMailer
{
    return app_mailer('Xander Global Scholars');
}
