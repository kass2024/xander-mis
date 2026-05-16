<?php
declare(strict_types=1);

require_once __DIR__ . '/role.php';

function xander_prescreening_require_superadmin(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['admin_id'])) {
        header('Location: admin-login.php');
        exit;
    }
    if (!pcvc_is_superadmin_role($_SESSION['role'] ?? '')) {
        http_response_code(403);
        exit('Pre-screening is only available to Superadmin.');
    }
}

function xander_prescreening_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['prescreen_csrf'])) {
        $_SESSION['prescreen_csrf'] = bin2hex(random_bytes(16));
    }

    return (string) $_SESSION['prescreen_csrf'];
}

function xander_prescreening_verify_csrf(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $expected = $_SESSION['prescreen_csrf'] ?? '';

    return $expected !== '' && is_string($token) && hash_equals($expected, $token);
}
