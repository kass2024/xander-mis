<?php
/**
 * Copy to mail_smtp.php and set SMTP credentials. Never commit mail_smtp.php.
 */
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

function xander_create_phpmailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'your-smtp-host.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your@email.com';
    $mail->Password = 'your-password';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 30;
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->setFrom('your@email.com', 'Xander Global Scholars');

    return $mail;
}

function xander_create_phpmailer_applicant_sender(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'your-smtp-host.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your@email.com';
    $mail->Password = 'your-password';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 30;
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->setFrom('your@email.com', 'Xander Global Scholars');

    return $mail;
}
