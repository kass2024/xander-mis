<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/helpers/role.php';
require_once dirname(__DIR__) . '/helpers/admin_menu_permissions.php';
require_once dirname(__DIR__) . '/includes/admin_menu_registry.php';

function menu_api_out(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['admin_id']) || !pcvc_is_superadmin_role($_SESSION['role'] ?? '')) {
    menu_api_out(['ok' => false, 'message' => 'Superadmin only'], 403);
}

xander_admin_menu_ensure_table($conn);

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? '');

if ($action === 'schema') {
    $exists = false;
    $r = $conn->query(
        "SELECT 1 FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_menu_permissions' LIMIT 1"
    );
    if ($r && $r->num_rows > 0) {
        $exists = true;
    }
    if ($r) {
        $r->free();
    }
    menu_api_out(['ok' => true, 'table' => 'admin_menu_permissions', 'exists' => $exists]);
}

if ($action === 'registry') {
    $registry = xander_admin_menu_registry();
    $formatted = [];
    foreach ($registry as $key => $def) {
        $formatted[] = [
            'key' => $key,
            'title' => $def['title'],
            'icon' => $def['icon'],
            'section' => $def['section'],
            'links' => $def['links'],
        ];
    }
    menu_api_out(['ok' => true, 'registry' => $formatted, 'role_defaults' => xander_admin_menu_role_defaults()]);
}

if ($action === 'admins') {
    menu_api_out(['ok' => true, 'admins' => xander_admin_menu_list_admins($conn)]);
}

if ($action === 'get') {
    $adminId = (int) ($_GET['admin_id'] ?? 0);
    if ($adminId <= 0) {
        menu_api_out(['ok' => false, 'message' => 'Invalid admin'], 400);
    }
    $stmt = $conn->prepare('SELECT id, username, email, role FROM admins WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$admin) {
        menu_api_out(['ok' => false, 'message' => 'Admin not found'], 404);
    }
    if (pcvc_is_superadmin_role($admin['role'] ?? '')) {
        menu_api_out([
            'ok' => true,
            'admin' => $admin,
            'access' => xander_admin_menu_resolve($conn, $admin),
            'readonly' => true,
            'message' => 'Superadmin accounts always have full menu access.',
        ]);
    }
    $custom = xander_admin_menu_get_custom($conn, $adminId);
    $roleDefault = xander_admin_menu_permissions_from_role((string) $admin['role']);
    menu_api_out([
        'ok' => true,
        'admin' => $admin,
        'access' => $custom ?? $roleDefault,
        'role_default' => $roleDefault,
        'readonly' => false,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    menu_api_out(['ok' => false, 'message' => 'Invalid action'], 400);
}

$body = json_decode(file_get_contents('php://input') ?: '{}', true);
if (!is_array($body)) {
    $body = $_POST;
}

if ($action === 'save') {
    $adminId = (int) ($body['admin_id'] ?? 0);
    if ($adminId <= 0) {
        menu_api_out(['ok' => false, 'message' => 'Invalid admin'], 400);
    }
    $stmt = $conn->prepare('SELECT id, role FROM admins WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$admin) {
        menu_api_out(['ok' => false, 'message' => 'Admin not found'], 404);
    }
    if (pcvc_is_superadmin_role($admin['role'] ?? '')) {
        menu_api_out(['ok' => false, 'message' => 'Cannot restrict superadmin menus'], 400);
    }
    $ok = xander_admin_menu_save_custom(
        $conn,
        $adminId,
        [
            'menus' => $body['menus'] ?? [],
            'submenus' => $body['submenus'] ?? [],
        ],
        (int) $_SESSION['admin_id']
    );
    menu_api_out(['ok' => $ok, 'message' => $ok ? 'Permissions saved.' : 'Save failed.']);
}

if ($action === 'reset') {
    $adminId = (int) ($body['admin_id'] ?? 0);
    if ($adminId <= 0) {
        menu_api_out(['ok' => false, 'message' => 'Invalid admin'], 400);
    }
    $ok = xander_admin_menu_reset_custom($conn, $adminId);
    menu_api_out(['ok' => $ok, 'message' => $ok ? 'Reset to role defaults.' : 'Reset failed.']);
}

menu_api_out(['ok' => false, 'message' => 'Unknown action'], 400);
