<?php
declare(strict_types=1);

require_once __DIR__ . '/role.php';
require_once __DIR__ . '/admin_menu_permissions.php';

/**
 * Map prescreening scripts to registry submenu file keys.
 */
function xander_prescreening_submenu_file_for_script(string $scriptBasename): ?string
{
    $map = [
        'prescreening.php' => 'prescreening.php',
        'prescreening-report.php' => 'prescreening-report.php',
        'prescreening-admin-form.php' => 'prescreening.php',
        'prescreening-apply.php' => 'prescreening-report.php',
        'delete_prescreening.php' => 'prescreening-report.php',
        'save_prescreening.php' => 'prescreening.php',
        'send_prescreening_invite.php' => 'prescreening.php',
        'send_prescreening_link_email.php' => 'prescreening.php',
        'upload_prescreening_document.php' => 'prescreening.php',
    ];

    return $map[$scriptBasename] ?? null;
}

/**
 * @return array<string, mixed>|null
 */
function xander_prescreening_current_admin_row(mysqli $conn): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $adminId = (int) ($_SESSION['admin_id'] ?? $_SESSION['id'] ?? 0);
    if ($adminId <= 0) {
        return null;
    }

    $stmt = $conn->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * Whether the logged-in admin may use a prescreening feature (respects Menu Access custom permissions).
 */
function xander_prescreening_has_menu_access(mysqli $conn, ?string $submenuFile = null): bool
{
    $admin = xander_prescreening_current_admin_row($conn);
    if (!$admin) {
        return false;
    }

    if (pcvc_is_superadmin_role((string) ($admin['role'] ?? ''))) {
        return true;
    }

    xander_admin_menu_ensure_table($conn);
    $access = xander_admin_menu_resolve($conn, $admin);

    $file = $submenuFile;
    if ($file === null || $file === '') {
        $file = xander_prescreening_submenu_file_for_script(basename($_SERVER['SCRIPT_NAME'] ?? ''));
    }

    if ($file === null || $file === '') {
        return xander_admin_menu_allowed($access, 'prescreening');
    }

    return xander_admin_submenu_allowed($access, 'prescreening', $file);
}

/**
 * Require login + prescreening permission from Menu Access (superadmin always allowed).
 */
function xander_prescreening_require_menu_access(?string $submenuFile = null, bool $jsonResponse = false): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $adminId = (int) ($_SESSION['admin_id'] ?? $_SESSION['id'] ?? 0);
    if ($adminId <= 0) {
        if ($jsonResponse) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        header('Location: admin-login.php');
        exit;
    }

    require_once __DIR__ . '/../db.php';
    global $conn;

    if (!isset($conn) || !($conn instanceof mysqli)) {
        http_response_code(500);
        exit('Database unavailable.');
    }

    if (!xander_prescreening_has_menu_access($conn, $submenuFile)) {
        if ($jsonResponse) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'error',
                'message' => 'You do not have access to Pre-screening. Ask a superadmin to enable it under Menu Access.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        http_response_code(403);
        exit('You do not have access to Pre-screening. Ask a superadmin to enable it under Menu Access.');
    }
}

/** @deprecated Use xander_prescreening_require_menu_access() — kept for backwards compatibility. */
function xander_prescreening_require_superadmin(): void
{
    xander_prescreening_require_menu_access();
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
