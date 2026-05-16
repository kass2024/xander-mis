<?php
declare(strict_types=1);

function pcvc_has_assigned_admin_column(mysqli $conn): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $r = $conn->query("SHOW COLUMNS FROM student_applications LIKE 'assigned_to_admin_id'");
    $cache = $r && $r->num_rows > 0;

    return $cache;
}

function pcvc_ensure_assigned_admin_column(mysqli $conn): bool
{
    if (pcvc_has_assigned_admin_column($conn)) {
        return true;
    }

    return (bool) $conn->query(
        'ALTER TABLE student_applications
         ADD COLUMN assigned_to_admin_id INT UNSIGNED NULL DEFAULT NULL'
    );
}
