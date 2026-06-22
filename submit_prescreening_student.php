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

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/helpers/prescreening_invite.php';
    require_once __DIR__ . '/helpers/prescreening_save.php';
    require_once __DIR__ . '/helpers/prescreening_async_notify.php';

    $token = trim((string) ($_POST['token'] ?? ''));
    $postedUserId = trim((string) ($_POST['user_id'] ?? ''));
    $invite = null;
    $isPublic = ($token === '');

    if ($token !== '') {
        $invite = xander_prescreening_load_invite_by_token($conn, $token);
        if (!$invite) {
            student_prescreen_respond(['status' => 'error', 'message' => 'Invalid or expired link.'], 404);
        }
    } elseif ($postedUserId !== '' && preg_match('/^user-[0-9]+-[0-9]+$/', $postedUserId)) {
        if (($_SESSION['prescreen_student_draft_user_id'] ?? '') !== $postedUserId) {
            student_prescreen_respond(['status' => 'error', 'message' => 'Session expired. Please refresh the page and try again.'], 403);
        }
        xander_prescreening_ensure_public_draft($conn, $postedUserId);
        $invite = xander_prescreening_load_draft_by_user_id($conn, $postedUserId);
        if (!$invite) {
            student_prescreen_respond(['status' => 'error', 'message' => 'Could not start your session. Please refresh and try again.'], 500);
        }
    } else {
        student_prescreen_respond(['status' => 'error', 'message' => 'Invalid session. Please refresh the page.'], 400);
    }

    $userId = (string) ($invite['user_id'] ?? '');
    $already = $conn->prepare(
        'SELECT id FROM prescreening_submissions WHERE user_id = ? AND submitted_at IS NOT NULL LIMIT 1'
    );
    if ($already) {
        $already->bind_param('s', $userId);
        $already->execute();
        if ($already->get_result()->fetch_row()) {
            $already->close();
            student_prescreen_respond(['status' => 'error', 'message' => 'This form was already submitted.']);
        }
        $already->close();
    }

    $studentName = trim((string) ($_POST['student_name'] ?? $invite['student_name'] ?? ''));
    $studentEmail = trim((string) ($_POST['student_email'] ?? $invite['student_email'] ?? ''));
    $whatsapp = trim((string) ($_POST['whatsapp_number'] ?? $invite['whatsapp_number'] ?? ''));

    if ($studentName === '') {
        student_prescreen_respond(['status' => 'error', 'message' => 'Please enter your full name.']);
    }
    if ($studentEmail === '' || !filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
        student_prescreen_respond(['status' => 'error', 'message' => 'Please enter a valid email address.']);
    }
    if ($whatsapp === '') {
        student_prescreen_respond(['status' => 'error', 'message' => 'Please enter your WhatsApp number (with country code).']);
    }

    $parsed = xander_prescreening_parse_form_payload($_POST, $_FILES, 'user');
    if ($parsed['errors'] !== []) {
        student_prescreen_respond(['status' => 'error', 'message' => $parsed['errors'][0]]);
    }
    $parsed['docPaths'] = xander_prescreening_merge_doc_paths_from_row($invite, $parsed['docPaths']);

    $invite = ($token !== '' ? xander_prescreening_load_invite_by_token($conn, $token) : xander_prescreening_load_draft_by_user_id($conn, $userId)) ?: $invite;

    $saved = xander_prescreening_save_submission(
        $conn,
        $userId,
        $isPublic ? 'public_link' : 'web_link',
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

    if ($isPublic) {
        unset($_SESSION['prescreen_student_draft_user_id']);
    }

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
