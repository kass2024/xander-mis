<?php
declare(strict_types=1);

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json; charset=utf-8');
session_start();

function prescreening_respond(array $data, int $code = 200): void
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    if (empty($_SESSION['admin_id'])) {
        prescreening_respond(['status' => 'error', 'message' => 'Unauthorized'], 401);
    }

    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/helpers/prescreening_access.php';
    if (!xander_prescreening_has_menu_access($conn, 'prescreening.php')) {
        prescreening_respond(['status' => 'error', 'message' => 'You do not have access to Pre-screening.'], 403);
    }
    require_once __DIR__ . '/helpers/prescreening_schema.php';
    require_once __DIR__ . '/helpers/prescreening_invite.php';
    require_once __DIR__ . '/helpers/prescreening_save.php';
    require_once __DIR__ . '/helpers/prescreening_async_notify.php';
    require_once __DIR__ . '/helpers/prescreening_whatsapp_schema.php';

    if (!isset($conn) || !$conn) {
        throw new RuntimeException('Database connection failed');
    }

    xander_ensure_prescreening_whatsapp_tables($conn);

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        prescreening_respond(['status' => 'error', 'message' => 'Invalid method'], 405);
    }

    $userId = trim((string) ($_POST['user_id'] ?? ''));
    if ($userId === '' || !preg_match('/^user-[0-9]+-[0-9]+$/', $userId)) {
        $userId = 'user-' . time() . '-' . random_int(1000, 9999);
    }

    $studentName = trim((string) ($_POST['student_name'] ?? ''));
    $studentEmail = trim((string) ($_POST['student_email'] ?? ''));
    $whatsapp = trim((string) ($_POST['whatsapp_number'] ?? ''));

    if ($studentName === '') {
        prescreening_respond(['status' => 'error', 'message' => 'Student name is required.']);
    }
    if ($whatsapp === '') {
        prescreening_respond(['status' => 'error', 'message' => 'Student WhatsApp number is required.']);
    }
    if ($studentEmail !== '' && !filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
        prescreening_respond(['status' => 'error', 'message' => 'Invalid student email address.']);
    }

    $parsed = xander_prescreening_parse_form_payload($_POST, $_FILES, 'user');
    if ($parsed['errors'] !== []) {
        prescreening_respond(['status' => 'error', 'message' => $parsed['errors'][0]]);
    }
    $fields = $parsed['fields'];
    $docPaths = $parsed['docPaths'];
    $existingRow = null;
    $load = $conn->prepare('SELECT * FROM prescreening_submissions WHERE user_id = ? LIMIT 1');
    if ($load) {
        $load->bind_param('s', $userId);
        $load->execute();
        $existingRow = $load->get_result()->fetch_assoc();
        $load->close();
    }
    if (is_array($existingRow)) {
        $docPaths = xander_prescreening_merge_doc_paths_from_row($existingRow, $docPaths);
    }
    $draftInvite = xander_prescreening_load_draft_by_user_id($conn, $userId);
    if (is_array($draftInvite)) {
        $docPaths = xander_prescreening_merge_doc_paths_from_row($draftInvite, $docPaths);
    }

    $adminId = (int) ($_SESSION['admin_id'] ?? $_SESSION['id'] ?? 0);
    if ($adminId < 0) {
        $adminId = 0;
    }

    $saved = xander_prescreening_save_submission(
        $conn,
        $userId,
        'admin',
        $studentName,
        $studentEmail,
        $whatsapp,
        $fields,
        $docPaths,
        $adminId > 0 ? $adminId : null,
        false
    );

    $row = array_merge([
        'student_name' => $studentName,
        'student_email' => $studentEmail,
        'whatsapp_number' => $whatsapp,
        'user_id' => $userId,
    ], $fields, $docPaths);

    $reference = $saved['reference'];

    xander_prescreening_delete_invite($conn, $userId);
    unset($_SESSION['prescreen_admin_draft_user_id']);

    xander_prescreening_flush_json_and_notify(
        [
            'status' => 'success',
            'message' => 'Pre-screening saved. Email and WhatsApp notifications are being sent.',
            'user_id' => $userId,
            'reference' => $reference,
        ],
        $conn,
        $row,
        $reference,
        $userId,
        false
    );
} catch (Throwable $e) {
    error_log('[save_prescreening] ' . $e->getMessage());
    prescreening_respond(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()], 500);
}
