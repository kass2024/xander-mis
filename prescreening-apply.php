<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/prescreening_schema.php';
require_once __DIR__ . '/helpers/prescreening_access.php';
require_once __DIR__ . '/helpers/prescreening_apply.php';

xander_prescreening_require_superadmin();
xander_ensure_prescreening_schema($conn);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = xander_prescreening_load_by_id($conn, $id);
if (!$row) {
    http_response_code(404);
    exit('Pre-screening record not found.');
}
if (empty($row['submitted_at'])) {
    http_response_code(400);
    exit('Student has not completed pre-screening yet.');
}

$handoff = xander_prescreening_build_apply_handoff($conn, $row);
$_SESSION['xander_prescreen_handoff'] = $handoff;

$target = 'student-application.php?id=' . rawurlencode($handoff['user_id']) . '&from_prescreen=1';
header('Location: ' . $target);
exit;
