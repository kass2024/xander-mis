<?php
declare(strict_types=1);

/** @return array<string, string> */
function xander_prescreening_service_types(): array
{
    return [
        'study_abroad' => 'Study Abroad',
        'work_abroad'  => 'Work Abroad',
    ];
}

/** @return list<string> */
function xander_prescreening_education_levels(): array
{
    return [
        'High School / Secondary',
        'Diploma / Certificate',
        "Bachelor's Degree",
        "Master's Degree",
        'PhD / Doctorate',
        'Other',
    ];
}

/** @return list<string> */
function xander_prescreening_course_programs(): array
{
    return [
        'Business / Management',
        'Computer Science / IT',
        'Engineering',
        'Health / Medicine / Nursing',
        'Law',
        'Education',
        'Hospitality / Tourism',
        'Arts / Design',
        'Social Sciences',
        'Natural Sciences',
        'Other (specify in comments)',
    ];
}

/** @return list<string> */
function xander_prescreening_tuition_budgets(): array
{
    return [
        'Under $5,000 / year',
        '$5,000 – $10,000 / year',
        '$10,000 – $15,000 / year',
        '$15,000 – $25,000 / year',
        'Over $25,000 / year',
        'Not sure yet',
    ];
}

/** @return list<string> */
function xander_prescreening_planned_intakes(): array
{
    $year = (int) date('Y');
    $out = [];
    foreach (['Spring', 'Summer', 'Fall', 'Winter'] as $term) {
        for ($y = $year; $y <= $year + 3; $y++) {
            $out[] = $term . ' ' . $y;
        }
    }
    $out[] = 'Flexible / Not sure';

    return $out;
}

/** @return list<string> */
function xander_prescreening_english_tests(): array
{
    return ['No', 'IELTS', 'TOEFL', 'Duolingo', 'Other'];
}

/** @return list<string> */
function xander_prescreening_yes_no_maybe(): array
{
    return ['Yes', 'No', 'Maybe / open to discuss'];
}

/**
 * Countries for study interest (DB + popular destinations).
 *
 * @return list<string>
 */
function xander_prescreening_study_countries(?mysqli $conn = null): array
{
    $fromDb = xander_prescreening_countries_from_db($conn);
    $popular = [
        'Canada', 'United States', 'United Kingdom', 'Australia', 'Germany',
        'France', 'Netherlands', 'Ireland', 'Malta', 'Cyprus', 'India',
        'Turkey', 'Poland', 'Hungary', 'Rwanda', 'Kenya', 'South Africa',
    ];
    $merged = array_values(array_unique(array_merge($popular, $fromDb)));
    sort($merged, SORT_NATURAL | SORT_FLAG_CASE);

    return $merged;
}

/**
 * Work-abroad destination countries (full list from DB when available).
 *
 * @return list<string>
 */
function xander_prescreening_work_countries(?mysqli $conn = null): array
{
    $fromDb = xander_prescreening_countries_from_db($conn);
    if ($fromDb !== []) {
        return $fromDb;
    }

    return [
        'Canada', 'United States', 'United Kingdom', 'Germany', 'Poland',
        'Romania', 'Hungary', 'Czech Republic', 'Spain', 'Portugal', 'Italy',
        'France', 'Netherlands', 'Belgium', 'Austria', 'Switzerland', 'Ireland',
        'Malta', 'Cyprus', 'United Arab Emirates', 'Qatar', 'Saudi Arabia',
        'Kuwait', 'Oman', 'Bahrain', 'Australia', 'New Zealand', 'Japan',
        'South Korea', 'Singapore', 'Malaysia', 'Rwanda', 'Kenya', 'Uganda',
        'Tanzania', 'South Africa', 'Zambia', 'Nigeria', 'Ghana', 'Morocco',
        'Turkey', 'India',
    ];
}

/** @return list<string> */
function xander_prescreening_countries_from_db(?mysqli $conn): array
{
    if (!$conn) {
        return [];
    }
    $res = @$conn->query('SELECT name FROM countries ORDER BY name ASC');
    if (!$res) {
        return [];
    }
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $name = trim((string) ($row['name'] ?? ''));
        if ($name !== '') {
            $out[] = $name;
        }
    }
    $res->free();

    return $out;
}

/** @return array<string, string> Work-abroad document checklist labels (no file key). */
function xander_prescreening_work_checklist_labels(): array
{
    return [
        'passport'           => 'Valid passport (minimum 1 year validity)',
        'cv'                 => 'Updated CV / Resume',
        'passport_photo'     => 'Passport-size photo',
        'education_cert'     => 'Highest education certificate',
        'emergency_contact'  => 'Emergency contact information (full names, phone number, and address)',
        'birth_certificate'  => 'Birth certificate',
    ];
}

/** @return array<string, string> Optional uploads for study abroad. */
function xander_prescreening_document_labels(): array
{
    return [
        'doc_valid_passport' => 'Valid Passport',
        'doc_degree_transcripts' => 'Degree / Academic Transcripts',
        'doc_high_school' => 'High School Certificate',
        'doc_cv_resume' => 'CV / Resume',
        'doc_recommendation' => 'Recommendation Letter(s)',
        'doc_personal_statement' => 'Personal Statement / Motivation Letter',
        'doc_english_certificate' => 'English Proficiency Certificate',
        'doc_birth_certificate' => 'Birth Certificate',
        'doc_payment_proof' => 'Application / Payment Proof',
    ];
}

/** @return array<string, string> Optional uploads for work abroad. */
function xander_prescreening_work_document_labels(): array
{
    return [
        'doc_valid_passport'      => 'Valid passport (minimum 1 year validity)',
        'doc_cv_resume'           => 'Updated CV / Resume',
        'doc_passport_photo'      => 'Passport-size photo',
        'doc_degree_transcripts'  => 'Highest education certificate',
        'doc_birth_certificate'   => 'Birth certificate',
    ];
}

/**
 * Render a <select> with placeholder.
 *
 * @param list<string> $options
 */
function xander_prescreening_render_select(
    string $name,
    array $options,
    string $value,
    bool $required,
    bool $disabled,
    string $placeholder = 'Select an option'
): string {
    $req = $required ? ' required' : '';
    $dis = $disabled ? ' disabled' : '';
    $html = '<select name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" class="form-select"' . $req . $dis . '>';
    $html .= '<option value="">' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '</option>';
    foreach ($options as $opt) {
        $sel = ($value === $opt) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
            . htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $html .= '</select>';

    return $html;
}

/**
 * Parse posted country list (multi-select array or legacy single field).
 *
 * @param array<string, mixed> $post
 * @return list<string>
 */
function xander_prescreening_parse_country_list_from_post(array $post, string $arrayKey, string $singleKey = ''): array
{
    $list = [];
    if (isset($post[$arrayKey]) && is_array($post[$arrayKey])) {
        foreach ($post[$arrayKey] as $c) {
            $c = trim((string) $c);
            if ($c !== '') {
                $list[] = $c;
            }
        }
    } elseif ($singleKey !== '' && trim((string) ($post[$singleKey] ?? '')) !== '') {
        $raw = trim((string) $post[$singleKey]);
        foreach (preg_split('/\s*,\s*/', $raw) ?: [] as $part) {
            $part = trim((string) $part);
            if ($part !== '') {
                $list[] = $part;
            }
        }
    }

    return array_values(array_unique($list));
}

/**
 * @return list<string>
 */
function xander_prescreening_split_stored_countries(string $stored): array
{
    $stored = trim($stored);
    if ($stored === '') {
        return [];
    }
    if (str_starts_with($stored, '[')) {
        $decoded = json_decode($stored, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $decoded)));
        }
    }

    return array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/', $stored) ?: [])));
}

/**
 * @param list<string> $countries
 */
function xander_prescreening_format_country_list(array $countries): string
{
    $countries = array_values(array_unique(array_filter(array_map('trim', $countries))));

    return implode(', ', $countries);
}

/**
 * Multi-select for countries (at least min selections enforced client/server-side).
 *
 * @param list<string> $options
 * @param list<string>|string $selected
 */
function xander_prescreening_render_country_multi_select(
    string $name,
    array $options,
    $selected,
    bool $readonly,
    int $min = 2,
    string $placeholder = 'Select countries'
): string {
    $selectedList = is_array($selected)
        ? $selected
        : xander_prescreening_split_stored_countries((string) $selected);
    $dis = $readonly ? ' disabled' : '';
    $minAttr = $min > 0 ? ' data-min-selections="' . (int) $min . '"' : '';
    $html = '<select name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '[]" class="form-select prescreen-country-multi" multiple size="8"' . $dis . $minAttr . '>';
    $html .= '<option value="" disabled>' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '</option>';
    foreach ($options as $opt) {
        $sel = in_array($opt, $selectedList, true) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
            . htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $html .= '</select>';
    if ($min > 0 && !$readonly) {
        $html .= '<div class="form-text">Select at least ' . (int) $min . ' countries (hold Ctrl/Cmd to select multiple).</div>';
    }

    return $html;
}
