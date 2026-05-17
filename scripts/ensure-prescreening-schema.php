<?php
declare(strict_types=1);

/**
 * Run once after deploy (or in CI) to create/upgrade pre-screening tables.
 *
 *   php scripts/ensure-prescreening-schema.php
 */

$root = dirname(__DIR__);
require_once $root . '/db.php';
require_once $root . '/helpers/prescreening_schema.php';

xander_ensure_prescreening_schema($conn);

echo "OK: prescreening schema ensured.\n";
echo "  - prescreening_submissions\n";
echo "  - prescreening_invites\n";
echo "  - whatsapp_prescreening_sessions\n";
echo "  - whatsapp_inbound_dedup\n";
