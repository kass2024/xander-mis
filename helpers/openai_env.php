<?php
/**
 * OpenAI API key from .env only — never hardcode keys in app files.
 */
declare(strict_types=1);

require_once __DIR__ . '/env_load.php';

/**
 * Load OPENAI_API_KEY from .env into constants (safe to commit callers).
 */
function xander_openai_bootstrap(): void
{
    if (defined('OPENAI_API_KEY')) {
        return;
    }

    $key = xander_openai_api_key();
    define('OPENAI_API_KEY', $key);
    if (!defined('AI_API_KEY')) {
        define('AI_API_KEY', $key);
    }
}

function xander_openai_api_key(): string
{
    return xander_env_get('OPENAI_API_KEY');
}

function xander_openai_is_configured(): bool
{
    $key = xander_openai_api_key();

    return $key !== '' && preg_match('/^sk-[A-Za-z0-9_\-]+$/', $key);
}
