<?php
declare(strict_types=1);

/**
 * Compute the application base path dynamically.
 *
 * Examples:
 * - Local XAMPP:   /Xander/student/index.php  -> base "/Xander"
 * - cPanel root:   /student/index.php        -> base ""
 * - API endpoint:  /Xander/api/x.php         -> base "/Xander"
 */
function pcvc_app_base_path(): string
{
    $sn = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    if ($sn === '') return '';

    foreach (['/student/', '/institution/', '/api/'] as $seg) {
        $pos = strpos($sn, $seg);
        if ($pos !== false) {
            $base = rtrim(substr($sn, 0, $pos), '/');
            return $base;
        }
    }

    $dir = rtrim(dirname($sn), '/');
    return $dir === '/' ? '' : $dir;
}

/**
 * Build an absolute-path URL within the app, respecting base path.
 * Pass paths like "/student/index.php" or "/student-login.php".
 */
function pcvc_url(string $path): string
{
    $path = trim($path);
    if ($path === '') return pcvc_app_base_path() ?: '/';
    if ($path[0] !== '/') $path = '/' . $path;
    return pcvc_app_base_path() . $path;
}

/**
 * Public site base URL for emails and external links (uses APP_URL from .env when set).
 */
function pcvc_public_base_url(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $envBootstrap = __DIR__ . '/env_load.php';
    if (is_readable($envBootstrap)) {
        require_once $envBootstrap;
        if (function_exists('xander_load_env_file')) {
            xander_load_env_file();
        }
        if (function_exists('xander_env_get')) {
            $appUrl = rtrim(trim(xander_env_get('APP_URL')), '/');
            if ($appUrl !== '') {
                return $cached = $appUrl;
            }
        }
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));

    return $cached = rtrim($scheme . '://' . $host . pcvc_app_base_path(), '/');
}

/**
 * Absolute URL for a path within this app (emails, WhatsApp, etc.).
 */
function pcvc_public_url(string $path): string
{
    return pcvc_public_base_url() . pcvc_url($path);
}

