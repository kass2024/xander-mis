<?php
declare(strict_types=1);

/**
 * Ensure admins table has columns required for self-registration + approval workflow.
 */
function xander_ensure_admins_registration_schema(mysqli $conn): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;

    $res = @$conn->query("SHOW COLUMNS FROM `admins` LIKE 'status'");
    if ($res && $res->num_rows > 0) {
        $res->free();
        return;
    }
    if ($res) {
        $res->free();
    }

    $sql = "ALTER TABLE `admins`
            ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'pending'
            AFTER `role`";
    if (!@$conn->query($sql)) {
        error_log('[admin_schema] Failed to add admins.status: ' . $conn->error);
    }
}
