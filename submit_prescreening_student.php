<?php
declare(strict_types=1);

ob_start();
header('Content-Type: application/json; charset=utf-8');

function student_prescreen_respond(array $data, int $code = 200): void
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        student_prescreen_respond(['status' => 'error', 'message' => 'Invalid method'], 405);
    }

    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/helpers/prescreening_invite.php';
    require_once __DIR__ . '/helpers/prescreening_save.php';
    require_once __DIR__ . '/helpers/prescreening_notify.php';
    require_once __DIR__ . '/helpers/prescreening_whatsapp_flow.php';

    $token = trim((string) ($_POST['token'] ?? ''));
    $invite = xander_prescreening_load_invite_by_token($conn, $token);
    if (!$invite) {
        student_prescreen_respond(['status' => 'error', 'message' => 'Invalid or expired link.'], 404);
    }
    if (!empty($invite['submitted_at'])) {
        student_prescreen_respond(['status' => 'error', 'message' => 'This form was already submitted.']);
    }

    $userId = (string) ($invite['user_id'] ?? '');
    $studentName = trim((string) ($invite['student_name'] ?? ''));
    $studentEmail = trim((string) ($invite['student_email'] ?? ''));
    $whatsapp = trim((string) ($invite['whatsapp_number'] ?? ''));

    $parsed = xander_prescreening_parse_form_payload($_POST, [], 'user');
    if ($parsed['errors'] !== []) {
        student_prescreen_respond(['status' => 'error', 'message' => $parsed['errors'][0]]);
    }
    $parsed['docPaths'] = xander_prescreening_merge_doc_paths_from_row($invite, $parsed['docPaths']);

    $invite = xander_prescreening_load_invite_by_token($conn, $token) ?: $invite;

    $saved = xander_prescreening_save_submission(
        $conn,
        $userId,
        'web_link',
        $studentName,
        $studentEmail,
        $whatsapp,
        $parsed['fields'],
        $parsed['docPaths'],
        null,
        false
    );

    $row = array_merge([
        'student_name' => $studentName,
        'student_email' => $studentEmail,
        'whatsapp_number' => $whatsapp,
        'user_id' => $userId,
    ], $parsed['fields'], $parsed['docPaths']);

    $reference = $saved['reference'];
    $notify = xander_send_prescreening_notifications($row, $reference, true);
    xander_prescreening_notify_staff_whatsapp($row, $reference);

    $emailOk = !empty($notify['email']['admin']);
    $waOk = !empty($notify['whatsapp']['sent']);
    $upd = $conn->prepare('UPDATE prescreening_submissions SET email_sent = ?, whatsapp_sent = ?, notify_errors = ? WHERE user_id = ? LIMIT 1');
    if ($upd) {
        $emailSent = $emailOk ? 1 : 0;
        $waSent = $waOk ? 1 : 0;
        $errJson = (!$emailOk && !$waOk) ? json_encode(['Notification issues'], JSON_UNESCAPED_UNICODE) : null;
        $upd->bind_param('iiss', $emailSent, $waSent, $errJson, $userId);
        $upd->execute();
        $upd->close();
    }

    student_prescreen_respond([
        'status' => 'success',
        'message' => 'Thank you! Your pre-screening has been submitted.',
        'reference' => $reference,
    ]);
} catch (Throwable $e) {
    error_log('[submit_prescreening_student] ' . $e->getMessage());
    student_prescreen_respond(['status' => 'error', 'message' => 'Server error. Please try again.'], 500);
}
