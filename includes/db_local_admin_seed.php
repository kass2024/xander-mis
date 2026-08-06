<?php
declare(strict_types=1);

/**
 * Local XAMPP only: ensure at least one active superadmin exists after table repair.
 */
function xander_db_seed_local_admin_if_empty(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        $res = $conn->query('SELECT COUNT(*) AS c FROM admins');
        if (!$res) {
            return;
        }
        $count = (int) ($res->fetch_assoc()['c'] ?? 0);
        $res->free();
        if ($count > 0) {
            return;
        }
    } catch (Throwable $e) {
        error_log('[db_local_admin_seed] count failed: ' . $e->getMessage());
        return;
    }

    $username = 'info@xanderglobalscholars.com';
    $email = 'info@xanderglobalscholars.com';
    $fullName = 'Xander Administrator';
    $role = 'superadmin';
    $status = 'active';
    $hash = password_hash('admin123', PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        'INSERT INTO admins (username, email, full_name, password_hash, role, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())'
    );
    if (!$stmt) {
        error_log('[db_local_admin_seed] prepare failed: ' . $conn->error);
        return;
    }
    $stmt->bind_param('ssssss', $username, $email, $fullName, $hash, $role, $status);
    if (!$stmt->execute()) {
        error_log('[db_local_admin_seed] insert failed: ' . $stmt->error);
        $stmt->close();
        return;
    }
    $adminId = (int) $conn->insert_id;
    $stmt->close();

    error_log("[db_local_admin_seed] created local superadmin id={$adminId} username={$username}");
}
