<?php

declare(strict_types=1);



/**

 * Auto-create / repair / sync database objects on db.php connect (once per request).

 */

function xander_db_maybe_auto_schema(mysqli $conn, bool $isLocal = false): void

{

    static $done = false;

    if ($done) {

        return;

    }

    $done = true;

    $light = defined('XANDER_DB_LIGHT') && XANDER_DB_LIGHT;

    require_once __DIR__ . '/db_table_repair.php';

    if (!$light) {
        require_once __DIR__ . '/fee_packages_sync.php';
    }

    require_once __DIR__ . '/contract_branding.php';



    // Repair corrupted InnoDB tables before any schema checks (local: auto-remove orphan .ibd)
    if (!$light) {
        try {
            xander_db_repair_critical_tables($conn, $isLocal);
        } catch (Throwable $e) {
            error_log('[db_auto_schema] table repair: ' . $e->getMessage());
        }
    }

    require_once __DIR__ . '/db_schema_align.php';
    try {
        xander_db_align_core_schema($conn);
    } catch (Throwable $e) {
        error_log('[db_auto_schema] schema align: ' . $e->getMessage());
    }



    if ($isLocal) {

        require_once __DIR__ . '/db_local_admin_seed.php';

        try {

            xander_db_seed_local_admin_if_empty($conn);

        } catch (Throwable $e) {

            error_log('[db_auto_schema] local admin seed: ' . $e->getMessage());

        }

    }



    // Ensure signature/stamp image files are present for contracts

    xander_contract_ensure_branding_assets();



    // Menu Access permissions — always ensure

    $menuHelper = dirname(__DIR__) . '/helpers/admin_menu_permissions.php';

    if (is_readable($menuHelper)) {

        require_once $menuHelper;

        xander_admin_menu_ensure_table($conn);

    }



    $adminSchema = dirname(__DIR__) . '/helpers/admin_schema.php';

    if (is_readable($adminSchema)) {

        require_once $adminSchema;

        try {

            xander_ensure_admins_registration_schema($conn);

        } catch (Throwable $e) {

            error_log('[db_auto_schema] admins schema: ' . $e->getMessage());

        }

    }



    $pwdReset = dirname(__DIR__) . '/helpers/admin_password_reset.php';

    if (is_readable($pwdReset)) {

        require_once $pwdReset;

        try {

            xander_ensure_admin_password_reset_columns($conn);

        } catch (Throwable $e) {

            error_log('[db_auto_schema] admin password reset columns: ' . $e->getMessage());

        }

    }



    $sigSchema = __DIR__ . '/contract_signature_schema.php';

    if (is_readable($sigSchema)) {

        require_once $sigSchema;

        try {

            xander_ensure_contract_signature_columns($conn);

        } catch (Throwable $e) {

            error_log('[db_auto_schema] contract signature columns: ' . $e->getMessage());

            if ($isLocal) {
                xander_db_repair_contract_tables($conn, true);
                try {
                    xander_ensure_contract_signature_columns($conn);
                } catch (Throwable $e2) {
                    error_log('[db_auto_schema] contract signature retry: ' . $e2->getMessage());
                }
            }

        }

    }



    $instSchema = dirname(__DIR__) . '/helpers/institution_portal_schema.php';

    if (is_readable($instSchema)) {

        require_once $instSchema;

        try {

            xander_institution_portal_ensure_schema($conn);

        } catch (Throwable $e) {

            error_log('[db_auto_schema] institution portal: ' . $e->getMessage());

        }

    }



    // Sync contract fee packages → payment portal (skip on light/API requests; throttle on production)
    if (!$light) {
        try {
            $runSync = $isLocal;
            if (!$runSync) {
                $stamp = dirname(__DIR__) . '/uploads/.fee_packages_sync_ts';
                $runSync = !is_file($stamp) || (time() - (int) @filemtime($stamp)) > 3600;
                if ($runSync) {
                    @touch($stamp);
                }
            }
            if ($runSync) {
                xander_sync_fee_packages_from_catalog($conn);
            }
        } catch (Throwable $e) {
            error_log('[db_auto_schema] fee packages sync: ' . $e->getMessage());
        }
    }



    // Pre-screening + WhatsApp tables when XANDER_AUTO_SCHEMA=1 in .env

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

    try {

        xander_ensure_prescreening_schema($conn);

    } catch (Throwable $e) {

        error_log('[db_auto_schema] prescreening schema: ' . $e->getMessage());

        if ($isLocal) {

            xander_db_repair_prescreening_tables($conn, true);

            try {

                xander_ensure_prescreening_schema($conn);

            } catch (Throwable $e2) {

                error_log('[db_auto_schema] prescreening retry: ' . $e2->getMessage());

            }

        }

    }

}


