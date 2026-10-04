<?php
declare(strict_types=1);

require_once __DIR__ . '/service_contract_rules.php';
require_once __DIR__ . '/service_contract_schema.php';
require_once __DIR__ . '/contract_fee_catalog.php';
require_once dirname(__DIR__) . '/helpers/currencies.php';

function xander_sc_app_base_path(): string
{
    $sn = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($sn === '') {
        return '';
    }

    if (preg_match('#^(.*)/(?:api|contract|admin)(?:/|$)#', $sn, $m)) {
        return rtrim($m[1], '/');
    }

    $dir = rtrim(dirname($sn), '/');
    if ($dir === '' || $dir === '/' || $dir === '.') {
        return '';
    }

    return $dir;
}

function xander_sc_public_url(string $token): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $base = xander_sc_app_base_path();

    return $scheme . '://' . $host . $base . '/contract/sign/' . $token;
}

function xander_sc_expire_days(): int
{
    $raw = '';
    if (function_exists('xander_env_get')) {
        $raw = trim((string) xander_env_get('SERVICE_CONTRACT_EXPIRE_DAYS'));
    }
    if ($raw === '') {
        return 30;
    }

    return max(0, min(3650, (int) $raw));
}

function xander_sc_json(mixed $value): string
{
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
}

/** @return array<string, mixed> */
function xander_sc_decode(mixed $json): array
{
    if (!is_string($json) || $json === '') {
        return [];
    }
    $data = json_decode($json, true);

    return is_array($data) ? $data : [];
}

function xander_sc_expire_overdue(mysqli $conn): void
{
    $conn->query(
        "UPDATE service_contracts
         SET status = 'expired', updated_at = NOW()
         WHERE status IN ('draft', 'pending_signature', 'viewed')
           AND expires_at IS NOT NULL
           AND expires_at <= NOW()"
    );
}

/**
 * @return array<string, array{id:?int, name:string, ref:string}>
 */
function xander_sc_country_index(mysqli $conn): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $cache = [];
    if (!xander_db_table_exists($conn, 'countries')) {
        return $cache;
    }

    $res = $conn->query('SELECT id, name FROM countries');
    if (!$res) {
        return $cache;
    }
    while ($row = $res->fetch_assoc()) {
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $cache[mb_strtolower($name)] = [
            'id'   => (int) $row['id'],
            'name' => $name,
            'ref'  => 'id:' . (int) $row['id'],
        ];
    }
    $res->free();

    return $cache;
}

/** @return array{id:?int, name:string, ref:string} */
function xander_sc_resolve_country(mysqli $conn, string $canonicalName): array
{
    $index = xander_sc_country_index($conn);
    $key = mb_strtolower($canonicalName);
    if (isset($index[$key])) {
        return $index[$key];
    }

    return [
        'id'   => null,
        'name' => $canonicalName,
        'ref'  => 'name:' . $canonicalName,
    ];
}

/**
 * @param list<array{name:string, ref:string}> $rows
 * @return list<array{id:?int, name:string, ref:string}>
 */
function xander_sc_attach_country_ids(mysqli $conn, array $rows): array
{
    $out = [];
    foreach ($rows as $row) {
        $resolved = xander_sc_resolve_country($conn, (string) $row['name']);
        $out[] = $resolved;
    }

    return $out;
}

/** @return list<array{id:?int, name:string, ref:string}> */
function xander_sc_study_countries(mysqli $conn): array
{
    if (!xander_db_table_exists($conn, 'programs') || !xander_db_table_exists($conn, 'universities') || !xander_db_table_exists($conn, 'countries')) {
        return [];
    }

    $parts = [
        "
        SELECT DISTINCT c.id, c.name
        FROM programs p
        INNER JOIN universities u ON u.id = p.university_id
        INNER JOIN countries c ON c.id = u.country_id
        WHERE p.is_active = 1 AND u.country_id IS NOT NULL AND u.country_id > 0
        ",
    ];
    if (xander_db_table_exists($conn, 'regions') && xander_db_column_exists($conn, 'regions', 'country_id')) {
        $parts[] = "
            SELECT DISTINCT c.id, c.name
            FROM programs p
            INNER JOIN universities u ON u.id = p.university_id
            INNER JOIN regions r ON r.id = u.region_id
            INNER JOIN countries c ON c.id = r.country_id
            WHERE p.is_active = 1 AND (u.country_id IS NULL OR u.country_id = 0)
        ";
    }
    if (xander_db_table_exists($conn, 'institution_programs')) {
        $parts[] = "
            SELECT DISTINCT c.id, c.name
            FROM institution_programs ip
            INNER JOIN universities u ON u.id = ip.university_id
            INNER JOIN countries c ON c.id = u.country_id
            WHERE ip.status = 'active' AND u.country_id IS NOT NULL AND u.country_id > 0
        ";
    }
    $sql = 'SELECT id, name FROM (' . implode(' UNION ', $parts) . ') study_countries ORDER BY name ASC';
    $res = $conn->query($sql);
    if (!$res) {
        error_log('[service_contract] study countries: ' . $conn->error);
        $res = $conn->query($parts[0] . ' ORDER BY name ASC');
    }
    if (!$res) {
        return [];
    }

    $out = [];
    while ($row = $res->fetch_assoc()) {
        $id = (int) ($row['id'] ?? 0);
        $name = trim((string) ($row['name'] ?? ''));
        if ($id <= 0 || $name === '') {
            continue;
        }
        $out[] = ['id' => $id, 'name' => $name, 'ref' => 'id:' . $id];
    }
    $res->free();

    return $out;
}

/** @return list<array{id:?int, name:string, ref:string}> */
function xander_sc_all_countries(mysqli $conn): array
{
    if (!xander_db_table_exists($conn, 'countries')) {
        return [];
    }
    $res = $conn->query('SELECT id, name FROM countries WHERE name IS NOT NULL AND name <> \'\' ORDER BY name ASC');
    if (!$res) {
        return [];
    }
    $out = [];
    $seen = [];
    while ($row = $res->fetch_assoc()) {
        $id = (int) ($row['id'] ?? 0);
        $name = trim((string) ($row['name'] ?? ''));
        $key = mb_strtolower($name);
        if ($id <= 0 || $name === '' || isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $out[] = ['id' => $id, 'name' => $name, 'ref' => 'id:' . $id];
    }
    $res->free();

    return $out;
}

/** @return list<array{id:?int, name:string, ref:string}> */
function xander_sc_countries(mysqli $conn, string $service): array
{
    if (!xander_sc_is_service($service)) {
        return [];
    }
    $all = xander_sc_all_countries($conn);
    if ($all !== []) {
        return $all;
    }
    if ($service === 'study') {
        return xander_sc_study_countries($conn);
    }

    $rows = xander_sc_countries_for_service_catalog(xander_contract_fee_catalog(), $service);

    return xander_sc_attach_country_ids($conn, $rows);
}

function xander_sc_country_by_ref(mysqli $conn, string $service, string $countryRef): ?array
{
    foreach (xander_sc_countries($conn, $service) as $country) {
        if ($country['ref'] === $countryRef) {
            return $country;
        }
    }

    return null;
}

/** @return list<array<string, mixed>> */
function xander_sc_study_offerings(mysqli $conn, array $country): array
{
    $countryId = (int) ($country['id'] ?? 0);
    if ($countryId <= 0) {
        return [];
    }

    $out = [];
    $hasLevels = xander_db_table_exists($conn, 'program_levels');
    $levelSelect = $hasLevels ? 'pl.name AS level_name' : 'NULL AS level_name';
    $levelJoin = $hasLevels ? 'LEFT JOIN program_levels pl ON pl.id = p.program_level_id' : '';

    $queries = [
        "
        SELECT p.id, p.program_name, {$levelSelect}, u.name AS university_name, u.city, c.id AS country_id, c.name AS country_name
        FROM programs p
        INNER JOIN universities u ON u.id = p.university_id
        {$levelJoin}
        INNER JOIN countries c ON c.id = u.country_id
        WHERE p.is_active = 1 AND c.id = ?
        ",
    ];

    if (xander_db_table_exists($conn, 'regions') && xander_db_column_exists($conn, 'regions', 'country_id')) {
        $queries[] = "
            SELECT p.id, p.program_name, {$levelSelect}, u.name AS university_name, u.city, c.id AS country_id, c.name AS country_name
            FROM programs p
            INNER JOIN universities u ON u.id = p.university_id
            {$levelJoin}
            INNER JOIN regions r ON r.id = u.region_id
            INNER JOIN countries c ON c.id = r.country_id
            WHERE p.is_active = 1 AND (u.country_id IS NULL OR u.country_id = 0) AND c.id = ?
        ";
    }

    foreach ($queries as $sql) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            continue;
        }
        $stmt->bind_param('i', $countryId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $id = (int) $row['id'];
            $out['program:' . $id] = xander_sc_study_row_to_offering($row, $country, 'program:' . $id, 'program');
        }
        $stmt->close();
    }

    if (xander_db_table_exists($conn, 'institution_programs')) {
        $sql = "
            SELECT ip.id, ip.title AS program_name, ip.program_type AS level_name, ip.duration, ip.summary,
                   u.name AS university_name, u.city, c.id AS country_id, c.name AS country_name
            FROM institution_programs ip
            INNER JOIN universities u ON u.id = ip.university_id
            INNER JOIN countries c ON c.id = u.country_id
            WHERE ip.status = 'active' AND c.id = ?
        ";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $countryId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $id = (int) $row['id'];
                $out['instprog:' . $id] = xander_sc_study_row_to_offering($row, $country, 'instprog:' . $id, 'institution_program');
            }
            $stmt->close();
        }
    }

    $list = array_values($out);
    usort($list, static function (array $a, array $b): int {
        $school = strcasecmp((string) $a['school_name'], (string) $b['school_name']);
        return $school !== 0 ? $school : strcasecmp((string) $a['program_name'], (string) $b['program_name']);
    });

    return $list;
}

/** @param array<string, mixed> $row @param array{id:?int,name:string,ref:string} $country */
function xander_sc_study_row_to_offering(array $row, array $country, string $id, string $kind): array
{
    $school = trim((string) ($row['university_name'] ?? ''));
    $program = trim((string) ($row['program_name'] ?? ''));
    $level = trim((string) ($row['level_name'] ?? ''));
    $city = trim((string) ($row['city'] ?? ''));
    $duration = trim((string) ($row['duration'] ?? ''));
    $summary = trim((string) ($row['summary'] ?? ''));

    return [
        'id'            => $id,
        'kind'          => $kind,
        'service_type'  => 'study',
        'active'        => true,
        'country_ref'   => $country['ref'],
        'country_id'    => $country['id'],
        'country_name'  => (string) ($row['country_name'] ?? $country['name']),
        'school_name'   => $school,
        'program_name'  => $program,
        'level'         => $level,
        'duration'      => $duration,
        'city'          => $city,
        'summary'       => $summary,
        'title'         => trim($school . ' — ' . $program),
    ];
}

/** @return list<array<string, mixed>> */
function xander_sc_catalog_service_offerings(mysqli $conn, string $service, array $country): array
{
    $rows = xander_sc_catalog_offerings(xander_contract_fee_catalog(), $service, (string) $country['name']);
    foreach ($rows as &$row) {
        $row['country_ref'] = $country['ref'];
        $row['country_id'] = $country['id'];
        $row['country_name'] = $country['name'];
        $row['kind'] = $service === 'work' ? 'job' : 'visit_package';
    }
    unset($row);

    return $rows;
}

/** @return list<array<string, mixed>> */
function xander_sc_offerings(mysqli $conn, string $service, string $countryRef): array
{
    $country = xander_sc_country_by_ref($conn, $service, $countryRef);
    if ($country === null) {
        return [];
    }
    if ($service === 'study') {
        return xander_sc_study_offerings($conn, $country);
    }

    return xander_sc_catalog_service_offerings($conn, $service, $country);
}

function xander_sc_find_offering(mysqli $conn, string $service, string $countryRef, string $offeringId): ?array
{
    return xander_sc_pick_offering(xander_sc_offerings($conn, $service, $countryRef), $offeringId);
}

/** @return list<array<string, mixed>> */
function xander_sc_search_customers(mysqli $conn, string $query): array
{
    $query = trim($query);
    if (mb_strlen($query) < 2 || !xander_db_table_exists($conn, 'student_applications')) {
        return [];
    }

    $like = '%' . $query . '%';
    $sql = "
        SELECT id, first_name, last_name, email, phone_number, dob, nationality, passport_number, address_line1
        FROM student_applications
        WHERE email LIKE ? OR first_name LIKE ? OR last_name LIKE ?
           OR CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?
        ORDER BY id DESC
        LIMIT 12
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param('ssss', $like, $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = [
            'id'           => (int) $row['id'],
            'first_name'   => (string) ($row['first_name'] ?? ''),
            'last_name'    => (string) ($row['last_name'] ?? ''),
            'email'        => (string) ($row['email'] ?? ''),
            'phone'        => (string) ($row['phone_number'] ?? ''),
            'dob'          => (string) ($row['dob'] ?? ''),
            'nationality'  => (string) ($row['nationality'] ?? ''),
            'passport'     => (string) ($row['passport_number'] ?? ''),
            'address'      => (string) ($row['address_line1'] ?? ''),
        ];
    }
    $stmt->close();

    return $out;
}

function xander_sc_customer_exists(mysqli $conn, int $customerId): bool
{
    if ($customerId <= 0 || !xander_db_table_exists($conn, 'student_applications')) {
        return false;
    }
    $stmt = $conn->prepare('SELECT id FROM student_applications WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $customerId);
    $stmt->execute();
    $ok = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();

    return $ok;
}

/**
 * @param array<string, mixed> $input
 * @return array{ok:bool, error?:string, reference?:string, url?:string, id?:int, token?:string}
 */
function xander_sc_generate(mysqli $conn, int $staffId, string $staffName, array $input): array
{
    if (!xander_sc_staff_authorized($staffId)) {
        return ['ok' => false, 'error' => 'Unauthorized.'];
    }

    xander_ensure_service_contract_table($conn);

    $service = (string) ($input['service'] ?? '');
    $countryRef = (string) ($input['country_ref'] ?? '');
    $offeringId = (string) ($input['offering_id'] ?? '');
    $feeType = (string) ($input['fee_type'] ?? '');
    $currency = strtoupper(trim((string) ($input['currency'] ?? '')));
    $feeDescription = trim((string) ($input['fee_description'] ?? ''));
    $customer = is_array($input['customer'] ?? null) ? $input['customer'] : [];

    if (!xander_sc_is_service($service)) {
        return ['ok' => false, 'error' => 'Select a service.'];
    }
    if ($countryRef === '') {
        return ['ok' => false, 'error' => 'Select a destination country.'];
    }
    if ($offeringId === '') {
        return ['ok' => false, 'error' => 'Select a program, job, or visit package, or add the service.'];
    }

    $country = xander_sc_country_by_ref($conn, $service, $countryRef);
    if ($country === null) {
        return ['ok' => false, 'error' => 'That destination is not available for the selected service.'];
    }

    if ($offeringId === 'custom') {
        $customTitle = trim((string) ($input['custom_title'] ?? ''));
        $customDetails = trim((string) ($input['custom_details'] ?? ''));
        if ($customTitle === '') {
            return ['ok' => false, 'error' => 'Enter the service you are adding.'];
        }
        $offering = [
            'id'            => 'custom',
            'kind'          => 'custom',
            'service_type'  => $service,
            'active'        => true,
            'country_ref'   => $country['ref'],
            'country_id'    => $country['id'],
            'country_name'  => $country['name'],
            'title'         => $customTitle,
            'description'   => $customDetails,
            'school_name'   => $service === 'study' ? $customTitle : '',
            'program_name'  => $service === 'study' ? $customDetails : '',
            'job_title'     => $service === 'work' ? $customTitle : '',
            'package_name'  => $service === 'visit' ? $customTitle : '',
        ];
    } else {
        $offering = xander_sc_find_offering($conn, $service, $countryRef, $offeringId);
    }
    $offeringError = xander_sc_reject_offering($offering, $service, $countryRef);
    if ($offeringError !== null) {
        return ['ok' => false, 'error' => $offeringError];
    }

    $customerError = xander_sc_validate_customer($customer);
    if ($customerError !== null) {
        return ['ok' => false, 'error' => $customerError];
    }

    $otherKind = (string) ($input['other_kind'] ?? '');
    if ($feeType === 'other' && !in_array($otherKind, ['promotion', 'standard'], true)) {
        return ['ok' => false, 'error' => 'Select the promotion type for this other fee.'];
    }
    $allowZero = in_array($feeType, ['upfront', 'promotion'], true) || ($feeType === 'other' && $otherKind === 'promotion');
    $feeError = xander_sc_validate_fee($feeType, $input['amount'] ?? null, $currency, xander_is_valid_currency($currency), $allowZero);
    if ($feeError !== null) {
        return ['ok' => false, 'error' => $feeError];
    }
    $remainingCurrency = strtoupper(trim((string) ($input['remaining_currency'] ?? '')));
    $remainingError = xander_sc_validate_remaining($input['remaining_amount'] ?? null, $remainingCurrency, xander_is_valid_currency($remainingCurrency));
    if ($remainingError !== null) {
        return ['ok' => false, 'error' => $remainingError];
    }
    $preparedFirst = trim((string) ($input['prepared_by_first'] ?? ''));
    $preparedLast = trim((string) ($input['prepared_by_last'] ?? ''));
    $preparedError = xander_sc_validate_prepared_by($preparedFirst, $preparedLast);
    if ($preparedError !== null) {
        return ['ok' => false, 'error' => $preparedError];
    }
    if ($feeType === 'other' && $otherKind === 'promotion' && $feeDescription === '') {
        $feeDescription = 'Promotion';
    }
    if (mb_strlen($feeDescription) > 255) {
        return ['ok' => false, 'error' => 'Fee description is too long.'];
    }
    if (in_array($feeType, ['other', 'promotion'], true) && $feeDescription === '') {
        return ['ok' => false, 'error' => 'Describe this fee.'];
    }

    $customerId = (int) ($input['customer_id'] ?? 0);
    if ($customerId > 0 && !xander_sc_customer_exists($conn, $customerId)) {
        return ['ok' => false, 'error' => 'The selected customer record was not found.'];
    }

    $amount = round((float) $input['amount'], 2);
    $remainingAmount = round((float) $input['remaining_amount'], 2);
    $fullName = trim(trim((string) $customer['first_name']) . ' ' . trim((string) $customer['last_name']));
    $customerSnapshot = [
        'first_name'        => trim((string) $customer['first_name']),
        'last_name'         => trim((string) $customer['last_name']),
        'full_name'         => $fullName,
        'email'             => trim((string) $customer['email']),
        'phone'             => trim((string) $customer['phone']),
        'address'           => trim((string) ($customer['address'] ?? '')),
        'passport'          => trim((string) ($customer['passport'] ?? '')),
        'nationality'       => trim((string) $customer['nationality']),
        'dob'               => trim((string) $customer['dob']),
        'residence_country' => trim((string) ($customer['residence_country'] ?? '')),
    ];
    $countrySnapshot = [
        'ref'  => $country['ref'],
        'id'   => $country['id'],
        'name' => $country['name'],
    ];
    $feeSnapshot = [
        'fee_type'    => $feeType,
        'fee_label'   => xander_sc_fee_types()[$feeType],
        'description' => $feeDescription,
        'amount'      => $amount,
        'currency'    => $currency,
        'formatted'   => xander_sc_format_money($amount, $currency),
        'remaining_amount' => $remainingAmount,
        'remaining_currency' => $remainingCurrency,
        'remaining_formatted' => xander_sc_format_money($remainingAmount, $remainingCurrency),
        'other_kind' => $feeType === 'other' ? $otherKind : '',
    ];
    $preparedBy = trim($preparedFirst . ' ' . $preparedLast);
    $staffSnapshot = [
        'id'   => $staffId,
        'name' => $preparedBy,
        'first_name' => $preparedFirst,
        'last_name' => $preparedLast,
        'prepared_by' => $preparedBy,
    ];
    $offeringSnapshot = $offering;
    $offeringTitle = (string) ($offering['title'] ?? '');
    $offeringKind = (string) ($offering['kind'] ?? $service);

    $contractSnapshot = [
        'template'    => 'master_international_v1',
        'template_label' => 'Xander Global Scholars – Master International Services Agreement',
        'service_type'=> $service,
        'service_label' => xander_sc_service_types()[$service],
        'customer'    => $customerSnapshot,
        'country'     => $countrySnapshot,
        'offering'    => $offeringSnapshot,
        'fee'         => $feeSnapshot,
        'staff'       => $staffSnapshot,
    ];

    $token = '';
    $insertedId = 0;
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $token = xander_sc_new_token();
        $pendingRef = 'TMP-' . $token;
        $days = xander_sc_expire_days();
        $customerIdParam = $customerId > 0 ? $customerId : null;
        $countryIdParam = $country['id'] !== null ? (int) $country['id'] : null;
        $customerJson = xander_sc_json($customerSnapshot);
        $countryJson = xander_sc_json($countrySnapshot);
        $offeringJson = xander_sc_json($offeringSnapshot);
        $feeJson = xander_sc_json($feeSnapshot);
        $staffJson = xander_sc_json($staffSnapshot);
        $contractJson = xander_sc_json($contractSnapshot);
        $email = $customerSnapshot['email'];
        $feeDescriptionParam = $feeDescription !== '' ? $feeDescription : null;

        $sql = "
            INSERT INTO service_contracts (
                reference, public_token, status, service_type, customer_id, customer_name, customer_email,
                customer_snapshot, destination_country_id, country_name, country_snapshot,
                offering_kind, offering_id, offering_title, offering_snapshot,
                fee_type, fee_description, amount, currency, fee_snapshot, staff_snapshot, contract_snapshot,
                contract_template, created_by_staff_id, created_at, updated_at, expires_at
            ) VALUES (
                ?, ?, 'pending_signature', ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                'master_international_v1', ?, NOW(), NOW(),
                " . ($days > 0 ? 'DATE_ADD(NOW(), INTERVAL ' . $days . ' DAY)' : 'NULL') . "
            )
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return ['ok' => false, 'error' => 'Could not create the contract.'];
        }
        $countryName = (string) $country['name'];
        $stmt->bind_param(
            'sssisssissssssssdssssi',
            $pendingRef,
            $token,
            $service,
            $customerIdParam,
            $fullName,
            $email,
            $customerJson,
            $countryIdParam,
            $countryName,
            $countryJson,
            $offeringKind,
            $offeringId,
            $offeringTitle,
            $offeringJson,
            $feeType,
            $feeDescriptionParam,
            $amount,
            $currency,
            $feeJson,
            $staffJson,
            $contractJson,
            $staffId
        );
        $ok = $stmt->execute();
        $errNo = $stmt->errno;
        $insertedId = (int) $stmt->insert_id;
        $stmt->close();
        if ($ok && $insertedId > 0) {
            break;
        }
        if ($errNo === 1062) {
            $token = '';
            $insertedId = 0;
            continue;
        }
        error_log('[service_contract] insert failed: ' . $conn->error);
        return ['ok' => false, 'error' => 'Could not create the contract.'];
    }

    if ($insertedId <= 0 || !xander_sc_token_is_valid($token)) {
        return ['ok' => false, 'error' => 'Could not create a unique contract link.'];
    }

    $year = date('Y');
    $yearStmt = $conn->prepare('SELECT DATE_FORMAT(created_at, "%Y") AS y FROM service_contracts WHERE id = ?');
    if ($yearStmt) {
        $yearStmt->bind_param('i', $insertedId);
        $yearStmt->execute();
        $yearRow = $yearStmt->get_result()->fetch_assoc();
        $yearStmt->close();
        if (!empty($yearRow['y'])) {
            $year = (string) $yearRow['y'];
        }
    }

    $reference = xander_sc_format_reference($insertedId, $year);
    $upd = $conn->prepare('UPDATE service_contracts SET reference = ? WHERE id = ? AND status = \'pending_signature\'');
    if (!$upd) {
        return ['ok' => false, 'error' => 'Could not assign a contract reference.'];
    }
    $upd->bind_param('si', $reference, $insertedId);
    $upd->execute();
    $upd->close();

    $snap = $conn->prepare('SELECT contract_snapshot FROM service_contracts WHERE id = ?');
    if ($snap) {
        $snap->bind_param('i', $insertedId);
        $snap->execute();
        $snapRow = $snap->get_result()->fetch_assoc();
        $snap->close();
        $decoded = xander_sc_decode($snapRow['contract_snapshot'] ?? '');
        $decoded['reference'] = $reference;
        $decoded['public_token'] = $token;
        $encoded = xander_sc_json($decoded);
        $write = $conn->prepare('UPDATE service_contracts SET contract_snapshot = ? WHERE id = ? AND status = \'pending_signature\'');
        if ($write) {
            $write->bind_param('si', $encoded, $insertedId);
            $write->execute();
            $write->close();
        }
    }

    return [
        'ok'        => true,
        'id'        => $insertedId,
        'reference' => $reference,
        'token'     => $token,
        'url'       => xander_sc_public_url($token),
    ];
}

/** @return array<string, mixed>|null */
function xander_sc_load_by_token(mysqli $conn, string $token, bool $markViewed = false): ?array
{
    if (!xander_sc_token_is_valid($token)) {
        return null;
    }
    xander_ensure_service_contract_table($conn);
    xander_sc_expire_overdue($conn);

    $stmt = $conn->prepare('SELECT * FROM service_contracts WHERE public_token = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return null;
    }

    if ($markViewed && ($row['status'] ?? '') === 'pending_signature') {
        $id = (int) $row['id'];
        $view = $conn->prepare(
            "UPDATE service_contracts
             SET status = 'viewed', viewed_at = NOW(), updated_at = NOW()
             WHERE id = ? AND status = 'pending_signature'"
        );
        if ($view) {
            $view->bind_param('i', $id);
            $view->execute();
            $view->close();
            $row['status'] = 'viewed';
            $row['viewed_at'] = $row['viewed_at'] ?: date('Y-m-d H:i:s');
        }
    }

    return xander_sc_hydrate($row);
}

/** @param array<string, mixed> $row @return array<string, mixed> */
function xander_sc_hydrate(array $row): array
{
    $snapshot = xander_sc_decode($row['contract_snapshot'] ?? '');
    $row['snapshot'] = $snapshot;
    $row['customer'] = $snapshot['customer'] ?? xander_sc_decode($row['customer_snapshot'] ?? '');
    $row['country'] = $snapshot['country'] ?? xander_sc_decode($row['country_snapshot'] ?? '');
    $row['offering'] = $snapshot['offering'] ?? xander_sc_decode($row['offering_snapshot'] ?? '');
    $row['fee'] = $snapshot['fee'] ?? xander_sc_decode($row['fee_snapshot'] ?? '');
    $row['staff'] = $snapshot['staff'] ?? xander_sc_decode($row['staff_snapshot'] ?? '');

    return $row;
}

/**
 * @return array{ok:bool, error?:string, status?:string}
 */
function xander_sc_sign(mysqli $conn, string $token, string $signature, bool $agreed, string $signedName): array
{
    if (!xander_sc_token_is_valid($token)) {
        return ['ok' => false, 'error' => 'Invalid contract link.'];
    }
    if (!$agreed) {
        return ['ok' => false, 'error' => 'You must confirm that you have reviewed this contract and agree to its terms.'];
    }
    if (!str_starts_with($signature, 'data:image/png;base64,')) {
        return ['ok' => false, 'error' => 'A drawn signature is required.'];
    }
    if (strlen($signature) > 1500000) {
        return ['ok' => false, 'error' => 'Signature image is too large.'];
    }

    xander_ensure_service_contract_table($conn);
    xander_sc_expire_overdue($conn);

    $stmt = $conn->prepare(
        'SELECT id, status, expires_at, amount, upfront_paid_at, service_type, customer_snapshot, contract_snapshot FROM service_contracts WHERE public_token = ? LIMIT 1'
    );
    if (!$stmt) {
        return ['ok' => false, 'error' => 'Could not load the contract.'];
    }
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return ['ok' => false, 'error' => 'This contract link is invalid.'];
    }

    $now = date('Y-m-d H:i:s');
    $block = xander_sc_sign_block_reason($row, $now);
    if ($block !== null) {
        return ['ok' => false, 'error' => $block];
    }
    $agreedAmount = (float) ($row['amount'] ?? 0);
    $studyContract = (string) ($row['service_type'] ?? '') === 'study';
    if (!$studyContract && $agreedAmount > 0 && trim((string) ($row['upfront_paid_at'] ?? '')) === '') {
        return ['ok' => false, 'error' => 'Pay the upfront fee with Pay Here before signing. A zero upfront fee does not need payment. Study contracts do not use Pay Here.'];
    }

    $customer = xander_sc_decode($row['customer_snapshot'] ?? '');
    $expected = trim((string) ($customer['full_name'] ?? ''));
    if ($expected === '' || strcasecmp($expected, trim($signedName)) !== 0) {
        return ['ok' => false, 'error' => 'The signature name must match the customer name on this contract.'];
    }

    $id = (int) $row['id'];
    $beforeSnapshot = (string) ($row['contract_snapshot'] ?? '');
    $upd = $conn->prepare(
        "UPDATE service_contracts
         SET status = 'signed',
             signed_at = NOW(),
             signature_name = ?,
             signature_image = ?,
             agreement_accepted = 1,
             updated_at = NOW()
         WHERE id = ?
           AND status IN ('pending_signature', 'viewed')
           AND (expires_at IS NULL OR expires_at > NOW())"
    );
    if (!$upd) {
        return ['ok' => false, 'error' => 'Could not save the signature.'];
    }
    $upd->bind_param('ssi', $expected, $signature, $id);
    $upd->execute();
    $changed = $upd->affected_rows > 0;
    $upd->close();
    if (!$changed) {
        return ['ok' => false, 'error' => 'This contract can no longer be signed.'];
    }

    $check = $conn->prepare('SELECT contract_snapshot FROM service_contracts WHERE id = ?');
    if ($check) {
        $check->bind_param('i', $id);
        $check->execute();
        $after = $check->get_result()->fetch_assoc();
        $check->close();
        if ((string) ($after['contract_snapshot'] ?? '') !== $beforeSnapshot) {
            error_log('[service_contract] snapshot changed during sign for contract ' . $id);
        }
    }

    return ['ok' => true, 'status' => 'signed'];
}

function xander_sc_cancel(mysqli $conn, int $contractId, int $staffId): bool
{
    if (!xander_sc_staff_authorized($staffId) || $contractId <= 0) {
        return false;
    }
    xander_ensure_service_contract_table($conn);
    $stmt = $conn->prepare(
        "UPDATE service_contracts
         SET status = 'cancelled', cancelled_at = NOW(), updated_at = NOW()
         WHERE id = ? AND status IN ('draft', 'pending_signature', 'viewed')"
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $contractId);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();

    return $ok;
}

/**
 * @return list<array<string, mixed>>
 */
function xander_sc_list(mysqli $conn, string $status, string $service, string $query): array
{
    xander_ensure_service_contract_table($conn);
    xander_sc_expire_overdue($conn);

    $where = [];
    $types = '';
    $params = [];

    if ($status !== '' && $status !== 'all' && xander_sc_is_status($status)) {
        $where[] = 'status = ?';
        $types .= 's';
        $params[] = $status;
    }
    if ($service !== '' && $service !== 'all' && xander_sc_is_service($service)) {
        $where[] = 'service_type = ?';
        $types .= 's';
        $params[] = $service;
    }
    $query = trim($query);
    if ($query !== '') {
        $like = '%' . $query . '%';
        $where[] = '(customer_name LIKE ? OR customer_email LIKE ? OR reference LIKE ?)';
        $types .= 'sss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql = "
        SELECT id, reference, public_token, status, service_type, customer_name, customer_email,
               country_name, offering_title, amount, currency, fee_type, created_by_staff_id,
               staff_snapshot, created_at, signed_at, viewed_at, expires_at, cancelled_at
        FROM service_contracts
    ";
    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY id DESC LIMIT 300';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $staff = xander_sc_decode($row['staff_snapshot'] ?? '');
        $row['staff_name'] = (string) ($staff['name'] ?? '');
        unset($row['staff_snapshot']);
        $rows[] = $row;
    }
    $stmt->close();

    return $rows;
}

function xander_sc_mark_upfront_paid(mysqli $conn, string $token): bool
{
    if (!xander_sc_token_is_valid($token)) {
        return false;
    }
    xander_ensure_service_contract_table($conn);
    $stmt = $conn->prepare(
        "UPDATE service_contracts
         SET upfront_paid_at = NOW(), updated_at = NOW()
         WHERE public_token = ?
           AND amount > 0
           AND status IN ('pending_signature', 'viewed')
           AND upfront_paid_at IS NULL"
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->close();

    $check = $conn->prepare('SELECT upfront_paid_at FROM service_contracts WHERE public_token = ? LIMIT 1');
    if (!$check) {
        return false;
    }
    $check->bind_param('s', $token);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();
    $check->close();

    return $row && trim((string) ($row['upfront_paid_at'] ?? '')) !== '';
}
