<?php
declare(strict_types=1);
/**
 * CLI: test SMTP settings from .env (no secrets printed).
 *
 * Usage:
 *   php scripts/smtp_test.php
 *   php scripts/smtp_test.php you@example.com
 */
$root = dirname(__DIR__);
require_once $root . '/helpers/mailer.php';

xander_load_env_file();

$to = $argv[1] ?? '';
$checks = [
    'host' => xander_smtp_host(),
    'port' => xander_smtp_transport_settings()['port'],
    'secure' => xander_smtp_transport_settings()['secure'] ?: 'none',
    'username' => xander_env_get('SMTP_USERNAME') ?: 'admissions@xanderglobalscholars.com',
    'password_set' => xander_smtp_password() !== '',
    'from' => xander_env_get('SMTP_FROM_EMAIL') ?: xander_env_get('SMTP_USERNAME'),
];

echo json_encode($checks, JSON_PRETTY_PRINT) . PHP_EOL;

if (!$checks['password_set']) {
    fwrite(STDERR, "SMTP_PASSWORD is empty — set it in .env\n");
    exit(1);
}

if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo "Add a recipient email to send a test message:\n  php scripts/smtp_test.php you@example.com\n";
    exit(0);
}

$mail = null;
try {
    $mail = app_applicant_mailer();
    $mail->addAddress($to);
    $mail->Subject = 'Xander SMTP test';
    $mail->Body = '<p>SMTP test from Xander at ' . date('c') . '</p>';
    $mail->AltBody = 'SMTP test from Xander at ' . date('c');
    $mail->send();
    echo "Sent test email to {$to}\n";
    exit(0);
} catch (Throwable $e) {
    $detail = xander_mailer_error_detail($mail, $e);
    fwrite(STDERR, 'Send failed: ' . $detail . PHP_EOL);
    if (str_contains(strtolower($detail), 'authenticate')) {
        fwrite(STDERR, "Hint: reset the mailbox password in cPanel → Email Accounts, then update SMTP_PASSWORD in .env\n");
    }
    exit(1);
}
