<?php
declare(strict_types=1);

require_once __DIR__ . '/prescreening_schema.php';
require_once __DIR__ . '/prescreening_notify.php';

/**
 * @param array<string,string> $fields
 * @param array<string,string> $docPaths
 * @return array{user_id:string,reference:string}
 */
function xander_prescreening_save_submission(
    mysqli $conn,
    string $userId,
    string $source,
    string $studentName,
    string $studentEmail,
    string $whatsapp,
    array $fields,
    array $docPaths,
    ?int $adminId = null,
    bool $notify = true
): array {
    xander_prescreening_ensure_submissions_columns($conn);
    $submittedAt = date('Y-m-d H:i:s');
    $adminId = $adminId !== null && $adminId > 0 ? $adminId : null;

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
        $userId, $source, $studentName, $studentEmail, $whatsapp,
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
        $err = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Save failed: ' . $err);
    }
    $stmt->close();

    $reference = 'PS-' . strtoupper(substr(md5($userId), 0, 8));

    return ['user_id' => $userId, 'reference' => $reference, 'submitted_at' => $submittedAt];
}

/**
 * @param array<string,mixed> $post
 * @param array<string,mixed> $files
 * @return array{fields:array<string,string>,docPaths:array<string,string>,errors:array<int,string>}
 */
function xander_prescreening_parse_form_payload(array $post, array $files, ?string $userIdPrefix = null): array
{
    $errors = [];
    $fields = [
        'education_level' => trim((string) ($post['education_level'] ?? '')),
        'course_program' => trim((string) ($post['course_program'] ?? '')),
        'country_interest' => trim((string) ($post['country_interest'] ?? '')),
        'open_other_countries' => trim((string) ($post['open_other_countries'] ?? '')),
        'budget_tuition' => trim((string) ($post['budget_tuition'] ?? '')),
        'funds_application_visa' => trim((string) ($post['funds_application_visa'] ?? '')),
        'sponsor' => trim((string) ($post['sponsor'] ?? '')),
        'afford_deposit' => trim((string) ($post['afford_deposit'] ?? '')),
        'has_valid_passport' => trim((string) ($post['has_valid_passport'] ?? '')),
        'academic_docs_ready' => trim((string) ($post['academic_docs_ready'] ?? '')),
        'english_level' => trim((string) ($post['english_level'] ?? '')),
        'english_test_taken' => trim((string) ($post['english_test_taken'] ?? '')),
        'visa_denied' => trim((string) ($post['visa_denied'] ?? '')),
        'planned_intake' => trim((string) ($post['planned_intake'] ?? '')),
        'ready_to_apply' => trim((string) ($post['ready_to_apply'] ?? '')),
    ];

    $required = [
        'education_level', 'course_program', 'country_interest', 'budget_tuition',
        'funds_application_visa', 'sponsor', 'afford_deposit', 'has_valid_passport',
        'academic_docs_ready', 'english_level', 'visa_denied', 'planned_intake', 'ready_to_apply',
    ];
    foreach ($required as $rq) {
        if (($fields[$rq] ?? '') === '') {
            $errors[] = 'Please answer all required questions.';
            break;
        }
    }

    $uid = trim((string) ($post['user_id'] ?? ''));
    if ($uid === '' || !preg_match('/^user-[0-9]+-[0-9]+$/', $uid)) {
        $uid = ($userIdPrefix ?? 'user') . '-' . time() . '-' . random_int(1000, 9999);
    }

    $docKeys = array_keys(xander_prescreening_document_labels());
    $uploadDir = dirname(__DIR__) . '/uploads/prescreening/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    $maxSize = 10 * 1024 * 1024;
    $docPaths = [];

    foreach ($docKeys as $docKey) {
        if (!isset($files[$docKey]) || (int) ($files[$docKey]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $docPaths[$docKey] = trim((string) ($post[$docKey . '_existing'] ?? ''));
            continue;
        }
        if ((int) $files[$docKey]['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload failed for ' . $docKey;
            continue;
        }
        $file = $files[$docKey];
        if ((int) $file['size'] > $maxSize) {
            $errors[] = 'File too large (max 10MB).';
            continue;
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            $errors[] = 'Invalid file type for ' . $docKey;
            continue;
        }
        $filename = $uid . '_' . $docKey . '_' . time() . '.' . $ext;
        $dest = $uploadDir . $filename;
        if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
            $errors[] = 'Could not save uploaded file.';
            continue;
        }
        $docPaths[$docKey] = 'uploads/prescreening/' . $filename;
    }

    return ['fields' => $fields, 'docPaths' => $docPaths, 'user_id' => $uid, 'errors' => $errors];
}

/**
 * @return array{ok:bool,path:string,error:string}
 */
function xander_prescreening_store_uploaded_file(array $file, string $userId, string $docKey): array
{
    $docKeys = array_keys(xander_prescreening_document_labels());
    if (!in_array($docKey, $docKeys, true)) {
        return ['ok' => false, 'path' => '', 'error' => 'Invalid document type.'];
    }
    if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'path' => '', 'error' => 'Upload failed.'];
    }
    $maxSize = 10 * 1024 * 1024;
    if ((int) $file['size'] > $maxSize) {
        return ['ok' => false, 'path' => '', 'error' => 'File too large (max 10MB).'];
    }
    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    if (!in_array($ext, $allowedExt, true)) {
        return ['ok' => false, 'path' => '', 'error' => 'Invalid file type.'];
    }
    $uploadDir = dirname(__DIR__) . '/uploads/prescreening/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '', $userId) . '_' . $docKey . '_' . time() . '.' . $ext;
    $dest = $uploadDir . $filename;
    if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
        return ['ok' => false, 'path' => '', 'error' => 'Could not save file.'];
    }

    return ['ok' => true, 'path' => 'uploads/prescreening/' . $filename, 'error' => ''];
}

/**
 * @param array<string,mixed> $invite prescreening_submissions row
 */
function xander_prescreening_persist_document_path(mysqli $conn, array $invite, string $docKey, string $relativePath): bool
{
    $docKeys = array_keys(xander_prescreening_document_labels());
    if (!in_array($docKey, $docKeys, true)) {
        return false;
    }
    $userId = (string) ($invite['user_id'] ?? '');
    if ($userId === '') {
        return false;
    }
    $sql = 'UPDATE prescreening_submissions SET `' . $docKey . '` = ? WHERE user_id = ? AND submitted_at IS NULL LIMIT 1';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ss', $relativePath, $userId);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

/**
 * Merge saved doc paths from DB when not posted (async uploads).
 *
 * @param array<string,mixed> $invite
 * @param array<string,string> $docPaths
 * @return array<string,string>
 */
function xander_prescreening_merge_doc_paths_from_row(array $invite, array $docPaths): array
{
    foreach (array_keys(xander_prescreening_document_labels()) as $key) {
        if (($docPaths[$key] ?? '') === '' && !empty($invite[$key])) {
            $docPaths[$key] = (string) $invite[$key];
        }
    }

    return $docPaths;
}
