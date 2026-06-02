<?php
declare(strict_types=1);

/**
 * Env helper for document vision (Gemini / Claude autofill).
 * Uses Xander .env loader — never hardcode API keys in PHP.
 */
require_once __DIR__ . '/env_load.php';

function pcvc_env(string $key, string $default = ''): string
{
    $v = xander_env_get($key);

    return $v !== '' ? $v : $default;
}
