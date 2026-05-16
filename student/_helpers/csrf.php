<?php
declare(strict_types=1);

/**
 * CSRF helpers (session-based).
 * Uses a single token key across the portal.
 */

function xgs_csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['xgs_csrf_token']) || !is_string($_SESSION['xgs_csrf_token'])) {
        $_SESSION['xgs_csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['xgs_csrf_token'];
}

function xgs_csrf_input(): string
{
    $t = xgs_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '">';
}

function xgs_csrf_validate_post(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return false;
    }

    $sent = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
    $sess = isset($_SESSION['xgs_csrf_token']) ? (string) $_SESSION['xgs_csrf_token'] : '';
    if ($sent === '' || $sess === '') {
        return false;
    }

    return hash_equals($sess, $sent);
}

