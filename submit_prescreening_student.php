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
    require_once __DIR__ . '/helpers/prescreening_async_notify.php';

    $token = trim((string) ($_POST['token'] ?? ''));
    $invite = xander_prescreening_load_invite_by_token($conn, $token);
    if (!$invite) {
        student_prescreen_respond(['status' => 'error', 'message' => 'Invalid or expired link.'], 404);
    }
    $already = $conn->prepare(
        'SELECT id FROM prescreening_submissions WHERE user_id = ? AND submitted_at IS NOT NULL LIMIT 1'
    );
    if ($already) {
        $uidCheck = (string) ($invite['user_id'] ?? '');
        $already->bind_param('s', $uidCheck);
        $already->execute();
        if ($already->get_result()->fetch_row()) {
            $already->close();
            student_prescreen_respond(['status' => 'error', 'message' => 'This form was already submitted.']);
        }
        $already->close();
    }

    $userId = (string) ($invite['user_id'] ?? '');
    $studentName = trim((string) ($invite['student_name'] ?? ''));
    $studentEmail = trim((string) ($invite['student_email'] ?? ''));
    $whatsapp = trim((string) ($invite['whatsapp_number'] ?? ''));

    $parsed = xander_prescreening_parse_form_payload($_POST, $_FILES, 'user');
    if (($parsed['fields']['service_type'] ?? '') === 'work_abroad') {
        $studentName = trim((string) ($_POST['student_name'] ?? $studentName));
        $studentEmail = trim((string) ($_POST['student_email'] ?? $studentEmail));
        $whatsapp = trim((string) ($_POST['whatsapp_number'] ?? $whatsapp));
    }
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

    if ($token !== '') {
        $tokUp = $conn->prepare(
            'UPDATE prescreening_submissions SET invite_token = ? WHERE user_id = ? LIMIT 1'
        );
        if ($tokUp) {
            $tokUp->bind_param('ss', $token, $userId);
            $tokUp->execute();
            $tokUp->close();
        }
    }
    xander_prescreening_delete_invite($conn, $userId);

    $row = array_merge([
        'student_name' => $studentName,
        'student_email' => $studentEmail,
        'whatsapp_number' => $whatsapp,
        'user_id' => $userId,
    ], $parsed['fields'], $parsed['docPaths']);

    $reference = $saved['reference'];

    xander_prescreening_flush_json_and_notify(
        [
            'status' => 'success',
            'message' => 'Thank you! Your pre-screening has been submitted.',
            'reference' => $reference,
        ],
        $conn,
        $row,
        $reference,
        $userId,
        true
    );
} catch (Throwable $e) {
    error_log('[submit_prescreening_student] ' . $e->getMessage());
    student_prescreen_respond(['status' => 'error', 'message' => 'Server error. Please try again.'], 500);
}
