<?php
/**
 * Pre-screening / WhatsApp tracking on cPanel.
 * Always writes to PHP error_log; also appends to a file when writable.
 *
 * View: cPanel → Errors, or tail whatsapp-prescreening.log in site root / logs / uploads.
 */
declare(strict_types=1);

/** @return array<int, string> */
function xander_whatsapp_track_log_paths(): array
{
    $root = dirname(__DIR__);
    $paths = [
        $root . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'whatsapp-prescreening.log',
        $root . DIRECTORY_SEPARATOR . 'whatsapp-prescreening.log',
        $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'whatsapp-prescreening.log',
    ];
    $tmp = sys_get_temp_dir();
    if ($tmp !== '') {
        $paths[] = $tmp . DIRECTORY_SEPARATOR . 'xander_whatsapp_prescreening.log';
    }

    return $paths;
}

function xander_whatsapp_track(string $action, array $context = []): void
{
    foreach (['secret', 'token', 'authorization', 'password', 'WHATSAPP_ACCESS_TOKEN'] as $key) {
        if (isset($context[$key])) {
            $context[$key] = '[redacted]';
        }
    }

    $line = date('c') . ' [' . $action . '] ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    error_log('[xander-wa] ' . $line);

    $written = false;
    foreach (xander_whatsapp_track_log_paths() as $file) {
        $dir = dirname($file);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (@file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX) !== false) {
            $written = true;
            break;
        }
    }

    if (! $written) {
        error_log('[xander-wa] track_file_write_failed action=' . $action);
    }
}

/** @return array{path:string,lines:array<int,string>,exists:bool} */
function xander_whatsapp_track_read_tail(int $maxLines = 80): array
{
    $maxLines = max(1, min(200, $maxLines));
    foreach (xander_whatsapp_track_log_paths() as $path) {
        if (! is_readable($path)) {
            continue;
        }
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            return ['path' => $path, 'lines' => [], 'exists' => true];
        }
        $lines = preg_split("/\r\n|\n|\r/", trim($content));
        if (! is_array($lines)) {
            $lines = [];
        }

        return [
            'path' => $path,
            'lines' => array_slice($lines, -$maxLines),
            'exists' => true,
        ];
    }

    return ['path' => '', 'lines' => [], 'exists' => false];
}
