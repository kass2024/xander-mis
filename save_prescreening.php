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
    if (empty($_SESSION['id']) && empty($_SESSION['admin_id'])) {
        prescreening_respond(['status' => 'error', 'message' => 'Unauthorized'], 401);
    }

    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/helpers/prescreening_schema.php';
    require_once __DIR__ . '/helpers/prescreening_save.php';
    require_once __DIR__ . '/helpers/prescreening_notify.php';
    require_once __DIR__ . '/helpers/prescreening_whatsapp_flow.php';
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

    $adminId = (int) ($_SESSION['admin_id'] ?? $_SESSION['id'] ?? 0);
    if ($adminId < 0) {
        $adminId = 0;
    }
    $submittedAt = date('Y-m-d H:i:s');

    $sql = "INSERT INTO prescreening_submissions (
        user_id, source, student_name, student_email, whatsapp_number,
        education_level, course_program, country_interest, open_other_countries,
        budget_tuition, funds_application_visa, sponsor, afford_deposit,
        has_valid_passport, academic_docs_ready, english_level, english_test_taken,
        visa_denied, planned_intake, ready_to_apply,
        doc_valid_passport, doc_degree_transcripts, doc_high_school, doc_cv_resume,
        doc_recommendation, doc_personal_statement, doc_english_certificate,
        doc_birth_certificate, doc_payment_proof,
        submitted_by_admin_id, submitted_at
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
        source=VALUES(source),
        student_name=VALUES(student_name),
        student_email=VALUES(student_email),
        whatsapp_number=VALUES(whatsapp_number),
        education_level=VALUES(education_level),
        course_program=VALUES(course_program),
        country_interest=VALUES(country_interest),
        open_other_countries=VALUES(open_other_countries),
        budget_tuition=VALUES(budget_tuition),
        funds_application_visa=VALUES(funds_application_visa),
        sponsor=VALUES(sponsor),
        afford_deposit=VALUES(afford_deposit),
        has_valid_passport=VALUES(has_valid_passport),
        academic_docs_ready=VALUES(academic_docs_ready),
        english_level=VALUES(english_level),
        english_test_taken=VALUES(english_test_taken),
        visa_denied=VALUES(visa_denied),
        planned_intake=VALUES(planned_intake),
        ready_to_apply=VALUES(ready_to_apply),
        doc_valid_passport=VALUES(doc_valid_passport),
        doc_degree_transcripts=VALUES(doc_degree_transcripts),
        doc_high_school=VALUES(doc_high_school),
        doc_cv_resume=VALUES(doc_cv_resume),
        doc_recommendation=VALUES(doc_recommendation),
        doc_personal_statement=VALUES(doc_personal_statement),
        doc_english_certificate=VALUES(doc_english_certificate),
        doc_birth_certificate=VALUES(doc_birth_certificate),
        doc_payment_proof=VALUES(doc_payment_proof),
        submitted_by_admin_id=VALUES(submitted_by_admin_id),
        submitted_at=VALUES(submitted_at)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }

    $bind = [
        $userId, 'admin', $studentName, $studentEmail, $whatsapp,
        $fields['education_level'], $fields['course_program'], $fields['country_interest'], $fields['open_other_countries'],
        $fields['budget_tuition'], $fields['funds_application_visa'], $fields['sponsor'], $fields['afford_deposit'],
        $fields['has_valid_passport'], $fields['academic_docs_ready'], $fields['english_level'], $fields['english_test_taken'],
        $fields['visa_denied'], $fields['planned_intake'], $fields['ready_to_apply'],
        $docPaths['doc_valid_passport'] ?? '',
        $docPaths['doc_degree_transcripts'] ?? '',
        $docPaths['doc_high_school'] ?? '',
        $docPaths['doc_cv_resume'] ?? '',
        $docPaths['doc_recommendation'] ?? '',
        $docPaths['doc_personal_statement'] ?? '',
        $docPaths['doc_english_certificate'] ?? '',
        $docPaths['doc_birth_certificate'] ?? '',
        $docPaths['doc_payment_proof'] ?? '',
        $adminId,
        $submittedAt,
    ];

    $types = str_repeat('s', 29) . 'is';
    $stmt->bind_param($types, ...$bind);
    if (!$stmt->execute()) {
        throw new RuntimeException('Save failed: ' . $stmt->error);
    }
    $stmt->close();

    $row = array_merge([
        'student_name' => $studentName,
        'student_email' => $studentEmail,
        'whatsapp_number' => $whatsapp,
        'user_id' => $userId,
    ], $fields, $docPaths);

    $reference = 'PS-' . strtoupper(substr(md5($userId), 0, 8));
    $notify = xander_send_prescreening_notifications($row, $reference);
    $staffWa = xander_prescreening_notify_staff_whatsapp($row, $reference);

    $emailOk = !empty($notify['email']['admin']);
    $waOk = !empty($notify['whatsapp']['sent']);
    $errors = [];
    if (!$emailOk) {
        $errors[] = 'Admin email could not be sent.';
    }
    if (!$waOk) {
        $errors[] = (string) ($notify['whatsapp']['error'] ?? 'WhatsApp could not be sent.');
    }

    $upd = $conn->prepare('UPDATE prescreening_submissions SET email_sent = ?, whatsapp_sent = ?, notify_errors = ? WHERE user_id = ? LIMIT 1');
    if ($upd) {
        $emailSent = $emailOk ? 1 : 0;
        $waSent = $waOk ? 1 : 0;
        $errJson = $errors !== [] ? json_encode($errors, JSON_UNESCAPED_UNICODE) : null;
        $upd->bind_param('iiss', $emailSent, $waSent, $errJson, $userId);
        $upd->execute();
        $upd->close();
    }

    prescreening_respond([
        'status' => ($emailOk || $waOk) ? 'success' : 'partial',
        'message' => $waOk && $emailOk
            ? 'Pre-screening sent to email and WhatsApp successfully.'
            : ($errors !== [] ? implode(' ', $errors) : 'Saved with notification issues.'),
        'user_id' => $userId,
        'reference' => $reference,
        'email' => $notify['email'],
        'whatsapp' => $notify['whatsapp'],
        'staff_whatsapp' => $staffWa,
    ]);
} catch (Throwable $e) {
    error_log('[save_prescreening] ' . $e->getMessage());
    prescreening_respond(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()], 500);
}
