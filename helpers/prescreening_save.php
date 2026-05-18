<?php
declare(strict_types=1);

require_once __DIR__ . '/prescreening_schema.php';
require_once __DIR__ . '/prescreening_notify.php';
require_once __DIR__ . '/prescreening_options.php';
require_once __DIR__ . '/prescreening_work_profile.php';

/**
 * @param array<string,string> $fields
 * @param array<string,string> $docPaths
 * @return array{user_id:string,reference:string,submitted_at:string}
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

    $serviceType = (string) ($fields['service_type'] ?? 'study_abroad');
    if (!in_array($serviceType, ['study_abroad', 'work_abroad'], true)) {
        $serviceType = 'study_abroad';
    }

    $sql = "INSERT INTO prescreening_submissions (
        user_id, source, student_name, student_email, whatsapp_number,
        service_type, applicant_address, work_country_destination, work_emergency_contact, work_profile_json, work_docs_checklist,
        education_level, course_program, country_interest, open_other_countries,
        budget_tuition, funds_application_visa, sponsor, afford_deposit,
        has_valid_passport, academic_docs_ready, english_level, english_test_taken,
        visa_denied, planned_intake, ready_to_apply,
        doc_valid_passport, doc_degree_transcripts, doc_high_school, doc_cv_resume,
        doc_recommendation, doc_personal_statement, doc_english_certificate,
        doc_birth_certificate, doc_passport_photo, doc_payment_proof,
        submitted_by_admin_id, submitted_at
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
        source=VALUES(source),
        student_name=VALUES(student_name),
        student_email=VALUES(student_email),
        whatsapp_number=VALUES(whatsapp_number),
        service_type=VALUES(service_type),
        applicant_address=VALUES(applicant_address),
        work_country_destination=VALUES(work_country_destination),
        work_emergency_contact=VALUES(work_emergency_contact),
        work_profile_json=VALUES(work_profile_json),
        work_docs_checklist=VALUES(work_docs_checklist),
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
        doc_passport_photo=VALUES(doc_passport_photo),
        doc_payment_proof=VALUES(doc_payment_proof),
        submitted_by_admin_id=VALUES(submitted_by_admin_id),
        submitted_at=VALUES(submitted_at)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }

    $bind = [
        $userId, $source, $studentName, $studentEmail, $whatsapp,
        $serviceType,
        (string) ($fields['applicant_address'] ?? ''),
        (string) ($fields['work_country_destination'] ?? ''),
        (string) ($fields['work_emergency_contact'] ?? ''),
        (string) ($fields['work_profile_json'] ?? ''),
        (string) ($fields['work_docs_checklist'] ?? ''),
        (string) ($fields['education_level'] ?? ''),
        (string) ($fields['course_program'] ?? ''),
        (string) ($fields['country_interest'] ?? ''),
        (string) ($fields['open_other_countries'] ?? ''),
        (string) ($fields['budget_tuition'] ?? ''),
        (string) ($fields['funds_application_visa'] ?? ''),
        (string) ($fields['sponsor'] ?? ''),
        (string) ($fields['afford_deposit'] ?? ''),
        (string) ($fields['has_valid_passport'] ?? ''),
        (string) ($fields['academic_docs_ready'] ?? ''),
        (string) ($fields['english_level'] ?? ''),
        (string) ($fields['english_test_taken'] ?? ''),
        (string) ($fields['visa_denied'] ?? ''),
        (string) ($fields['planned_intake'] ?? ''),
        (string) ($fields['ready_to_apply'] ?? ''),
        (string) ($docPaths['doc_valid_passport'] ?? ''),
        (string) ($docPaths['doc_degree_transcripts'] ?? ''),
        (string) ($docPaths['doc_high_school'] ?? ''),
        (string) ($docPaths['doc_cv_resume'] ?? ''),
        (string) ($docPaths['doc_recommendation'] ?? ''),
        (string) ($docPaths['doc_personal_statement'] ?? ''),
        (string) ($docPaths['doc_english_certificate'] ?? ''),
        (string) ($docPaths['doc_birth_certificate'] ?? ''),
        (string) ($docPaths['doc_passport_photo'] ?? ''),
        (string) ($docPaths['doc_payment_proof'] ?? ''),
        $adminId,
        $submittedAt,
    ];
    $types = str_repeat('s', 36) . 'is';
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
 * @return array<string,string>
 */
function xander_prescreening_empty_study_fields(): array
{
    return [
        'education_level' => '',
        'course_program' => '',
        'country_interest' => '',
        'open_other_countries' => '',
        'budget_tuition' => '',
        'funds_application_visa' => '',
        'sponsor' => '',
        'afford_deposit' => '',
        'has_valid_passport' => '',
        'academic_docs_ready' => '',
        'english_level' => '',
        'english_test_taken' => '',
        'visa_denied' => '',
        'planned_intake' => '',
        'ready_to_apply' => '',
    ];
}

/**
 * @return array<string,string>
 */
function xander_prescreening_empty_work_fields(): array
{
    return [
        'applicant_address' => '',
        'work_country_destination' => '',
        'work_emergency_contact' => '',
        'work_profile_json' => '',
        'work_docs_checklist' => '',
    ];
}

/**
 * @param array<string,mixed> $post
 * @param array<string,mixed> $files
 * @return array{fields:array<string,string>,docPaths:array<string,string>,user_id:string,errors:array<int,string>}
 */
function xander_prescreening_parse_form_payload(array $post, array $files, ?string $userIdPrefix = null): array
{
    $errors = [];
    $serviceType = trim((string) ($post['service_type'] ?? ''));
    if (!in_array($serviceType, ['study_abroad', 'work_abroad'], true)) {
        $errors[] = 'Please select the type of service you need (Study Abroad or Work Abroad).';
    }

    $fields = array_merge(
        ['service_type' => $serviceType],
        xander_prescreening_empty_study_fields(),
        xander_prescreening_empty_work_fields()
    );

    if ($serviceType === 'work_abroad') {
        $fields['applicant_address'] = trim((string) ($post['applicant_address'] ?? ''));
        $workCountries = xander_prescreening_parse_country_list_from_post(
            $post,
            'work_country_destination',
            'work_country_destination'
        );
        if (count($workCountries) < 2) {
            $errors[] = 'Please select at least two countries of interest for work abroad.';
        }
        $fields['work_country_destination'] = xander_prescreening_format_country_list($workCountries);

        $profile = xander_prescreening_work_profile_pack($post);
        $fields['work_profile_json'] = json_encode($profile, JSON_UNESCAPED_UNICODE);
        $fields['work_emergency_contact'] = xander_prescreening_work_profile_summary($profile);
        if ($fields['work_emergency_contact'] === '') {
            $fields['work_emergency_contact'] = trim((string) ($post['work_emergency_contact'] ?? ''));
        }

        $checklist = $post['work_checklist'] ?? [];
        if (!is_array($checklist)) {
            $checklist = [];
        }
        $labels = xander_prescreening_work_checklist_labels();
        $picked = [];
        foreach ($checklist as $key) {
            $key = (string) $key;
            if (isset($labels[$key])) {
                $picked[] = $labels[$key];
            }
        }
        $fields['work_docs_checklist'] = $picked !== []
            ? json_encode($picked, JSON_UNESCAPED_UNICODE)
            : '';
    } else {
        $fields['education_level'] = trim((string) ($post['education_level'] ?? ''));
        $fields['course_program'] = trim((string) ($post['course_program'] ?? ''));
        $studyCountries = xander_prescreening_parse_country_list_from_post(
            $post,
            'country_interest',
            'country_interest'
        );
        if (count($studyCountries) < 2) {
            $errors[] = 'Please select at least two countries of interest.';
        }
        $fields['country_interest'] = xander_prescreening_format_country_list($studyCountries);
        $fields['open_other_countries'] = trim((string) ($post['open_other_countries'] ?? ''));
        $fields['budget_tuition'] = trim((string) ($post['budget_tuition'] ?? ''));
        $fields['funds_application_visa'] = trim((string) ($post['funds_application_visa'] ?? ''));
        $fields['sponsor'] = trim((string) ($post['sponsor'] ?? ''));
        $fields['afford_deposit'] = trim((string) ($post['afford_deposit'] ?? ''));
        $fields['has_valid_passport'] = trim((string) ($post['has_valid_passport'] ?? ''));
        $fields['academic_docs_ready'] = trim((string) ($post['academic_docs_ready'] ?? ''));
        $fields['english_level'] = trim((string) ($post['english_level'] ?? ''));
        $fields['english_test_taken'] = trim((string) ($post['english_test_taken'] ?? ''));
        $fields['visa_denied'] = trim((string) ($post['visa_denied'] ?? ''));
        $fields['planned_intake'] = trim((string) ($post['planned_intake'] ?? ''));
        $fields['ready_to_apply'] = trim((string) ($post['ready_to_apply'] ?? ''));
    }

    $uid = trim((string) ($post['user_id'] ?? ''));
    if ($uid === '' || !preg_match('/^user-[0-9]+-[0-9]+$/', $uid)) {
        $uid = ($userIdPrefix ?? 'user') . '-' . time() . '-' . random_int(1000, 9999);
    }

    $docKeys = $serviceType === 'work_abroad'
        ? array_keys(xander_prescreening_work_document_labels())
        : array_keys(xander_prescreening_document_labels());

    $uploadDir = dirname(__DIR__) . '/uploads/prescreening/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    $maxSize = 10 * 1024 * 1024;
    $docPaths = [];

    foreach ($docKeys as $docKey) {
        $existing = trim((string) ($post[$docKey . '_existing'] ?? ''));
        $hasUpload = isset($files[$docKey])
            && (int) ($files[$docKey]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if (!$hasUpload) {
            $docPaths[$docKey] = $existing;
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
    $studyKeys = array_keys(xander_prescreening_document_labels());
    $workKeys = array_keys(xander_prescreening_work_document_labels());
    $docKeys = array_values(array_unique(array_merge($studyKeys, $workKeys)));
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
 * @param array<string,mixed> $invite prescreening_invites row
 */
function xander_prescreening_persist_document_path(mysqli $conn, array $invite, string $docKey, string $relativePath): bool
{
    $studyKeys = array_keys(xander_prescreening_document_labels());
    $workKeys = array_keys(xander_prescreening_work_document_labels());
    $docKeys = array_values(array_unique(array_merge($studyKeys, $workKeys)));
    if (!in_array($docKey, $docKeys, true)) {
        return false;
    }
    $userId = (string) ($invite['user_id'] ?? '');
    if ($userId === '') {
        return false;
    }
    xander_ensure_prescreening_invites_table($conn);
    $sql = 'UPDATE prescreening_invites SET `' . $docKey . '` = ? WHERE user_id = ? LIMIT 1';
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
 * @param array<string,mixed> $invite
 * @param array<string,string> $docPaths
 * @return array<string,string>
 */
function xander_prescreening_merge_doc_paths_from_row(array $invite, array $docPaths): array
{
    $studyKeys = array_keys(xander_prescreening_document_labels());
    $workKeys = array_keys(xander_prescreening_work_document_labels());
    foreach (array_unique(array_merge($studyKeys, $workKeys)) as $key) {
        if (($docPaths[$key] ?? '') === '' && !empty($invite[$key])) {
            $docPaths[$key] = (string) $invite[$key];
        }
    }

    return $docPaths;
}
