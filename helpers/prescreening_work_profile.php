<?php
declare(strict_types=1);

/**
 * Structured home address + emergency contact for Work Abroad pre-screening (JSON in work_profile_json).
 *
 * @return array{home:array<string,string>,emergency:array<string,string>}
 */
function xander_prescreening_work_profile_pack(array $post): array
{
    return [
        'home' => [
            'country' => trim((string) ($post['prescreen_home_country'] ?? '')),
            'province_state' => trim((string) ($post['prescreen_province_state'] ?? '')),
            'district' => trim((string) ($post['prescreen_district'] ?? '')),
            'sector' => trim((string) ($post['prescreen_sector'] ?? '')),
            'cell_ward' => trim((string) ($post['prescreen_cell_ward'] ?? '')),
            'village' => trim((string) ($post['prescreen_village'] ?? '')),
        ],
        'emergency' => [
            'full_name' => trim((string) ($post['emergency_full_name'] ?? '')),
            'relationship' => trim((string) ($post['emergency_relationship'] ?? '')),
            'email' => trim((string) ($post['emergency_email'] ?? '')),
            'phone' => trim((string) ($post['emergency_phone'] ?? '')),
            'area_code' => trim((string) ($post['emergency_area_code'] ?? '')),
            'phone_number' => trim((string) ($post['emergency_phone_number'] ?? '')),
        ],
    ];
}

/**
 * @return array{home:array<string,string>,emergency:array<string,string>}
 */
function xander_prescreening_work_profile_unpack(array $row): array
{
    $empty = ['home' => [], 'emergency' => []];
    $raw = trim((string) ($row['work_profile_json'] ?? ''));
    if ($raw !== '' && str_starts_with($raw, '{')) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return [
                'home' => array_map('strval', (array) ($decoded['home'] ?? [])),
                'emergency' => array_map('strval', (array) ($decoded['emergency'] ?? [])),
            ];
        }
    }

    $legacy = trim((string) ($row['work_emergency_contact'] ?? ''));
    if ($legacy !== '') {
        $empty['emergency']['full_name'] = $legacy;
    }

    return $empty;
}

function xander_prescreening_work_profile_summary(array $profile): string
{
    $lines = [];
    $home = $profile['home'] ?? [];
    $parts = array_filter([
        $home['village'] ?? '',
        $home['cell_ward'] ?? '',
        $home['sector'] ?? '',
        $home['district'] ?? '',
        $home['province_state'] ?? '',
        $home['country'] ?? '',
    ], static fn ($v) => trim((string) $v) !== '');
    if ($parts !== []) {
        $lines[] = 'Home: ' . implode(', ', $parts);
    }

    $em = $profile['emergency'] ?? [];
    if (trim((string) ($em['full_name'] ?? '')) !== '') {
        $lines[] = 'Emergency name: ' . $em['full_name'];
    }
    if (trim((string) ($em['relationship'] ?? '')) !== '') {
        $lines[] = 'Relationship: ' . $em['relationship'];
    }
    $phone = trim((string) ($em['phone'] ?? ''));
    if ($phone === '' && !empty($em['area_code']) && !empty($em['phone_number'])) {
        $phone = $em['area_code'] . $em['phone_number'];
    }
    if ($phone !== '') {
        $lines[] = 'Emergency phone: ' . $phone;
    }
    if (trim((string) ($em['email'] ?? '')) !== '') {
        $lines[] = 'Emergency email: ' . $em['email'];
    }

    return implode('; ', $lines);
}

/**
 * @return array<string, string> Job application form field prefill
 */
function xander_prescreening_work_profile_job_prefill(array $row, ?mysqli $conn = null): array
{
    $profile = xander_prescreening_work_profile_unpack($row);
    $home = $profile['home'];
    $em = $profile['emergency'];
    $out = [];

    foreach (['province_state', 'district', 'sector', 'cell_ward', 'village'] as $key) {
        if (!empty($home[$key])) {
            $out[$key] = $home[$key];
        }
    }

    if ($conn && !empty($home['country'])) {
        require_once __DIR__ . '/ai_autofill_utils.php';
        $cid = ai_lookup_country_id($conn, $home['country']);
        if ($cid !== '') {
            $out['address_country_id'] = $cid;
            if (empty($out['work_country_id'])) {
                $out['work_country_id'] = $cid;
            }
        }
    }

    if (!empty($em['full_name'])) {
        $out['emergency_full_name'] = $em['full_name'];
    }
    if (!empty($em['relationship'])) {
        $out['emergency_relationship'] = $em['relationship'];
    }
    if (!empty($em['email'])) {
        $out['emergency_email'] = $em['email'];
    }
    if (!empty($em['area_code']) && !empty($em['phone_number'])) {
        $out['emergency_area_code'] = ltrim((string) $em['area_code'], '+');
        $out['emergency_phone_number'] = (string) $em['phone_number'];
    } elseif (!empty($em['phone'])) {
        $digits = preg_replace('/\D+/', '', (string) $em['phone']);
        if ($digits !== '') {
            $out['emergency_phone_number'] = $digits;
        }
    }

    $addr = trim((string) ($row['applicant_address'] ?? ''));
    if ($addr !== '' && empty($out['village'])) {
        $out['village'] = $addr;
    }

    return $out;
}

/** @return array<string, string> Label => value lines for emails */
function xander_prescreening_work_profile_email_lines(array $row): array
{
    $profile = xander_prescreening_work_profile_unpack($row);
    $lines = [];
    $map = [
        'Home country' => $profile['home']['country'] ?? '',
        'Province / State' => $profile['home']['province_state'] ?? '',
        'District' => $profile['home']['district'] ?? '',
        'Sector' => $profile['home']['sector'] ?? '',
        'Cell / Ward' => $profile['home']['cell_ward'] ?? '',
        'Village' => $profile['home']['village'] ?? '',
        'Emergency full name' => $profile['emergency']['full_name'] ?? '',
        'Emergency relationship' => $profile['emergency']['relationship'] ?? '',
        'Emergency phone' => $profile['emergency']['phone'] ?? '',
        'Emergency email' => $profile['emergency']['email'] ?? '',
    ];
    foreach ($map as $label => $val) {
        $val = trim((string) $val);
        if ($val !== '') {
            $lines[$label] = $val;
        }
    }

    return $lines;
}
