<?php
declare(strict_types=1);

ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();

function link_email_respond(array $data, int $code = 200): void
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['admin_id'])) {
    link_email_respond(['status' => 'error', 'message' => 'Unauthorized'], 401);
}
require_once __DIR__ . '/helpers/role.php';
if (!pcvc_is_superadmin_role($_SESSION['role'] ?? '')) {
    link_email_respond(['status' => 'error', 'message' => 'Superadmin only'], 403);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    link_email_respond(['status' => 'error', 'message' => 'Invalid method'], 405);
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/prescreening_invite.php';

$token = trim((string) ($_POST['token'] ?? ''));
$row = xander_prescreening_load_invite_by_token($conn, $token);
if (!$row) {
    link_email_respond(['status' => 'error', 'message' => 'Invite not found.'], 404);
}

$url = xander_prescreening_invite_url($token);
$res = xander_prescreening_send_invite_email(
    (string) ($row['student_email'] ?? ''),
    (string) ($row['student_name'] ?? ''),
    $url
);

if (!$res['ok']) {
    link_email_respond(['status' => 'error', 'message' => $res['error']], 400);
}

link_email_respond(['status' => 'success', 'message' => 'Email sent.', 'link' => $url]);
