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
 * @param array<string, mixed> $post
 * @return array{ok: bool, message: string}
 */
function xander_submit_scholarship_application(mysqli $conn, int $scholarshipId, array $post): array
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

    $phone = trim((string) ($post['applicant_phone'] ?? ''));
    $nationality = trim((string) ($post['nationality'] ?? ''));
    $dob = trim((string) ($post['date_of_birth'] ?? ''));
    $dobVal = $dob !== '' ? $dob : null;
    $education = trim((string) ($post['education_level'] ?? ''));
    $currentInst = trim((string) ($post['current_institution'] ?? ''));
    $statement = trim((string) ($post['statement'] ?? ''));
    $universityId = (int) ($sch['university_id'] ?? 0);

    $st = $conn->prepare('
        INSERT INTO institution_scholarship_applications (
            scholarship_id, university_id, applicant_name, applicant_email, applicant_phone,
            nationality, date_of_birth, education_level, current_institution, statement, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'new\')
    ');
    if (!$st) {
        return ['ok' => false, 'message' => 'Could not submit application.'];
    }
    $st->bind_param(
        'iissssssss',
        $scholarshipId,
        $universityId,
        $name,
        $email,
        $phone,
        $nationality,
        $dobVal,
        $education,
        $currentInst,
        $statement
    );
    $ok = $st->execute();
    $st->close();

    return $ok
        ? ['ok' => true, 'message' => 'Your application has been submitted successfully.']
        : ['ok' => false, 'message' => 'Submission failed. Please try again.'];
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
