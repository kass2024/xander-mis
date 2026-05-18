<?php
declare(strict_types=1);

/**
 * Study vs job application AI autofill configuration.
 */

function ai_autofill_form_mode(): string
{
    return (defined('XANDER_AI_AUTOFILL_FORM') && XANDER_AI_AUTOFILL_FORM === 'job') ? 'job' : 'study';
}

/** @return array<string, string> */
function ai_autofill_field_labels(string $mode, string $lang): array
{
    if ($mode === 'job') {
        return [
            'passport' => 'Passport',
            'photo' => 'Passport Photo',
            'cv' => 'CV / Resume',
            'academic_certificates' => 'Academic Certificate',
            'national_id' => 'National ID / Birth Certificate',
        ];
    }

    return [
        'degree_transcripts' => $lang === 'fr' ? 'Diplomes / Releves de Notes' : 'Degree / Academic Transcripts',
        'high_school_degree' => $lang === 'fr' ? 'Certificat de Lycee' : 'High School Certificate',
        'valid_passport' => $lang === 'fr' ? 'Passeport Valide' : 'Valid Passport',
        'recommendation_letters' => $lang === 'fr' ? 'Lettres de Recommandation' : 'Recommendation Letter(s)',
        'personal_statement' => $lang === 'fr' ? 'Lettre de Motivation' : 'Personal Statement / Motivation Letter',
        'cv_resume' => $lang === 'fr' ? 'CV / Curriculum Vitae' : 'CV / Resume',
        'english_certificate' => $lang === 'fr' ? 'Certificat d Anglais' : 'English Proficiency Certificate',
        'birth_certificate' => $lang === 'fr' ? 'Certificat de Naissance' : 'Birth Certificate',
        'payment_proof' => $lang === 'fr' ? 'Preuve de Paiement' : 'Application / Payment Proof',
    ];
}

function ai_autofill_system_prompt(string $mode, string $lang): string
{
    if ($mode === 'job') {
        return <<<'PROMPT'
You are a job-placement document classification and extraction assistant.

Classify each uploaded document into exactly one of:
- passport
- photo
- cv
- academic_certificates
- national_id
- unknown

Rules:
1. Extract only applicant facts explicitly visible in the document.
2. Never invent data.
3. Return country names, not codes.
4. Do NOT extract phone numbers from any document (especially CV/resume). The applicant enters their phone on the form.
5. For CV/resume documents, prioritize contact block: email, address, nationality — never phone.
6. For passport photos (headshot only), classify as photo.
7. Return JSON only.

JSON schema:
{
  "document_type": "passport|photo|cv|academic_certificates|national_id|unknown",
  "confidence": 0.0,
  "summary": "short summary",
  "fields": {
    "first_name": "",
    "last_name": "",
    "email": "",
    "work_country": "",
    "address_country": "",
    "province_state": "",
    "district": "",
    "sector": "",
    "cell_ward": "",
    "village": "",
    "emergency_full_name": "",
    "emergency_relationship": "",
    "emergency_email": ""
  }
}
PROMPT;
    }

    return <<<PROMPT
You are an admissions document classification and extraction assistant.

Classify each uploaded document into exactly one of:
- valid_passport
- degree_transcripts
- high_school_degree
- cv_resume
- recommendation_letters
- personal_statement
- english_certificate
- birth_certificate
- payment_proof
- unknown

Rules:
1. Extract only applicant facts explicitly visible in the document.
2. Never invent data.
3. If the document mostly refers to someone other than the applicant, keep fields empty.
4. Recommendation letters may mention other people; only extract student data if it is clearly about the applicant.
5. Return country names, not codes.
6. When the document is a CV or resume, prioritize extracting the main contact block first: email, phone, address, city, nationality, and education institution details.
7. For CV/resume documents, if the phone is written locally but the country is explicit elsewhere in the same document, convert it to a full international number in phone_international.
8. Return the strongest real applicant email address visible in the document, not a school or company address unless it is clearly the applicant contact.
9. Ignore sample, placeholder, dummy, or template contact details.
10. Return JSON only.

JSON schema:
{
  "document_type": "valid_passport|degree_transcripts|high_school_degree|cv_resume|recommendation_letters|personal_statement|english_certificate|birth_certificate|payment_proof|unknown",
  "confidence": 0.0,
  "summary": "short summary",
  "fields": {
    "first_name": "",
    "last_name": "",
    "email": "",
    "phone_international": "",
    "dob": "",
    "gender": "",
    "passport_number": "",
    "student_national_id": "",
    "country_of_birth": "",
    "city_of_birth": "",
    "nationality": "",
    "second_nationality": "",
    "address_line1": "",
    "address_line2": "",
    "city": "",
    "state_province": "",
    "postal_code": "",
    "previous_institution_name": "",
    "previous_institution_city": "",
    "previous_institution_province": "",
    "previous_institution_country": "",
    "previous_institution_post_code": "",
    "previous_study_start": "",
    "previous_study_graduation": "",
    "language_of_instruction": "",
    "father_first_name": "",
    "father_last_name": "",
    "mother_first_name": "",
    "mother_last_name": ""
  }
}
PROMPT;
}

function ai_autofill_field_priority(string $field, string $source, string $mode): int
{
    if ($mode === 'job') {
        $preferences = [
            'first_name' => ['passport', 'national_id', 'cv', 'academic_certificates'],
            'last_name' => ['passport', 'national_id', 'cv', 'academic_certificates'],
            'email' => ['cv', 'passport'],
            'work_country_id' => ['cv', 'passport'],
            'address_country_id' => ['cv', 'passport', 'national_id'],
            'province_state' => ['cv', 'passport', 'national_id'],
            'district' => ['cv', 'national_id'],
            'sector' => ['cv', 'national_id'],
            'cell_ward' => ['cv', 'national_id'],
            'village' => ['cv', 'national_id'],
            'emergency_full_name' => ['cv'],
            'emergency_relationship' => ['cv'],
            'emergency_email' => ['cv'],
        ];
        $list = $preferences[$field] ?? ['passport', 'cv', 'national_id', 'academic_certificates'];
        $index = array_search($source, $list, true);

        return $index === false ? 0 : (count($list) - $index);
    }

    $preferences = [
        'first_name' => ['valid_passport', 'birth_certificate', 'cv_resume', 'degree_transcripts', 'high_school_degree'],
        'last_name' => ['valid_passport', 'birth_certificate', 'cv_resume', 'degree_transcripts', 'high_school_degree'],
        'dob' => ['valid_passport', 'birth_certificate', 'degree_transcripts', 'high_school_degree'],
        'gender' => ['valid_passport', 'birth_certificate'],
        'passport_number' => ['valid_passport'],
        'student_national_id' => ['valid_passport', 'birth_certificate'],
        'country_of_birth' => ['valid_passport', 'birth_certificate'],
        'city_of_birth' => ['valid_passport', 'birth_certificate'],
        'nationality' => ['valid_passport', 'birth_certificate', 'cv_resume'],
        'second_nationality' => ['valid_passport', 'birth_certificate'],
        'email' => ['cv_resume', 'personal_statement', 'recommendation_letters', 'payment_proof'],
        'area_code' => ['cv_resume', 'personal_statement'],
        'phone_number' => ['cv_resume', 'personal_statement'],
        'address_line1' => ['cv_resume', 'valid_passport', 'personal_statement'],
        'address_line2' => ['cv_resume', 'valid_passport', 'personal_statement'],
        'city' => ['cv_resume', 'valid_passport', 'personal_statement'],
        'state_province' => ['cv_resume', 'valid_passport', 'personal_statement'],
        'postal_code' => ['cv_resume', 'valid_passport', 'personal_statement'],
        'previous_institution_name' => ['degree_transcripts', 'high_school_degree', 'english_certificate'],
        'previous_institution_city' => ['degree_transcripts', 'high_school_degree'],
        'previous_institution_province' => ['degree_transcripts', 'high_school_degree'],
        'previous_institution_country' => ['degree_transcripts', 'high_school_degree'],
        'previous_institution_post_code' => ['degree_transcripts', 'high_school_degree'],
        'previous_study_start' => ['degree_transcripts', 'high_school_degree'],
        'previous_study_graduation' => ['degree_transcripts', 'high_school_degree'],
        'language_of_instruction' => ['english_certificate', 'degree_transcripts', 'high_school_degree'],
        'father_first_name' => ['birth_certificate'],
        'father_last_name' => ['birth_certificate'],
        'mother_first_name' => ['birth_certificate'],
        'mother_last_name' => ['birth_certificate'],
    ];

    $list = $preferences[$field] ?? ['valid_passport', 'cv_resume', 'degree_transcripts', 'high_school_degree', 'birth_certificate'];
    $index = array_search($source, $list, true);

    return $index === false ? 0 : (count($list) - $index);
}

/**
 * @param array<string, mixed> $fields
 * @return array<string, string>
 */
/**
 * @param array<string, mixed> $fields
 * @return array<string, string>
 */
function ai_autofill_normalize_job_fields(array $fields, mysqli $conn): array
{
    require_once __DIR__ . '/ai_autofill_utils.php';

    $normalized = [];
    foreach (['first_name', 'last_name', 'province_state', 'district', 'sector', 'cell_ward', 'village', 'emergency_full_name', 'emergency_relationship'] as $field) {
        $value = ai_normalize_text($fields[$field] ?? '');
        if ($value !== '') {
            $normalized[$field] = $value;
        }
    }

    $email = ai_normalize_email($fields['email'] ?? '');
    if ($email !== '') {
        $normalized['email'] = $email;
    }

    $emergencyEmail = ai_normalize_email($fields['emergency_email'] ?? '');
    if ($emergencyEmail !== '') {
        $normalized['emergency_email'] = $emergencyEmail;
    }

    $workCountryId = ai_lookup_country_id($conn, $fields['work_country'] ?? '');
    if ($workCountryId !== '') {
        $normalized['work_country_id'] = $workCountryId;
    }

    $addressCountryId = ai_lookup_country_id($conn, $fields['address_country'] ?? '');
    if ($addressCountryId !== '') {
        $normalized['address_country_id'] = $addressCountryId;
    }

    // Phone numbers are entered by the applicant on the form — never taken from CV/documents.

    return $normalized;
}
