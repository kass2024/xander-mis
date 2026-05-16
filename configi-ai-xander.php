<?php
/**
 * XGS AI Configuration — keys from .env only (OPENAI_API_KEY).
 */
declare(strict_types=1);

require_once __DIR__ . '/helpers/openai_env.php';

$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    || ($_SERVER['HTTP_HOST'] ?? '') === 'localhost';

if ($isLocal) {
    define('ENVIRONMENT', 'development');
    define('DEBUG_MODE', true);
} else {
    define('ENVIRONMENT', 'production');
    define('DEBUG_MODE', false);
}

xander_openai_bootstrap();

define('AI_MODEL', 'gpt-4o-mini');
define('AI_TEMPERATURE', 0.6);
define('AI_MAX_TOKENS', 350);
define('AI_TIMEOUT', 30);

define('APP_NAME', 'Xander Global Scholars AI Assistant');
define('APP_VERSION', '1.0.0');

define('RATE_LIMIT_REQUESTS', 50);
define('RATE_LIMIT_PERIOD', 3600);

define('SESSION_LIFETIME', 1800);
define('ENABLE_CONTENT_FILTER', true);

define('LOG_ERRORS', true);
define('LOG_FILE', __DIR__ . '/logs/ai_errors.log');

if (LOG_ERRORS && !is_dir(dirname(LOG_FILE))) {
    mkdir(dirname(LOG_FILE), 0755, true);
}

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    if (LOG_ERRORS) {
        ini_set('log_errors', '1');
        ini_set('error_log', LOG_FILE);
    }
}

function validateApiKey(): bool
{
    if (!xander_openai_is_configured()) {
        error_log('[XGS AI] OpenAI API key missing or invalid in .env');

        return false;
    }

    return true;
}

function getApiHeaders(): array
{
    return [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY,
    ];
}

function isApiAvailable(): bool
{
    return validateApiKey() && function_exists('curl_init');
}
