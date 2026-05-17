<?php
declare(strict_types=1);

/**
 * Background worker: send pre-screening emails (with attachments) + staff WhatsApp summary.
 * Usage: php scripts/prescreening_notify_worker.php /path/to/queue-file.json
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$queueFile = $argv[1] ?? '';
if ($queueFile === '' || !is_file($queueFile)) {
    fwrite(STDERR, "Missing queue file\n");
    exit(1);
}

$payload = json_decode((string) file_get_contents($queueFile), true);
@unlink($queueFile);

if (!is_array($payload) || empty($payload['user_id'])) {
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/db.php';
require_once $root . '/helpers/prescreening_async_notify.php';

xander_prescreening_dispatch_notifications(
    $conn,
    (array) ($payload['row'] ?? []),
    (string) ($payload['reference'] ?? ''),
    (string) $payload['user_id'],
    !empty($payload['skip_student_whatsapp'])
);

exit(0);
