<?php
declare(strict_types=1);

if ($argc < 2) {
    exit(1);
}

$queueFile = $argv[1];
if (!is_file($queueFile)) {
    exit(1);
}

$payload = json_decode((string) file_get_contents($queueFile), true);
@unlink($queueFile);

if (!is_array($payload)) {
    exit(1);
}

$userId = trim((string) ($payload['user_id'] ?? ''));
$reference = trim((string) ($payload['reference'] ?? ''));
if ($userId === '') {
    exit(1);
}

require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/helpers/application_confirmation_emails.php';

try {
    xander_send_job_application_confirmation_emails($conn, $userId, $reference);
} catch (Throwable $e) {
    error_log('[job_confirm_email_worker] ' . $e->getMessage());
    exit(1);
}

exit(0);
