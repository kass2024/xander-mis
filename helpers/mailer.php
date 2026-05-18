<?php
declare(strict_types=1);

require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Central SMTP settings for PHPMailer across Xander.
 * Uses env vars when available, falls back to Xander defaults.
 */
function app_mailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();

    // Prefer env variables when present (works on cPanel/production).
    require_once __DIR__ . '/env_load.php';
    xander_load_env_file();

    $host = xander_env_get('SMTP_HOST') ?: 'xanderglobalscholars.com';
    $username = xander_env_get('SMTP_USERNAME') ?: 'admissions@xanderglobalscholars.com';
    $password = xander_env_get('SMTP_PASSWORD');
    $port = (int) (xander_env_get('SMTP_PORT') ?: 465);

    $fromEmail = xander_env_get('SMTP_FROM_EMAIL') ?: $username;
    $fromName = xander_env_get('SMTP_FROM_NAME') ?: 'Xander Global Scholars';

    $mail->Host = $host;
    $mail->SMTPAuth = true;
    $mail->Username = $username;
    $mail->Password = $password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = $port > 0 ? $port : 465;

    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);
    $mail->setFrom($fromEmail, $fromName);

    return $mail;
}

