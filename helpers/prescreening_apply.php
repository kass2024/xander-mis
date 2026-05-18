<?php
declare(strict_types=1);

require_once __DIR__ . '/prescreening_notify.php';
require_once __DIR__ . '/prescreening_options.php';

/** Map pre-screening work document column → job application upload field. */
function xander_prescreening_job_field_for_doc_key(string $key): string
{
    $map = [
        'doc_valid_passport' => 'passport',
        'doc_cv_resume' => 'cv',
        'doc_passport_photo' => 'photo',
        'doc_degree_transcripts' => 'academic_certificates',
        'doc_birth_certificate' => 'national_id',
    ];

    return $map[$key] ?? '';
}

function xander_prescreening_is_work_abroad(array $row): bool
{
    return (string) ($row['service_type'] ?? 'study_abroad') === 'work_abroad';
}

/** @return array{first:string,last:string} */
function xander_prescreening_split_name(string $full): array
{
    $full = trim($full);
    if ($full === '') {
        return ['first' => '', 'last' => ''];
    }
    $parts = preg_split('/\s+/u', $full, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($parts) <= 1) {
        return ['first' => $full, 'last' => ''];
    }
    $first = (string) array_shift($parts);

    return ['first' => $first, 'last' => implode(' ', $parts)];
}

/**
 * @return array<string,mixed>|null
 */
function xander_prescreening_load_by_id(mysqli $conn, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $stmt = $conn->prepare('SELECT * FROM prescreening_submissions WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * @return list<array{key:string,label:string,path:string,filename:string}>
 */
function xander_prescreening_collect_documents(array $row): array
{
    $labels = xander_prescreening_is_work_abroad($row)
        ? xander_prescreening_work_document_labels()
        : xander_prescreening_document_labels();
    $out = [];
    foreach ($labels as $key => $label) {
        $path = trim((string) ($row[$key] ?? ''));
        if ($path === '') {
            continue;
        }
        $item = [
            'key' => $key,
            'label' => $label,
            'path' => $path,
            'filename' => basename($path),
        ];
        $jobField = xander_prescreening_job_field_for_doc_key($key);
        if ($jobField !== '') {
            $item['job_field'] = $jobField;
        }
        $out[] = $item;
    }

    return $out;
}

function xander_prescreening_resolve_application_user_id(mysqli $conn, array $row): string
{
    $email = strtolower(trim((string) ($row['student_email'] ?? '')));
    $work = xander_prescreening_is_work_abroad($row);

    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if ($work) {
            $stmt = $conn->prepare(
                'SELECT user_id FROM job_applications
                 WHERE LOWER(TRIM(email)) = ?
                 ORDER BY id DESC LIMIT 1'
            );
        } else {
            $stmt = $conn->prepare(
                'SELECT user_id FROM student_applications
                 WHERE LOWER(TRIM(email)) = ?
                 ORDER BY id DESC LIMIT 1'
            );
        }
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!empty($existing['user_id'])) {
                return (string) $existing['user_id'];
            }
        }
    }

    $uid = trim((string) ($row['user_id'] ?? ''));
    if ($uid !== '' && preg_match('/^user-[0-9]+-[0-9]+$/', $uid)) {
        return $uid;
    }

    return 'user-' . time() . '-' . random_int(1000, 9999);
}

/**
 * @return array{token:string,user_id:string,prefill:array<string,string>,hints:array<string,string>,docs:array<int,array<string,string>>}
 */
function xander_prescreening_build_apply_handoff(mysqli $conn, array $row): array
{
    $work = xander_prescreening_is_work_abroad($row);
    $name = xander_prescreening_split_name((string) ($row['student_name'] ?? ''));
    $phone = trim((string) ($row['whatsapp_number'] ?? ''));
    $userId = xander_prescreening_resolve_application_user_id($conn, $row);
    $token = bin2hex(random_bytes(16));
    $docs = [];

    foreach (xander_prescreening_collect_documents($row) as $doc) {
        $entry = [
            'key' => $doc['key'],
            'label' => $doc['label'],
            'filename' => $doc['filename'],
            'url' => 'prescreening_apply_doc.php?h=' . rawurlencode($token) . '&key=' . rawurlencode($doc['key']),
        ];
        if (!empty($doc['job_field'])) {
            $entry['job_field'] = $doc['job_field'];
        }
        $docs[] = $entry;
    }

    $prefill = array_filter([
        'email' => trim((string) ($row['student_email'] ?? '')),
        'first_name' => $name['first'],
        'last_name' => $name['last'],
        'phone_number' => $phone,
    ], static fn ($v) => trim((string) $v) !== '');

    $hints = [];
    if ($work) {
        require_once __DIR__ . '/prescreening_work_profile.php';
        $workCountries = xander_prescreening_split_stored_countries((string) ($row['work_country_destination'] ?? ''));
        if ($workCountries !== []) {
            $hints['work_country'] = implode(', ', $workCountries);
            $hints['work_countries'] = $workCountries;
            require_once __DIR__ . '/ai_autofill_utils.php';
            $primary = $workCountries[0];
            $workCountryId = ai_lookup_country_id($conn, $primary);
            if ($workCountryId !== '') {
                $prefill['work_country_id'] = $workCountryId;
            }
        }
        $address = trim((string) ($row['applicant_address'] ?? ''));
        if ($address !== '') {
            $hints['applicant_address'] = $address;
            if (empty($prefill['village'])) {
                $prefill['village'] = $address;
            }
        }
        foreach (xander_prescreening_work_profile_job_prefill($row, $conn) as $key => $val) {
            if (trim((string) $val) !== '') {
                $prefill[$key] = (string) $val;
            }
        }
    } else {
        $hints = array_filter([
            'country_interest' => trim((string) ($row['country_interest'] ?? '')),
            'course_program' => trim((string) ($row['course_program'] ?? '')),
            'education_level' => trim((string) ($row['education_level'] ?? '')),
        ], static fn ($v) => trim((string) $v) !== '');
    }

    return [
        'token' => $token,
        'prescreen_id' => (int) ($row['id'] ?? 0),
        'user_id' => $userId,
        'service_type' => $work ? 'work_abroad' : 'study_abroad',
        'prefill' => $prefill,
        'hints' => $hints,
        'docs' => $docs,
        'paths' => array_column(xander_prescreening_collect_documents($row), 'path', 'key'),
        'auto_run' => true,
    ];
}

function xander_prescreening_absolute_doc_path(string $relativePath): ?string
{
    $relativePath = str_replace('\\', '/', trim($relativePath));
    if ($relativePath === '' || str_contains($relativePath, '..')) {
        return null;
    }
    $base = realpath(dirname(__DIR__) . '/uploads/prescreening');
    $file = realpath(dirname(__DIR__) . '/' . ltrim($relativePath, '/'));
    if (!$base || !$file || !is_file($file) || !str_starts_with($file, $base)) {
        return null;
    }

    return $file;
}

function xander_prescreening_delete_submission(mysqli $conn, int $id): bool
{
    $row = xander_prescreening_load_by_id($conn, $id);
    if (!$row) {
        return false;
    }

    foreach (xander_prescreening_collect_documents($row) as $doc) {
        $abs = xander_prescreening_absolute_doc_path($doc['path']);
        if ($abs) {
            @unlink($abs);
        }
    }

    $stmt = $conn->prepare('DELETE FROM prescreening_submissions WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();

    return $ok;
}

/**
 * Job application form uses session XGS_JOB_FORM — store handoff there so Apply now + doc URLs work.
 *
 * @param array<string, mixed> $handoff
 */
function xander_prescreening_store_apply_handoff_sessions(array $handoff, bool $isWork): void
{
    if ($isWork) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        session_name('XGS_JOB_FORM');
        session_start([
            'cookie_lifetime' => 7200,
            'cookie_secure' => false,
            'cookie_httponly' => true,
            'use_strict_mode' => true,
        ]);
        $_SESSION['user_id'] = (string) ($handoff['user_id'] ?? '');
        $_SESSION['xander_prescreen_handoff'] = $handoff;
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        session_write_close();
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['xander_prescreen_handoff'] = $handoff;
}

/**
 * Load apply handoff for document download (job form session + default admin session).
 *
 * @return array<string, mixed>|null
 */
function xander_prescreening_load_handoff_by_token(string $token): ?array
{
    if ($token === '') {
        return null;
    }

    $sessionNames = ['XGS_JOB_FORM'];
    if (session_status() === PHP_SESSION_ACTIVE) {
        $current = session_name();
        if ($current !== '' && !in_array($current, $sessionNames, true)) {
            $sessionNames[] = $current;
        }
        session_write_close();
    } else {
        $sessionNames[] = session_name() !== '' ? session_name() : 'PHPSESSID';
    }

    foreach (array_unique($sessionNames) as $name) {
        session_name($name);
        session_start();
        $handoff = $_SESSION['xander_prescreen_handoff'] ?? null;
        if (
            is_array($handoff)
            && isset($handoff['token'])
            && hash_equals((string) $handoff['token'], $token)
        ) {
            return $handoff;
        }
        session_write_close();
    }

    return null;
}

/**
 * List metadata: service type, pre-screen status, linked application status (study/job tables).
 *
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function xander_prescreening_row_list_meta(mysqli $conn, array $row): array
{
    require_once __DIR__ . '/application_filters.php';
    require_once __DIR__ . '/job_application_status.php';

    xander_ensure_job_applications_process_status_column($conn);

    $isWork = xander_prescreening_is_work_abroad($row);
    $submitted = !empty($row['submitted_at']);
    $userId = trim((string) ($row['user_id'] ?? ''));
    $email = strtolower(trim((string) ($row['student_email'] ?? '')));

    $meta = [
        'service_type' => $isWork ? 'work_abroad' : 'study_abroad',
        'service_label' => $isWork ? 'Work Abroad' : 'Study Abroad',
        'prescreen_status' => $submitted ? 'submitted' : 'pending',
        'prescreen_status_label' => $submitted ? 'Submitted' : 'Pending',
        'has_application' => false,
        'application_status' => '',
        'application_status_label' => '',
    ];

    if ($userId === '' && $email === '') {
        return $meta;
    }

    if ($isWork) {
        if ($email !== '') {
            $stmt = $conn->prepare(
                'SELECT process_status FROM job_applications
                 WHERE user_id = ? OR LOWER(TRIM(email)) = ?
                 ORDER BY id DESC LIMIT 1'
            );
            if (!$stmt) {
                return $meta;
            }
            $stmt->bind_param('ss', $userId, $email);
        } else {
            $stmt = $conn->prepare(
                'SELECT process_status FROM job_applications WHERE user_id = ? ORDER BY id DESC LIMIT 1'
            );
            if (!$stmt) {
                return $meta;
            }
            $stmt->bind_param('s', $userId);
        }
        $stmt->execute();
        $app = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($app) {
            $meta['has_application'] = true;
            $key = xander_normalize_job_process_status($app['process_status'] ?? null);
            $labels = xander_job_application_process_statuses();
            $meta['application_status'] = $key;
            $meta['application_status_label'] = $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
        }

        return $meta;
    }

    $statusCols = pcvc_application_status_columns_for_db($conn);
    $selectCols = ['id', 'user_id', 'email'];
    foreach ($statusCols as $col) {
        if (!in_array($col, $selectCols, true)) {
            $selectCols[] = $col;
        }
    }
    $sql = 'SELECT ' . implode(', ', array_map(static fn ($c) => '`' . $c . '`', $selectCols))
        . ' FROM student_applications
           WHERE user_id = ? OR LOWER(TRIM(email)) = ?
           ORDER BY id DESC LIMIT 1';
    if ($email !== '') {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return $meta;
        }
        $stmt->bind_param('ss', $userId, $email);
    } else {
        $sqlUid = 'SELECT ' . implode(', ', array_map(static fn ($c) => '`' . $c . '`', $selectCols))
            . ' FROM student_applications WHERE user_id = ? ORDER BY id DESC LIMIT 1';
        $stmt = $conn->prepare($sqlUid);
        if (!$stmt) {
            return $meta;
        }
        $stmt->bind_param('s', $userId);
    }
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($app) {
        $meta['has_application'] = true;
        $key = pcvc_application_effective_status($app);
        if ($key !== null) {
            $labels = pcvc_application_status_labels();
            $meta['application_status'] = $key;
            $meta['application_status_label'] = $labels[$key] ?? $key;
        } else {
            $meta['application_status'] = 'submitted';
            $meta['application_status_label'] = 'Submitted';
        }
    }

    return $meta;
}
