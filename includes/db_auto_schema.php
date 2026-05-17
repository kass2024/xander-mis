<?php
declare(strict_types=1);

/**
 * Auto-create app tables on db.php connect (once per request).
 */
function xander_db_maybe_auto_schema(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    // Menu Access permissions — always ensure (Menu Access + admin dashboard)
    $menuHelper = dirname(__DIR__) . '/helpers/admin_menu_permissions.php';
    if (is_readable($menuHelper)) {
        require_once $menuHelper;
        xander_admin_menu_ensure_table($conn);
    }

    // Pre-screening tables — only when XANDER_AUTO_SCHEMA=1 in .env
    $envBootstrap = dirname(__DIR__) . '/helpers/env_load.php';
    if (!is_readable($envBootstrap)) {
        return;
    }
    require_once $envBootstrap;
    if (!function_exists('xander_env_is_true') || !xander_env_is_true('XANDER_AUTO_SCHEMA')) {
        return;
    }
    $schema = dirname(__DIR__) . '/helpers/prescreening_schema.php';
    if (!is_readable($schema)) {
        return;
    }
    require_once $schema;
    xander_ensure_prescreening_schema($conn);
}
