<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/prescreening_schema.php';
require_once __DIR__ . '/helpers/prescreening_access.php';
require_once __DIR__ . '/helpers/prescreening_apply.php';

xander_prescreening_require_menu_access('prescreening-report.php');
xander_ensure_prescreening_schema($conn);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$token = (string) ($_POST['csrf'] ?? '');
if (!xander_prescreening_verify_csrf($token)) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$deleted = xander_prescreening_delete_submission($conn, $id);

header('Location: prescreening-report.php?' . ($deleted ? 'deleted=1' : 'error=delete_failed'));
exit;
