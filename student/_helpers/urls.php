<?php
declare(strict_types=1);

/**
 * Student portal URL helper (Xander).
 *
 * Computes the app base path dynamically:
 * - Local XAMPP: /Xander/student/index.php -> base "/Xander"
 * - cPanel root: /student/index.php        -> base ""
 */
function xgs_app_base_path(): string
{
    $sn = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    if ($sn === '') return '';

    $pos = strpos($sn, '/student/');
    if ($pos !== false) {
        return rtrim(substr($sn, 0, $pos), '/');
    }

    // Fallback: directory of script (e.g. /Xander)
    $dir = rtrim(dirname($sn), '/');
    return $dir === '/' ? '' : $dir;
}

/**
 * Build an absolute-path URL within the app, respecting base path.
 * Pass paths like "/student/index.php" or "/student-login.php".
 */
function xgs_url(string $path): string
{
    $path = trim($path);
    if ($path === '') return xgs_app_base_path() ?: '/';
    if ($path[0] !== '/') $path = '/' . $path;
    return xgs_app_base_path() . $path;
}

