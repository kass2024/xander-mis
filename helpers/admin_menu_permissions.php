<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_menu_registry.php';
require_once __DIR__ . '/role.php';

function xander_admin_menu_ensure_table(mysqli $conn): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;

    $sql = "CREATE TABLE IF NOT EXISTS admin_menu_permissions (
        admin_id INT UNSIGNED NOT NULL,
        permissions JSON NOT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        updated_by INT UNSIGNED NULL DEFAULT NULL,
        PRIMARY KEY (admin_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!$conn->query($sql)) {
        error_log('[admin_menu_permissions] CREATE TABLE failed: ' . $conn->error);
        return;
    }

    // Verify table exists (some hosts silently ignore CREATE)
    $check = $conn->query(
        "SELECT 1 FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_menu_permissions' LIMIT 1"
    );
    if (!$check || $check->num_rows === 0) {
        error_log('[admin_menu_permissions] Table admin_menu_permissions missing after CREATE');
    }
    if ($check) {
        $check->free();
    }
}

/**
 * @return array{menus: list<string>, submenus: array<string, list<string>>, is_custom: bool, source: string}
 */
function xander_admin_menu_permissions_from_role(string $role): array
{
    $registry = xander_admin_menu_registry();
    $defaults = xander_admin_menu_role_defaults();
    $roleKey = $role;
    $menus = $defaults[$roleKey] ?? $defaults['standard'] ?? [];
    $submenus = [];
    foreach ($menus as $menuKey) {
        if (!isset($registry[$menuKey]['links'])) {
            continue;
        }
        $submenus[$menuKey] = array_keys($registry[$menuKey]['links']);
    }

    return [
        'menus' => array_values($menus),
        'submenus' => $submenus,
        'is_custom' => false,
        'source' => 'role:' . $role,
    ];
}

/**
 * @return array{menus: list<string>, submenus: array<string, list<string>>, is_custom: bool, source: string}|null
 */
function xander_admin_menu_get_custom(mysqli $conn, int $adminId): ?array
{
    xander_admin_menu_ensure_table($conn);
    $stmt = $conn->prepare('SELECT permissions FROM admin_menu_permissions WHERE admin_id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row || empty($row['permissions'])) {
        return null;
    }
    $data = json_decode((string) $row['permissions'], true);
    if (!is_array($data)) {
        return null;
    }
    $menus = array_values(array_filter(array_map('strval', $data['menus'] ?? [])));
    $submenus = [];
    if (isset($data['submenus']) && is_array($data['submenus'])) {
        foreach ($data['submenus'] as $menuKey => $files) {
            if (!is_array($files)) {
                continue;
            }
            $submenus[(string) $menuKey] = array_values(array_filter(array_map('strval', $files)));
        }
    }

    return [
        'menus' => $menus,
        'submenus' => $submenus,
        'is_custom' => true,
        'source' => 'custom',
    ];
}

/**
 * @param array{menus?:array,submenus?:array} $payload
 */
function xander_admin_menu_save_custom(mysqli $conn, int $adminId, array $payload, int $updatedBy): bool
{
    xander_admin_menu_ensure_table($conn);
    $registry = xander_admin_menu_registry();
    $menus = [];
    $submenus = [];

    foreach ($payload['menus'] ?? [] as $menuKey) {
        $menuKey = (string) $menuKey;
        if (isset($registry[$menuKey])) {
            $menus[] = $menuKey;
        }
    }

    foreach ($payload['submenus'] ?? [] as $menuKey => $files) {
        if (!isset($registry[$menuKey]) || !is_array($files)) {
            continue;
        }
        $allowedFiles = array_keys($registry[$menuKey]['links']);
        $picked = [];
        foreach ($files as $file) {
            $file = (string) $file;
            if (in_array($file, $allowedFiles, true)) {
                $picked[] = $file;
            }
        }
        if ($picked !== []) {
            $submenus[$menuKey] = $picked;
        }
    }

  // Drop menus with zero allowed submenus when submenus were specified
    foreach ($menus as $i => $menuKey) {
        if (isset($submenus[$menuKey]) && $submenus[$menuKey] === []) {
            unset($menus[$i]);
        }
    }
    $menus = array_values($menus);

    $json = json_encode(['menus' => $menus, 'submenus' => $submenus], JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }

    $stmt = $conn->prepare(
        'INSERT INTO admin_menu_permissions (admin_id, permissions, updated_by)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE permissions = VALUES(permissions), updated_by = VALUES(updated_by)'
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('isi', $adminId, $json, $updatedBy);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function xander_admin_menu_reset_custom(mysqli $conn, int $adminId): bool
{
    xander_admin_menu_ensure_table($conn);
    $stmt = $conn->prepare('DELETE FROM admin_menu_permissions WHERE admin_id = ?');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $ok = $stmt->affected_rows >= 0;
    $stmt->close();

    return $ok;
}

/**
 * Resolved access for logged-in admin.
 *
 * @param array<string,mixed> $adminRow
 * @return array{menus: list<string>, submenus: array<string, list<string>>, is_custom: bool, source: string}
 */
function xander_admin_menu_resolve(mysqli $conn, array $adminRow): array
{
    $role = (string) ($adminRow['role'] ?? 'standard');
    if (pcvc_is_superadmin_role($role)) {
        $registry = xander_admin_menu_registry();
        $menus = array_keys($registry);
        $submenus = [];
        foreach ($registry as $menuKey => $def) {
            $submenus[$menuKey] = array_keys($def['links']);
        }

        return [
            'menus' => $menus,
            'submenus' => $submenus,
            'is_custom' => false,
            'source' => 'superadmin',
        ];
    }

    $adminId = (int) ($adminRow['id'] ?? 0);
    if ($adminId > 0) {
        $custom = xander_admin_menu_get_custom($conn, $adminId);
        if ($custom !== null) {
            return $custom;
        }
    }

    return xander_admin_menu_permissions_from_role($role);
}

function xander_admin_menu_allowed(array $access, string $menuKey): bool
{
    return in_array($menuKey, $access['menus'] ?? [], true);
}

function xander_admin_submenu_allowed(array $access, string $menuKey, string $file): bool
{
    if (!xander_admin_menu_allowed($access, $menuKey)) {
        return false;
    }
    $subs = $access['submenus'][$menuKey] ?? null;
    if ($subs === null) {
        return true;
    }

    return in_array($file, $subs, true);
}

/**
 * @return list<array{id:int,username:string,email:string,role:string,name:string,is_superadmin:bool}>
 */
function xander_admin_menu_list_admins(mysqli $conn): array
{
    $out = [];
    $res = $conn->query(
        "SELECT id, username, email, role,
                TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) AS name
         FROM admins
         WHERE COALESCE(status, 'active') != 'disabled'
         ORDER BY role ASC, name ASC, username ASC"
    );
    if (!$res) {
        return $out;
    }
    while ($row = $res->fetch_assoc()) {
        $out[] = [
            'id' => (int) $row['id'],
            'username' => (string) ($row['username'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'role' => (string) ($row['role'] ?? ''),
            'name' => trim((string) ($row['name'] ?? '')) ?: (string) ($row['username'] ?? ''),
            'is_superadmin' => pcvc_is_superadmin_role($row['role'] ?? ''),
        ];
    }
    $res->free();

    return $out;
}
