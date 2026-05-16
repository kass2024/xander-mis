<?php
declare(strict_types=1);

/**
 * Run prescreening CREATE/ALTER when XANDER_AUTO_SCHEMA=1 in project .env (cPanel deploy).
 */
function xander_db_maybe_auto_schema(mysqli $conn): void
{
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
