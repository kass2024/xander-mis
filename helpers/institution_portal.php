<?php
declare(strict_types=1);

require_once __DIR__ . '/institution_portal_schema.php';
require_once __DIR__ . '/urls.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/../includes/company_branding.php';

function xander_institution_email_norm(string $email): string
{
    return strtolower(trim($email));
}

/**
 * @return array<int, array<string, mixed>>
 */
function xander_institution_search_universities(mysqli $conn, string $query, int $limit = 12): array
{
    $query = trim($query);
    if ($query === '' || mb_strlen($query) < 2) {
        return [];
    }
    $limit = max(1, min(25, $limit));
    $like = '%' . $query . '%';

    $sql = "
        SELECT u.id, u.name, u.region_id, u.country_id,
               u.website, u.city, u.institution_phone, u.institution_kind,
               r.name AS region_name, c.name AS country_name,
               (SELECT 1 FROM institution_portal_accounts ipa WHERE ipa.university_id = u.id LIMIT 1) AS has_portal
        FROM universities u
        LEFT JOIN regions r ON r.id = u.region_id
        LEFT JOIN countries c ON c.id = u.country_id
        WHERE u.name LIKE ?
        ORDER BY
            CASE WHEN LOWER(TRIM(u.name)) = LOWER(TRIM(?)) THEN 0
                 WHEN LOWER(TRIM(u.name)) LIKE LOWER(CONCAT(?, '%')) THEN 1
                 ELSE 2 END,
            u.name ASC
        LIMIT ?
    ";
    $st = $conn->prepare($sql);
    if (!$st) {
        return [];
    }
    $st->bind_param('sssi', $like, $query, $query, $limit);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'region_id' => (int) ($row['region_id'] ?? 0),
            'country_id' => (int) ($row['country_id'] ?? 0),
            'region_name' => (string) ($row['region_name'] ?? ''),
            'country_name' => (string) ($row['country_name'] ?? ''),
            'website' => (string) ($row['website'] ?? ''),
            'city' => (string) ($row['city'] ?? ''),
            'institution_phone' => (string) ($row['institution_phone'] ?? ''),
            'institution_kind' => (string) ($row['institution_kind'] ?? ''),
            'has_portal' => !empty($row['has_portal']),
        ];
    }

    return $out;
}

/**
 * @return array<string, mixed>|null
 */
function xander_institution_load_university_by_id(mysqli $conn, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $st = $conn->prepare("
        SELECT u.id, u.name, u.region_id, u.country_id,
               u.website, u.city, u.institution_phone, u.institution_kind,
               r.name AS region_name, c.name AS country_name
        FROM universities u
        LEFT JOIN regions r ON r.id = u.region_id
        LEFT JOIN countries c ON c.id = u.country_id
        WHERE u.id = ?
        LIMIT 1
    ");
    if (!$st) {
        return null;
    }
    $st->bind_param('i', $id);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    return $row ?: null;
}

/**
 * Find existing university by name (+ optional region/country).
 */
function xander_institution_find_university_id(
    mysqli $conn,
    string $name,
    int $regionId = 0,
    int $countryId = 0
): int {
    $name = trim($name);
    if ($name === '') {
        return 0;
    }

    if ($regionId > 0 && $countryId > 0) {
        $st = $conn->prepare('SELECT id FROM universities WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) AND region_id = ? AND country_id = ? LIMIT 1');
        if ($st) {
            $st->bind_param('sii', $name, $regionId, $countryId);
            $st->execute();
            $row = $st->get_result()->fetch_assoc();
            $st->close();
            if ($row) {
                return (int) $row['id'];
            }
        }
    }

    $st = $conn->prepare('SELECT id FROM universities WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) ORDER BY id DESC LIMIT 1');
    if (!$st) {
        return 0;
    }
    $st->bind_param('s', $name);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    return $row ? (int) $row['id'] : 0;
}

/**
 * Insert or update university row; returns university id.
 */
function xander_institution_upsert_university(
    mysqli $conn,
    int $universityId,
    string $name,
    int $regionId,
    int $countryId,
    string $website = '',
    string $city = '',
    string $institutionPhone = '',
    string $institutionKind = ''
): int {
    $name = trim($name);
    if ($name === '' || $regionId <= 0 || $countryId <= 0) {
        return 0;
    }

    $website = trim($website);
    $city = trim($city);
    $institutionPhone = trim($institutionPhone);
    $institutionKind = trim($institutionKind);

    if ($universityId <= 0) {
        $existing = xander_institution_find_university_id($conn, $name, $regionId, $countryId);
        if ($existing > 0) {
            $universityId = $existing;
        }
    }

    if ($universityId > 0) {
        $st = $conn->prepare('
            UPDATE universities
            SET name = ?, region_id = ?, country_id = ?,
                website = COALESCE(NULLIF(?, ""), website),
                city = COALESCE(NULLIF(?, ""), city),
                institution_phone = COALESCE(NULLIF(?, ""), institution_phone),
                institution_kind = COALESCE(NULLIF(?, ""), institution_kind)
            WHERE id = ?
            LIMIT 1
        ');
        if (!$st) {
            return 0;
        }
        $st->bind_param('siissssi', $name, $regionId, $countryId, $website, $city, $institutionPhone, $institutionKind, $universityId);
        $st->execute();
        $st->close();

        return $universityId;
    }

    $st = $conn->prepare('
        INSERT INTO universities (name, region_id, country_id, website, city, institution_phone, institution_kind)
        VALUES (?, ?, ?, NULLIF(?, ""), NULLIF(?, ""), NULLIF(?, ""), NULLIF(?, ""))
    ');
    if (!$st) {
        return 0;
    }
    $st->bind_param('siissss', $name, $regionId, $countryId, $website, $city, $institutionPhone, $institutionKind);
    $st->execute();
    $newId = (int) $conn->insert_id;
    $st->close();

    return $newId;
}

function xander_institution_generate_temp_password(int $length = 12): string
{
    $length = max(10, min(20, $length));
    $lower = 'abcdefghjkmnpqrstuvwxyz';
    $upper = 'ABCDEFGHJKMNPQRSTUVWXYZ';
    $digits = '23456789';
    $special = '!@#$%&*';
    $all = $lower . $upper . $digits . $special;
    $pick = static function (string $pool): string {
        return $pool[random_int(0, strlen($pool) - 1)];
    };

    $password = $pick($lower) . $pick($upper) . $pick($digits) . $pick($special);
    for ($i = 4; $i < $length; $i++) {
        $password .= $pick($all);
    }

    return str_shuffle($password);
}

/**
 * @return array{ok: bool, message: string, account_id?: int, temp_password?: string}
 */
function xander_institution_register_portal_account(
    mysqli $conn,
    int $universityId,
    string $contactName,
    string $contactTitle,
    string $email,
    ?string $password = null,
    string $phone = ''
): array {
    xander_institution_portal_ensure_schema($conn);

    $email = xander_institution_email_norm($email);
    $contactName = trim($contactName);
    $contactTitle = trim($contactTitle);
    $phone = trim($phone);

    if ($universityId <= 0) {
        return ['ok' => false, 'message' => 'Please select or enter a valid institution.'];
    }
    if ($contactName === '') {
        return ['ok' => false, 'message' => 'Contact name is required.'];
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'A valid login email is required.'];
    }

    $plainPassword = trim((string) $password);
    if ($plainPassword === '') {
        $plainPassword = xander_institution_generate_temp_password();
    } elseif (strlen($plainPassword) < 8) {
        return ['ok' => false, 'message' => 'Password must be at least 8 characters.'];
    }

    $chkUni = $conn->prepare('SELECT id FROM institution_portal_accounts WHERE university_id = ? LIMIT 1');
    if ($chkUni) {
        $chkUni->bind_param('i', $universityId);
        $chkUni->execute();
        if ($chkUni->get_result()->fetch_assoc()) {
            $chkUni->close();

            return ['ok' => false, 'message' => 'This institution already has a portal account. Use Institution Login or contact support.'];
        }
        $chkUni->close();
    }

    $chkEmail = $conn->prepare('SELECT id FROM institution_portal_accounts WHERE email = ? LIMIT 1');
    if ($chkEmail) {
        $chkEmail->bind_param('s', $email);
        $chkEmail->execute();
        if ($chkEmail->get_result()->fetch_assoc()) {
            $chkEmail->close();

            return ['ok' => false, 'message' => 'This email is already registered. Please sign in instead.'];
        }
        $chkEmail->close();
    }

    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $st = $conn->prepare('
        INSERT INTO institution_portal_accounts
            (university_id, contact_name, contact_title, email, password_hash, phone, status)
        VALUES (?, ?, ?, ?, ?, ?, "active")
    ');
    if (!$st) {
        return ['ok' => false, 'message' => 'Could not create portal account.'];
    }
    $st->bind_param('isssss', $universityId, $contactName, $contactTitle, $email, $hash, $phone);
    $ok = $st->execute();
    $accountId = (int) $conn->insert_id;
    $st->close();

    if (!$ok || $accountId <= 0) {
        return ['ok' => false, 'message' => 'Registration failed. Please try again.'];
    }

    xander_institution_send_portal_access_email($conn, $email, $contactName, $plainPassword);

    return [
        'ok' => true,
        'message' => 'Registration successful.',
        'account_id' => $accountId,
        'temp_password' => $plainPassword,
    ];
}

function xander_institution_send_portal_access_email(
    mysqli $conn,
    string $email,
    string $contactName,
    string $plainPassword
): bool {
    $email = xander_institution_email_norm($email);
    if ($email === '') {
        return false;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $loginUrl = $scheme . '://' . $host . pcvc_url('/institution-login.php') . '?email=' . rawurlencode($email);

    try {
        $mail = app_mailer();
        if (method_exists($mail, 'setFrom')) {
            $mail->setFrom(PCVC_COMPANY_SUPPORT_EMAIL, PCVC_COMPANY_DISPLAY_NAME);
        }
        $mail->clearAddresses();
        $mail->addAddress($email, $contactName);
        $mail->Subject = 'Your institution portal access — ' . PCVC_COMPANY_DISPLAY_NAME;
        $mail->isHTML(true);
        $mail->Body = '
      <div style="font-family:Arial,sans-serif;line-height:1.6;color:#111">
        <h2 style="margin:0 0 12px 0">Institution Portal Access</h2>
        <p>Hello <strong>' . htmlspecialchars($contactName, ENT_QUOTES, 'UTF-8') . '</strong>,</p>
        <p>Your institution portal account is ready. Use the temporary password below to sign in. You can change it later from your profile in the dashboard.</p>
        <p><a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '">Open Institution Portal</a></p>
        <p>Email: <strong>' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</strong><br>
        Temporary password: <strong>' . htmlspecialchars($plainPassword, ENT_QUOTES, 'UTF-8') . '</strong></p>
      </div>';
        $mail->AltBody = "Institution portal\nLogin: {$loginUrl}\nEmail: {$email}\nTemporary password: {$plainPassword}\nYou can change your password from your profile in the dashboard.\n";
        $mail->send();

        return true;
    } catch (Throwable $e) {
        error_log('[institution_portal_email] ' . $e->getMessage());

        return false;
    }
}

/**
 * @return array{ok: bool, message: string, account?: array<string, mixed>}
 */
function xander_institution_authenticate(mysqli $conn, string $email, string $password): array
{
    xander_institution_portal_ensure_schema($conn);

    $email = xander_institution_email_norm($email);
    if ($email === '' || $password === '') {
        return ['ok' => false, 'message' => 'Please enter email and password.'];
    }

    $st = $conn->prepare('
        SELECT ipa.*, u.name AS university_name
        FROM institution_portal_accounts ipa
        INNER JOIN universities u ON u.id = ipa.university_id
        WHERE ipa.email = ?
        LIMIT 1
    ');
    if (!$st) {
        return ['ok' => false, 'message' => 'System error. Please try again.'];
    }
    $st->bind_param('s', $email);
    $st->execute();
    $acc = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$acc || ($acc['status'] ?? '') !== 'active') {
        return ['ok' => false, 'message' => 'Invalid email or password.'];
    }
    if (!password_verify($password, (string) ($acc['password_hash'] ?? ''))) {
        return ['ok' => false, 'message' => 'Invalid email or password.'];
    }

    return ['ok' => true, 'message' => 'OK', 'account' => $acc];
}

/**
 * Dashboard stats for one university.
 *
 * @return array<string, int>
 */
function xander_institution_dashboard_stats(mysqli $conn, int $universityId): array
{
    $stats = [
        'applications_primary' => 0,
        'applications_choices' => 0,
        'applications_total' => 0,
    ];
    if ($universityId <= 0) {
        return $stats;
    }

    $st = $conn->prepare('SELECT COUNT(*) AS c FROM student_applications WHERE university_id = ?');
    if ($st) {
        $st->bind_param('i', $universityId);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        $stats['applications_primary'] = (int) ($row['c'] ?? 0);
    }

    if ($conn->query("SHOW TABLES LIKE 'application_study_choices'")->num_rows > 0) {
        $st2 = $conn->prepare('SELECT COUNT(DISTINCT application_id) AS c FROM application_study_choices WHERE university_id = ?');
        if ($st2) {
            $st2->bind_param('i', $universityId);
            $st2->execute();
            $row2 = $st2->get_result()->fetch_assoc();
            $st2->close();
            $stats['applications_choices'] = (int) ($row2['c'] ?? 0);
        }
    }

    $stats['applications_total'] = max($stats['applications_primary'], $stats['applications_choices']);

    return $stats;
}

/**
 * Default empty profile row for a university.
 *
 * @return array<string, mixed>
 */
function xander_institution_default_profile(int $universityId): array
{
    return [
        'university_id' => $universityId,
        'scholarship_program_name' => '',
        'scholarship_tagline' => '',
        'scholarship_summary' => '',
        'scholarship_eligibility' => '',
        'scholarship_benefits' => '',
        'scholarship_amount_notes' => '',
        'scholarship_deadline' => '',
        'scholarship_apply_url' => '',
        'loan_program_name' => '',
        'loan_institution_name' => '',
        'loan_summary' => '',
        'loan_coverage' => '',
        'loan_eligibility' => '',
        'loan_rates_notes' => '',
        'loan_contact_email' => '',
        'loan_apply_url' => '',
        'profile_complete_scholarship' => 0,
        'profile_complete_loan' => 0,
        'homepage_published' => 0,
    ];
}

/**
 * @return array<string, mixed>
 */
function xander_institution_load_profile(mysqli $conn, int $universityId): array
{
    xander_institution_portal_ensure_schema($conn);
    $defaults = xander_institution_default_profile($universityId);
    if ($universityId <= 0) {
        return $defaults;
    }

    $st = $conn->prepare('SELECT * FROM institution_university_profiles WHERE university_id = ? LIMIT 1');
    if (!$st) {
        return $defaults;
    }
    $st->bind_param('i', $universityId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    return $row ? array_merge($defaults, $row) : $defaults;
}

/**
 * Dashboard overview metrics (field fill %, documents, next steps).
 *
 * @param array<string, mixed> $profile
 * @return array<string, mixed>
 */
function xander_institution_dashboard_overview(array $profile, int $docsScholarship, int $docsLoan): array
{
    $schKeys = [
        'scholarship_program_name',
        'scholarship_summary',
        'scholarship_tagline',
        'scholarship_eligibility',
        'scholarship_benefits',
        'scholarship_amount_notes',
        'scholarship_deadline',
        'scholarship_apply_url',
    ];
    $loanKeys = [
        'loan_institution_name',
        'loan_summary',
        'loan_program_name',
        'loan_coverage',
        'loan_eligibility',
        'loan_rates_notes',
        'loan_contact_email',
        'loan_apply_url',
    ];

    $pctForKeys = static function (array $row, array $keys): int {
        if ($keys === []) {
            return 0;
        }
        $filled = 0;
        foreach ($keys as $key) {
            if (trim((string) ($row[$key] ?? '')) !== '') {
                $filled++;
            }
        }

        return (int) round(($filled / count($keys)) * 100);
    };

    $schPct = $pctForKeys($profile, $schKeys);
    $loanPct = $pctForKeys($profile, $loanKeys);
    $overallPct = (int) round(($schPct + $loanPct) / 2);
    $docsTotal = $docsScholarship + $docsLoan;

    $nextSteps = [];
    if ($schPct < 100) {
        $nextSteps[] = [
            'icon' => 'fa-award',
            'tone' => 'sch',
            'text' => 'Complete your scholarship program details',
            'url' => 'index.php?tab=scholarship',
        ];
    }
    if ($loanPct < 100) {
        $nextSteps[] = [
            'icon' => 'fa-hand-holding-dollar',
            'tone' => 'loan',
            'text' => 'Add your loan partnership information',
            'url' => 'index.php?tab=loan',
        ];
    }
    if ($docsScholarship === 0 && $schPct > 0) {
        $nextSteps[] = [
            'icon' => 'fa-file-arrow-up',
            'tone' => 'sch',
            'text' => 'Upload scholarship brochures or guides',
            'url' => 'index.php?tab=scholarship',
        ];
    }
    if ($docsLoan === 0 && $loanPct > 0) {
        $nextSteps[] = [
            'icon' => 'fa-file-invoice-dollar',
            'tone' => 'loan',
            'text' => 'Upload loan program documents',
            'url' => 'index.php?tab=loan',
        ];
    }
    if ($nextSteps === []) {
        $nextSteps[] = [
            'icon' => 'fa-circle-check',
            'tone' => 'ok',
            'text' => 'Content looks great — review the live preview before publishing',
            'url' => 'index.php?tab=scholarship',
        ];
    }

    return [
        'scholarship_pct' => $schPct,
        'loan_pct' => $loanPct,
        'overall_pct' => $overallPct,
        'docs_scholarship' => $docsScholarship,
        'docs_loan' => $docsLoan,
        'docs_total' => $docsTotal,
        'scholarship_complete' => !empty($profile['profile_complete_scholarship']),
        'loan_complete' => !empty($profile['profile_complete_loan']),
        'homepage_published' => !empty($profile['homepage_published']),
        'scholarship_title' => trim((string) ($profile['scholarship_program_name'] ?? '')),
        'loan_title' => trim((string) ($profile['loan_institution_name'] ?? '')),
        'next_steps' => $nextSteps,
    ];
}

/**
 * @return array<int, array<string, mixed>>
 */
function xander_institution_list_documents(mysqli $conn, int $universityId, ?string $section = null): array
{
    if ($universityId <= 0) {
        return [];
    }
    if ($section !== null && $section !== '') {
        $st = $conn->prepare('SELECT * FROM institution_profile_documents WHERE university_id = ? AND section = ? ORDER BY uploaded_at DESC');
        if (!$st) {
            return [];
        }
        $st->bind_param('is', $universityId, $section);
    } else {
        $st = $conn->prepare('SELECT * FROM institution_profile_documents WHERE university_id = ? ORDER BY section, uploaded_at DESC');
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
 * @param array<string, mixed> $post
 * @return array{ok: bool, message: string}
 */
function xander_institution_save_profile(mysqli $conn, int $universityId, array $post): array
{
    xander_institution_portal_ensure_schema($conn);
    if ($universityId <= 0) {
        return ['ok' => false, 'message' => 'Invalid institution.'];
    }

    $schName = trim((string) ($post['scholarship_program_name'] ?? ''));
    $schSummary = trim((string) ($post['scholarship_summary'] ?? ''));
    $loanName = trim((string) ($post['loan_institution_name'] ?? ''));
    $loanSummary = trim((string) ($post['loan_summary'] ?? ''));

    $schComplete = ($schName !== '' && $schSummary !== '') ? 1 : 0;
    $loanComplete = ($loanName !== '' && $loanSummary !== '') ? 1 : 0;
    $homepagePublished = !empty($post['homepage_published']) ? 1 : 0;

    $deadline = trim((string) ($post['scholarship_deadline'] ?? ''));
    $deadlineVal = $deadline !== '' ? $deadline : null;

    $sql = '
        INSERT INTO institution_university_profiles (
            university_id,
            scholarship_program_name, scholarship_tagline, scholarship_summary,
            scholarship_eligibility, scholarship_benefits, scholarship_amount_notes,
            scholarship_deadline, scholarship_apply_url,
            loan_program_name, loan_institution_name, loan_summary, loan_coverage,
            loan_eligibility, loan_rates_notes, loan_contact_email, loan_apply_url,
            profile_complete_scholarship, profile_complete_loan, homepage_published
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            scholarship_program_name = VALUES(scholarship_program_name),
            scholarship_tagline = VALUES(scholarship_tagline),
            scholarship_summary = VALUES(scholarship_summary),
            scholarship_eligibility = VALUES(scholarship_eligibility),
            scholarship_benefits = VALUES(scholarship_benefits),
            scholarship_amount_notes = VALUES(scholarship_amount_notes),
            scholarship_deadline = VALUES(scholarship_deadline),
            scholarship_apply_url = VALUES(scholarship_apply_url),
            loan_program_name = VALUES(loan_program_name),
            loan_institution_name = VALUES(loan_institution_name),
            loan_summary = VALUES(loan_summary),
            loan_coverage = VALUES(loan_coverage),
            loan_eligibility = VALUES(loan_eligibility),
            loan_rates_notes = VALUES(loan_rates_notes),
            loan_contact_email = VALUES(loan_contact_email),
            loan_apply_url = VALUES(loan_apply_url),
            profile_complete_scholarship = VALUES(profile_complete_scholarship),
            profile_complete_loan = VALUES(profile_complete_loan),
            homepage_published = VALUES(homepage_published)
    ';

    $st = $conn->prepare($sql);
    if (!$st) {
        return ['ok' => false, 'message' => 'Could not save profile.'];
    }

    $schTag = trim((string) ($post['scholarship_tagline'] ?? ''));
    $schElig = trim((string) ($post['scholarship_eligibility'] ?? ''));
    $schBen = trim((string) ($post['scholarship_benefits'] ?? ''));
    $schAmt = trim((string) ($post['scholarship_amount_notes'] ?? ''));
    $schUrl = trim((string) ($post['scholarship_apply_url'] ?? ''));
    $loanProg = trim((string) ($post['loan_program_name'] ?? ''));
    $loanCov = trim((string) ($post['loan_coverage'] ?? ''));
    $loanElig = trim((string) ($post['loan_eligibility'] ?? ''));
    $loanRates = trim((string) ($post['loan_rates_notes'] ?? ''));
    $loanEmail = trim((string) ($post['loan_contact_email'] ?? ''));
    $loanUrl = trim((string) ($post['loan_apply_url'] ?? ''));

    $st->bind_param(
        'isssssssssssssssiii',
        $universityId,
        $schName,
        $schTag,
        $schSummary,
        $schElig,
        $schBen,
        $schAmt,
        $deadlineVal,
        $schUrl,
        $loanProg,
        $loanName,
        $loanSummary,
        $loanCov,
        $loanElig,
        $loanRates,
        $loanEmail,
        $loanUrl,
        $schComplete,
        $loanComplete,
        $homepagePublished
    );
    $ok = $st->execute();
    $st->close();

    if (!$ok) {
        return ['ok' => false, 'message' => 'Save failed. Please try again.'];
    }

    return ['ok' => true, 'message' => 'Your scholarship and loan information has been saved.'];
}

/**
 * @return array{ok: bool, message: string, doc_id?: int}
 */
function xander_institution_store_upload(
    mysqli $conn,
    int $universityId,
    string $section,
    array $file,
    string $label = ''
): array {
    xander_institution_portal_ensure_schema($conn);
    if ($universityId <= 0) {
        return ['ok' => false, 'message' => 'Invalid institution.'];
    }
    $allowedSections = ['scholarship', 'loan', 'general'];
    if (!in_array($section, $allowedSections, true)) {
        $section = 'general';
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => 'Upload failed or no file selected.'];
    }

    $maxBytes = 12 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxBytes) {
        return ['ok' => false, 'message' => 'File is too large (max 12 MB).'];
    }

    $orig = basename((string) ($file['name'] ?? 'document'));
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $safeExt = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'bin';
    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($safeExt, $allowed, true)) {
        return ['ok' => false, 'message' => 'Allowed types: PDF, Word, JPG, PNG.'];
    }

    $dir = dirname(__DIR__) . '/uploads/institution/' . $universityId;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'message' => 'Could not create upload folder.'];
    }

    $stored = $section . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $safeExt;
    $dest = $dir . '/' . $stored;
    if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
        return ['ok' => false, 'message' => 'Could not save uploaded file.'];
    }

    $relative = 'uploads/institution/' . $universityId . '/' . $stored;
    $mime = (string) ($file['type'] ?? 'application/octet-stream');
    $size = (int) ($file['size'] ?? 0);
    $label = trim($label);

    $st = $conn->prepare('
        INSERT INTO institution_profile_documents
            (university_id, section, label, original_name, stored_path, mime_type, size_bytes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    if (!$st) {
        @unlink($dest);

        return ['ok' => false, 'message' => 'Database error saving document.'];
    }
    $st->bind_param('isssssi', $universityId, $section, $label, $orig, $relative, $mime, $size);
    $st->execute();
    $docId = (int) $conn->insert_id;
    $st->close();

    return ['ok' => true, 'message' => 'Document uploaded.', 'doc_id' => $docId];
}

function xander_institution_delete_document(mysqli $conn, int $docId, int $universityId): bool
{
    $st = $conn->prepare('SELECT stored_path FROM institution_profile_documents WHERE id = ? AND university_id = ? LIMIT 1');
    if (!$st) {
        return false;
    }
    $st->bind_param('ii', $docId, $universityId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$row) {
        return false;
    }

    $rel = trim((string) ($row['stored_path'] ?? ''));
    if ($rel !== '' && !str_contains($rel, '..')) {
        $abs = dirname(__DIR__) . '/' . ltrim($rel, '/');
        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    $del = $conn->prepare('DELETE FROM institution_profile_documents WHERE id = ? AND university_id = ? LIMIT 1');
    if (!$del) {
        return false;
    }
    $del->bind_param('ii', $docId, $universityId);
    $del->execute();
    $ok = $del->affected_rows > 0;
    $del->close();

    return $ok;
}

function xander_institution_h(?string $v): string
{
    return htmlspecialchars(trim((string) $v), ENT_QUOTES, 'UTF-8');
}

function xander_institution_str_or(string $value, string $fallback): string
{
    return trim($value) !== '' ? $value : $fallback;
}

function xander_institution_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach ($parts as $part) {
        if ($part !== '') {
            $letters .= strtoupper($part[0]);
        }
        if (strlen($letters) >= 2) {
            break;
        }
    }

    return $letters !== '' ? $letters : 'IN';
}

/**
 * @return array<string, mixed>|null
 */
function xander_institution_load_account(mysqli $conn, int $accountId): ?array
{
    if ($accountId <= 0) {
        return null;
    }
    $st = $conn->prepare('
        SELECT ipa.*, u.name AS university_name
        FROM institution_portal_accounts ipa
        INNER JOIN universities u ON u.id = ipa.university_id
        WHERE ipa.id = ?
        LIMIT 1
    ');
    if (!$st) {
        return null;
    }
    $st->bind_param('i', $accountId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    return $row ?: null;
}

/**
 * @param array<string, mixed> $post
 * @return array{ok: bool, message: string}
 */
function xander_institution_update_account_profile(mysqli $conn, int $accountId, array $post): array
{
    if ($accountId <= 0) {
        return ['ok' => false, 'message' => 'Invalid account.'];
    }

    $account = xander_institution_load_account($conn, $accountId);
    if (!$account) {
        return ['ok' => false, 'message' => 'Account not found.'];
    }

    $contactName = trim((string) ($post['contact_name'] ?? ''));
    $contactTitle = trim((string) ($post['contact_title'] ?? ''));
    $phone = trim((string) ($post['phone'] ?? ''));
    $email = xander_institution_email_norm((string) ($post['email'] ?? ''));

    if ($contactName === '') {
        return ['ok' => false, 'message' => 'Your name is required.'];
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'A valid email is required.'];
    }

    $chk = $conn->prepare('SELECT id FROM institution_portal_accounts WHERE email = ? AND id <> ? LIMIT 1');
    if ($chk) {
        $chk->bind_param('si', $email, $accountId);
        $chk->execute();
        if ($chk->get_result()->fetch_assoc()) {
            $chk->close();

            return ['ok' => false, 'message' => 'This email is already used by another account.'];
        }
        $chk->close();
    }

    $st = $conn->prepare('
        UPDATE institution_portal_accounts
        SET contact_name = ?, contact_title = ?, email = ?, phone = ?
        WHERE id = ?
        LIMIT 1
    ');
    if (!$st) {
        return ['ok' => false, 'message' => 'Could not update profile.'];
    }
    $st->bind_param('ssssi', $contactName, $contactTitle, $email, $phone, $accountId);
    $ok = $st->execute();
    $st->close();

    if (!$ok) {
        return ['ok' => false, 'message' => 'Profile update failed.'];
    }

    return ['ok' => true, 'message' => 'Profile updated successfully.'];
}

/**
 * @return array{ok: bool, message: string}
 */
function xander_institution_change_account_password(
    mysqli $conn,
    int $accountId,
    string $currentPassword,
    string $newPassword,
    string $confirmPassword
): array {
    if ($accountId <= 0) {
        return ['ok' => false, 'message' => 'Invalid account.'];
    }
    if ($newPassword !== $confirmPassword) {
        return ['ok' => false, 'message' => 'New passwords do not match.'];
    }
    if (strlen($newPassword) < 8) {
        return ['ok' => false, 'message' => 'New password must be at least 8 characters.'];
    }

    $st = $conn->prepare('SELECT password_hash FROM institution_portal_accounts WHERE id = ? LIMIT 1');
    if (!$st) {
        return ['ok' => false, 'message' => 'System error.'];
    }
    $st->bind_param('i', $accountId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$row || !password_verify($currentPassword, (string) ($row['password_hash'] ?? ''))) {
        return ['ok' => false, 'message' => 'Current password is incorrect.'];
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $up = $conn->prepare('UPDATE institution_portal_accounts SET password_hash = ? WHERE id = ? LIMIT 1');
    if (!$up) {
        return ['ok' => false, 'message' => 'Could not update password.'];
    }
    $up->bind_param('si', $hash, $accountId);
    $ok = $up->execute();
    $up->close();

    if (!$ok) {
        return ['ok' => false, 'message' => 'Password update failed.'];
    }

    return ['ok' => true, 'message' => 'Password changed successfully.'];
}
