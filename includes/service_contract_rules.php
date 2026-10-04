<?php
declare(strict_types=1);

/**
 * Pure rules for dynamic service contracts.
 * No database access. Safe to unit-test without MySQL.
 */

function xander_sc_service_types(): array
{
    return [
        'study' => 'Study',
        'work'  => 'Work / Job',
        'visit' => 'Visit',
    ];
}

function xander_sc_fee_types(): array
{
    return [
        'upfront'    => 'Upfront Fee',
        'commitment' => 'Commitment Fee',
        'service'    => 'Service Fee',
        'promotion'  => 'Promotion',
        'other'      => 'Other Fee',
    ];
}

function xander_sc_statuses(): array
{
    return [
        'draft'              => 'DRAFT',
        'pending_signature'  => 'PENDING_SIGNATURE',
        'viewed'             => 'VIEWED',
        'signed'             => 'SIGNED',
        'expired'            => 'EXPIRED',
        'cancelled'          => 'CANCELLED',
    ];
}

function xander_sc_is_service(string $service): bool
{
    return isset(xander_sc_service_types()[$service]);
}

function xander_sc_is_fee_type(string $feeType): bool
{
    return isset(xander_sc_fee_types()[$feeType]);
}

function xander_sc_is_status(string $status): bool
{
    return isset(xander_sc_statuses()[$status]);
}

function xander_sc_staff_authorized(?int $adminId): bool
{
    return $adminId !== null && $adminId > 0;
}

function xander_sc_client_type_for_service(string $service): string
{
    return match ($service) {
        'study' => 'Student',
        'work'  => 'Job Applicant',
        'visit' => 'Visitor Visa Applicant',
        default => '',
    };
}

/**
 * Destination words used by the existing fee catalog titles.
 * Longest needles are matched first by xander_sc_destinations_in_title().
 *
 * @return array<string, string> needle => canonical country/region name
 */
function xander_sc_destination_alias_map(): array
{
    return [
        'United Kingdom'  => 'United Kingdom',
        'United States'   => 'United States',
        'Czech Republic'  => 'Czech Republic',
        'New Zealand'     => 'New Zealand',
        'South America'   => 'South America',
        'Middle East'     => 'Middle East',
        'USA'             => 'United States',
        'Canada'          => 'Canada',
        'Europe'          => 'Europe',
        'Netherlands'     => 'Netherlands',
        'Luxembourg'      => 'Luxembourg',
        'Australia'       => 'Australia',
        'Albania'         => 'Albania',
        'Belgium'         => 'Belgium',
        'Bulgaria'        => 'Bulgaria',
        'Belarus'         => 'Belarus',
        'Croatia'         => 'Croatia',
        'Cyprus'          => 'Cyprus',
        'Estonia'         => 'Estonia',
        'Finland'         => 'Finland',
        'Germany'         => 'Germany',
        'Greece'          => 'Greece',
        'Hungary'         => 'Hungary',
        'Italy'           => 'Italy',
        'Malta'           => 'Malta',
        'Poland'          => 'Poland',
        'Portugal'        => 'Portugal',
        'Romania'         => 'Romania',
        'Russia'          => 'Russia',
        'Serbia'          => 'Serbia',
        'Slovakia'        => 'Slovakia',
        'Spain'           => 'Spain',
        'Asia'            => 'Asia',
        'Africa'          => 'Africa',
    ];
}

/** @return list<string> */
function xander_sc_destinations_in_title(string $title): array
{
    $found = [];
    $map = xander_sc_destination_alias_map();
    uksort($map, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

    foreach ($map as $needle => $canonical) {
        $pattern = '/\b' . preg_quote($needle, '/') . '\b/i';
        if (preg_match($pattern, $title)) {
            $found[$canonical] = true;
        }
    }

    $names = array_keys($found);
    sort($names, SORT_NATURAL | SORT_FLAG_CASE);

    return $names;
}

function xander_sc_catalog_section_for_service(string $service): ?string
{
    return match ($service) {
        'work'  => 'job',
        'visit' => 'visit',
        default => null,
    };
}

/** @return list<string> */
function xander_sc_sections_for_service(string $service): array
{
    return match ($service) {
        'study' => ['study', 'credit'],
        'work'  => ['job'],
        'visit' => ['visit'],
        default => [],
    };
}

/**
 * A service is available in a country when staff selected that country,
 * or when an older service title already names the country.
 *
 * @param list<string> $linkedCountryNames
 */
function xander_sc_package_matches_country(string $title, array $linkedCountryNames, string $countryName): bool
{
    $countryName = trim($countryName);
    if ($countryName === '') {
        return false;
    }
    foreach ($linkedCountryNames as $name) {
        if (strcasecmp(trim((string) $name), $countryName) === 0) {
            return true;
        }
    }

    return in_array($countryName, xander_sc_destinations_in_title($title), true);
}

/**
 * @param list<array<string, mixed>> $catalog
 * @return list<array{name:string, ref:string}>
 */
function xander_sc_countries_for_service_catalog(array $catalog, string $service): array
{
    $section = xander_sc_catalog_section_for_service($service);
    if ($section === null) {
        return [];
    }

    $names = [];
    foreach ($catalog as $pkg) {
        if (($pkg['section'] ?? '') !== $section) {
            continue;
        }
        if (($pkg['contract_code'] ?? '') === 'p544') {
            continue;
        }
        foreach (xander_sc_destinations_in_title((string) ($pkg['title'] ?? '')) as $name) {
            $names[$name] = true;
        }
    }

    $out = [];
    $keys = array_keys($names);
    sort($keys, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($keys as $name) {
        $out[] = [
            'name' => $name,
            'ref'  => 'name:' . $name,
        ];
    }

    return $out;
}

/**
 * @param list<array<string, mixed>> $catalog
 * @return list<array<string, mixed>>
 */
function xander_sc_catalog_offerings(array $catalog, string $service, string $countryName): array
{
    $section = xander_sc_catalog_section_for_service($service);
    if ($section === null || $countryName === '') {
        return [];
    }

    $out = [];
    foreach ($catalog as $pkg) {
        if (($pkg['section'] ?? '') !== $section) {
            continue;
        }
        if (($pkg['contract_code'] ?? '') === 'p544') {
            continue;
        }
        $title = (string) ($pkg['title'] ?? '');
        $destinations = xander_sc_destinations_in_title($title);
        if (!in_array($countryName, $destinations, true)) {
            continue;
        }

        $lines = array_values(array_map('strval', $pkg['lines'] ?? []));
        $code = (string) ($pkg['contract_code'] ?? '');
        $row = [
            'id'           => $code,
            'service_type' => $service,
            'active'       => true,
            'country_name' => $countryName,
            'title'        => $title,
            'lines'        => $lines,
            'currency'     => (string) ($pkg['currency'] ?? 'EUR'),
            'catalog_total'=> $pkg['total'] ?? null,
        ];

        $profile = is_array($pkg['profile'] ?? null) ? $pkg['profile'] : [];
        if ($service === 'work') {
            $row['job_title'] = $title;
            $row['category'] = 'Job seeker';
            $row['roles'] = (string) ($profile['roles'] ?? '');
            $row['processing'] = (string) ($profile['processing'] ?? '');
            $row['salary'] = (string) ($profile['salary'] ?? '');
            $row['requirements'] = (string) ($profile['requirements'] ?? '');
            $row['note'] = (string) ($profile['note'] ?? '');
            $bits = array_filter([
                $row['roles'] !== '' ? 'Jobs: ' . $row['roles'] : '',
                $row['processing'] !== '' ? 'Processing: ' . $row['processing'] : '',
                $row['salary'] !== '' ? 'Salary from: ' . $row['salary'] : '',
                $row['requirements'] !== '' ? $row['requirements'] : '',
                $row['note'] !== '' ? $row['note'] : '',
            ]);
            $row['description'] = $bits !== [] ? implode(' · ', $bits) : implode('; ', $lines);
            $row['employer'] = '';
        } else {
            $row['package_name'] = $title;
            $row['description'] = implode('; ', $lines);
            $row['included'] = $lines;
        }

        $out[] = $row;
    }

    usort($out, static fn (array $a, array $b): int => strcasecmp((string) $a['title'], (string) $b['title']));

    return $out;
}

/**
 * @param list<array<string, mixed>> $rows
 * @return list<array<string, mixed>>
 */
function xander_sc_filter_study_rows(array $rows, string $countryRef): array
{
    $out = [];
    foreach ($rows as $row) {
        if (($row['country_ref'] ?? '') !== $countryRef) {
            continue;
        }
        if (isset($row['active']) && !$row['active']) {
            continue;
        }
        $out[] = $row;
    }

    return $out;
}

function xander_sc_pick_offering(array $offerings, string $offeringId): ?array
{
    foreach ($offerings as $offering) {
        if ((string) ($offering['id'] ?? '') === $offeringId) {
            return $offering;
        }
    }

    return null;
}

function xander_sc_reject_offering(?array $offering, string $service, string $countryRef): ?string
{
    if ($offering === null) {
        return 'The selected program, job, or package is not available for this destination.';
    }
    if (($offering['service_type'] ?? '') !== $service) {
        return 'The selected offering does not match the selected service.';
    }
    if (($offering['country_ref'] ?? '') !== $countryRef) {
        return 'The selected offering does not belong to the selected destination.';
    }
    if (array_key_exists('active', $offering) && !$offering['active']) {
        return 'The selected offering is not active.';
    }

    return null;
}

function xander_sc_validate_customer(array $customer): ?string
{
    $first = trim((string) ($customer['first_name'] ?? ''));
    $last = trim((string) ($customer['last_name'] ?? ''));
    $email = trim((string) ($customer['email'] ?? ''));
    $phone = trim((string) ($customer['phone'] ?? ''));
    $dob = trim((string) ($customer['dob'] ?? ''));
    $nationality = trim((string) ($customer['nationality'] ?? ''));

    if ($first === '' || $last === '') {
        return 'First name and last name are required.';
    }
    if (mb_strlen($first) > 80 || mb_strlen($last) > 80) {
        return 'Name is too long.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'A valid email address is required.';
    }
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (strlen($digits) < 6) {
        return 'A valid phone number is required.';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        return 'Date of birth is required.';
    }
    if ($nationality === '') {
        return 'Nationality is required.';
    }

    return null;
}

function xander_sc_validate_fee(string $feeType, mixed $amount, string $currency, bool $currencyKnown, ?bool $allowZero = null): ?string
{
    if (!xander_sc_is_fee_type($feeType)) {
        return 'Fee type is required.';
    }
    if (!is_numeric($amount)) {
        return 'Amount must be a number.';
    }
    $value = (float) $amount;
    if ($value < 0 || $value > 100000000) {
        return 'Amount cannot be negative.';
    }
    $zeroAllowed = $allowZero ?? in_array($feeType, ['upfront', 'promotion'], true);
    if ($value <= 0 && !$zeroAllowed) {
        return 'Amount must be greater than zero.';
    }
    $currency = strtoupper(trim($currency));
    if ($currency === '' || !$currencyKnown) {
        return 'Currency is required.';
    }

    return null;
}

function xander_sc_validate_remaining(mixed $amount, string $currency, bool $currencyKnown): ?string
{
    if (!is_numeric($amount) || (float) $amount <= 0 || (float) $amount > 100000000) {
        return 'Remaining balance is required and must be greater than zero.';
    }
    if (strtoupper(trim($currency)) === '' || !$currencyKnown) {
        return 'Remaining balance currency is required.';
    }

    return null;
}

function xander_sc_validate_prepared_by(string $firstName, string $lastName): ?string
{
    if (trim($firstName) === '' || trim($lastName) === '') {
        return 'Enter the first and last name of the staff member who prepared this contract.';
    }
    if (mb_strlen(trim($firstName)) > 80 || mb_strlen(trim($lastName)) > 80) {
        return 'Staff name is too long.';
    }

    return null;
}

/**
 * Wizard state. Changing service or country drops stale dependents.
 *
 * @param array<string, mixed> $state
 * @return array<string, mixed>
 */
function xander_sc_apply_change(array $state, string $field, mixed $value): array
{
    $next = $state;
    if ($field === 'service') {
        $previous = (string) ($state['service'] ?? '');
        $next['service'] = (string) $value;
        if ($previous !== (string) $value) {
            $next['country_ref'] = '';
            $next['country_name'] = '';
            $next['offering_id'] = '';
            $next['offering'] = null;
        }
        return $next;
    }

    if ($field === 'country') {
        $previous = (string) ($state['country_ref'] ?? '');
        $ref = is_array($value) ? (string) ($value['ref'] ?? '') : (string) $value;
        $name = is_array($value) ? (string) ($value['name'] ?? '') : (string) ($state['country_name'] ?? '');
        $next['country_ref'] = $ref;
        $next['country_name'] = $name;
        if ($previous !== $ref) {
            $next['offering_id'] = '';
            $next['offering'] = null;
        }
        return $next;
    }

    $next[$field] = $value;

    return $next;
}

function xander_sc_new_token(): string
{
    return bin2hex(random_bytes(32));
}

function xander_sc_token_is_valid(string $token): bool
{
    return (bool) preg_match('/\A[a-f0-9]{64}\z/', $token);
}

function xander_sc_format_reference(int $id, string $year): string
{
    $year = preg_replace('/\D/', '', $year) ?: date('Y');

    return 'CTR-' . $year . '-' . str_pad((string) max(0, $id), 6, '0', STR_PAD_LEFT);
}

/**
 * Why a public sign attempt must be refused. Null means signing is allowed.
 *
 * @param array<string, mixed> $contract
 */
function xander_sc_sign_block_reason(array $contract, string $now): ?string
{
    $status = (string) ($contract['status'] ?? '');
    if ($status === 'signed') {
        return 'This contract has already been signed.';
    }
    if ($status === 'cancelled') {
        return 'This contract has been cancelled and can no longer be signed.';
    }
    if ($status === 'expired') {
        return 'This contract has expired and can no longer be signed.';
    }
    if ($status === 'draft') {
        return 'This contract is not ready for signature.';
    }
    if (!in_array($status, ['pending_signature', 'viewed'], true)) {
        return 'This contract cannot be signed.';
    }

    $expires = trim((string) ($contract['expires_at'] ?? ''));
    if ($expires !== '' && $expires <= $now) {
        return 'This contract has expired and can no longer be signed.';
    }

    return null;
}

/** Columns a signature update is allowed to write. Snapshots are intentionally absent. */
function xander_sc_sign_update_columns(): array
{
    return ['status', 'signed_at', 'signature_name', 'signature_image', 'agreement_accepted', 'updated_at'];
}

function xander_sc_snapshot_columns(): array
{
    return [
        'customer_snapshot',
        'country_snapshot',
        'offering_snapshot',
        'fee_snapshot',
        'staff_snapshot',
        'contract_snapshot',
        'service_type',
        'amount',
        'currency',
        'fee_type',
    ];
}

function xander_sc_sign_preserves_snapshot(): bool
{
    return array_intersect(xander_sc_sign_update_columns(), xander_sc_snapshot_columns()) === [];
}

/**
 * @param array<string, mixed> $offering
 * @return array{show_study:bool,show_work:bool,show_visit:bool,study_text:string,work_text:string,visit_text:string}
 */
function xander_sc_renderer_facts(string $service, array $offering): array
{
    $study = $service === 'study';
    $work = $service === 'work';
    $visit = $service === 'visit';

    $studyText = '';
    if ($study) {
        $studyText = trim(
            'School: ' . (string) ($offering['school_name'] ?? '')
            . ' Program: ' . (string) ($offering['program_name'] ?? '')
            . ' Level: ' . (string) ($offering['level'] ?? '')
        );
    }

    $workText = '';
    if ($work) {
        $workText = trim(
            'Job: ' . (string) ($offering['job_title'] ?? $offering['title'] ?? '')
            . ' Category: ' . (string) ($offering['category'] ?? '')
        );
    }

    $visitText = '';
    if ($visit) {
        $visitText = trim(
            'Visit package: ' . (string) ($offering['package_name'] ?? $offering['title'] ?? '')
        );
    }

    return [
        'show_study'  => $study,
        'show_work'   => $work,
        'show_visit'  => $visit,
        'study_text'  => $studyText,
        'work_text'   => $workText,
        'visit_text'  => $visitText,
    ];
}

function xander_sc_format_money(float $amount, string $currency): string
{
    return strtoupper($currency) . ' ' . number_format($amount, 2, '.', ',');
}
