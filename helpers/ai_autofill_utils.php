<?php
declare(strict_types=1);

function ai_normalize_text(?string $value): string
{
    $value = trim((string) $value);
    $value = preg_replace('/\s+/u', ' ', $value);

    return trim((string) $value);
}

function ai_normalize_email(?string $value): string
{
    $value = strtolower(ai_normalize_text($value));
    if ($value === '' || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return '';
    }

    [$local] = explode('@', $value, 2);
    $genericLocals = [
        'info', 'contact', 'admin', 'office', 'admission', 'admissions',
        'apply', 'application', 'support', 'help', 'registrar', 'enquiry',
        'enquiries', 'inquiry', 'hello',
    ];
    if (in_array($local, $genericLocals, true)) {
        return '';
    }

    return $value;
}

function ai_normalize_country_name(?string $value): string
{
    $value = strtolower(ai_normalize_text($value));
    if ($value === '') {
        return '';
    }

    $value = str_replace(
        ['é', 'è', 'ê', 'ë', 'à', 'â', 'ä', 'î', 'ï', 'ô', 'ö', 'ù', 'û', 'ü', 'ç'],
        ['e', 'e', 'e', 'e', 'a', 'a', 'a', 'i', 'i', 'o', 'o', 'u', 'u', 'u', 'c'],
        $value
    );
    $value = preg_replace('/[^a-z0-9]+/', ' ', $value);

    return trim((string) $value);
}

function ai_lookup_country_id(mysqli $conn, ?string $name): string
{
    $name = ai_normalize_text($name);
    if ($name === '') {
        return '';
    }

    if (ctype_digit($name)) {
        return $name;
    }

    $stmt = $conn->prepare('SELECT id FROM countries WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $stmt->bind_result($id);
        $found = $stmt->fetch();
        $stmt->close();
        if ($found) {
            return (string) $id;
        }
    }

    $like = '%' . $name . '%';
    $stmt = $conn->prepare('SELECT id FROM countries WHERE LOWER(name) LIKE LOWER(?) ORDER BY CHAR_LENGTH(name) ASC LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $stmt->bind_result($id);
        $found = $stmt->fetch();
        $stmt->close();
        if ($found) {
            return (string) $id;
        }
    }

    return '';
}

function ai_country_dial_code_from_name(?string $country): string
{
    $country = ai_normalize_country_name($country);
    if ($country === '') {
        return '';
    }

    $codes = [
        'rwanda' => '250', 'kenya' => '254', 'uganda' => '256', 'tanzania' => '255',
        'burundi' => '257', 'democratic republic of congo' => '243', 'dr congo' => '243',
        'ethiopia' => '251', 'south africa' => '27', 'nigeria' => '234', 'ghana' => '233',
        'united kingdom' => '44', 'uk' => '44', 'france' => '33', 'germany' => '49',
        'united states' => '1', 'usa' => '1', 'canada' => '1', 'india' => '91',
    ];

    return $codes[$country] ?? '';
}

function ai_starts_with(string $haystack, string $needle): bool
{
    if ($needle === '') {
        return true;
    }

    return substr($haystack, 0, strlen($needle)) === $needle;
}

/**
 * @param list<string> $countryHints
 * @return array{area_code:string,phone_number:string}
 */
function ai_normalize_phone_pair(?string $value, array $countryHints = []): array
{
    $value = ai_normalize_text($value);
    if ($value === '') {
        return ['area_code' => '', 'phone_number' => ''];
    }

    $hasPlus = ai_starts_with($value, '+');
    $digits = preg_replace('/\D+/', '', $value);
    if ($digits === null || $digits === '') {
        return ['area_code' => '', 'phone_number' => ''];
    }

    if ($hasPlus && preg_match('/^\+(\d{1,4})/', $value, $match)) {
        $areaDigits = $match[1];
        $phoneDigits = substr($digits, strlen($areaDigits));
        if ($phoneDigits !== false && $phoneDigits !== '') {
            return ['area_code' => '+' . $areaDigits, 'phone_number' => $phoneDigits];
        }
    }

    foreach ($countryHints as $hint) {
        $dialCode = ai_country_dial_code_from_name($hint);
        if ($dialCode === '') {
            continue;
        }

        $phoneDigits = $digits;
        if (ai_starts_with($phoneDigits, $dialCode)) {
            $phoneDigits = substr($phoneDigits, strlen($dialCode));
        } elseif (ai_starts_with($phoneDigits, '0')) {
            $phoneDigits = ltrim($phoneDigits, '0');
        }

        if ($phoneDigits !== '' && preg_match('/^\d{6,15}$/', $phoneDigits)) {
            return ['area_code' => '+' . $dialCode, 'phone_number' => $phoneDigits];
        }
    }

    return ['area_code' => '', 'phone_number' => ''];
}
