<?php
declare(strict_types=1);

require_once __DIR__ . '/institution_portal_schema.php';
require_once __DIR__ . '/institution_portal.php';

/**
 * @return array<string, int>
 */
function xander_institution_full_dashboard_stats(mysqli $conn, int $universityId): array
{
    xander_institution_portal_ensure_schema($conn);
    $stats = [
        'total_applications' => 0,
        'active_scholarships' => 0,
        'pending_reviews' => 0,
        'approved_students' => 0,
        'draft_scholarships' => 0,
        'expired_scholarships' => 0,
        'active_programs' => 0,
        'new_applications' => 0,
    ];
    if ($universityId <= 0) {
        return $stats;
    }

    $st = $conn->prepare("SELECT status, COUNT(*) AS c FROM institution_scholarships WHERE university_id = ? GROUP BY status");
    if ($st) {
        $st->bind_param('i', $universityId);
        $st->execute();
        $res = $st->get_result();
        while ($row = $res->fetch_assoc()) {
            $status = (string) ($row['status'] ?? '');
            $c = (int) ($row['c'] ?? 0);
            if ($status === 'active') {
                $stats['active_scholarships'] = $c;
            } elseif ($status === 'draft') {
                $stats['draft_scholarships'] = $c;
            } elseif ($status === 'expired') {
                $stats['expired_scholarships'] = $c;
            }
        }
        $st->close();
    }

    $st = $conn->prepare("SELECT COUNT(*) AS c FROM institution_programs WHERE university_id = ? AND status = 'active'");
    if ($st) {
        $st->bind_param('i', $universityId);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        $stats['active_programs'] = (int) ($row['c'] ?? 0);
    }

    $st = $conn->prepare("SELECT status, COUNT(*) AS c FROM institution_scholarship_applications WHERE university_id = ? GROUP BY status");
    if ($st) {
        $st->bind_param('i', $universityId);
        $st->execute();
        $res = $st->get_result();
        while ($row = $res->fetch_assoc()) {
            $status = (string) ($row['status'] ?? '');
            $c = (int) ($row['c'] ?? 0);
            $stats['total_applications'] += $c;
            if ($status === 'new') {
                $stats['new_applications'] = $c;
            }
            if ($status === 'under_review') {
                $stats['pending_reviews'] = $c;
            }
            if ($status === 'accepted') {
                $stats['approved_students'] = $c;
            }
        }
        $st->close();
    }

    $legacy = xander_institution_dashboard_stats($conn, $universityId);
    if ($stats['total_applications'] === 0 && ($legacy['applications_total'] ?? 0) > 0) {
        $stats['total_applications'] = (int) $legacy['applications_total'];
    }

    return $stats;
}

/**
 * @return array<int, array<string, mixed>>
 */
function xander_institution_recent_activity(mysqli $conn, int $universityId, int $limit = 8): array
{
    if ($universityId <= 0) {
        return [];
    }
    $limit = max(1, min(20, $limit));
    $sql = "
        SELECT a.id, a.applicant_name, a.status, a.created_at, s.title AS scholarship_title
        FROM institution_scholarship_applications a
        INNER JOIN institution_scholarships s ON s.id = a.scholarship_id
        WHERE a.university_id = ?
        ORDER BY a.created_at DESC
        LIMIT ?
    ";
    $st = $conn->prepare($sql);
    if (!$st) {
        return [];
    }
    $st->bind_param('ii', $universityId, $limit);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    return $rows ?: [];
}

/**
 * @return array<int, array<string, mixed>>
 */
function xander_institution_list_scholarships(mysqli $conn, int $universityId, ?string $status = null): array
{
    xander_institution_portal_ensure_schema($conn);
    if ($universityId <= 0) {
        return [];
    }
    if ($status !== null && $status !== '') {
        $st = $conn->prepare('SELECT * FROM institution_scholarships WHERE university_id = ? AND status = ? ORDER BY updated_at DESC');
        if (!$st) {
            return [];
        }
        $st->bind_param('is', $universityId, $status);
    } else {
        $st = $conn->prepare('SELECT * FROM institution_scholarships WHERE university_id = ? ORDER BY updated_at DESC');
        if (!$st) {
            return [];
        }
        $st->bind_param('i', $universityId);
    }
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    return $rows ?: [];
}

/**
 * @return array<string, mixed>|null
 */
function xander_institution_load_scholarship(mysqli $conn, int $scholarshipId, int $universityId): ?array
{
    if ($scholarshipId <= 0 || $universityId <= 0) {
        return null;
    }
    $st = $conn->prepare('SELECT * FROM institution_scholarships WHERE id = ? AND university_id = ? LIMIT 1');
    if (!$st) {
        return null;
    }
    $st->bind_param('ii', $scholarshipId, $universityId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    return $row ?: null;
}

function xander_institution_scholarship_slug(string $title): string
{
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');

    return $slug !== '' ? $slug : 'scholarship';
}

/**
 * @param array<string, mixed> $post
 * @return array{ok: bool, message: string, id?: int}
 */
function xander_institution_save_scholarship(mysqli $conn, int $universityId, array $post, ?array $file = null): array
{
    xander_institution_portal_ensure_schema($conn);
    if ($universityId <= 0) {
        return ['ok' => false, 'message' => 'Invalid institution.'];
    }

    $id = (int) ($post['scholarship_id'] ?? 0);
    $title = trim((string) ($post['title'] ?? ''));
    if ($title === '') {
        return ['ok' => false, 'message' => 'Scholarship title is required.'];
    }

    $status = (string) ($post['status'] ?? 'draft');
    if (!in_array($status, ['draft', 'active', 'expired'], true)) {
        $status = 'draft';
    }
    $isPublished = !empty($post['is_published']) ? 1 : 0;
    if ($status === 'active' && $isPublished) {
        $isPublished = 1;
    }

    $deadline = trim((string) ($post['deadline'] ?? ''));
    $deadlineVal = $deadline !== '' ? $deadline : null;
    $slug = xander_institution_scholarship_slug($title);

    $brochurePath = null;
    if ($id > 0) {
        $existing = xander_institution_load_scholarship($conn, $id, $universityId);
        $brochurePath = $existing['brochure_path'] ?? null;
    }
    if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $upload = xander_institution_store_scholarship_brochure($universityId, $file);
        if (!$upload['ok']) {
            return $upload;
        }
        $brochurePath = $upload['path'];
    }

    $fields = [
        'tagline' => trim((string) ($post['tagline'] ?? '')),
        'summary' => trim((string) ($post['summary'] ?? '')),
        'eligibility' => trim((string) ($post['eligibility'] ?? '')),
        'requirements' => trim((string) ($post['requirements'] ?? '')),
        'tuition_coverage' => trim((string) ($post['tuition_coverage'] ?? '')),
        'accommodation_details' => trim((string) ($post['accommodation_details'] ?? '')),
        'benefits' => trim((string) ($post['benefits'] ?? '')),
        'award_amount' => trim((string) ($post['award_amount'] ?? '')),
    ];

    if ($id > 0) {
        $sql = '
            UPDATE institution_scholarships SET
                title = ?, slug = ?, tagline = ?, summary = ?, eligibility = ?, requirements = ?,
                tuition_coverage = ?, accommodation_details = ?, benefits = ?, award_amount = ?,
                deadline = ?, status = ?, is_published = ?, brochure_path = ?
            WHERE id = ? AND university_id = ?
        ';
        $st = $conn->prepare($sql);
        if (!$st) {
            return ['ok' => false, 'message' => 'Could not update scholarship.'];
        }
        $st->bind_param(
            'ssssssssssssisii',
            $title,
            $slug,
            $fields['tagline'],
            $fields['summary'],
            $fields['eligibility'],
            $fields['requirements'],
            $fields['tuition_coverage'],
            $fields['accommodation_details'],
            $fields['benefits'],
            $fields['award_amount'],
            $deadlineVal,
            $status,
            $isPublished,
            $brochurePath,
            $id,
            $universityId
        );
    } else {
        $sql = '
            INSERT INTO institution_scholarships (
                university_id, title, slug, tagline, summary, eligibility, requirements,
                tuition_coverage, accommodation_details, benefits, award_amount,
                deadline, status, is_published, brochure_path
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ';
        $st = $conn->prepare($sql);
        if (!$st) {
            return ['ok' => false, 'message' => 'Could not create scholarship.'];
        }
        $st->bind_param(
            'issssssssssssis',
            $universityId,
            $title,
            $slug,
            $fields['tagline'],
            $fields['summary'],
            $fields['eligibility'],
            $fields['requirements'],
            $fields['tuition_coverage'],
            $fields['accommodation_details'],
            $fields['benefits'],
            $fields['award_amount'],
            $deadlineVal,
            $status,
            $isPublished,
            $brochurePath
        );
    }

    $ok = $st->execute();
    if (!$ok) {
        $st->close();

        return ['ok' => false, 'message' => 'Save failed. Please try again.'];
    }
    if ($id <= 0) {
        $id = (int) $conn->insert_id;
    }
    $st->close();

    return ['ok' => true, 'message' => 'Scholarship saved successfully.', 'id' => $id];
}

/**
 * @return array{ok: bool, message: string, path?: string}
 */
function xander_institution_store_scholarship_brochure(int $universityId, array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => 'Upload failed.'];
    }
    if (($file['size'] ?? 0) > 12 * 1024 * 1024) {
        return ['ok' => false, 'message' => 'File too large (max 12 MB).'];
    }
    $orig = basename((string) ($file['name'] ?? 'brochure'));
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $safeExt = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'pdf';
    if (!in_array($safeExt, ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp'], true)) {
        return ['ok' => false, 'message' => 'Allowed: PDF, Word, JPG, PNG.'];
    }
    $dir = dirname(__DIR__) . '/uploads/institution/' . $universityId . '/brochures';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'message' => 'Could not create upload folder.'];
    }
    $stored = 'brochure_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $safeExt;
    $dest = $dir . '/' . $stored;
    if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
        return ['ok' => false, 'message' => 'Could not save file.'];
    }

    return ['ok' => true, 'message' => 'Brochure uploaded.', 'path' => 'uploads/institution/' . $universityId . '/brochures/' . $stored];
}

function xander_institution_delete_scholarship(mysqli $conn, int $scholarshipId, int $universityId): bool
{
    $st = $conn->prepare('DELETE FROM institution_scholarships WHERE id = ? AND university_id = ?');
    if (!$st) {
        return false;
    }
    $st->bind_param('ii', $scholarshipId, $universityId);
    $st->execute();
    $ok = $st->affected_rows > 0;
    $st->close();

    return $ok;
}

/**
 * @return array<int, array<string, mixed>>
 */
function xander_institution_list_programs(mysqli $conn, int $universityId, ?string $programType = null): array
{
    xander_institution_portal_ensure_schema($conn);
    if ($universityId <= 0) {
        return [];
    }
    $types = ['undergraduate', 'masters', 'phd', 'diploma', 'online', 'short'];
    if ($programType !== null && in_array($programType, $types, true)) {
        $st = $conn->prepare('SELECT * FROM institution_programs WHERE university_id = ? AND program_type = ? ORDER BY title ASC');
        if (!$st) {
            return [];
        }
        $st->bind_param('is', $universityId, $programType);
    } else {
        $st = $conn->prepare('SELECT * FROM institution_programs WHERE university_id = ? ORDER BY program_type, title ASC');
        if (!$st) {
            return [];
        }
        $st->bind_param('i', $universityId);
    }
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    return $rows ?: [];
}

/**
 * @return array<string, mixed>|null
 */
function xander_institution_load_program(mysqli $conn, int $programId, int $universityId): ?array
{
    if ($programId <= 0 || $universityId <= 0) {
        return null;
    }
    $st = $conn->prepare('SELECT * FROM institution_programs WHERE id = ? AND university_id = ? LIMIT 1');
    if (!$st) {
        return null;
    }
    $st->bind_param('ii', $programId, $universityId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    return $row ?: null;
}

/**
 * @param array<string, mixed> $post
 * @return array{ok: bool, message: string, id?: int}
 */
function xander_institution_save_program(mysqli $conn, int $universityId, array $post): array
{
    xander_institution_portal_ensure_schema($conn);
    if ($universityId <= 0) {
        return ['ok' => false, 'message' => 'Invalid institution.'];
    }

    $id = (int) ($post['program_id'] ?? 0);
    $title = trim((string) ($post['title'] ?? ''));
    if ($title === '') {
        return ['ok' => false, 'message' => 'Program title is required.'];
    }

    $programType = (string) ($post['program_type'] ?? 'undergraduate');
    $types = ['undergraduate', 'masters', 'phd', 'diploma', 'online', 'short'];
    if (!in_array($programType, $types, true)) {
        $programType = 'undergraduate';
    }
    $status = (string) ($post['status'] ?? 'active');
    if (!in_array($status, ['draft', 'active'], true)) {
        $status = 'active';
    }

    $summary = trim((string) ($post['summary'] ?? ''));
    $tuition = trim((string) ($post['tuition_notes'] ?? ''));
    $duration = trim((string) ($post['duration'] ?? ''));
    $intake = trim((string) ($post['intake_dates'] ?? ''));
    $requirements = trim((string) ($post['requirements'] ?? ''));
    $language = trim((string) ($post['language_requirements'] ?? ''));

    if ($id > 0) {
        $st = $conn->prepare('
            UPDATE institution_programs SET
                title = ?, program_type = ?, summary = ?, tuition_notes = ?, duration = ?,
                intake_dates = ?, requirements = ?, language_requirements = ?, status = ?
            WHERE id = ? AND university_id = ?
        ');
        if (!$st) {
            return ['ok' => false, 'message' => 'Could not update program.'];
        }
        $st->bind_param(
            'sssssssssii',
            $title,
            $programType,
            $summary,
            $tuition,
            $duration,
            $intake,
            $requirements,
            $language,
            $status,
            $id,
            $universityId
        );
    } else {
        $st = $conn->prepare('
            INSERT INTO institution_programs (
                university_id, title, program_type, summary, tuition_notes, duration,
                intake_dates, requirements, language_requirements, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        if (!$st) {
            return ['ok' => false, 'message' => 'Could not create program.'];
        }
        $st->bind_param(
            'isssssssss',
            $universityId,
            $title,
            $programType,
            $summary,
            $tuition,
            $duration,
            $intake,
            $requirements,
            $language,
            $status
        );
    }

    $ok = $st->execute();
    if (!$ok) {
        $st->close();

        return ['ok' => false, 'message' => 'Save failed.'];
    }
    if ($id <= 0) {
        $id = (int) $conn->insert_id;
    }
    $st->close();

    return ['ok' => true, 'message' => 'Program saved successfully.', 'id' => $id];
}

function xander_institution_delete_program(mysqli $conn, int $programId, int $universityId): bool
{
    $st = $conn->prepare('DELETE FROM institution_programs WHERE id = ? AND university_id = ?');
    if (!$st) {
        return false;
    }
    $st->bind_param('ii', $programId, $universityId);
    $st->execute();
    $ok = $st->affected_rows > 0;
    $st->close();

    return $ok;
}

/**
 * @return array<int, array<string, mixed>>
 */
function xander_institution_list_applications(
    mysqli $conn,
    int $universityId,
    ?string $status = null,
    int $scholarshipId = 0
): array {
    xander_institution_portal_ensure_schema($conn);
    if ($universityId <= 0) {
        return [];
    }

    $sql = '
        SELECT a.*, s.title AS scholarship_title
        FROM institution_scholarship_applications a
        INNER JOIN institution_scholarships s ON s.id = a.scholarship_id
        WHERE a.university_id = ?
    ';
    $params = [$universityId];
    $types = 'i';

    if ($scholarshipId > 0) {
        $sql .= ' AND a.scholarship_id = ?';
        $params[] = $scholarshipId;
        $types .= 'i';
    }
    if ($status !== null && $status !== '') {
        $sql .= ' AND a.status = ?';
        $params[] = $status;
        $types .= 's';
    }
    $sql .= ' ORDER BY a.created_at DESC';

    $st = $conn->prepare($sql);
    if (!$st) {
        return [];
    }
    $st->bind_param($types, ...$params);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    return $rows ?: [];
}

/**
 * @return array<string, mixed>|null
 */
function xander_institution_load_application(mysqli $conn, int $applicationId, int $universityId): ?array
{
    if ($applicationId <= 0 || $universityId <= 0) {
        return null;
    }
    $st = $conn->prepare('
        SELECT a.*, s.title AS scholarship_title
        FROM institution_scholarship_applications a
        INNER JOIN institution_scholarships s ON s.id = a.scholarship_id
        WHERE a.id = ? AND a.university_id = ?
        LIMIT 1
    ');
    if (!$st) {
        return null;
    }
    $st->bind_param('ii', $applicationId, $universityId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    return $row ?: null;
}

/**
 * @param array<string, mixed> $post
 * @return array{ok: bool, message: string}
 */
function xander_institution_update_application(mysqli $conn, int $applicationId, int $universityId, array $post): array
{
    $app = xander_institution_load_application($conn, $applicationId, $universityId);
    if (!$app) {
        return ['ok' => false, 'message' => 'Application not found.'];
    }

    $status = (string) ($post['status'] ?? $app['status']);
    $allowed = ['new', 'under_review', 'accepted', 'rejected', 'waitlisted'];
    if (!in_array($status, $allowed, true)) {
        return ['ok' => false, 'message' => 'Invalid status.'];
    }

    $notes = trim((string) ($post['internal_notes'] ?? ''));
    $st = $conn->prepare('
        UPDATE institution_scholarship_applications
        SET status = ?, internal_notes = ?, reviewed_at = NOW()
        WHERE id = ? AND university_id = ?
    ');
    if (!$st) {
        return ['ok' => false, 'message' => 'Update failed.'];
    }
    $st->bind_param('ssii', $status, $notes, $applicationId, $universityId);
    $ok = $st->execute();
    $st->close();

    return $ok
        ? ['ok' => true, 'message' => 'Application updated.']
        : ['ok' => false, 'message' => 'Update failed.'];
}

/**
 * Public scholarship listings for homepage.
 *
 * @return array<int, array<string, mixed>>
 */
function xander_homepage_published_scholarships(mysqli $conn, int $limit = 12): array
{
    xander_institution_portal_ensure_schema($conn);
    $limit = max(1, min(24, $limit));

    $sql = "
        SELECT s.*, u.name AS university_name, c.name AS country_name
        FROM institution_scholarships s
        INNER JOIN universities u ON u.id = s.university_id
        LEFT JOIN countries c ON c.id = u.country_id
        WHERE s.is_published = 1 AND s.status = 'active'
          AND (s.deadline IS NULL OR s.deadline >= CURDATE())
        ORDER BY s.deadline IS NULL, s.deadline ASC, s.updated_at DESC
        LIMIT ?
    ";
    $st = $conn->prepare($sql);
    if (!$st) {
        return xander_homepage_legacy_scholarships($conn, $limit);
    }
    $st->bind_param('i', $limit);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    if ($rows !== []) {
        return $rows;
    }

    return xander_homepage_legacy_scholarships($conn, $limit);
}

/**
 * Fallback: legacy single profile per university.
 *
 * @return array<int, array<string, mixed>>
 */
function xander_homepage_legacy_scholarships(mysqli $conn, int $limit): array
{
    $limit = max(1, min(24, $limit));
    $sql = "
        SELECT p.scholarship_program_name AS title, p.scholarship_tagline AS tagline,
               p.scholarship_summary AS summary, p.scholarship_eligibility AS eligibility,
               p.scholarship_benefits AS benefits, p.scholarship_amount_notes AS award_amount,
               p.scholarship_deadline AS deadline, p.university_id,
               u.name AS university_name, c.name AS country_name,
               0 AS id, 'legacy' AS source
        FROM institution_university_profiles p
        INNER JOIN universities u ON u.id = p.university_id
        LEFT JOIN countries c ON c.id = u.country_id
        WHERE p.homepage_published = 1
          AND p.profile_complete_scholarship = 1
          AND TRIM(p.scholarship_program_name) <> ''
          AND (p.scholarship_deadline IS NULL OR p.scholarship_deadline >= CURDATE())
        ORDER BY p.updated_at DESC
        LIMIT ?
    ";
    $st = $conn->prepare($sql);
    if (!$st) {
        return [];
    }
    $st->bind_param('i', $limit);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    return $rows ?: [];
}

/**
 * @return array<string, mixed>|null
 */
function xander_public_load_scholarship(mysqli $conn, int $scholarshipId): ?array
{
    if ($scholarshipId <= 0) {
        return null;
    }
    $sql = "
        SELECT s.*, u.name AS university_name, c.name AS country_name
        FROM institution_scholarships s
        INNER JOIN universities u ON u.id = s.university_id
        LEFT JOIN countries c ON c.id = u.country_id
        WHERE s.id = ? AND s.is_published = 1 AND s.status = 'active'
        LIMIT 1
    ";
    $st = $conn->prepare($sql);
    if (!$st) {
        return null;
    }
    $st->bind_param('i', $scholarshipId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    return $row ?: null;
}

/**
 * Scholarship application attachment fields (form name => stored type).
 *
 * @return array<string, array{label: string, required: bool}>
 */
function xander_scholarship_application_document_fields(): array
{
    return [
        'file_transcript' => ['label' => 'Academic transcript / marksheet', 'required' => true],
        'file_cv' => ['label' => 'CV / Resume', 'required' => true],
        'file_passport' => ['label' => 'Passport or national ID', 'required' => true],
        'file_english_test' => ['label' => 'English proficiency (IELTS, TOEFL, Duolingo, etc.)', 'required' => false],
        'file_recommendation_1' => ['label' => 'Letter of recommendation #1', 'required' => false],
        'file_recommendation_2' => ['label' => 'Letter of recommendation #2', 'required' => false],
        'file_enrollment_proof' => ['label' => 'Proof of enrollment / admission letter', 'required' => false],
        'file_financial' => ['label' => 'Financial statements / sponsor letter', 'required' => false],
        'file_portfolio' => ['label' => 'Portfolio / research work', 'required' => false],
        'file_statement_pdf' => ['label' => 'Personal statement (PDF upload)', 'required' => false],
        'file_other' => ['label' => 'Other supporting document', 'required' => false],
    ];
}

/**
 * @return array{ok: bool, message: string, path?: string}
 */
function xander_store_scholarship_application_document(
    int $universityId,
    int $applicationId,
    string $documentType,
    string $label,
    array $file
): array {
    if ($universityId <= 0 || $applicationId <= 0) {
        return ['ok' => false, 'message' => 'Invalid upload context.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'message' => 'No file uploaded.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => 'Upload failed.'];
    }
    if (($file['size'] ?? 0) > 12 * 1024 * 1024) {
        return ['ok' => false, 'message' => 'File too large (max 12 MB).'];
    }

    $orig = basename((string) ($file['name'] ?? 'document'));
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $safeExt = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'pdf';
    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($safeExt, $allowed, true)) {
        return ['ok' => false, 'message' => 'Allowed: PDF, Word, JPG, PNG.'];
    }

    $dir = dirname(__DIR__) . '/uploads/institution/' . $universityId . '/applications/' . $applicationId;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'message' => 'Could not create upload folder.'];
    }

    $stored = preg_replace('/[^a-z0-9_]/', '_', $documentType) . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $safeExt;
    $dest = $dir . '/' . $stored;
    if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
        return ['ok' => false, 'message' => 'Could not save file.'];
    }

    $relative = 'uploads/institution/' . $universityId . '/applications/' . $applicationId . '/' . $stored;

    return [
        'ok' => true,
        'message' => 'Uploaded.',
        'path' => $relative,
        'original_name' => $orig,
        'mime_type' => (string) ($file['type'] ?? 'application/octet-stream'),
        'size_bytes' => (int) ($file['size'] ?? 0),
    ];
}

/**
 * @return array<int, array<string, mixed>>
 */
function xander_institution_list_application_documents(mysqli $conn, int $applicationId, int $universityId): array
{
    xander_institution_portal_ensure_schema($conn);
    if ($applicationId <= 0 || $universityId <= 0) {
        return [];
    }
    $st = $conn->prepare('
        SELECT * FROM institution_scholarship_application_documents
        WHERE application_id = ? AND university_id = ?
        ORDER BY uploaded_at ASC
    ');
    if (!$st) {
        return [];
    }
    $st->bind_param('ii', $applicationId, $universityId);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    return $rows ?: [];
}

/**
 * @param array<string, mixed> $post
 * @param array<string, mixed> $files $_FILES
 * @return array{ok: bool, message: string, application_id?: int}
 */
function xander_submit_scholarship_application(mysqli $conn, int $scholarshipId, array $post, array $files = []): array
{
    xander_institution_portal_ensure_schema($conn);
    $sch = xander_public_load_scholarship($conn, $scholarshipId);
    if (!$sch) {
        return ['ok' => false, 'message' => 'This scholarship is not available.'];
    }

    $name = trim((string) ($post['applicant_name'] ?? ''));
    $email = xander_institution_email_norm((string) ($post['applicant_email'] ?? ''));
    if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Please provide a valid name and email.'];
    }

    $docFields = xander_scholarship_application_document_fields();
    foreach ($docFields as $fieldName => $meta) {
        if (empty($meta['required'])) {
            continue;
        }
        $f = $files[$fieldName] ?? null;
        if (!is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'message' => 'Required document missing: ' . ($meta['label'] ?? $fieldName) . '.'];
        }
    }

    $phone = trim((string) ($post['applicant_phone'] ?? ''));
    $nationality = trim((string) ($post['nationality'] ?? ''));
    $dob = trim((string) ($post['date_of_birth'] ?? ''));
    $dobVal = $dob !== '' ? $dob : null;
    $education = trim((string) ($post['education_level'] ?? ''));
    $currentInst = trim((string) ($post['current_institution'] ?? ''));
    $intendedProgram = trim((string) ($post['intended_program'] ?? ''));
    $fieldOfStudy = trim((string) ($post['field_of_study'] ?? ''));
    $gpa = trim((string) ($post['gpa_or_grade'] ?? ''));
    $address = trim((string) ($post['address'] ?? ''));
    $statement = trim((string) ($post['statement'] ?? ''));
    $universityId = (int) ($sch['university_id'] ?? 0);

    $st = $conn->prepare('
        INSERT INTO institution_scholarship_applications (
            scholarship_id, university_id, applicant_name, applicant_email, applicant_phone,
            nationality, date_of_birth, education_level, current_institution,
            intended_program, field_of_study, gpa_or_grade, address, statement, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'new\')
    ');
    if (!$st) {
        return ['ok' => false, 'message' => 'Could not submit application.'];
    }
    $st->bind_param(
        'iissssssssssss',
        $scholarshipId,
        $universityId,
        $name,
        $email,
        $phone,
        $nationality,
        $dobVal,
        $education,
        $currentInst,
        $intendedProgram,
        $fieldOfStudy,
        $gpa,
        $address,
        $statement
    );
    if (!$st->execute()) {
        $st->close();

        return ['ok' => false, 'message' => 'Submission failed. Please try again.'];
    }
    $applicationId = (int) $conn->insert_id;
    $st->close();

    foreach ($docFields as $fieldName => $meta) {
        $f = $files[$fieldName] ?? null;
        if (!is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $upload = xander_store_scholarship_application_document(
            $universityId,
            $applicationId,
            $fieldName,
            (string) ($meta['label'] ?? $fieldName),
            $f
        );
        if (!$upload['ok']) {
            continue;
        }
        $docType = $fieldName;
        $label = (string) ($meta['label'] ?? $fieldName);
        $orig = (string) ($upload['original_name'] ?? 'file');
        $path = (string) ($upload['path'] ?? '');
        $mime = (string) ($upload['mime_type'] ?? '');
        $size = (int) ($upload['size_bytes'] ?? 0);
        $ins = $conn->prepare('
            INSERT INTO institution_scholarship_application_documents
                (application_id, university_id, document_type, label, original_name, stored_path, mime_type, size_bytes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        if ($ins) {
            $ins->bind_param('iisssssi', $applicationId, $universityId, $docType, $label, $orig, $path, $mime, $size);
            $ins->execute();
            $ins->close();
        }
    }

    return [
        'ok' => true,
        'message' => 'Your application and documents have been submitted successfully.',
        'application_id' => $applicationId,
    ];
}

/**
 * Published education loan opportunities from institution profiles (homepage).
 *
 * @return array<int, array<string, mixed>>
 */
function xander_homepage_published_loans(mysqli $conn, int $limit = 12): array
{
    xander_institution_portal_ensure_schema($conn);
    $limit = max(1, min(24, $limit));

    $sql = "
        SELECT p.loan_program_name AS title,
               p.loan_institution_name AS loan_institution_name,
               p.loan_summary AS summary,
               p.loan_coverage AS loan_coverage,
               p.loan_eligibility AS eligibility,
               p.loan_rates_notes AS rates_notes,
               p.loan_apply_url AS loan_apply_url,
               p.loan_contact_email AS loan_contact_email,
               p.university_id,
               u.name AS university_name,
               c.name AS country_name
        FROM institution_university_profiles p
        INNER JOIN universities u ON u.id = p.university_id
        LEFT JOIN countries c ON c.id = u.country_id
        WHERE p.homepage_published = 1
          AND p.profile_complete_loan = 1
          AND TRIM(COALESCE(p.loan_program_name, '')) <> ''
        ORDER BY p.updated_at DESC
        LIMIT ?
    ";
    $st = $conn->prepare($sql);
    if (!$st) {
        return [];
    }
    $st->bind_param('i', $limit);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    return $rows ?: [];
}

function xander_institution_loan_apply_url(array $loan): string
{
    $url = trim((string) ($loan['loan_apply_url'] ?? ''));
    if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
        return $url;
    }

    return pcvc_url('/loan-providers.php');
}

/**
 * @return array<string, string>
 */
function xander_institution_program_type_labels(): array
{
    return [
        'undergraduate' => 'Undergraduate',
        'masters' => "Master's",
        'phd' => 'PhD',
        'diploma' => 'Diploma',
        'online' => 'Online Courses',
        'short' => 'Short Courses',
    ];
}

/**
 * @return array<string, string>
 */
function xander_institution_application_status_labels(): array
{
    return [
        'new' => 'New',
        'under_review' => 'Under Review',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
        'waitlisted' => 'Waitlisted',
    ];
}

function xander_institution_scholarship_apply_url(int $scholarshipId): string
{
    return pcvc_url('/scholarship-apply.php?id=' . $scholarshipId);
}
