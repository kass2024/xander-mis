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

    // Production fallback when .env is not deployed (matches cPanel admissions mailbox).
    return 'Xander2026$';
}

/**
 * Central SMTP settings for PHPMailer across Xander.
 * Credentials are read from project-root .env (SMTP_* keys).
 */
function app_mailer(?string $fromNameOverride = null): PHPMailer
{
    xander_load_env_file();

    $host = xander_env_get('SMTP_HOST') ?: 'xanderglobalscholars.com';
    $username = xander_env_get('SMTP_USERNAME') ?: 'admissions@xanderglobalscholars.com';
    $password = xander_smtp_password();
    $portStr = xander_env_get('SMTP_PORT');
    $port = $portStr !== '' ? (int) $portStr : 465;

    $fromEmail = xander_env_get('SMTP_FROM_EMAIL') ?: $username;
    $fromName = $fromNameOverride
        ?? (xander_env_get('SMTP_FROM_NAME') ?: 'Xander Global Scholars');

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $host;
    $mail->SMTPAuth = true;
    $mail->Username = $username;
    $mail->Password = $password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = $port > 0 ? $port : 465;
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
