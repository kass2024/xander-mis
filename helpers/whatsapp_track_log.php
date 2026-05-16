<?php
/**
 * Pre-screening / WhatsApp tracking on cPanel (file log).
 * tail -f logs/whatsapp-prescreening.log
 */
declare(strict_types=1);

function xander_whatsapp_track(string $action, array $context = []): void
{
    $root = dirname(__DIR__);
    $dir = $root . DIRECTORY_SEPARATOR . 'logs';
    if (! is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . DIRECTORY_SEPARATOR . 'whatsapp-prescreening.log';
    foreach (['secret', 'token', 'authorization', 'password'] as $key) {
        if (isset($context[$key])) {
            $context[$key] = '[redacted]';
        }
    }
    $line = date('c') . ' [' . $action . '] ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}
