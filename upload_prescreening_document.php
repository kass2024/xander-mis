<?php
declare(strict_types=1);

ob_start();
header('Content-Type: application/json; charset=utf-8');

function doc_upload_respond(array $data, int $code = 200): void
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    doc_upload_respond(['status' => 'error', 'message' => 'Invalid method'], 405);
}

try {
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/helpers/prescreening_invite.php';
    require_once __DIR__ . '/helpers/prescreening_save.php';
    require_once __DIR__ . '/helpers/prescreening_notify.php';

    $token = trim((string) ($_POST['token'] ?? ''));
    $docKey = trim((string) ($_POST['doc_key'] ?? ''));
    $invite = null;

    if ($token !== '') {
        $invite = xander_prescreening_load_invite_by_token($conn, $token);
    } else {
        session_start();
        $userId = trim((string) ($_POST['user_id'] ?? ''));
        if (!empty($_SESSION['admin_id']) && $userId !== '' && preg_match('/^user-[0-9]+-[0-9]+$/', $userId)) {
            $stmt = $conn->prepare('SELECT * FROM prescreening_submissions WHERE user_id = ? LIMIT 1');
            $stmt->bind_param('s', $userId);
            $stmt->execute();
            $invite = $stmt->get_result()->fetch_assoc() ?: null;
            $stmt->close();
        }
    }

    if (!$invite) {
        doc_upload_respond(['status' => 'error', 'message' => 'Invalid or expired session.'], 403);
    }
    if (!empty($invite['submitted_at'])) {
        doc_upload_respond(['status' => 'error', 'message' => 'This form is already submitted.']);
    }

    if (!isset($_FILES['file'])) {
        doc_upload_respond(['status' => 'error', 'message' => 'No file received.']);
    }

    $stored = xander_prescreening_store_uploaded_file($_FILES['file'], (string) $invite['user_id'], $docKey);
    if (!$stored['ok']) {
        doc_upload_respond(['status' => 'error', 'message' => $stored['error']], 400);
    }

    if (!xander_prescreening_persist_document_path($conn, $invite, $docKey, $stored['path'])) {
        doc_upload_respond(['status' => 'error', 'message' => 'Could not save document reference.'], 500);
    }

    doc_upload_respond([
        'status' => 'success',
        'message' => 'Document saved.',
        'doc_key' => $docKey,
        'path' => $stored['path'],
    ]);
} catch (Throwable $e) {
    error_log('[upload_prescreening_document] ' . $e->getMessage());
    doc_upload_respond(['status' => 'error', 'message' => 'Server error.'], 500);
}
