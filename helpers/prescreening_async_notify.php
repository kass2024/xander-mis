<?php
declare(strict_types=1);

/**
 * Return JSON immediately; notifications run in a detached PHP worker (fast on XAMPP + study with many docs).
 */

/**
 * @param array<string, mixed> $response
 * @param array<string, mixed> $row
 */
function xander_prescreening_flush_json_and_notify(
    array $response,
    mysqli $conn,
    array $row,
    string $reference,
    string $userId,
    bool $skipStudentWhatsapp = true,
    int $code = 200
): void {
    while (ob_get_level()) {
        ob_end_clean();
    }

    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Connection: close');

    $json = json_encode($response, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        $json = '{"status":"error","message":"Response encoding failed"}';
        $code = 500;
        http_response_code($code);
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

    if (!xander_prescreening_queue_notifications($row, $reference, $userId, $skipStudentWhatsapp)) {
        xander_prescreening_dispatch_notifications($conn, $row, $reference, $userId, $skipStudentWhatsapp);
    }

    exit;
}

/**
 * @param array<string, mixed> $row
 */
function xander_prescreening_queue_notifications(
    array $row,
    string $reference,
    string $userId,
    bool $skipStudentWhatsapp
): bool {
    $dir = dirname(__DIR__) . '/temp/prescreen_notify_queue/';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return false;
    }

    $queueFile = $dir . preg_replace('/[^a-zA-Z0-9_-]/', '', $userId) . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.json';
    $written = file_put_contents($queueFile, json_encode([
        'user_id' => $userId,
        'reference' => $reference,
        'skip_student_whatsapp' => $skipStudentWhatsapp,
        'row' => $row,
    ], JSON_UNESCAPED_UNICODE));

    if ($written === false) {
        return false;
    }

    return xander_prescreening_spawn_notify_worker($queueFile);
}

function xander_prescreening_resolve_php_cli_binary(): string
{
    $env = getenv('PHP_CLI_PATH');
    if (is_string($env) && $env !== '' && is_file($env)) {
        return $env;
    }

    if (defined('PHP_BINARY') && PHP_BINARY !== '' && is_file(PHP_BINARY)) {
        return PHP_BINARY;
    }

    $candidates = [
        'C:\\xampp\\php\\php.exe',
        'C:\\laragon\\bin\\php\\php-8.2.12-Win32-vs16-x64\\php.exe',
        '/usr/bin/php',
        '/usr/local/bin/php',
    ];
    foreach ($candidates as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return 'php';
}

function xander_prescreening_spawn_notify_worker(string $queueFile): bool
{
    $php = xander_prescreening_resolve_php_cli_binary();
    $script = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'prescreening_notify_worker.php';
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

/**
 * @param array<string, mixed> $row
 */
function xander_prescreening_dispatch_notifications(
    mysqli $conn,
    array $row,
    string $reference,
    string $userId,
    bool $skipStudentWhatsapp = true
): void {
    require_once __DIR__ . '/prescreening_notify.php';
    require_once __DIR__ . '/prescreening_whatsapp_flow.php';

    try {
        $notify = xander_send_prescreening_notifications($row, $reference, $skipStudentWhatsapp);
        xander_prescreening_notify_staff_whatsapp($row, $reference, false);

        $emailOk = !empty($notify['email']['admin']) || !empty($notify['email']['student']);
        $waOk = !empty($notify['whatsapp']['sent']);

        $upd = $conn->prepare(
            'UPDATE prescreening_submissions SET email_sent = ?, whatsapp_sent = ?, notify_errors = ? WHERE user_id = ? LIMIT 1'
        );
        if ($upd) {
            $emailSent = $emailOk ? 1 : 0;
            $waSent = $waOk ? 1 : 0;
            $errJson = (!$emailOk && !$waOk) ? json_encode(['Notification issues'], JSON_UNESCAPED_UNICODE) : null;
            $upd->bind_param('iiss', $emailSent, $waSent, $errJson, $userId);
            $upd->execute();
            $upd->close();
        }
    } catch (Throwable $e) {
        error_log('[prescreening_async_notify] ' . $e->getMessage());
    }
}
