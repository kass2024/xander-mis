<?php
declare(strict_types=1);

/**
 * Return JSON immediately after job application save; confirmation emails run in background.
 */

/**
 * @param array<string, mixed> $response
 */
function xander_job_application_flush_json(
    array $response,
    mysqli $conn,
    string $userId,
    string $reference,
    int $code = 200
): void {
    while (ob_get_level()) {
        ob_end_clean();
    }

    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Connection: close');
    header('Cache-Control: no-store');

    $json = json_encode($response, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        $json = '{"status":"error","message":"Response encoding failed"}';
        http_response_code(500);
    }

    header('Content-Length: ' . strlen($json));
    echo $json;

    if (function_exists('session_write_close')) {
        session_write_close();
    }

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        ignore_user_abort(true);
        if (ob_get_level()) {
            @ob_end_flush();
        }
        @flush();
    }

    if (!xander_job_application_queue_confirmation_email($userId, $reference)) {
        try {
            require_once __DIR__ . '/application_confirmation_emails.php';
            xander_send_job_application_confirmation_emails($conn, $userId, $reference);
        } catch (Throwable $e) {
            error_log('[job_application_async_notify] ' . $e->getMessage());
        }
    }

    exit;
}

function xander_job_application_queue_confirmation_email(string $userId, string $reference): bool
{
    $dir = dirname(__DIR__) . '/temp/job_confirm_queue/';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return false;
    }

    $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $userId);
    $queueFile = $dir . $safeId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.json';
    $written = file_put_contents($queueFile, json_encode([
        'user_id' => $userId,
        'reference' => $reference,
    ], JSON_UNESCAPED_UNICODE));

    if ($written === false) {
        return false;
    }

    return xander_job_application_spawn_confirm_worker($queueFile);
}

function xander_job_application_spawn_confirm_worker(string $queueFile): bool
{
    require_once __DIR__ . '/prescreening_async_notify.php';

    $php = xander_prescreening_resolve_php_cli_binary();
    $script = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'job_confirm_email_worker.php';
    if (!is_file($script)) {
        return false;
    }

    $queueFile = realpath($queueFile) ?: $queueFile;

    if (PHP_OS_FAMILY === 'Windows') {
        $cmd = 'cmd /c start "" /B ' . escapeshellarg($php) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($queueFile);
        $h = @popen($cmd, 'r');
        if (is_resource($h)) {
            pclose($h);

            return true;
        }

        return false;
    }

    $cmd = escapeshellarg($php) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($queueFile) . ' > /dev/null 2>&1 &';
    exec($cmd);

    return true;
}
